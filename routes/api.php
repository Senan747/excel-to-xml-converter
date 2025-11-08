<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::post('uploads', [UploadController::class, 'store']);
Route::get('uploads/{id}', [UploadController::class, 'show']);
