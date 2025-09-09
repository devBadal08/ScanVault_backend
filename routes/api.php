<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\UserLoginController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FolderShareController;
use Illuminate\Support\Facades\Broadcast;

// Your custom API route
// Route::get('/photos', [PhotoController::class, 'index']);
// Route::post('/photos', [PhotoController::class, 'store']);
Route::post('/login', [UserLoginController::class, 'login']);

Route::middleware('auth:sanctum')->get('/check-user', [UserController::class, 'checkUser']);
Route::get('/check-user', [UserController::class, 'checkUser']);

Route::middleware('auth:sanctum')->delete('/users/{id}', [UserController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/photos/{folder}', [PhotoController::class, 'getImagesByFolder']);
    Route::post('/photos/uploadAll', [PhotoController::class, 'uploadAll']);
    //Route::get('/user/photos', [PhotoController::class, 'getUserPhotos']);
    Route::post('/photos', [PhotoController::class, 'store']);
    Route::get('/photos', [PhotoController::class, 'getUserPhotos']);
    Route::get('/download-folder/{path}', [PhotoController::class, 'downloadFolderZip'])
        ->where('path', '.*')
        ->name('download.folder');

    // 🔹 Folder Share routes
    Route::post('/folder/share', [FolderShareController::class, 'share']);
    Route::get('/folder/my-shared', [FolderShareController::class, 'mySharedFolders']);
    Route::get('/folders/id', [FolderShareController::class, 'getFolderId']);
    Route::get('/shared-folder/{id}/photos', [FolderShareController::class, 'getSharedFolderPhotos']);
    Route::post('/shared-folders/{id}/upload', [\App\Http\Controllers\Api\FolderShareController::class, 'uploadToSharedFolder']);
});

