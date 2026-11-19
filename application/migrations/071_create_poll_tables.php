<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: group meeting polls (polls, poll_options, poll_votes).
 */
class Migration_Create_poll_tables extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('polls')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                'hash' => ['type' => 'VARCHAR', 'constraint' => '32'],
                'title' => ['type' => 'VARCHAR', 'constraint' => '256'],
                'description' => ['type' => 'TEXT', 'null' => true],
                'id_users_provider' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'id_services' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'id_users_creator' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => '16', 'default' => 'open'],
                'id_chosen_option' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true, 'null' => true],
                'create_datetime' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('polls', true);
        }

        if (!$this->db->table_exists('poll_options')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                'id_polls' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'start_datetime' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_polls');
            $this->dbforge->create_table('poll_options', true);
        }

        if (!$this->db->table_exists('poll_votes')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true, 'auto_increment' => true],
                'id_polls' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'id_poll_options' => ['type' => 'INT', 'constraint' => '11', 'unsigned' => true],
                'first_name' => ['type' => 'VARCHAR', 'constraint' => '128'],
                'last_name' => ['type' => 'VARCHAR', 'constraint' => '128', 'null' => true],
                'email' => ['type' => 'VARCHAR', 'constraint' => '256'],
                'create_datetime' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_polls');
            $this->dbforge->add_key('id_poll_options');
            $this->dbforge->create_table('poll_votes', true);
        }
    }

    public function down(): void
    {
        $this->dbforge->drop_table('poll_votes', true);
        $this->dbforge->drop_table('poll_options', true);
        $this->dbforge->drop_table('polls', true);
    }
}
