<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserStorageController extends Controller
{
    public function getStorageUsage(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->get('company_id');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 422);
        }

        if (!$user->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json(['error' => 'User does not belong to this company'], 403);
        }

        $companyAdmin = \DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.max_storage')
            ->first();

        $maxStorageMB = $companyAdmin?->max_storage ?? 0;

        if ($maxStorageMB <= 0) {
            return response()->json([
                'company_id' => $companyId,
                'used_storage_mb' => 0,
                'max_storage_mb' => 0,
                'percent_used' => 0,
                'message' => 'No storage limit assigned',
            ]);
        }

        $usedStorageBytes = 0;
        $companyPath = storage_path("app/public/{$companyId}");

        if (is_dir($companyPath)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($companyPath, \FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                if ($file->isFile()) {
                    $usedStorageBytes += $file->getSize();
                }
            }
        }

        $usedMB = round($usedStorageBytes / (1024 ** 2), 2);
        $percentUsed = min(
            100,
            round(($usedMB / $maxStorageMB) * 100, 1)
        );

        return response()->json([
            'company_id' => $companyId,
            'used_storage_mb' => $usedMB,
            'max_storage_mb' => $maxStorageMB,
            'percent_used' => $percentUsed,
            'message' => $percentUsed >= 99
                ? 'Storage full'
                : ($percentUsed >= 85 ? 'Storage close to limit' : 'Storage OK'),
        ]);
    }
}
