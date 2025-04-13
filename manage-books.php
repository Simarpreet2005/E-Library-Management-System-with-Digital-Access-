<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle book deletion
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    try {
        // Delete book from database
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $success = "Book deleted successfully";
    } catch (PDOException $e) {
        $error = "Error deleting book: " . $e->getMessage();
    }
}

// Fetch all books
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        ORDER BY b.created_at DESC
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching books: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - E-Library Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .book-card {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .book-cover {
            height: 200px;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }
        .book-info {
            padding: 1.5rem;
        }
        .book-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            margin-bottom: 0.5rem;
        }
        .book-author {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 1rem;
        }
        .book-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: var(--dark-accent);
            color: var(--light-accent);
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .action-buttons .btn {
            flex: 1;
            padding: 0.5rem;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }
        .btn-edit {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: var(--text-color);
        }
        .add-book-btn {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .add-book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light" style="font-family: 'Cormorant Garamond', serif;">Manage Books</h1>
            <a href="add-book.php" class="btn add-book-btn">
                <i class="fas fa-plus me-2"></i>Add New Book
            </a>
        </div>

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

        <div class="row">
            <?php foreach ($books as $book): ?>
                <div class="col-md-4 animate-fade-in">
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        <div class="book-info">
                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                            <span class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></span>
                            <div class="action-buttons">
                                <a href="edit-book.php?id=<?php echo $book['id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="delete_book" class="btn btn-delete">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 