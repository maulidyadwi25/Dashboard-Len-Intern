<?php
$host = 'localhost';
$port = '2306';
$user = 'root';
$password = '';
$database = 'dashboard_len_intern';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database: $database ===\n\n";
    
    $tables = ['master_projects', 'progress_revenue_values', 'progress_wbs_items', 'progress_wbs_weekly_values', 'budget_breakdowns', 'project_profiles'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "$table: " . $count['cnt'] . " rows\n";
    }
    
    echo "\n=== Sample master_projects ===\n";
    $stmt = $pdo->query("SELECT id, project_name FROM master_projects LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: " . $row['id'] . ", Name: " . $row['project_name'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
