<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNativeAdminsTable extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'auto_increment' => true
			],

			'username' => [
				'type' => 'VARCHAR',
				'constraint' => 20
			],

			'password' => [
				'type' => 'VARCHAR',
				'constraint' => 255
			],

			'role' => [
				'type' => 'ENUM',
				'constraint' => ['admin', 'superadmin'],
				'null' => false
			],

			'is_active' => [
				'type' => 'BOOLEAN',
				'default' => true
			],

			'created_at' => [
				'type' => 'TIMESTAMP',
				'null' => true
			]
		]);

		$this->forge->addKey('id', true);
		$this->forge->createTable('native_admins');

		$this->db->query('ALTER TABLE native_admins MODIFY created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
	}

	public function down()
	{
		$this->forge->dropTable('native_admins');
	}
}
