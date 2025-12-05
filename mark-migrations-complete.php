#!/usr/bin/env php
<?php
/**
 * Script to mark existing migrations as complete in phinxlog table
 * Use this when you already have tables in your database from old SQL files
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Colors for output
define('GREEN', "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('RED', "\033[0;31m");
define('NC', "\033[0m"); // No Color

function printInfo($message) {
    echo GREEN . "[INFO]" . NC . " $message\n";
}

function printWarning($message) {
    echo YELLOW . "[WARNING]" . NC . " $message\n";
}

function printError($message) {
    echo RED . "[ERROR]" . NC . " $message\n";
}

try {
    // Connect to database
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME']),
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    printInfo("Connected to database: " . $_ENV['DB_NAME']);
    
    // Check if phinxlog table exists
    $result = $pdo->query("SHOW TABLES LIKE 'phinxlog'");
    if ($result->rowCount() === 0) {
        printInfo("Creating phinxlog table...");
        $pdo->exec("
            CREATE TABLE phinxlog (
                version BIGINT NOT NULL,
                migration_name VARCHAR(100),
                start_time TIMESTAMP NULL,
                end_time TIMESTAMP NULL,
                breakpoint TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (version)
            )
        ");
    } else {
        printInfo("phinxlog table already exists");
    }
    
    // List of migrations to mark as complete
    $migrations = [
        ['version' => '20251205000001', 'name' => 'CreateUserTable'],
        ['version' => '20251205000002', 'name' => 'CreateProteinTable'],
        ['version' => '20251205000003', 'name' => 'CreateCutTable'],
        ['version' => '20251205000004', 'name' => 'CreateFlavourTable'],
        ['version' => '20251205000005', 'name' => 'CreateProteinCutTable'],
        ['version' => '20251205000006', 'name' => 'CreateProteinFlavourTable'],
        ['version' => '20251205000007', 'name' => 'CreateRefreshTokenTable'],
    ];
    
    printInfo("Marking migrations as complete...\n");
    
    $stmt = $pdo->prepare("
        INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
        VALUES (?, ?, NOW(), NOW(), 0)
        ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name)
    ");
    
    $marked = 0;
    foreach ($migrations as $migration) {
        $stmt->execute([$migration['version'], $migration['name']]);
        printInfo("  ✓ Marked {$migration['name']} as complete");
        $marked++;
    }
    
    echo "\n";
    printInfo("Successfully marked $marked migrations as complete!");
    printInfo("Run './migrate.sh status' to verify");
    
} catch (PDOException $e) {
    printError("Database error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    printError("Error: " . $e->getMessage());
    exit(1);
}
