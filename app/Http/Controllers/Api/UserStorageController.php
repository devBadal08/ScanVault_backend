<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserStorageController extends Controller
{
    public function getStorageUsage()
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'error' => 'User or company not found.',
            ], 404);
        }

        $totalSize = 0;

        // ✅ Get all users under this company
        $companyUserIds = User::where('company_id', $user->company_id)
            ->pluck('id')
            ->toArray();

        // ✅ Find the company's admin (the one who has the main max_storage)
        $companyAdmin = User::where('company_id', $user->company_id)
            ->where('role', 'admin')
            ->first();

        $maxStorageMB = $companyAdmin?->max_storage ?? 0;

        // ✅ Sum up total folder sizes of all users in this company
        foreach ($companyUserIds as $uid) {
            $folderPath = storage_path("app/public/{$uid}");
            if (is_dir($folderPath)) {
                foreach (new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    $totalSize += $file->getSize();
                }
            }
        }

        // ✅ Convert bytes to MB and GB
        $totalSizeMB = round($totalSize / (1024 ** 2), 2);
        $totalSizeGB = round($totalSize / (1024 ** 3), 2);

        // ✅ Calculate % used against admin's max_storage
        $percentUsed = $maxStorageMB > 0 ? round(($totalSizeMB / $maxStorageMB) * 100, 1) : 0;
        $isNearLimit = $percentUsed >= 85;

        return response()->json([
            'company_id' => $user->company_id,
            'used_storage_mb' => $totalSizeMB,
            'used_storage_gb' => $totalSizeGB,
            'max_storage_mb' => $maxStorageMB,
            'percent_used' => $percentUsed,
            'is_near_limit' => $isNearLimit,
            'message' => $isNearLimit
                ? '⚠️ Your company is close to its storage limit!'
                : '✅ Company storage usage is within safe limits.',
        ]);
    }
}
