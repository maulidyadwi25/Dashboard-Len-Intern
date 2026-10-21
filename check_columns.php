<?php
$host = 'localhost';
$port = '2306';
$user = 'root';
$password = '';
$database = 'dashboard_len_intern';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== master_projects columns ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM master_projects");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n=== Sample data ===\n";
    $stmt = $pdo->query("SELECT * FROM master_projects LIMIT 3");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
