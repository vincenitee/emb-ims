<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCarhrisUsersView extends Migration
{
	public function up()
	{
		$this->db->query('
			CREATE OR REPLACE VIEW carhris_users AS
			SELECT
				u.id,
				u.FirstName AS first_name,
				u.MiddleName AS middle_name,
				u.LastName AS last_name,
				u.username,
				u.password,
				u.DivisionID AS division_id,
				d.DivisionAbbreviation AS division_abbr,
				d.DivisionName AS division_name,
				u.SectionID AS section_id,
				s.SectionAbbreviation AS section_abbr,
				s.SectionName AS section_name
			FROM carhris.users AS u
				JOIN carhris.tblofficedivisions AS d
					ON u.DivisionID = d.id
				JOIN carhris.tblsections AS s
					ON u.SectionID = s.id
			WHERE u.EmploymentStatus = \'PERMANENT\'
		');
	}

	public function down()
	{
		$this->db->query('DROP VIEW IF EXISTS carhris_users');
	}
}
