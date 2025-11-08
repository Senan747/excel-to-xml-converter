<?php

namespace App\Http\Controllers;

use App\Services\UploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:512',
            ]);

            $result = $this->uploadService->processUpload($request->file('file'));
            return $this->apiResponse($result, 'success', 'XML successfully uploaded.', 200);
        } catch (ValidationException $e) {
            return $this->apiResponse(
                ['errors' => $e->errors()],
                'error',
                'Validation failed',
                422
            );
        } catch (Exception $e) {
            return $this->apiResponse('', 'Error', 'There is an error while creating xml: ' . $e->getMessage(), 500);
        }
    }
}
