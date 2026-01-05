<?php
namespace App\Core;

use App\Models\Protein;
use App\Models\Flavour;
use App\Models\Cut;

class OrderValidator {

    private $proteinModel;
    private $flavourModel;
    private $cutModel;

    public function __construct(Protein $proteinModel){
        $this->proteinModel = $proteinModel;
    }

    public function validateOrderItem($item){
        $id = $item['id'] ?? null;
        $plate = $item['plate'] ?? null;
        $numberOfPlates = $item['numberOfPlates'] ?? null;
        $total = $item['total'] ?? null;

        $checkingTotal = 0;

        foreach($plate as $plate_item){
            $this->validatePlateItem($plate_item);
            $checkingTotal += $plate_item['price'] * $numberOfPlates;
        }

        if($checkingTotal != $total){
            throw new \Exception("Total price mismatch for order item ID $id", 1);
        }

        return true;
    }

    public function validatePlateItem($plate){
        $cut_id = $plate['cutID'] ?? null;
        $flavour_id = $plate['flavourID'] ?? null;
        $protein_id = $plate['meatID'] ?? null;
        $price = $plate['price'] ?? null;

        $proteinFlavour = $this->proteinModel->getProteinFlavour($protein_id, $flavour_id);
        $proteinCut = $this->proteinModel->getProteinCut($protein_id, $cut_id);

        if ($proteinFlavour['price'] + $proteinCut['price'] != $price) {
            throw new \Exception("Invalid price for protein ID $protein_id with flavour ID $flavour_id and cut ID $cut_id", 1);
            
        } else {
            return true;
        }
    }
}