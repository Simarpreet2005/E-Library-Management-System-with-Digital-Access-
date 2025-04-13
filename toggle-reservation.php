<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if book_id is provided
if (!isset($_POST['book_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'];

try {
    // Check if book is already reserved by this user
    $check_sql = "SELECT id, status FROM book_reservations WHERE user_id = :user_id AND book_id = :book_id";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // If reservation exists and is pending, cancel it
        if ($existing['status'] === 'pending') {
            $update_sql = "UPDATE book_reservations SET status = 'cancelled' WHERE id = :id";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute(['id' => $existing['id']]);
            $is_reserved = false;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'This reservation cannot be cancelled']);
            exit();
        }
    } else {
        // Check if book is available for reservation
        $check_availability_sql = "SELECT COUNT(*) FROM book_reservations WHERE book_id = :book_id AND status IN ('pending', 'approved')";
        $stmt = $pdo->prepare($check_availability_sql);
        $stmt->execute(['book_id' => $book_id]);
        $reservation_count = $stmt->fetchColumn();

        if ($reservation_count > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'This book is already reserved']);
            exit();
        }

        // Add new reservation
        $insert_sql = "INSERT INTO book_reservations (user_id, book_id) VALUES (:user_id, :book_id)";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        $is_reserved = true;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'is_reserved' => $is_reserved]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 