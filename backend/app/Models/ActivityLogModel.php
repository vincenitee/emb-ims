<?php

namespace App\Models;

use App\Entities\ActivityLogEntity;
use App\Enums\ActivityAction;
use CodeIgniter\Model;
use InvalidArgumentException;

class ActivityLogModel extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'activity_logs';
	protected $primaryKey           = 'id';
	protected $useAutoIncrement     = true;
	protected $insertID             = 0;
	protected $returnType           = ActivityLogEntity::class;
	protected $useSoftDeletes       = false;
	protected $protectFields        = true;
	protected $allowedFields        = [
		'carhris_emp_id',
		'native_admin_id',
		'action',
		'description',
		'ip_address',
		'user_agent',
	];

	// Dates
	//
	// No updated_at/deleted_at, and useTimestamps stays false: this table is
	// insert-only. An accountability log that can be edited or soft-deleted
	// after the fact isn't an accountability log. created_at is filled by
	// the DB's own DEFAULT CURRENT_TIMESTAMP (see the migration), not CI4.
	protected $useTimestamps        = false;
	protected $dateFormat           = 'datetime';
	protected $createdField         = 'created_at';

	// Validation
	//
	// required_without on both actor columns enforces "at least one of
	// these is set." It can't express "at most one" on its own, so the
	// stricter exactly-one-actor rule is enforced in
	// preventMultipleOrMissingActor() below instead.
	//
	// is_not_unique[...] is a plain, statically-written existence check (no
	// {field} placeholder interpolation) — see CLAUDE.md's note on never
	// building CI4 validation rule strings from user input.
	protected $validationRules      = [
		'carhris_emp_id'  => 'permit_empty|is_natural_no_zero|is_not_unique[system_access.carhris_emp_id]|required_without[native_admin_id]',
		'native_admin_id' => 'permit_empty|is_natural_no_zero|is_not_unique[native_admins.id]|required_without[carhris_emp_id]',
		'action'          => 'required|max_length[50]',
		'description'     => 'permit_empty|max_length[255]',
		'ip_address'      => 'permit_empty|max_length[45]',
		'user_agent'      => 'permit_empty|max_length[255]',
	];
	protected $validationMessages   = [
		'carhris_emp_id' => [
			'is_not_unique'      => 'No system_access record matches this CARHRIS employee ID.',
			'required_without'   => 'Either carhris_emp_id or native_admin_id is required.',
		],
		'native_admin_id' => [
			'is_not_unique'      => 'No native_admins record matches this admin ID.',
			'required_without'   => 'Either carhris_emp_id or native_admin_id is required.',
		],
		'action' => [
			'required' => 'An action is required to log an activity.',
		],
	];
	protected $skipValidation       = false;
	protected $cleanValidationRules = true;

	// Callbacks
	protected $allowCallbacks       = true;
	protected $beforeInsert         = ['preventMultipleActors', 'validateAction'];
	protected $afterInsert          = [];
	protected $beforeUpdate         = [];
	protected $afterUpdate          = [];
	protected $beforeFind           = [];
	protected $afterFind            = [];
	protected $beforeDelete         = [];
	protected $afterDelete          = [];

	/**
	 * The two actor columns are mutually exclusive: a row is either a
	 * CARHRIS-side user action or a native_admin action, never both and
	 * never neither. required_without (above) only guarantees "at least
	 * one" — this guarantees "at most one."
	 */
	protected function preventMultipleActors(array $data): array
	{
		$hasCarhrisActor = !empty($data['data']['carhris_emp_id']);
		$hasAdminActor   = !empty($data['data']['native_admin_id']);

		if ($hasCarhrisActor && $hasAdminActor) {
			throw new InvalidArgumentException(
				'An activity log row must have exactly one actor — carhris_emp_id and native_admin_id cannot both be set.'
			);
		}

		return $data;
	}

	/**
	 * action is a plain VARCHAR, not a DB ENUM (see ActivityAction), so
	 * nothing at the schema level stops a typo or an undefined action
	 * string from being written. Guard it here instead.
	 */
	protected function validateAction(array $data): array
	{
		if (isset($data['data']['action']) && !ActivityAction::isValid($data['data']['action'])) {
			throw new InvalidArgumentException("'{$data['data']['action']}' is not a recognized ActivityAction.");
		}

		return $data;
	}
}
