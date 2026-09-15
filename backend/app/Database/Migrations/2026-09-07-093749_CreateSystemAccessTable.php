<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemAccessTable extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'auto_increment' => true
			],

			'carhris_emp_id' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'null' => false
			],

			'is_active' => [
				'type' => 'BOOLEAN',
				'default' => true,
			],

			'is_division_chief' => [
				'type' => 'BOOLEAN',
				'default' => false
			],

			'is_document_handler' => [
				'type' => 'BOOLEAN',
				'default' => false
			],

			'is_regional_director' => [
				'type' => 'BOOLEAN',
				'default' => false
			],

			'granted_by' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'null' => false
			],

			'granted_at' => [
				'type' => 'TIMESTAMP',
			],

			'revoked_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],

			'revoked_by' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'null' => false
			],

			'revocation_reason' => [
				'type' => 'ENUM',
				'constraint' => [
					'manual',
					'inactivity_threshold'
				],
			],

			'revocation_notes' => [
				'type' => 'VARCHAR',
				'constraint' => 100,
				'null' => true
			],

			'last_signed_in' => [
				'type' => 'DATETIME',
				'null' => true,
			]
		]);

		$this->forge->addKey('id', true);

		$this->forge->addForeignKey('granted_by', 'native_admins', 'id', 'CASCADE', 'CASCADE');

		$this->forge->addForeignKey('revoked_by', 'native_admins', 'id', 'CASCADE', 'CASCADE');

		$this->forge->createTable('system_access');

		$this->db->query('ALTER TABLE system_access MODIFY granted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
	}

	public function down()
	{
		$this->forge->dropForeignKey('system_access', 'system_access_granted_by_foreign');
		$this->forge->dropForeignKey('system_access', 'system_access_revoked_by_foreign');
		$this->forge->dropTable('system_access');
	}
}
