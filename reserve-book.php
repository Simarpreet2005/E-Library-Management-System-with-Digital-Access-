<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to reserve a book']);
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
    // Start transaction
    $pdo->beginTransaction();

    // Check if book is available
    $book_sql = "SELECT available_quantity, total_quantity FROM books WHERE id = :book_id FOR UPDATE";
    $stmt = $pdo->prepare($book_sql);
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['available_quantity'] > 0) {
        throw new Exception('Book is available for borrowing. No need to reserve.');
    }

    // Check if user already has an active reservation
    $reservation_sql = "SELECT id FROM book_reservations 
                       WHERE user_id = :user_id AND book_id = :book_id 
                       AND status IN ('pending', 'ready')";
    $stmt = $pdo->prepare($reservation_sql);
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    $existing_reservation = $stmt->fetch();

    if ($existing_reservation) {
        throw new Exception('You already have an active reservation for this book');
    }

    // Calculate expiry date (7 days from now)
    $expiry_date = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Create reservation
    $insert_sql = "INSERT INTO book_reservations (user_id, book_id, expiry_date) 
                  VALUES (:user_id, :book_id, :expiry_date)";
    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute([
        'user_id' => $user_id,
        'book_id' => $book_id,
        'expiry_date' => $expiry_date
    ]);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Book reserved successfully!',
        'expiry_date' => $expiry_date
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 