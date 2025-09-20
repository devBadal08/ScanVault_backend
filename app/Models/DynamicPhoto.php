<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicPhoto extends Model
{
    protected $guarded = [];

    public function setTableName(string $tableName): self
    {
        $this->setTable($tableName);
        return $this;
    }

    // Accessor for photo URL
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder(string $connection = null)
    {
        $folder = new Folder();
        if ($connection) {
            $folder->setConnection($connection);
        }
        return $this->belongsTo(get_class($folder), 'folder_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
