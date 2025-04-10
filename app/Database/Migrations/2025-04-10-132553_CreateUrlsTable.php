<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


class CreateUrlsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'null'           => true,
            ],
            'short_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'unique'     => true,
                'null'       => true,
            ],
            'original_url' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'visit_count' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'default'        => 0,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true); 
        $this->forge->addKey('short_code');
        $this->forge->createTable('urls', true);
    }

    public function down()
    {
        $this->forge->dropTable('urls', true);
    }
}