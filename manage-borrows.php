<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle return book
if (isset($_POST['return_book'])) {
    $borrow_id = $_POST['borrow_id'];
    try {
        // Update borrow status to returned
        $stmt = $pdo->prepare("UPDATE book_borrows SET status = 'returned', return_date = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$borrow_id]);
        
        // Update book availability
        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1, status = 'available' WHERE id = (SELECT book_id FROM book_borrows WHERE id = ?)");
        $stmt->execute([$borrow_id]);
        
        $success = "Book returned successfully";
    } catch (PDOException $e) {
        $error = "Error returning book: " . $e->getMessage();
    }
}

// Fetch all borrows with user and book details
try {
    $stmt = $pdo->query("
        SELECT 
            b.id,
            b.book_id,
            b.user_id,
            b.borrow_date,
            b.due_date,
            b.return_date,
            b.status,
            u.username,
            bk.title,
            bk.cover_image
        FROM book_borrows b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        ORDER BY b.borrow_date DESC
    ");
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching borrows: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrows - E-Library Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .borrow-card {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        .borrow-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .book-cover {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid var(--accent-color);
        }
        .borrow-details {
            font-family: 'Cormorant Garamond', serif;
        }
        .borrow-title {
            color: var(--light-accent);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .borrow-info {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        .borrow-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .status-borrowed {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .status-returned {
            background-color: var(--dark-accent);
            color: var(--light-accent);
        }
        .status-overdue {
            background-color: #dc3545;
            color: var(--text-color);
        }
        .btn-return {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-return:hover {
            transform: translateY(-2px);
            background-color: var(--light-accent);
            border-color: var(--light-accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="text-light mb-4" style="font-family: 'Cormorant Garamond', serif;">Manage Borrows</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($borrows)): ?>
            <div class="alert alert-info animate-fade-in">
                <i class="fas fa-info-circle me-2"></i>
                No borrows found.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($borrows as $borrow): ?>
                    <div class="col-md-6 animate-fade-in">
                        <div class="borrow-card">
                            <div class="row">
                                <div class="col-md-3">
                                    <img src="assets/images/books/<?php echo htmlspecialchars($borrow['cover_image']); ?>" 
                                         alt="Book Cover" class="book-cover">
                                </div>
                                <div class="col-md-9">
                                    <div class="borrow-details">
                                        <h3 class="borrow-title"><?php echo htmlspecialchars($borrow['title']); ?></h3>
                                        <p class="borrow-info">
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($borrow['username']); ?>
                                        </p>
                                        <p class="borrow-info">
                                            <i class="fas fa-calendar me-2"></i>
                                            Borrowed: <?php echo date('M d, Y', strtotime($borrow['borrow_date'])); ?>
                                        </p>
                                        <p class="borrow-info">
                                            <i class="fas fa-clock me-2"></i>
                                            Due: <?php echo date('M d, Y', strtotime($borrow['due_date'])); ?>
                                        </p>
                                        <?php if ($borrow['return_date']): ?>
                                            <p class="borrow-info">
                                                <i class="fas fa-calendar-check me-2"></i>
                                                Returned: <?php echo date('M d, Y', strtotime($borrow['return_date'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <span class="borrow-status status-<?php echo $borrow['status']; ?>">
                                            <?php echo ucfirst(htmlspecialchars($borrow['status'])); ?>
                                        </span>
                                        
                                        <?php if ($borrow['status'] === 'borrowed' || $borrow['status'] === 'overdue'): ?>
                                            <form method="POST" class="mt-3">
                                                <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                                <button type="submit" name="return_book" class="btn btn-return w-100">
                                                    <i class="fas fa-book-return me-2"></i>Mark as Returned
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 