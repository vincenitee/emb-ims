<?php

namespace App\Models;

use App\Entities\NativeAdminEntity;
use CodeIgniter\Model;

class NativeAdminModel extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'native_admins';
	protected $primaryKey           = 'id';
	protected $useAutoIncrement     = true;
	protected $insertID             = 0;
	protected $returnType           = NativeAdminEntity::class;
	protected $useSoftDelete        = false;
	protected $protectFields        = true;
	protected $allowedFields        = ['username', 'password', 'is_active', 'created_at'];

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

	// No groupAdminUsers() here, unlike CarhrisUserModel — that method exists
	// to collapse rows a JOIN duplicates. native_admins is a standalone
	// table with no join in its query surface, so searchAdminUsers() below
	// can return its rows/entities directly; there's nothing to collapse.
	public function searchAdminUsers(array $filters = [], int $page = 1, $perPage = 15): array {
		$builder = $this->db
			->table('native_admins')
			->select([
				'id',
				'username',
				'role',
				'is_active',
				'created_at'
			]);

		if(!empty($filters['id'])) {
			$builder->where('id', $filters['id']);
		}

		if(!empty($filters['username'])) {
			$builder->where('username', $filters['username']);
		}

		if(!empty($filters['role'])) {
			$builder->where('role', $filters['role']);
		}

		$builder->orderBy('created_at', 'DESC');

		$total = (clone $builder)->countAllResults(false);

		$offset = ($page - 1) * $perPage;

		$data = $builder
			->limit($perPage, $offset)
			->get()
			->getResultArray();

		return [
			'data'     => $data,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $perPage
		];
	}

	public function findAdminByUsername(string $username): ?NativeAdminEntity
	{
		return $this->where('username', $username)->first();
	}
}
