<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (strlen($username) < 3) {
        echo json_encode(['available' => false]);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $available = $stmt->rowCount() === 0;
        
        echo json_encode(['available' => $available]);
    } catch (PDOException $e) {
        echo json_encode(['available' => false]);
    }
} else {
    echo json_encode(['available' => false]);
} 