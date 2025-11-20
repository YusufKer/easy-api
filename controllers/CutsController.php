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

        Response::success('Cuts data retrieved successfully', $cuts);
    }
}