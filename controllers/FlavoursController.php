<?php

class FlavoursController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM flavour";
        $stmt = $this->db->query($query);
        $flavours = $stmt->fetchAll();

        $response = [
            'message' => 'Flavour data retrieved successfully',
            'data' => $flavours,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}