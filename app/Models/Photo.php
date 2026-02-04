<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;
    protected $fillable = ['path', 'user_id', 'folder_id', 'uploaded_by', 'company_id'];

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
}