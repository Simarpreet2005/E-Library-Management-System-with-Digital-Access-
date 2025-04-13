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
    // Start transaction
    $pdo->beginTransaction();

    // Check if book is available
    $check_book_sql = "SELECT available_quantity, total_quantity FROM books WHERE id = :book_id FOR UPDATE";
    $stmt = $pdo->prepare($check_book_sql);
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        throw new Exception("Book not found");
    }

    if ($book['available_quantity'] <= 0) {
        throw new Exception("This book is not available for borrowing");
    }

    // Check if user already has this book borrowed
    $check_borrow_sql = "SELECT id FROM book_borrows WHERE user_id = :user_id AND book_id = :book_id AND status = 'borrowed'";
    $stmt = $pdo->prepare($check_borrow_sql);
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    if ($stmt->fetch()) {
        throw new Exception("You have already borrowed this book");
    }

    // Calculate due date (14 days from now)
    $due_date = date('Y-m-d H:i:s', strtotime('+14 days'));

    // Insert borrow record
    $borrow_sql = "INSERT INTO book_borrows (book_id, user_id, due_date) VALUES (:book_id, :user_id, :due_date)";
    $stmt = $pdo->prepare($borrow_sql);
    $stmt->execute([
        'book_id' => $book_id,
        'user_id' => $user_id,
        'due_date' => $due_date
    ]);

    // Update book's available quantity
    $update_quantity_sql = "UPDATE books SET available_quantity = available_quantity - 1, 
                          status = CASE 
                              WHEN available_quantity - 1 = 0 THEN 'borrowed'
                              ELSE status 
                          END 
                          WHERE id = :book_id";
    $stmt = $pdo->prepare($update_quantity_sql);
    $stmt->execute(['book_id' => $book_id]);

    // Commit transaction
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Book borrowed successfully',
        'due_date' => $due_date
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 