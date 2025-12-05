<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserTable extends AbstractMigration
{
    /**
     * Create user table
     */
    public function change(): void
    {
        $table = $this->table('user');
        $table->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('password_hash', 'string', ['limit' => 255])
              ->addColumn('role', 'enum', ['values' => ['admin', 'user'], 'default' => 'user'])
              ->addColumn('api_key', 'string', ['limit' => 64, 'null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['api_key'], ['unique' => true])
              ->create();
    }
}
