<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add to favorites']);
    exit();
}

// Check if book_id is provided
if (!isset($_POST['book_id'])) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'];

try {
    // Check if book is already in favorites
    $check_sql = "SELECT id FROM book_favorites WHERE user_id = :user_id AND book_id = :book_id";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    $favorite = $stmt->fetch();

    if ($favorite) {
        // Remove from favorites
        $delete_sql = "DELETE FROM book_favorites WHERE user_id = :user_id AND book_id = :book_id";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Book removed from favorites']);
    } else {
        // Add to favorites
        $insert_sql = "INSERT INTO book_favorites (user_id, book_id) VALUES (:user_id, :book_id)";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Book added to favorites']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 