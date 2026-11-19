<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: per-provider "require approval" setting.
 */
class Migration_Add_require_approval_to_user_settings extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->field_exists('require_approval', 'user_settings')) {
            $this->dbforge->add_column('user_settings', [
                'require_approval' => [
                    'type' => 'TINYINT',
                    'constraint' => '1',
                    'default' => 0,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->field_exists('require_approval', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'require_approval');
        }
    }
}
