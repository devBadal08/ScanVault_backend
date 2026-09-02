<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FolderPermissionService;
use App\Models\MediaFile;

class PhotoController extends Controller
{
    /**
     * Create folder (DB + physical directory)
     */
    public function createFolder(Request $request)
    {
        $parentFolder = null;

        if ($request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id);

            // 🔐 permission check
            if (!FolderPermissionService::canWrite($parentFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No permission to create subfolder'
                ], 403);
            }

            // ✅ KEEP OWNER SAME AS PARENT
            $userId = $parentFolder->user_id;
        } else {
            $userId = Auth::id();
        }

        $request->validate([
            'name'       => 'required|string|max:50',
            'company_id' => 'required|integer',
            'parent_id'  => 'nullable|integer|exists:folders,id',
        ]);

        $companyId = $request->company_id;

        // Check existing folder
        $existing = Folder::where('name', $request->name)
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('parent_id', $request->parent_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'folder'  => $existing,
                'path'    => $existing->path,
                'exists'  => true,
            ]);
        }

        // Create folder record
        $folder = Folder::create([
            'name'       => $request->name,
            'company_id' => $companyId,
            'user_id'    => $userId,
            'parent_id'  => $request->parent_id,
        ]);

        // Build path using parent path (NO recursion)
        if ($request->parent_id) {
            $parent = Folder::findOrFail($request->parent_id);
            $folder->path = $parent->path . '/' . $folder->name;
        } else {
            $folder->path = $companyId . '/' . $userId . '/' . $folder->name;
        }

        $folder->save();

        // Create physical directory
        Storage::disk('public')->makeDirectory($folder->path);

        return response()->json([
            'success' => true,
            'folder'  => $folder,
            'path'    => $folder->path,
            'exists'  => false,
        ]);
    }

    /**
     * Upload images / videos / pdfs
     */
    public function uploadAll(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $request->input('company_id');
        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 422);
        }

        // Company access check
        if (!Auth::user()->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json(['error' => 'You do not have access to this company'], 403);
        }

        // Company storage info (NO filesystem scan)
        $company = DB::table('companies')
            ->select('id', 'used_storage_mb')
            ->where('id', $companyId)
            ->first();

        $companyAdmin = DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.max_storage')
            ->first();

        $maxStorage = $companyAdmin->max_storage ?? 0;

        if ($maxStorage > 0) {
            $percentUsed = round(($company->used_storage_mb / $maxStorage) * 100, 2);

            if ($percentUsed >= 99) {
                return response()->json([
                    'error' => "🚫 Storage almost full ($percentUsed% used)",
                ], 403);
            }
        }

        $folders = $request->input('folders');
        if (!is_array($folders)) {
            return response()->json(['error' => 'Folders array required'], 422);
        }

        $request->validate([
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'videos.*' => 'nullable|mimes:mp4,mov,avi|max:51200',
            'pdfs.*'   => 'nullable|mimes:pdf|max:20480',
        ]);

        $uploaded  = [];
        $failed    = [];
        $folderIds = [];

        // Folder resolver (DB path, no recursion)
        $getFolder = function ($folderData) use ($companyId) {
            if (!isset($folderData['folder_id'])) {
                throw new \Exception('folder_id is required');
            }

            $folder = Folder::where('id', $folderData['folder_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            return [$folder, $folder->path];
        };

        /**
         * Generic upload handler
         */
        $handleUpload = function ($files) use (
            $folders,
            $getFolder,
            $userId,
            $companyId,
            &$uploaded,
            &$failed,
            &$folderIds
        ) {
            foreach ($files as $index => $file) {
                try {
                    [$folder, $storagePath] = $getFolder($folders[$index]);

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();

                    $count = 0;

                    do {
                        $filename = $count === 0 
                            ? $originalName . '.' . $extension 
                            : $originalName . $count . '.' . $extension;

                        $pathCheck = $storagePath . '/' . $filename;

                        $count++;
                    } while (Storage::disk('public')->exists($pathCheck));

                    $extension = strtolower($file->getClientOriginalExtension());

                    $type = match ($extension) {
                        'mp4', 'mov', 'avi' => 'video',
                        'pdf' => 'pdf',
                        default => 'image'
                    };

                    /*
                    |--------------------------------------------------------------------------
                    | Get actual capture date from EXIF
                    |--------------------------------------------------------------------------
                    */
                    $capturedAt = null;

                    /*
                    |--------------------------------------------------------------------------
                    | PHOTO DATE FROM EXIF
                    |--------------------------------------------------------------------------
                    */
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        try {
                            $exif = @exif_read_data($file->getRealPath());

                            if (!empty($exif['DateTimeOriginal'])) {
                                $capturedAt = \Carbon\Carbon::createFromFormat(
                                    'Y:m:d H:i:s',
                                    trim($exif['DateTimeOriginal'])
                                );
                            }

                            if (!$capturedAt && !empty($exif['DateTimeDigitized'])) {
                                $capturedAt = \Carbon\Carbon::createFromFormat(
                                    'Y:m:d H:i:s',
                                    trim($exif['DateTimeDigitized'])
                                );
                            }

                            if (!$capturedAt && !empty($exif['DateTime'])) {
                                $capturedAt = \Carbon\Carbon::createFromFormat(
                                    'Y:m:d H:i:s',
                                    trim($exif['DateTime'])
                                );
                            }

                        } catch (\Throwable $e) {
                            $capturedAt = null;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VIDEO DATE FROM METADATA
                    |--------------------------------------------------------------------------
                    */
                    if (in_array($extension, ['mp4', 'mov', 'avi'])) {
                        try {
                            $videoPath = $file->getRealPath();

                            $command = 'ffprobe -v quiet -print_format json -show_entries format_tags=creation_time '
                                . escapeshellarg($videoPath);

                            $output = shell_exec($command);

                            if ($output) {
                                $metadata = json_decode($output, true);

                                $creationTime = $metadata['format']['tags']['creation_time'] ?? null;

                                if ($creationTime) {
                                    $capturedAt = \Carbon\Carbon::parse($creationTime);
                                }
                            }
                        } catch (\Throwable $e) {
                            $capturedAt = null;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PDF DATE FROM METADATA
                    |--------------------------------------------------------------------------
                    */
                    if ($extension === 'pdf') {
                        try {
                            $pdfPath = $file->getRealPath();

                            $command = 'pdfinfo ' . escapeshellarg($pdfPath) . ' 2>/dev/null';

                            $output = shell_exec($command);

                            if ($output) {
                                if (preg_match('/^CreationDate:\s*(.+)$/mi', $output, $matches)) {
                                    $creationDate = trim($matches[1]);

                                    // Example:
                                    // D:20260831153000+05'30'

                                    $creationDate = preg_replace(
                                        '/^D:/',
                                        '',
                                        $creationDate
                                    );

                                    if (preg_match(
                                        '/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',
                                        $creationDate,
                                        $m
                                    )) {
                                        $capturedAt = \Carbon\Carbon::create(
                                            (int) $m[1],
                                            (int) $m[2],
                                            (int) $m[3],
                                            (int) $m[4],
                                            (int) $m[5],
                                            (int) $m[6]
                                        );
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            $capturedAt = null;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | FALLBACK CAPTURE DATE FROM FILENAME
                    |--------------------------------------------------------------------------
                    |
                    | Filename example:
                    | 1788178125609.jpg
                    |
                    | 13-digit value = Unix timestamp in milliseconds
                    |
                    */
                    /*
                    |--------------------------------------------------------------------------
                    | Validate EXIF / metadata date
                    |--------------------------------------------------------------------------
                    */

                    // Don't accept future capture dates
                    if ($capturedAt && $capturedAt->greaterThan(now())) {
                        $capturedAt = null;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | FALLBACK CAPTURE DATE FROM FILENAME
                    |--------------------------------------------------------------------------
                    |
                    | Example:
                    | 20260901_153010_2024581845.jpg
                    |
                    | First 8 digits  = YYYYMMDD
                    | Next 6 digits   = HHMMSS
                    |
                    */

                    if (!$capturedAt) {

                        $filenameWithoutExtension = pathinfo(
                            $file->getClientOriginalName(),
                            PATHINFO_FILENAME
                        );

                        if (preg_match(
                            '/(\d{8})_(\d{6})/',
                            $filenameWithoutExtension,
                            $matches
                        )) {

                            try {

                                $capturedAt = \Carbon\Carbon::createFromFormat(
                                    'Ymd_His',
                                    $matches[1] . '_' . $matches[2]
                                );

                            } catch (\Throwable $e) {
                                $capturedAt = null;
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 13-digit Unix timestamp fallback
                    |--------------------------------------------------------------------------
                    */

                    if (!$capturedAt) {

                        if (preg_match(
                            '/(\d{13})/',
                            $filenameWithoutExtension,
                            $matches
                        )) {

                            try {

                                $capturedAt = \Carbon\Carbon::createFromTimestampMs(
                                    (int) $matches[1]
                                );

                            } catch (\Throwable $e) {
                                $capturedAt = null;
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | FINAL FALLBACK
                    |--------------------------------------------------------------------------
                    */

                    $capturedAt ??= now();

                    /*
                    |--------------------------------------------------------------------------
                    | Store file
                    |--------------------------------------------------------------------------
                    */
                    $path = $file->storeAs(
                        $storagePath,
                        $filename,
                        'public'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Save photo record
                    |--------------------------------------------------------------------------
                    */
                    Photo::create([
                        'path'        => $path,
                        'user_id'     => $userId,
                        'folder_id'   => $folder->id,
                        'type'        => $type,
                        'company_id'  => $folder->company_id,
                        'uploaded_by' => $userId,
                        'captured_at' => $capturedAt,
                    ]);

                    // Increment company storage
                    $sizeMB = round($file->getSize() / (1024 ** 2), 2);

                    // Company storage
                    DB::table('companies')
                        ->where('id', $companyId)
                        ->increment('used_storage_mb', $sizeMB);

                    // User storage
                    DB::table('users')
                        ->where('id', $userId)
                        ->increment('used_storage_mb', $sizeMB);

                    // Count only jpg, jpeg, png as photos
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {

                        DB::table('companies')
                            ->where('id', $companyId)
                            ->increment('total_photos');

                        DB::table('companies')
                            ->where('id', $companyId)
                            ->increment('lifetime_total_photos');

                        // User current photos
                        DB::table('users')
                            ->where('id', $userId)
                            ->increment('total_photos');
                    }

                    $uploaded[] = asset('storage/' . $path);
                    $folderIds[] = $folder->id;

                } catch (\Throwable $e) {

                    \Log::error('UPLOAD FILE FAILED', [
                        'file' => $file->getClientOriginalName(),
                        'message' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file_path' => $e->getFile(),
                    ]);

                    $failed[] = $file->getClientOriginalName();
                }
            }
        };

        if ($request->hasFile('images')) {
            $handleUpload($request->file('images'));
        }

        if ($request->hasFile('videos')) {
            $handleUpload($request->file('videos'));
        }

        if ($request->hasFile('pdfs')) {
            $handleUpload($request->file('pdfs'));
        }

        if (empty($uploaded) && empty($failed)) {
            return response()->json([
                'error' => 'No files uploaded'
            ], 400);
        }

        if (!empty($failed)) {
            return response()->json([
                'message'    => 'Some files failed to upload',
                'uploaded'   => $uploaded,
                'failed'     => $failed,
                'folder_ids' => array_values(array_unique($folderIds)),
            ], 422);
        }

        return response()->json([
            'message'    => 'Upload completed successfully',
            'uploaded'   => $uploaded,
            'failed'     => [],
            'folder_ids' => array_values(array_unique($folderIds)),
        ], 200);
    }

    /**
     * Rename folder (safe for nested paths)
     */
    public function renameFolder(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'company_id' => 'required|integer',
        ]);

        $folder = Folder::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        $oldPath = $folder->path;
        $newPath = dirname($oldPath) . '/' . $request->name;

        Storage::disk('public')->move($oldPath, $newPath);

        // Update all child paths
        Folder::where('path', 'like', $oldPath . '%')->update([
            'path' => DB::raw("REPLACE(path, '$oldPath', '$newPath')")
        ]);

        $folder->update([
            'name' => $request->name,
            'path' => $newPath,
        ]);

        return response()->json([
            'success'   => true,
            'folder_id'=> $folder->id,
            'old_name' => basename($oldPath),
            'new_name' => $request->name,
        ]);
    }

    public function renameFile(Request $request)
    {
        $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string',
        ]);

        $oldPath = $request->old_path;

        // extract folder path
        $folderPath = dirname($oldPath);
        $extension = pathinfo($oldPath, PATHINFO_EXTENSION);

        $newFileName = $request->new_name . '.' . $extension;
        $newPath = $folderPath . '/' . $newFileName;

        // 🔥 handle duplicate
        $count = 0;
        while (Storage::disk('public')->exists($newPath)) {
            $newFileName = $request->new_name . $count . '.' . $extension;
            $newPath = $folderPath . '/' . $newFileName;
            $count++;
        }

        Storage::disk('public')->move($oldPath, $newPath);

        // ✅ update DB also
        Photo::where('path', $oldPath)->update([
            'path' => $newPath
        ]);

        return response()->json([
            'success' => true,
            'new_path' => $newPath
        ]);
    }
}
