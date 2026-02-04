<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\CompanyDownloadController;

// Home
Route::get('/', function () {
    return view('welcome');
});

// Auth::routes(['verify' => false]);

// Default dashboard (optional fallback)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth routes
require __DIR__.'/auth.php';

// ADD your custom login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/download-folder', [DownloadController::class, 'download'])->name('download-folder');
Route::get('/download-today-folders', [DownloadController::class, 'downloadToday'])
    ->name('download-today-folders');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', function () {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/manager/dashboard', function () {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Unauthorized');
        }
        return view('manager.dashboard');
    })->name('manager.dashboard');
});

Route::get('/company/backup/download', function () {
    $zipPath = storage_path('app/company_backups/company_backup.zip');

    abort_unless(file_exists($zipPath), 404);

    return response()->download(
        $zipPath,
        'company_backup.zip',
        ['Content-Type' => 'application/zip']
    )->deleteFileAfterSend(true);
})->name('company.download.all');
