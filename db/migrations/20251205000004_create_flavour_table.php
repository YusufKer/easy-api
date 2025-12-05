<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFlavourTable extends AbstractMigration
{
    /**
     * Create flavour table
     */
    public function change(): void
    {
        $table = $this->table('flavour');
        $table->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
