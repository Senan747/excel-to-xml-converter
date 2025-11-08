<?php

namespace App\Http\Controllers;

use App\Jobs\UploadJob;
use App\Models\Upload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:512',
            ]);

            $path = $request->file('file')->store('uploads', 'public');

            $upload = Upload::create([
                'original_filename' => $request->file('file')->getClientOriginalName(),
                'stored_filepath' => $path,
                'status' => 'pending',
                'uploaded_at' => now(),
            ]);

            UploadJob::dispatch($upload->id);

            return $this->apiResponse(
                ['upload_id' => $upload->id],
                'success',
                'File successfully uploaded. Processing in background.',
                200
            );

        } catch (ValidationException $e) {
            return $this->apiResponse(
                ['errors' => $e->errors()],
                'error',
                'Validation failed',
                422
            );

        } catch (Exception $e) {
            return $this->apiResponse(
                '',
                'error',
                'Upload failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function show($id)
    {
        $upload = Upload::find($id);

        if (!$upload) {
            return $this->apiResponse('', 'error', 'Upload not found', 404);
        }

        $response = [
            'id' => $upload->id,
            'original_filename' => $upload->original_filename,
            'status' => $upload->status,
            'uploaded_at' => $upload->uploaded_at,
            'xml_filepath' => $upload->xml_filepath ? asset('storage/' . $upload->xml_filepath) : null,
            'response' => $upload->response ? json_decode($upload->response, true) : null,
        ];

        return $this->apiResponse($response, 'success', 'Upload status fetched successfully', 200);
    }
}
