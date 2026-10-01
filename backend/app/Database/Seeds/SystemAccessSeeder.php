<?php

namespace App\Database\Seeds;

use App\Models\NativeAdminModel;
use App\Models\SystemAccessModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Grants system_access to CARHRIS employee #156 — local/dev use only.
 *
 * Goes through SystemAccessModel (not the query builder) deliberately: its
 * validation rules already implement the CLAUDE.md-mandated existence check
 * against carhris_users (is_not_unique[carhris_users.id]) and reject a grant
 * with no match, so this seeder doesn't need to duplicate that check —
 * it just has to handle the model saying no.
 *
 * No role flags (is_division_chief / is_document_handler /
 * is_regional_director) are set — the caller didn't ask for a specific
 * IMS role, so this grants baseline access only, using the table's own
 * defaults (all false). Toggle them separately once the intended role
 * is known.
 */
class SystemAccessSeeder extends Seeder
{
    private const TARGET_CARHRIS_EMP_ID = 156;

    public function run()
    {
        $systemAccessModel = new SystemAccessModel();
        $nativeAdminModel  = new NativeAdminModel();

        if ($systemAccessModel->findByCarhrisId((string) self::TARGET_CARHRIS_EMP_ID)) {
            CLI::write("system_access already exists for CARHRIS employee " . self::TARGET_CARHRIS_EMP_ID . " — skipping.", 'yellow');
            return;
        }

        // granted_by is NOT NULL + FK'd to native_admins — pick a real admin
        // rather than hardcoding an id that only holds if seeders ran in a
        // particular order. Prefers a superadmin, falls back to any admin.
        $grantingAdmin = $nativeAdminModel->where('role', 'superadmin')->first()
            ?? $nativeAdminModel->first();

        if (!$grantingAdmin) {
            CLI::error('No native_admins row exists to act as granted_by — run NativeAdminSeeder first.');
            return;
        }

        $inserted = $systemAccessModel->insert([
            'carhris_emp_id' => self::TARGET_CARHRIS_EMP_ID,
            'is_active'      => true,
            'granted_by'     => $grantingAdmin->id,
        ]);

        if (!$inserted) {
            CLI::error('Grant rejected: ' . implode(' ', $systemAccessModel->errors()));
            return;
        }

        CLI::write('Granted system_access to CARHRIS employee ' . self::TARGET_CARHRIS_EMP_ID . " (granted_by native_admins.id={$grantingAdmin->id}).", 'green');
    }
}
