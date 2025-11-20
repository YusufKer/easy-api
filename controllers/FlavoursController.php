<?php

class FlavoursController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM flavour";
        $result = mysqli_query($this->db, $query);

        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $flavours[] = $row;
            }
        }

        $response = [
            'message' => 'Flavour data retrieved successfully',
            'data' => $flavours,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}