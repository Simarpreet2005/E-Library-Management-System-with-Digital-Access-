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

    // Check if user has this book borrowed
    $check_borrow_sql = "SELECT id FROM book_borrows 
                        WHERE user_id = :user_id 
                        AND book_id = :book_id 
                        AND status = 'borrowed'";
    $stmt = $pdo->prepare($check_borrow_sql);
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    $borrow = $stmt->fetch();

    if (!$borrow) {
        throw new Exception("You haven't borrowed this book");
    }

    // Update borrow record
    $return_sql = "UPDATE book_borrows 
                  SET return_date = CURRENT_TIMESTAMP,
                      status = 'returned'
                  WHERE id = :borrow_id";
    $stmt = $pdo->prepare($return_sql);
    $stmt->execute(['borrow_id' => $borrow['id']]);

    // Update book's available quantity
    $update_quantity_sql = "UPDATE books 
                          SET available_quantity = available_quantity + 1,
                              status = CASE 
                                  WHEN available_quantity + 1 = total_quantity THEN 'available'
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
        'message' => 'Book returned successfully'
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