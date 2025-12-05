<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokenTable extends AbstractMigration
{
    /**
     * Create refresh_token table
     */
    public function change(): void
    {
        $table = $this->table('refresh_token');
        $table->addColumn('user_id', 'integer')
              ->addColumn('token', 'string', ['limit' => 255])
              ->addColumn('expires_at', 'timestamp')
              ->addColumn('is_revoked', 'boolean', ['default' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['token'], ['unique' => true])
              ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
