<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan; // if you use Artisan::call for migrations

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'admin_name',
        'company_logo',
        'database_name',
    ];

    protected static function booted()
    {
        static::created(function ($company) {
            // 1️⃣ Generate database name
            $dbName = 'company_' . strtolower(str_replace(' ', '_', $company->company_name)) . '_db';

            // 2️⃣ Create database if it doesn't exist
            DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName`");

            // 3️⃣ Save database name in company record (before running migration)
            $company->database_name = $dbName;
            $company->save();

            // 4️⃣ Dynamically set tenant connection to the new database
            config(['database.connections.tenant.database' => $dbName]);
            DB::purge('tenant'); // refresh connection

            // 5️⃣ Run tenant migrations
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path'     => '/database/migrations/tenant',
                '--force'    => true,
            ]);
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getCompanyLogoUrlAttribute()
    {
        return $this->company_logo
            ? asset('storage/' . $this->company_logo)
            : null;
    }

}
