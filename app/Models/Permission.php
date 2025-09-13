<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Force Spatie to use the main database
    protected $connection = 'mysql'; // replace with your main DB connection if different
}
