<?php

namespace App\Traits;

trait ApiResponses {
    public function apiResponse($data = [], $status = 'success', $message = 'Success', $code = 200) {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
