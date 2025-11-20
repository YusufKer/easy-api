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

    public function getById($id) {
        $query = "SELECT id, name FROM protein WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($row = mysqli_fetch_assoc($result)){
            $response = [
                'message' => 'Protein retrieved successfully',
                'data' => $row,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => 'Protein not found',
                'id' => $id
            ]);
        }
    }
}
