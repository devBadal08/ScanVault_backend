<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\FolderShare;
use Illuminate\Support\Facades\Auth;

class FolderPermissionService
{
    public static function canWrite(Folder $folder): bool
    {
        // Owner can always write
        if ($folder->user_id === Auth::id()) {
            return true;
        }

        // Shared user with write access
        return FolderShare::where('folder_id', $folder->id)
            ->where('shared_with', Auth::id())
            ->where('access_type', 'write')
            ->exists();
    }
}
