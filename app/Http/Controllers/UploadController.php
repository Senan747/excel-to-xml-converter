<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Sabre\Xml\Service;

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
                throw new Exception('The excel file is empty.');
            }

            $headers = array_shift($rows);

            $xmlData = [];
            foreach ($rows as $row) {
                $item = [];
                foreach ($headers as $key => $header) {
                    $cleanHeader = str_replace(' ', '_', trim($header));
                    $item[$cleanHeader] = $row[$key] ?? '';
                }
                $xmlData[] = $item;
            }

            $service = new Service();
            $service->namespaceMap = [];

            $xml = $service->write('IPD-UPLOAD', function ($writer) use ($xmlData) {
                $writer->startElement('HEADER');
                $writer->writeElement('USER-NAME', 'test_user');
                $writer->writeElement('PASSWD', 'password');
                $writer->writeElement('UPLOADING-SOCIETY', 'IPF');
                $writer->writeElement('FILE-ID', '12345');
                $writer->writeElement('ISO-CHAR-SET', 'ISO8859-1');
                $writer->endElement();

                $writer->startElement('RIGHTHOLDERS');
                foreach ($xmlData as $item) {
                    $writer->startElement('RIGHTHOLDER');

                    $writer->writeElement('ACTION', $item['ACTION'] ?? 'INSERT');
                    $writer->writeElement('IPN', $item['IPN'] ?? '');
                    $writer->writeElement('RIGHTHOLDER-LOCAL-ID', $item['RIGHTHOLDER_LOCAL_ID'] ?? 'A1');
                    $writer->writeElement('RIGHTHOLDER-FIRST-NAME', $item['RIGHTHOLDER_FIRST_NAME'] ?? 'John');
                    $writer->writeElement('RIGHTHOLDER-LAST-NAME', $item['RIGHTHOLDER_LAST_NAME'] ?? 'Doe');
                    $writer->writeElement('SEX', $item['SEX'] ?? 'M');
                    $writer->writeElement('DATE-OF-BIRTH', $item['DATE_OF_BIRTH'] ?? '1980-01-01');
                    $writer->writeElement('COUNTRY-OF-BIRTH', $item['COUNTRY_OF_BIRTH'] ?? 'AZE');
                    $writer->writeElement('COUNTRY-OF-RESIDENCE', $item['COUNTRY_OF_RESIDENCE'] ?? 'AZE');

                    $writer->startElement('IDENTIFYING-ROLES');
                    $writer->writeElement('IDENTIFYING-ROLE-CODE', $item['IDENTIFYING_ROLE_CODE'] ?? 'MU');
                    $writer->endElement();

                    $writer->startElement('MANDATE-INFOS');
                    $writer->startElement('MANDATE-INFO');
                    $writer->writeElement('MANDATE-TYPE', 'WW');
                    $writer->writeElement('MANDATED-SOCIETY-CODE', '123');
                    $writer->writeElement('MANDATED-SOCIETY-NAME', 'IPF');

                    $writer->startElement('MANDATE-PARAMETERS');
                    $writer->startElement('MANDATE-PARAMETER');
                    $writer->writeElement('MANDATE-START-DATE', '2024-01-01');
                    $writer->writeElement('MANDATE-END-DATE', '2025-01-01');
                    $writer->endElement();
                    $writer->endElement();

                    $writer->endElement();
                    $writer->endElement();

                    $writer->writeElement('ADDITIONAL-INFO', $item['ADDITIONAL_INFO'] ?? '');

                    $writer->endElement();
                }
                $writer->endElement();
            });

            $xmlFileName = pathinfo($fileOriginalName, PATHINFO_FILENAME) . '.xml';
            $xmlPath = 'xml_outputs/' . $xmlFileName;
            Storage::disk('public')->put($xmlPath, $xml);

            $xsdPath = public_path('schemas/IPD4upload.xsd');
            $dom = new DOMDocument();
            $dom->loadXML($xml);

            libxml_use_internal_errors(true);
            if (!$dom->schemaValidate($xsdPath)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMessages = '';
                foreach ($errors as $error) {
                    $errorMessages .= trim($error->message) . '; ';
                }
                throw new Exception('XML validation failed: ' . $errorMessages);
            }

            $upload->update([
                'status' => 'completed',
                'xml_filepath' => $xmlPath,
            ]);

            return $this->apiResponse([
                'xml_download_url' => Storage::url($xmlPath),
                'upload' => $upload
            ], 'success', 'XML successfully uploaded.', 200);
        } catch (Exception $e) {
            Log::error('Upload Error: ' . $e->getMessage());

            $upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $this->apiResponse('', 'Error', 'There is an error while creating xml: ' . $e->getMessage(), 500);
        }
    }
}
