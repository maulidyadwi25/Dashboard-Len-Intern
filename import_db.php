<?php
$host = 'localhost';
$port = '3306';
$user = 'root';
$password = '';
$database = 'dashboard_len_intern';
$sqlFile = __DIR__ . '/dashboard_len_intern.sql';

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS $database");
    echo "Dropped existing database (if any)\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Created database: $database\n";
    
    // Select database
    $pdo->exec("USE $database");
    
    // Read SQL file
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)), fn($s) => !empty($s));
    
    $count = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $count++;
        } catch (PDOException $e) {
            echo "Error executing: " . substr($statement, 0, 100) . "...\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Successfully imported $count SQL statements\n";
    
    // Verify tables
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables created: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
