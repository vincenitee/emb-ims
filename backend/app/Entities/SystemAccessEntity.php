<?php

namespace App\Entities;

use App\Enums\RevocationReason;
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
		'granted_at',
		'revoked_at',
		'last_signed_in',
	];
	protected $casts   = [
		'id' => 'integer',
		'carhris_emp_id' => 'integer',

		'is_active' => 'bool',
		'is_division_chief' => 'bool',
		'is_document_handler' => 'bool',
		'is_regional_director' => 'bool',

		'granted_by' => 'integer',
		'revoked_by' => 'integer',

		// revocation_reason intentionally has NO entry here. CI4 4.0.5's
		// Entity::castAs() only understands a fixed set of built-in types
		// (int/float/string/bool/csv/array/json/datetime/timestamp/object)
		// — there is no pluggable cast-handler mechanism in this version,
		// so a value like 'enum' is silently ignored (castAs() falls
		// through and returns the raw value unchanged). Casting to
		// RevocationReason is instead done explicitly below via
		// getRevocationReason()/setRevocationReason(), which CI4's magic
		// __get()/__set() look for and use ahead of $attributes/$casts.
		'revocation_notes' => null,
		'last_signed_in' => null,
	];

	public function getRevocationReason(): ?RevocationReason
	{
		$raw = $this->attributes['revocation_reason'] ?? null;

		return $raw === null ? null : new RevocationReason($raw);
	}

	/**
	 * Accepts either a RevocationReason instance or one of its raw string
	 * values ('manual' | 'inactivity_threshold'). Always normalizes to the
	 * raw string before storing, so $attributes/toRawArray() stay
	 * DB-ready — new RevocationReason($value) also validates the value,
	 * rejecting anything that isn't a real enum member.
	 *
	 * @param RevocationReason|string|null $value
	 */
	public function setRevocationReason($value): self
	{
		$this->attributes['revocation_reason'] = $value === null
			? null
			: (new RevocationReason($value))->getValue();

		return $this;
	}
}