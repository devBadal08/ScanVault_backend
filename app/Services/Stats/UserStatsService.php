<?php

namespace App\Services\Stats;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserStatsService
{
    public static function get($user)
    {
        return Cache::remember(
            "user_stats_{$user->id}_{$user->role}",
            now()->addMinutes(5),
            function () use ($user) {

                /*
                |--------------------------------------------------
                | SUPER ADMIN
                |--------------------------------------------------
                */
                if ($user->hasRole('Super Admin')) {
                    return [
                        'total_companies'  => Company::count(),
                        'total_admins'     => User::where('role', 'admin')->count(),
                        'total_managers'   => User::where('role', 'manager')->count(),
                        'total_users'      => User::where('role', 'user')->count(),
                        'total_storage_mb' => Company::sum('used_storage_mb'),
                        'total_photos'     => Company::sum('total_photos'),
                    ];
                }

                /*
                |--------------------------------------------------
                | ADMIN
                |--------------------------------------------------
                */
                if ($user->hasRole('admin')) {

                    $managerIds = User::where('role', 'manager')
                        ->where('created_by', $user->id)
                        ->pluck('id');

                    $totalUsers = User::where('role', 'user')
                        ->where(function ($q) use ($user, $managerIds) {
                            $q->where('created_by', $user->id)
                              ->orWhereIn('created_by', $managerIds);
                        })
                        ->count();

                    $directUsers = User::where('created_by', $user->id)->count();

                    $assignedToManagers = User::where('role', 'manager')
                        ->where('created_by', $user->id)
                        ->sum('max_limit');

                    $used = $directUsers + $assignedToManagers;
                    $max  = $user->max_limit ?? 0;

                    $percent = $max > 0 ? round(($used / $max) * 100, 1) : 0;
                    $reached = $max > 0 && $used >= $max;

                    $color = $percent >= 90
                        ? 'danger'
                        : ($percent >= 70 ? 'warning' : 'success');

                    return [
                        'total_admins'   => User::where('role', 'admin')
                            ->where('created_by', $user->id)->count(),

                        'total_managers' => $managerIds->count(),
                        'total_users'    => $totalUsers,

                        'limit' => [
                            'max'        => $max,
                            'used'       => $used,
                            'percent'    => $percent,
                            'reached'    => $reached,
                            'color'      => $color,
                            'description'=> $reached
                                ? 'Limit reached! Cannot create more.'
                                : "You've used {$used} of {$max}",
                        ],
                    ];
                }

                /*
                |--------------------------------------------------
                | MANAGER
                |--------------------------------------------------
                */
                if ($user->hasRole('manager')) {

                    $used = User::where('role', 'user')
                        ->where('created_by', $user->id)
                        ->count();

                    $max = $user->max_limit ?? 0;

                    $percent = $max > 0 ? round(($used / $max) * 100, 1) : 0;
                    $reached = $max > 0 && $used >= $max;

                    $color = $percent >= 90
                        ? 'danger'
                        : ($percent >= 70 ? 'warning' : 'success');

                    return [
                        'total_users' => $used,

                        'limit' => [
                            'max'        => $max,
                            'used'       => $used,
                            'percent'    => $percent,
                            'reached'    => $reached,
                            'color'      => $color,
                            'description'=> $reached
                                ? 'Limit reached!'
                                : "You've used {$used} of {$max}",
                        ],
                    ];
                }

                return [];
            }
        );
    }
}
