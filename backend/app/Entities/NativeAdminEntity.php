<?php

namespace App\Entities;

use CodeIgniter\Entity;

class NativeAdminEntity extends Entity {    
    protected $attributes = [
        'id' => null,
        'username' => null,
        'password' => null,
        'role' => null,
        'is_active' => null,
        'created_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'username' => 'string',
        'password' => 'string',
        'role' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'timestamp'
    ];
}
