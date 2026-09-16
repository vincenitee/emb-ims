<?php

namespace App\Models;

use App\Entities\CarhrisUserEntity;
use CodeIgniter\Model;

class CarhrisUserModel extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'carhris_users';
	protected $primaryKey           = 'id';
	protected $useAutoIncrement     = true;
	protected $insertID             = 0;
	protected $returnType           = CarhrisUserEntity::class;
	protected $useSoftDeletes       = false;
	protected $protectFields        = true;
	protected $allowedFields        = [];

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

	/**
	 * Format the query response
	 */
	public function groupCarhrisUsers(array $rows): array
	{
		$grouped = [];

		foreach ($rows as $row) {
			$id = (int) $row['id'];

			if (!isset($grouped[$id])) {
				$grouped[$id] = [
					'id' => $id,
					'first_name' => $row['first_name'],
					'middle_name' => $row['middle_name'],
					'last_name' => $row['last_name'],
					'username' => $row['username'],
				];
			}

			if (!empty($row['division_id'])) {
				$grouped[$id]['division'] = [
					'id' => $row['division_id'],
					'abbr' => $row['division_abbr'],
					'name' => $row['division_name'],
				];
			}

			if (!empty($row['section'])) {
				$grouped[$id]['section'] = [
					'id' => $row['section'],
					'abbr' => $row['section_abbr'],
					'name' => $row['section_name'],
				];
			}
		}

		return array_values($grouped);
	}

	/**
	 * Searches for CARHRIS users by providing array of filters, page and content per page
	 */
	public function searchCarhrisUsers(array $filters = [], int $page = 1, int $perPage = 15): array
	{
		$builder = $this->db
			->table('carhris_users')
			->select([
				'id',

				'last_name',
				'first_name',
				'middle_name',

				'username',

				'division_id',
				'division_abbr',
				'division_name',

				'section_id',
				'section_abbr',
				'section_name',
			])
			->orderBy('last_name', 'DESC');

		if (!empty($filters['id'])) {
			$builder->where('id', $filters['id']);
		}

		if (!empty($filters['last_name'])) {
			$builder->where('last_name', $filters['last_name']);
		}

		if (!empty($filters['first_name'])) {
			$builder->where('first_name', $filters['first_name']);
		}

		if (!empty($filters['middle_name'])) {
			$builder->where('middle_name', $filters['middle_name']);
		}

		if (!empty($filters['username'])) {
			$builder->where('username', $filters['username']);
		}

		if (!empty($filters['division_id'])) {
			$builder->where('division_id', $filters['division_id']);
		}

		if (!empty($filters['division_abbr'])) {
			$builder->where('division_abbr', $filters['division_abbr']);
		}

		if (!empty($filters['division_name'])) {
			$builder->where('division_name', $filters['division_name']);
		}

		if (!empty($filters['section_id'])) {
			$builder->where('section_id', $filters['section_id']);
		}

		if (!empty($filters['section_abbr'])) {
			$builder->where('section_abbr', $filters['section_abbr']);
		}

		if (!empty($filters['section_name'])) {
			$builder->where('section_name', $filters['section_name']);
		}

		$builder->orderBy('last_name', 'DESC');

		$total = (clone $builder)->countAllResults(false);

		$offset = ($page - 1) * $perPage;

		$data = $builder
			->limit($perPage, $offset)
			->get()
			->getResultArray();

		return [
			'data' => $this->groupCarhrisUsers($data),
			'total' => $total,
			'page' => $page,
			'per_page' => $perPage
		];
	}

	/**
	 * Retrieves the CARHRIS user using it's username
	 */
	public function findByUsername(string $username): ?CarhrisUserEntity
	{
		return $this->where('username', $username)->first();
	}
}