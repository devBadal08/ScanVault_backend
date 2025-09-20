<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DownloadController extends Controller
{
    protected function getCompanyConnection(string $databaseName): string
    {
        // set runtime connection config
        config([
            "database.connections.dynamic_company" => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $databaseName,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
        ]);

        return 'dynamic_company';
    }

    protected function getCompanyUser($targetUser)
    {
        $company = DB::table('companies')->where('id', $targetUser->company_id)->first();
        if (!$company || !$company->database_name) {
            throw new \Exception("Company database not found");
        }

        $connection = $this->getCompanyConnection($company->database_name);

        // Try to find the user by email first
        $companyUser = DB::connection($connection)
            ->table('users')
            ->where('email', $targetUser->email)
            ->first();

        // Fallback: try by name if email not found (for manager-created users)
        if (!$companyUser) {
            $companyUser = DB::connection($connection)
                ->table('users')
                ->where('name', $targetUser->name)
                ->first();
        }

        if (!$companyUser) {
            Log::error('User not found in company DB', [
                'company_id' => $targetUser->company_id,
                'company_database' => $company->database_name,
                'user_email' => $targetUser->email,
                'user_name' => $targetUser->name,
            ]);
            throw new \Exception("User not found in company database");
        }

        return [$companyUser, $connection, $company->database_name];
    }

    protected function createZip($images, $zipFileName, $baseFolder = null)
    {
        $zipPath = storage_path('app/temp/' . $zipFileName);
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($images as $img) {
                if (empty($img->path)) continue;

                $filePath = storage_path('app/public/' . ltrim($img->path, '/'));
                if (!file_exists($filePath)) {
                    Log::warning('File not found while zipping', ['file' => $filePath]);
                    continue;
                }

                // --- Build relative path ---
                $relativePath = $img->path;

                // 1️⃣ Remove user_id folder (like "3/_")
                $parts = explode('/', $relativePath);
                if (is_numeric($parts[0])) {
                    array_shift($parts); // remove "3"
                }
                if ($parts && $parts[0] === '_') {
                    array_shift($parts); // remove "_" if present
                }
                $relativePath = implode('/', $parts);

                // 2️⃣ Ensure top-level folder is always under $baseFolder
                if ($baseFolder) {
                    // If $relativePath already starts with base folder, keep it
                    if (!Str::startsWith($relativePath, $baseFolder)) {
                        $relativePath = $baseFolder . '/' . $relativePath;
                    }
                }

                $zip->addFile($filePath, $relativePath);
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        throw new \Exception("Could not create ZIP file");
    }

    /**
     * Find the photos table name for company user. Tries multiple strategies
     * and falls back to scanning information_schema for *_photos tables.
     */
    protected function findPhotosTable($companyUser, $connection, $companyDatabase)
    {
        $candidates = [];

        // Common column names that might hold the username
        if (!empty($companyUser->username)) $candidates[] = $companyUser->username . '_photos';
        if (!empty($companyUser->name)) {
            // slugify / sanitize username
            $slug = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($companyUser->name));
            $slug = trim($slug, '_');
            if ($slug) $candidates[] = $slug . '_photos';
        }
        if (!empty($companyUser->email)) {
            $local = explode('@', $companyUser->email)[0] ?? null;
            if ($local) {
                $localSlug = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($local));
                $candidates[] = $localSlug . '_photos';
            }
        }
        if (!empty($companyUser->id)) {
            $candidates[] = 'user' . $companyUser->id . '_photos';
            $candidates[] = 'user_' . $companyUser->id . '_photos';
        }

        // normalize and unique
        $candidates = array_values(array_unique(array_filter(array_map(function ($v) {
            return strtolower(trim($v));
        }, $candidates))));

        Log::info('Trying photos table candidates', [
            'company_database' => $companyDatabase,
            'candidates' => $candidates,
            'connection' => $connection,
        ]);

        foreach ($candidates as $tbl) {
            if (Schema::connection($connection)->hasTable($tbl)) {
                Log::info('Photos table found by candidate', ['table' => $tbl]);
                return $tbl;
            }
        }

        // fallback: query information_schema for any *_photos tables in this database
        $rows = DB::connection($connection)->select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?",
            [$companyDatabase, '%\_photos']
        );

        $found = array_map(function ($r) {
            // result object can vary; normalize to string
            return is_object($r) ? (array)$r : $r;
        }, $rows);

        $tableNames = array_map(function ($r) {
            if (is_array($r)) {
                return array_values($r)[0];
            }
            return (string)$r;
        }, $found);

        Log::info('Photos tables in DB (information_schema search)', [
            'tables' => $tableNames,
            'connection' => $connection,
        ]);

        if (count($tableNames) === 1) {
            Log::info('Exactly one *_photos table found, using it', ['table' => $tableNames[0]]);
            return $tableNames[0];
        }

        // if multiple, try to match prefix by username slug
        if (!empty($companyUser->username) || !empty($companyUser->name)) {
            $bestMatch = null;
            $usernameCandidates = [];
            if (!empty($companyUser->username)) $usernameCandidates[] = strtolower($companyUser->username);
            if (!empty($companyUser->name)) $usernameCandidates[] = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($companyUser->name));
            foreach ($tableNames as $tn) {
                foreach ($usernameCandidates as $uc) {
                    if (Str::startsWith(strtolower($tn), $uc)) {
                        $bestMatch = $tn;
                        break 2;
                    }
                }
            }
            if ($bestMatch) {
                Log::info('Best match chosen from multiple *_photos tables', ['table' => $bestMatch]);
                return $bestMatch;
            }
        }

        // No table found
        $err = 'Photos table not found for this user. Candidates tried: ' . json_encode($candidates) . '. Tables in DB: ' . json_encode($tableNames);
        Log::error($err, ['company_database' => $companyDatabase]);
        throw new \Exception($err);
    }

    public function download(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) return response()->json(['error' => 'Unauthorized'], 401);

        $folderPath = trim($request->query('path') ?? '');
        $targetUserId = $request->query('user_id'); // <-- new

        if (!$folderPath) return response()->json(['error' => 'Missing folder path'], 400);
        if (!$targetUserId) return response()->json(['error' => 'Missing user_id'], 400);

        try {
            // Load the target user (could be created by admin or manager)
            $targetUser = \App\Models\User::find($targetUserId);
            if (!$targetUser) return response()->json(['error' => 'User not found'], 404);

            [$companyUser, $connection, $companyDatabase] = $this->getCompanyUser($targetUser);

            $photosTable = $this->findPhotosTable($companyUser, $connection, $companyDatabase);

            $normalizedFolderPath = trim($folderPath, '/');

            $images = DB::connection($connection)
                ->table($photosTable)
                ->where('path', 'like', $normalizedFolderPath . '%')
                ->get();

            if ($images->isEmpty()) {
                $images = DB::connection($connection)
                    ->table($photosTable)
                    ->where('path', 'like', '%' . $normalizedFolderPath . '%')
                    ->get();
            }

            if ($images->isEmpty()) {
                return response()->json([
                    'error' => 'No photos found in this folder',
                    'folderPath' => $folderPath
                ], 404);
            }

            $zipFileName = 'download_' . basename($normalizedFolderPath) . '.zip';
            return $this->createZip($images, $zipFileName, $normalizedFolderPath);

        } catch (\Exception $e) {
            Log::error("Folder download failed: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Download failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadToday(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) return response()->json(['error' => 'Unauthorized'], 401);

        $targetUserId = $request->query('user_id');
        $users = collect();

        // Admin: get all users they created, or specific user if user_id provided
        if ($authUser->role === 'admin') {
            if ($targetUserId) {
                $user = \App\Models\User::find($targetUserId);
                if ($user) $users->push($user);
            } else {
                $users = \App\Models\User::where('created_by', $authUser->id)->get();
            }
        }

        // Manager: get specific user if user_id provided, or default to their first user
        if ($authUser->role === 'manager') {
            if ($targetUserId) {
                $user = \App\Models\User::find($targetUserId);
                if ($user) $users->push($user);
            } else {
                $user = \App\Models\User::where('created_by', $authUser->id)->first();
                if ($user) $users->push($user);
            }
        }

        if ($users->isEmpty()) return response()->json(['error' => 'No users found for this role'], 404);

        $allImages = collect();

        try {
            foreach ($users as $user) {
                [$companyUser, $connection, $companyDatabase] = $this->getCompanyUser($user);
                $photosTable = $this->findPhotosTable($companyUser, $connection, $companyDatabase);

                $today = Carbon::today()->toDateString();

                $imagesToday = DB::connection($connection)
                    ->table($photosTable)
                    ->whereDate('created_at', $today)
                    ->get();

                // Don't prepend companyUser->name here!
                $allImages = $allImages->merge($imagesToday);
            }

            if ($allImages->isEmpty()) {
                return response()->json(['error' => 'No photos uploaded today'], 404);
            }

            // Pass base folder as null or a name if you want
            $zipFileName = 'today_photos_' . Carbon::today()->format('Y-m-d') . '.zip';
            return $this->createZip($allImages, $zipFileName, null); // or $baseFolder = 'today_photos'
            
        } catch (\Exception $e) {
            Log::error("Today download failed: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Download failed', 'message' => $e->getMessage()], 500);
        }
    }
}
