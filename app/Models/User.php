<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'max_limit',
        'created_by',
        'assigned_to',
    ];

    // public function tokens(): MorphMany
    // {
    //     return $this->morphMany(\App\Models\PersonalAccessToken::class, 'tokenable');
    // }

    public function setConnectionByCompany($databaseName)
    {
        config(["database.connections.tenant" => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);

        $this->setConnection('tenant');

        return $this;
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function userPermission()
    {
        return $this->hasOne(\App\Models\UserPermission::class);
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function getAllDescendantUsers()
    {
        $all = collect();

        foreach ($this->createdUsers as $user) {
            $all->push($user);
            $all = $all->merge($user->getAllDescendantUsers());
        }

        return $all;
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUsers()
    {
        return $this->hasMany(User::class, 'assigned_to');
    }

    public function canShow($type): bool
    {
        if (!$this->userPermission) {
            return false;
        }

        return match ($type) {
            'total_users' => (bool) $this->userPermission->show_total_users,
            'total_managers' => (bool) $this->userPermission->show_total_managers,
            'total_admins' => (bool) $this->userPermission->show_total_admins,
            'total_limit' => (bool) $this->userPermission->show_total_limit,
            default => false,
        };
    }
}