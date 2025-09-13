<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Force Spatie to use the main database
    protected $connection = 'mysql'; // replace with your main DB connection if different
}
