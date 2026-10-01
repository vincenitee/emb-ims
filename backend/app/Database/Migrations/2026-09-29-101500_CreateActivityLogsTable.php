<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsTable extends Migration
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

			// Exactly one of carhris_emp_id / native_admin_id is set per
			// row — enforced in ActivityLogModel, not here (MySQL can't FK
			// one column against two different tables, and can't express
			// "exactly one of these two" as a constraint on its own).
			'carhris_emp_id' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'null' => true
			],

			'native_admin_id' => [
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => true,
				'null' => true
			],

			'action' => [
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => false
			],

			'description' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => true
			],

			'ip_address' => [
				'type' => 'VARCHAR',
				'constraint' => 45,
				'null' => true
			],

			'user_agent' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => true
			],

			'created_at' => [
				'type' => 'TIMESTAMP',
				'null' => true
			]
		]);

		$this->forge->addKey('id', true);

		// ON DELETE SET NULL (not CASCADE, unlike granted_by/revoked_by on
		// system_access) — deliberately. This is an accountability log;
		// deleting the actor it points to must never delete the history of
		// what they did. system_access/native_admins rows are meant to be
		// toggled (is_active), never actually deleted, so this should
		// rarely fire in practice — but if it ever does, the log row
		// survives with the actor link nulled out rather than disappearing.
		$this->forge->addForeignKey('carhris_emp_id', 'system_access', 'carhris_emp_id', 'CASCADE', 'SET NULL');
		$this->forge->addForeignKey('native_admin_id', 'native_admins', 'id', 'CASCADE', 'SET NULL');

		$this->forge->createTable('activity_logs');

		$this->db->query('ALTER TABLE activity_logs MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
	}

	public function down()
	{
		$this->forge->dropForeignKey('activity_logs', 'activity_logs_carhris_emp_id_foreign');
		$this->forge->dropForeignKey('activity_logs', 'activity_logs_native_admin_id_foreign');
		$this->forge->dropTable('activity_logs');
	}
}
