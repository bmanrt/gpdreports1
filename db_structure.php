<?php
require_once 'config/config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get reports table structure
    $query = "SHOW CREATE TABLE reports";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Reports Table Structure:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Get columns info
    $query = "SHOW COLUMNS FROM reports";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columns Details:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
