<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemAccessModel extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'system_access';
	protected $primaryKey           = 'id';
	protected $useAutoIncrement     = true;
	protected $insertID             = 0;
	protected $returnType           = 'array';
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
	protected $validationRules      = [];
	protected $validationMessages   = [];
	protected $skipValidation       = false;
	protected $cleanValidationRules = true;

	// Callbacks
	protected $allowCallbacks       = true;
	protected $beforeInsert         = [];
	protected $afterInsert          = [];
	protected $beforeUpdate         = [];
	protected $afterUpdate          = [];
	protected $beforeFind           = [];
	protected $afterFind            = [];
	protected $beforeDelete         = [];
	protected $afterDelete          = [];
}
