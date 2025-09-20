<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    protected function getPhotoModel($connection, $table)
    {
        return new class($connection, $table) extends Model {
            protected $guarded = [];
            public $timestamps = true;

            public function __construct($connection = null, $table = null, array $attributes = [])
            {
                parent::__construct($attributes);
                if ($connection) $this->setConnection($connection);
                if ($table) $this->setTable($table);
            }
        };
    }

    protected function getCompanyConnection(string $databaseName): string
    {
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
                'engine' => null,
            ],
        ]);

        return 'dynamic_company';
    }

    protected function getCompanyUserId($connection, $user)
    {
        $companyUser = DB::connection($connection)->table('users')->where('email', $user->email)->first();
        if (!$companyUser) {
            throw new \Exception("User not found in company database");
        }
        return $companyUser->id;
    }

    protected function getUsernameTable($user)
    {
        $tableName = strtolower($user->name) . '_photos';
        $tableName = preg_replace('/\s+/', '_', $tableName);
        $tableName = preg_replace('/[^a-z0-9_]/', '', $tableName);
        return $tableName;
    }

    // -----------------------
    // New: Create folders hierarchy correctly
    // -----------------------
    protected function createFoldersHierarchy($connection, $companyUserId, $folderPath)
    {
        $folders = explode('/', $folderPath);
        $parentId = null;

        foreach ($folders as $folderName) {
            $folder = DB::connection($connection)->table('folders')
                ->where('user_id', $companyUserId)
                ->where('name', $folderName)
                ->where('parent_id', $parentId)
                ->first();

            if (!$folder) {
                $folderId = DB::connection($connection)->table('folders')->insertGetId([
                    'user_id' => $companyUserId,
                    'name' => $folderName,
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $folderId = $folder->id;
            }

            $parentId = $folderId;
        }

        return $parentId; // return id of the deepest folder
    }

    // -----------------------
    // Store single image
    // -----------------------
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $companyDb = $user->company->database_name ?? null;
        if (!$companyDb) return response()->json(['error' => 'Company database not found'], 500);

        if (!$request->hasFile('image')) return response()->json(['error' => 'No image found'], 422);

        $connection = $this->getCompanyConnection($companyDb);
        $usernameTable = $this->getUsernameTable($user);

        if (!Schema::connection($connection)->hasTable($usernameTable)) {
            return response()->json(['error' => 'User photos table not found'], 500);
        }

        try {
            $companyUserId = $this->getCompanyUserId($connection, $user);

            $folderName = $request->input('folder', 'default');
            $subfolderName = $request->input('subfolder', null);
            $folderPath = $subfolderName ? "$folderName/$subfolderName" : $folderName;

            // Create folders hierarchy
            $folderId = $this->createFoldersHierarchy($connection, $companyUserId, $folderPath);

            // Store photo
            $storePath = $folderPath ? "$companyUserId/$folderPath" : "$companyUserId";
            $path = $request->file('image')->store($storePath, 'public');
            $path = str_replace('public/', '', $path);

            DB::connection($connection)->table($usernameTable)->insert([
                'path' => $path,
                'user_id' => $companyUserId,
                'uploaded_by' => $companyUserId,
                'folder_id' => $folderId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Uploaded successfully', 'path' => $path]);
        } catch (\Exception $e) {
            Log::error("Photo upload failed: " . $e->getMessage());
            return response()->json(['error' => 'Photo upload failed', 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------
    // Upload multiple images
    // -----------------------
    public function uploadAll(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $companyDb = $user->company->database_name ?? null;
        if (!$companyDb) return response()->json(['error' => 'Company database not found'], 500);

        $connection = $this->getCompanyConnection($companyDb);
        $usernameTable = $this->getUsernameTable($user);

        if (!$request->hasFile('images')) return response()->json(['error' => 'No images uploaded'], 400);

        $folderPaths = $request->input('folders'); // array of full paths like 'folder/subfolder/subsubfolder'
        $images = $request->file('images');

        if (!$folderPaths || !is_array($folderPaths) || count($images) !== count($folderPaths)) {
            return response()->json(['error' => 'Folders count must match images count'], 422);
        }

        if (!Schema::connection($connection)->hasTable($usernameTable)) {
            return response()->json(['error' => 'User photos table not found'], 500);
        }

        try {
            $companyUserId = $this->getCompanyUserId($connection, $user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $uploaded = [];
        $failed = [];

        foreach ($images as $index => $image) {
            try {
                $folderPath = $folderPaths[$index];

                // Create full folder hierarchy and get the deepest folder ID
                $folderId = $this->createFoldersHierarchy($connection, $companyUserId, $folderPath);

                // Store photo
                $storePath = "$companyUserId/$folderPath";
                $filename = $image->getClientOriginalName();
                $path = $image->storeAs($storePath, $filename, 'public');
                $path = str_replace('public/', '', $path);

                DB::connection($connection)->table($usernameTable)->insert([
                    'path' => $path,
                    'user_id' => $companyUserId,
                    'uploaded_by' => $companyUserId,
                    'folder_id' => $folderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $uploaded[] = asset('storage/' . $path);
            } catch (\Exception $e) {
                Log::error("Photo upload failed: " . $e->getMessage());
                $failed[] = $image->getClientOriginalName();
            }
        }

        return response()->json([
            'message' => 'Upload finished',
            'uploaded' => $uploaded,
            'failed' => $failed
        ]);
    }

    // -----------------------
    // Get photos by folder
    // -----------------------
    public function getImagesByFolder($folderName)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $companyDb = $user->company->database_name ?? null;
        if (!$companyDb) return response()->json(['error' => 'Company database not found'], 500);

        $connection = $this->getCompanyConnection($companyDb);
        $usernameTable = $this->getUsernameTable($user);

        if (!Schema::connection($connection)->hasTable($usernameTable)) {
            return response()->json(['message' => 'No photos found'], 404);
        }

        $photoModel = $this->getPhotoModel($connection, $usernameTable);
        $companyUserId = $this->getCompanyUserId($connection, $user);

        $photos = $photoModel->where('user_id', $companyUserId)
                             ->where('path', 'like', "%$folderName/%")
                             ->get();

        return response()->json($photos);
    }

    public function getUserPhotos(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $companyDb = $user->company->database_name ?? null;
        if (!$companyDb) return response()->json(['error' => 'Company database not found'], 500);

        $connection = $this->getCompanyConnection($companyDb);
        $usernameTable = $this->getUsernameTable($user);

        if (!Schema::connection($connection)->hasTable($usernameTable)) return response()->json([]);

        $photoModel = $this->getPhotoModel($connection, $usernameTable);
        $companyUserId = $this->getCompanyUserId($connection, $user);

        $query = $photoModel->where('user_id', $companyUserId);

        if ($folder = $request->input('folder')) {
            $query->where('path', 'like', "%$folder/%");
        }

        return response()->json($query->get());
    }
}
