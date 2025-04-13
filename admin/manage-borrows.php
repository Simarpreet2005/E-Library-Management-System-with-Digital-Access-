<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle return book
if (isset($_POST['return_book'])) {
    $borrow_id = $_POST['borrow_id'];
    $book_id = $_POST['book_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update borrow record
        $stmt = $pdo->prepare("UPDATE book_borrows SET return_date = NOW() WHERE id = ?");
        $stmt->execute([$borrow_id]);
        
        // Update book quantity
        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        
        $pdo->commit();
        header('Location: manage-borrows.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error processing return: " . $e->getMessage();
    }
}

// Get all active borrows with book and user details
$stmt = $pdo->query("
    SELECT b.*, bk.title as book_title, bk.cover_image, u.username, u.email
    FROM book_borrows b
    JOIN books bk ON b.book_id = bk.id
    JOIN users u ON b.user_id = u.id
    WHERE b.return_date IS NULL
    ORDER BY b.borrow_date DESC
");
$borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrows - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Manage Borrows</h1>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>User</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($borrows as $borrow): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($borrow['cover_image']); ?>" 
                                                 alt="Book Cover" 
                                                 style="width: 40px; height: 60px; object-fit: cover; margin-right: 10px;">
                                            <?php echo htmlspecialchars($borrow['book_title']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($borrow['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($borrow['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($borrow['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($borrow['due_date'])); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                            <input type="hidden" name="book_id" value="<?php echo $borrow['book_id']; ?>">
                                            <button type="submit" 
                                                    name="return_book" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Mark this book as returned?')">
                                                <i class="fas fa-check"></i> Mark Returned
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