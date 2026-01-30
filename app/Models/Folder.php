<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id', 'parent_id', 'company_id', 'path'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'folder_id');
    }

    public function shares() {
        return $this->hasMany(FolderShare::class);
    }

    public function linkedFolders()
    {
        return $this->belongsToMany(Folder::class, 'folder_links', 'source_folder_id', 'target_folder_id')
                    ->withPivot('link_type')
                    ->withTimestamps();
    }

    public function linkedFrom()
    {
        return $this->belongsToMany(
            Folder::class,
            'folder_links',
            'target_folder_id',
            'source_folder_id'
        )
        ->withPivot('link_type')
        ->withTimestamps();
    }

    public function allPhotos()
    {
        $photos = $this->photos()->get();

        foreach ($this->linkedFolders as $linked) {
            $photos = $photos->merge($linked->photos);
        }

        return $photos;
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }
}