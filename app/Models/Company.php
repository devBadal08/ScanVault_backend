<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'admin_name',
        'company_logo',
        'used_storage_mb',
        'total_photos',
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

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($company) {

            // 1. Delete company logo
            if (!empty($company->company_logo)) {
                Storage::disk('public')->delete($company->company_logo);
            }

            // 2. Delete entire company folder (ALL users data)
            Storage::disk('public')->deleteDirectory($company->id);
        });

        static::updating(function ($company) {

            if ($company->isDirty('company_logo')) {

                $oldLogo = $company->getOriginal('company_logo');

                if (!empty($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
            }

        });
    }
}
