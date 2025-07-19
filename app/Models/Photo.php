<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;
    protected $fillable = ['path'];

    public function getUrlAttribute()
    {
        return Storage::url($this->path); // Auto prepends /storage/
    }
}
