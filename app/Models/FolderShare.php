<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolderShare extends Model
{
    protected $fillable = [
        'folder_id',
        'shared_by',
        'shared_with',
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function sharedByUser()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'shared_with');
    }
}
