<?php

class CutsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM cut";
        $result = mysqli_query($this->db, $query);

        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $cuts[] = $row;
            }
        }

        $response = [
            'message' => 'Cuts data retrieved successfully',
            'data' => $cuts,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}