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

    /**
     * The only sanctioned way to read the password hash — used exclusively
     * during credential verification. Never expose this value through
     * toArray(), logging, or any other general-purpose path.
     */
    public function getPasswordHash(): ?string
    {
        return $this->attributes['password'] ?? null;
    }

    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $array = parent::toArray($onlyChanged, $cast, $recursive);
        unset($array['password']);

        return $array;
    }

    public function toRawArray(bool $onlyChanged = false, bool $recursive = false): array
    {
        $array = parent::toRawArray($onlyChanged, $recursive);
        unset($array['password']);

        return $array;
    }
}
