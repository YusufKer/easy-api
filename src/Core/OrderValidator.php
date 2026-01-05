<?php
namespace App\Core;

use App\Models\Protein;
use App\Models\Flavour;
use App\Models\Cut;

class OrderValidator {

    private $proteinModel;
    private $flavourModel;
    private $cutModel;

    public function __construct(Protein $proteinModel, Flavour $flavourModel, Cut $cutModel){
        $this->proteinModel = $proteinModel;
        $this->flavourModel = $flavourModel;
        $this->cutModel = $cutModel;

    }

    public function validateOrderItem($item){
        $id = $item['id'] ?? null;
        $plate = $item['plate'] ?? null;
        $numberOfPlates = $item['numberOfPlates'] ?? null;
        $total = $item['total'] ?? null;

        foreach($plate as $plate_item){
            $this->validatePlateItem($plate_item);
        }
    }

    public function validatePlateItem($plate){
        $cut_id = $plate['cutID'] ?? null;
        $flavour_id = $plate['flavourID'] ?? null;
        $protein_id = $plate['meatID'] ?? null;
        $price = $plate['price'] ?? null;

        $protein = $this->proteinModel->findById($protein_id);

        var_dump($protein);

    }
}