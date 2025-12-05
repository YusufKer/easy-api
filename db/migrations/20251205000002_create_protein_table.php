<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProteinTable extends AbstractMigration
{
    /**
     * Create protein table
     */
    public function change(): void
    {
        $table = $this->table('protein');
        $table->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['name'], ['unique' => true])
              ->create();
    }
}
