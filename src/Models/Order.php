<?php

namespace App\Models;

use PDO;
use PDOException;

class Order
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create new order
     */
    public function create(string $orderNumber, int $userId): int
    {
        $query = 'INSERT INTO `order` (order_number, user_id) VALUES (?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$orderNumber, $userId]);
        return (int) $this->db->lastInsertId();
    }
}
