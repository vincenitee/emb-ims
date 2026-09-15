<?php

namespace App\Entities;

use CodeIgniter\Entity;

class CarhrisUserEntity extends Entity {
    protected $attributes = [
        'id' => null,
        'first_name' => null,
        'middle_name' => null,
        'last_name' => null,
        'username' => null,
        'password' => null,

        'division_id' => null,
        'division_abbr' => null,
        'division_name' => null,
        
        'section_id' => null,
        'section_abbr' => null,
        'section_name' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'first_name' => 'string',
        'middle_name' => '?string',
        'last_name' => 'string',
        'username' => 'string',
        'password' => 'string',

        'division_id' => 'integer',
        'division_abbr' => 'string',
        'division_name' => 'string',
        
        'section_id' => 'integer',
        'section_abbr' => 'string',
        'section_name' => 'string',
    ];
}
