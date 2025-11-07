<?php

namespace App\Http\Controllers;

use App\Services\UploadService;
use Exception;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        try {
            $result = $this->uploadService->processUpload($request->file('file'));
            return $this->apiResponse($result, 'success', 'XML successfully uploaded.', 200);
        } catch (Exception $e) {
            return $this->apiResponse('', 'Error', 'There is an error while creating xml: ' . $e->getMessage(), 500);
        }
    }
}
