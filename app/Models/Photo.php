<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'user_id', 'folder_id', 'uploaded_by'];
    protected $connection = 'mysql'; // default
    protected $table = 'photos'; // default, will be overridden dynamically

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Dynamically set connection and user-specific photo table
     */
    public function setCompanyConnection(string $connection, string $username): self
    {
        $this->setConnection($connection);
        $this->setTable($username . '_photos');
        return $this;
    }
}
