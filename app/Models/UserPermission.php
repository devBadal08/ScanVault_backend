<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'show_total_users',
        'show_total_managers',
        'show_total_admins',
        'show_total_limit',
        'show_total_storage',
    ];
}
