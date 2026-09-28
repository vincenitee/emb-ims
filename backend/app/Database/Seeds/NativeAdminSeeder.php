<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds one native_admins row per role defined on the table's `role` ENUM
 * ('admin', 'superadmin') — local/dev use only.
 *
 * Uses the query builder directly rather than NativeAdminModel: the model's
 * $allowedFields doesn't currently include 'role', which is a NOT NULL
 * column with no default, so inserting through the model would fail. Worth
 * fixing in NativeAdminModel separately; this seeder works around it rather
 * than papering over it there.
 *
 * These are throwaway dev credentials, not provisioning for a real
 * environment — rotate/remove before anything resembling production data
 * touches this table.
 */
class NativeAdminSeeder extends Seeder
{
    public function run()
    {
        $admins = [
            [
                'username'   => 'admin',
                'password'   => password_hash('ChangeMe!123', PASSWORD_DEFAULT),
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username'   => 'superadmin',
                'password'   => password_hash('ChangeMe!123', PASSWORD_DEFAULT),
                'role'       => 'superadmin',
                'is_active'  => true,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($admins as $admin) {
            // No unique constraint exists on `username` at the DB level, so
            // guard idempotency here instead — re-running the seeder
            // shouldn't pile up duplicate accounts per role.
            $exists = $this->db->table('native_admins')
                ->where('username', $admin['username'])
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->db->table('native_admins')->insert($admin);
        }
    }
}
