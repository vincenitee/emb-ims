<?php

namespace App\Entities;

use CodeIgniter\Entity;

class SystemAccessEntity extends Entity
{
	protected $attributes = [
		'id' => null,
		'carhris_emp_id' => null,

		'is_active' => null,
		'is_division_chief' => null,
		'is_document_handler' => null,
		'is_regional_director' => null,

		'granted_by' => null,
		'granted_at' => null,
		'revoked_at' => null,
		'revoked_by' => null,

		'revocation_reason' => null,
		'revocation_notes' => null,
		'last_signed_in' => null,
	];
	protected $datamap = [];
	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];
	protected $casts   = [];
}
