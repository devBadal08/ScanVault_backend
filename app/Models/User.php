<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
        'created_by'
    ];

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

    public function userPermission()
    {
        return $this->hasOne(\App\Models\UserPermission::class);
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
