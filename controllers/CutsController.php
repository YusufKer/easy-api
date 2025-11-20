<?php

class CutsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM cut";
        $stmt = $this->db->query($query);
        $cuts = $stmt->fetchAll();

        $response = [
            'message' => 'Cuts data retrieved successfully',
            'data' => $cuts,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}