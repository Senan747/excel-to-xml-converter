<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Sabre\Xml\Service;
use Exception;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        $file = $request->file('file');
        $fileOriginalName = $file->getClientOriginalName();

        $path = $file->store('uploads');

        $upload = Upload::create([
            'original_filename' => $fileOriginalName,
            'stored_filepath' => $path,
            'status' => 'pending',
        ]);

        try {
            $array = Excel::toArray([], $file);
            $rows = $array[0] ?? [];

            if (empty($rows)) {
                throw new Exception('Excel-de sehv var.');
            }

            $headers = array_shift($rows);

            $xmlData = [];

            foreach ($rows as $row) {
                $item = [];
                foreach ($headers as $key => $header) {
                    $cleanHeader = str_replace(' ', '_', trim($header));
                    $item[$cleanHeader] = $row[$key] ?? null;
                }
                $xmlData[] = $item;
            }

            $service = new Service();

            $xml = $service->write('root', function ($writer) use ($xmlData) {
                foreach ($xmlData as $item) {
                    $writer->startElement('record');
                    foreach ($item as $key => $value) {
                        $tag = preg_replace('/[^A-Za-z0-9_]/', '_', trim($key));
                        $writer->writeElement($tag, trim((string) $value));
                    }
                    $writer->endElement();
                }
            });

            $xmlFileName = pathinfo($fileOriginalName, PATHINFO_FILENAME) . '.xml';
            $xmlPath = 'xml_outputs/' . $xmlFileName;
            Storage::disk('public')->put($xmlPath, $xml);

            $upload->update([
                'status' => 'completed',
                'xml_filepath' => $xmlPath,
            ]);

            return response()->json([
                'message' => 'XML faylı yaradıldı.',
                'xml_download_url' => Storage::url($xmlPath),
                'upload' => $upload,
            ], 200);


        } catch (Exception $e) {
            Log::error('Upload Error: ' . $e->getMessage());

            $upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Fayl emal edilərkən xəta baş verdi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
