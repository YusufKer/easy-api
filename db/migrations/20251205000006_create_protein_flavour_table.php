<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProteinFlavourTable extends AbstractMigration
{
    /**
     * Create protein_flavour junction table
     */
    public function change(): void
    {
        $table = $this->table('protein_flavour');
        $table->addColumn('protein_id', 'integer')
              ->addColumn('flavour_id', 'integer')
              ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('protein_id', 'protein', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('flavour_id', 'flavour', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
