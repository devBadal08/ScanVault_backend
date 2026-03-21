<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'folder_id',
        'file_name',
        'file_path',
        'type',
        'captured_at'
    ];
}
