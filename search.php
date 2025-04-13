<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$query = '%' . $_GET['q'] . '%';

try {
    $stmt = $pdo->prepare("
        SELECT id, title, author, cover_image 
        FROM books 
        WHERE title LIKE ? 
        OR author LIKE ? 
        OR category LIKE ?
        LIMIT 5
    ");
    
    $stmt->execute([$query, $query, $query]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add placeholder images if cover_image is empty
    foreach ($results as &$book) {
        if (empty($book['cover_image'])) {
            $book['cover_image'] = 'https://picsum.photos/300/400?random=' . $book['id'];
        }
    }
    
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 