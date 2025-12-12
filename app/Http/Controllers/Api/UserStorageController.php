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
        $companyId = $request->get('company_id'); // 🔹 from Flutter query param

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 422);
        }

        // 🔹 Security: ensure user is assigned to this company
        if (!$user->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json(['error' => 'User does not belong to this company'], 403);
        }

        // 🔹 Company admin record (max_storage)
        $companyAdmin = \DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.id', 'users.max_storage')
            ->first();

        if (!$companyAdmin) {
            return response()->json(['error' => 'Company admin not found'], 404);
        }

        $maxStorageMB = $companyAdmin->max_storage ?? 0;
        $usedStorage = 0;

        // 🔹 calculate storage only for this company folders
        $companyPath = storage_path("app/public/{$companyId}");

        if (is_dir($companyPath)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($companyPath, \FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                $usedStorage += $file->getSize();
            }
        }

        $usedMB = round($usedStorage / (1024 ** 2), 2);
        $percentUsed = $maxStorageMB > 0 ? round(($usedMB / $maxStorageMB) * 100, 1) : 0;

        return response()->json([
            'company_id' => $companyId,
            'used_storage_mb' => $usedMB,
            'max_storage_mb' => $maxStorageMB,
            'percent_used' => $percentUsed,
            'message' => $percentUsed >= 85
                ? '⚠️ Storage close to limit'
                : 'Storage OK',
        ]);
    }
}
