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

        Response::success('Flavour data retrieved successfully', $flavours);
    }
}