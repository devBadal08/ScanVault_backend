<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'admin_name',
        'company_logo',
    ];

    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id');
    }

    public function getCompanyLogoUrlAttribute()
    {
        return $this->company_logo
            ? asset('storage/' . $this->company_logo)
            : null;
    }

    public function parent()
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

}
