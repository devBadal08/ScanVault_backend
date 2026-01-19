<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'profile_photo',
        'password',
        'role',
        'company_id',
        'max_limit',
        'max_storage',
        'created_by',
        'assigned_to',
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

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id')->withTimestamps();
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
            'total_storage' => (bool) $this->userPermission->show_total_storage,
            'total_photos' => (bool) $this->userPermission->show_total_photos,
            default => false,
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow admin panel
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->hasAnyRole([
            'Super Admin',
            'admin',
            'manager',
        ]);
    }
}