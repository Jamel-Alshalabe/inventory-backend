<?php

namespace App\Models;

use Laratrust\Models\Role as LaratrustRole;

class Role extends LaratrustRole
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'guard_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
