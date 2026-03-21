<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoDeleteHistory extends Model
{
    protected $fillable = [
        'deleted_by',
        'company_id',
        'user_id',
        'photo_path',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    
}
