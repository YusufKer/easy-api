<?php
    require __DIR__ . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();



    // Create database connection
    function getDbConnection() {
        // Database configuration
        $db_host = $_ENV['DB_HOST'];
        $db_port = $_ENV['DB_PORT'];
        $db_name = $_ENV['DB_NAME'];
        $db_user = $_ENV['DB_USER'];
        $db_pass = $_ENV['DB_PASSWORD'];

        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
        
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
            exit();
        }
        
        // Set charset to utf8mb4 for proper character support
        mysqli_set_charset($conn, 'utf8mb4');
        
        return $conn;
    }