<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle book deletion
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    try {
        // Start a transaction
        $pdo->beginTransaction();

        // First, delete related borrow records
        $stmt = $pdo->prepare("DELETE FROM book_borrows WHERE book_id = ?");
        $stmt->execute([$book_id]);

        // Then, delete related reservation records
        $stmt = $pdo->prepare("DELETE FROM book_reservations WHERE book_id = ?");
        $stmt->execute([$book_id]);

        // Get the book file and cover image paths
        $stmt = $pdo->prepare("SELECT file_path, cover_image FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            // Delete the files if they exist
            if (file_exists($book['file_path'])) {
                unlink($book['file_path']);
            }
            if (file_exists($book['cover_image'])) {
                unlink($book['cover_image']);
            }
        }
        
        // Finally, delete the book record
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        
        // Commit the transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Book and all related records deleted successfully!";
    } catch (PDOException $e) {
        // Rollback the transaction if something goes wrong
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting book: " . $e->getMessage();
    }
    header('Location: manage-books.php');
    exit();
}

// Get all books
$stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Books</h1>
            <a href="add-book.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Book
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cover</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>ISBN</th>
                                <th>Total Quantity</th>
                                <th>Available</th>
                                <th>Added Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($books as $book): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                             alt="Book Cover" 
                                             style="width: 50px; height: 70px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['category']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><?php echo htmlspecialchars($book['total_quantity']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($book['available_quantity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                    <td>
                                        <a href="edit-book.php?id=<?php echo $book['id']; ?>" 
                                           class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" 
                                                    name="delete_book" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 