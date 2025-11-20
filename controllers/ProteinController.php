<?php

class ProteinController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM protein";
        $result = mysqli_query($this->db, $query);

        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $proteins[] = $row;
            }
        }

        $response = [
            'message' => 'Protein data retrieved successfully',
            'data' => $proteins,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}
