<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id', 'parent_id'];
    protected $connection = 'mysql'; // default connection

    // -----------------------------
    // Relationships (default connection only)
    // -----------------------------
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Subfolders (default connection only)
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id', 'id');
    }

    // -----------------------------
    // Dynamic Company DB Support
    // -----------------------------
    protected $dynamicConnection = null;

    /**
     * Set dynamic connection for company DB.
     */
    public function setCompanyConnection(string $connectionName): self
    {
        $this->dynamicConnection = $connectionName;
        $this->setConnection($connectionName);
        return $this;
    }

    /**
     * Get subfolders using dynamic connection.
     */
    public function getSubfolders()
    {
        if (!$this->dynamicConnection) {
            throw new \Exception("Dynamic connection not set for Folder ID {$this->id}");
        }

        $subfolders = self::on($this->dynamicConnection)
            ->where('parent_id', $this->id)
            ->get();

        \Log::info("Subfolders for {$this->id}", $subfolders->toArray());

        return $subfolders->each(fn($sf) => $sf->setCompanyConnection($this->dynamicConnection));
    }

    /**
     * Get photos for this folder.
     * Supports per-user photo tables like "username_photos".
     */
    public function userPhotos(?string $username): Collection
    {
        if (!$this->dynamicConnection) {
            throw new \Exception("Dynamic connection not set for Folder ID {$this->id}");
        }

        if (!$username) return collect();

        $tableName = $username . '_photos';

        if (!DB::connection($this->dynamicConnection)->getSchemaBuilder()->hasTable($tableName)) {
            \Log::warning("Table {$tableName} does not exist in connection {$this->dynamicConnection}");
            return collect();
        }

        $folderIds = [$this->id];
        if (empty($folderIds)) return collect();

        return collect(
            DB::connection($this->dynamicConnection)
                ->table($tableName)
                ->whereIn('folder_id', $folderIds)
                ->get()
        );
    }

    /**
     * Recursively get all subfolder IDs.
     */
    public function getAllSubfolderIds(): Collection
    {
        $ids = collect();

        if (!$this->dynamicConnection) return $ids;

        $subfolders = self::on($this->dynamicConnection)
            ->where('parent_id', $this->id)
            ->get();

        foreach ($subfolders as $subfolder) {
            $subfolder->setCompanyConnection($this->dynamicConnection);
            $ids->push($subfolder->id);
            $ids = $ids->merge($subfolder->getAllSubfolderIds());
        }

        return $ids;
    }

    /**
     * Get top-level folders for a user.
     */
    public static function topLevelFolders(string $connectionName, int $userId)
    {
        return self::on($connectionName)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->get();
    }
}
