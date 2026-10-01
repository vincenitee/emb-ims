<?php

namespace App\Entities;

use App\Enums\ActivityAction;
use CodeIgniter\Entity;

class ActivityLogEntity extends Entity
{
	protected $attributes = [
		'id' => null,
		'carhris_emp_id' => null,
		'native_admin_id' => null,
		'action' => null,
		'description' => null,
		'ip_address' => null,
		'user_agent' => null,
		'created_at' => null,
	];

	protected $datamap = [];
	protected $dates   = ['created_at'];

	protected $casts   = [
		'id' => 'integer',
		'carhris_emp_id' => '?integer',
		'native_admin_id' => '?integer',

		// action intentionally has NO entry here — same reason as
		// SystemAccessEntity::revocation_reason. CI4 4.0.5's castAs() only
		// understands its fixed built-in types, so casting to ActivityAction
		// is done explicitly via getAction()/setAction() below instead.
		'description' => '?string',
		'ip_address' => '?string',
		'user_agent' => '?string',
	];

	public function getAction(): ?ActivityAction
	{
		$raw = $this->attributes['action'] ?? null;

		return $raw === null ? null : new ActivityAction($raw);
	}

	/**
	 * Accepts either an ActivityAction instance or one of its raw string
	 * values. Always normalizes to the raw string before storing, so
	 * $attributes/toRawArray() stay DB-ready — new ActivityAction($value)
	 * also validates the value, rejecting anything that isn't a real
	 * member of the enum.
	 *
	 * @param ActivityAction|string $value
	 */
	public function setAction($value): self
	{
		$this->attributes['action'] = $value instanceof ActivityAction
			? $value->getValue()
			: (new ActivityAction($value))->getValue();

		return $this;
	}
}
