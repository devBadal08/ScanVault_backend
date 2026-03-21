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

        // ✅ Get company directly
        $company = \App\Models\Company::find($companyId);

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // ✅ Get max storage from admin
        $companyAdmin = \DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.max_storage')
            ->first();

        $maxStorageMB = $companyAdmin?->max_storage ?? 0;

        // ✅ IMPORTANT: Use DB field (NOT file scan)
        $usedMB = (float) $company->used_storage_mb;

        if ($maxStorageMB <= 0) {
            return response()->json([
                'company_id' => $companyId,
                'used_storage_mb' => $usedMB,
                'max_storage_mb' => 0,
                'percent_used' => 0,
                'message' => 'No storage limit assigned',
            ]);
        }

        $percentUsed = min(
            100,
            round(($usedMB / $maxStorageMB) * 100, 1)
        );

        return response()->json([
            'company_id' => $companyId,
            'used_storage_mb' => round($usedMB, 2),
            'max_storage_mb' => $maxStorageMB,
            'percent_used' => $percentUsed,
            'message' => $percentUsed >= 99
                ? 'Storage full'
                : ($percentUsed >= 85 ? 'Storage close to limit' : 'Storage OK'),
        ]);
    }
}
