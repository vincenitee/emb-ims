<?php

namespace App\Models;

use App\Entities\SystemAccessEntity;
use CodeIgniter\Model;

class SystemAccessModel extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'system_access';
	protected $primaryKey           = 'id';
	protected $useAutoIncrement     = true;
	protected $insertID             = 0;
	protected $returnType           = SystemAccessEntity::class;
	protected $useSoftDeletes       = false;
	protected $protectFields        = true;
	protected $allowedFields        = [
		'carhris_emp_id',
		'is_active',
		'is_division_chief',
		'is_document_handler',
		'is_regional_director',
		'granted_by',
		'granted_at',
		'revoked_at',
		'revoked_by',
		'revocation_reason',
		'revocation_notes',
		'last_signed_in'
	];

	// Dates
	protected $useTimestamps        = false;
	protected $dateFormat           = 'datetime';
	protected $createdField         = 'created_at';
	protected $updatedField         = 'updated_at';
	protected $deletedField         = 'deleted_at';

	// Validation
	//
	// carhris_emp_id and granted_by both use is_not_unique[table.column] as a
	// plain, statically-written existence check (no {field} placeholder
	// interpolation) — see CLAUDE.md's note on never building CI4 validation
	// rule strings/placeholders from user input.
	//
	// carhris_emp_id: required + must exist in the carhris_users view, per
	// CLAUDE.md — "Any code that grants access ... must run an explicit
	// existence check against carhris_users first and reject the grant if
	// no match is found." The view is already filtered to
	// EmploymentStatus = 'PERMANENT', so this single check covers both
	// "employee exists" and "employee is currently eligible."
	//
	// is_unique[system_access.carhris_emp_id] enforces one row per employee
	// ever — access is toggled (is_active) on the same row, never
	// re-inserted. This only works safely because it's a plain static rule
	// (no {id}-exclusion placeholder): cleanValidationRules drops this rule
	// entirely on update() calls, which is why carhris_emp_id must never be
	// re-supplied on update — see preventCarhrisEmpIdChange() below, which
	// enforces that defensively regardless of what a caller passes in.
	//
	// revoked_by / revocation_reason are permit_empty: a freshly granted,
	// non-revoked row legitimately has neither set. null revoked_by means
	// system-revoked (inactivity threshold); a native_admin id means manual.
	protected $validationRules      = [
		'carhris_emp_id'       => 'required|is_natural_no_zero|is_not_unique[carhris_users.id]|is_unique[system_access.carhris_emp_id]',

		'is_active'            => 'permit_empty|in_list[0,1]',
		'is_division_chief'    => 'permit_empty|in_list[0,1]',
		'is_document_handler'  => 'permit_empty|in_list[0,1]',
		'is_regional_director' => 'permit_empty|in_list[0,1]',

		'granted_by'           => 'required|is_natural_no_zero|is_not_unique[native_admins.id]',
		'granted_at'           => 'permit_empty|valid_date',

		'revoked_at'           => 'permit_empty|valid_date',
		'revoked_by'           => 'permit_empty|is_natural_no_zero|is_not_unique[native_admins.id]',
		'revocation_reason'    => 'permit_empty|in_list[manual,inactivity_threshold]',
		'revocation_notes'     => 'permit_empty|max_length[100]',

		'last_signed_in'       => 'permit_empty|valid_date',
	];
	protected $validationMessages   = [
		'carhris_emp_id' => [
			'required'        => 'A CARHRIS employee ID is required to grant system access.',
			'is_natural_no_zero' => 'The CARHRIS employee ID must be a valid positive identifier.',
			'is_not_unique'   => 'No permanent CARHRIS employee record matches this ID — access cannot be granted.',
			'is_unique'       => 'This employee already has a system_access record — toggle is_active instead of granting again.',
		],
		'is_active' => [
			'in_list' => 'is_active must be either 0 or 1.',
		],
		'is_division_chief' => [
			'in_list' => 'is_division_chief must be either 0 or 1.',
		],
		'is_document_handler' => [
			'in_list' => 'is_document_handler must be either 0 or 1.',
		],
		'is_regional_director' => [
			'in_list' => 'is_regional_director must be either 0 or 1.',
		],
		'granted_by' => [
			'required'        => 'A granting native admin is required.',
			'is_natural_no_zero' => 'The granting admin ID must be a valid positive identifier.',
			'is_not_unique'   => 'No native admin account matches the granting admin ID.',
		],
		'granted_at' => [
			'valid_date' => 'granted_at must be a valid date/time.',
		],
		'revoked_at' => [
			'valid_date' => 'revoked_at must be a valid date/time.',
		],
		'revoked_by' => [
			'is_natural_no_zero' => 'The revoking admin ID must be a valid positive identifier.',
			'is_not_unique'   => 'No native admin account matches the revoking admin ID.',
		],
		'revocation_reason' => [
			'in_list' => "revocation_reason must be either 'manual' or 'inactivity_threshold'.",
		],
		'revocation_notes' => [
			'max_length' => 'revocation_notes cannot exceed 100 characters.',
		],
		'last_signed_in' => [
			'valid_date' => 'last_signed_in must be a valid date/time.',
		],
	];
	protected $skipValidation       = false;
	protected $cleanValidationRules = true;

	// Callbacks
	protected $allowCallbacks       = true;
	protected $beforeInsert         = [];
	protected $afterInsert          = [];
	protected $beforeUpdate         = ['preventCarhrisEmpIdChange'];
	protected $afterUpdate          = [];
	protected $beforeFind           = [];
	protected $afterFind            = [];
	protected $beforeDelete         = [];
	protected $afterDelete          = [];

	public function findByCarhrisId(string $carhrisEmpId): ?SystemAccessEntity
	{
		return $this->where('carhris_emp_id', $carhrisEmpId)->first();
	}
	
	protected function preventCarhrisEmpIdChange(array $data): array
	{
		unset($data['data']['carhris_emp_id']);

		return $data;
	}
}