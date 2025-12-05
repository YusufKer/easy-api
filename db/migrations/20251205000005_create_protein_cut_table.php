<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProteinCutTable extends AbstractMigration
{
    /**
     * Create protein_cut junction table
     */
    public function change(): void
    {
        $table = $this->table('protein_cut');
        $table->addColumn('cut_id', 'integer')
              ->addColumn('protein_id', 'integer')
              ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('cut_id', 'cut', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('protein_id', 'protein', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
