<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\UserLoginController;

// Your custom API route
// Route::get('/photos', [PhotoController::class, 'index']);
// Route::post('/photos', [PhotoController::class, 'store']);
Route::post('/login', [UserLoginController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/photos/{folder}', [PhotoController::class, 'getImagesByFolder']);
    Route::post('/photos/uploadAll', [PhotoController::class, 'uploadAll']);
    //Route::get('/user/photos', [PhotoController::class, 'getUserPhotos']);
    Route::post('/photos', [PhotoController::class, 'store']);
    Route::get('/photos', [PhotoController::class, 'getUserPhotos']);
    Route::get('/download-folder/{path}', [PhotoController::class, 'downloadFolderZip'])
        ->where('path', '.*')
        ->name('download.folder');
});

