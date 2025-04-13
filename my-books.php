<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get borrowed books (both current and returned)
    $borrowed_sql = "SELECT b.*, bb.borrow_date, bb.return_date, bb.status as borrow_status 
                    FROM books b 
                    JOIN book_borrows bb ON b.id = bb.book_id 
                    WHERE bb.user_id = :user_id 
                    ORDER BY 
                        CASE 
                            WHEN bb.status = 'borrowed' THEN 1
                            WHEN bb.status = 'overdue' THEN 2
                            ELSE 3
                        END,
                        bb.return_date DESC,
                        bb.borrow_date DESC";
    $stmt = $pdo->prepare($borrowed_sql);
    $stmt->execute(['user_id' => $user_id]);
    $borrowed_books = $stmt->fetchAll();

    // Get favorite books
    $favorites_sql = "SELECT b.* 
                     FROM books b 
                     JOIN book_favorites bf ON b.id = bf.book_id 
                     WHERE bf.user_id = :user_id 
                     ORDER BY bf.created_at DESC";
    $stmt = $pdo->prepare($favorites_sql);
    $stmt->execute(['user_id' => $user_id]);
    $favorite_books = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .book-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--accent-color);
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .book-cover {
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .book-card:hover .book-cover {
            transform: scale(1.05);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
        }
        .returned-book {
            opacity: 0.8;
            filter: grayscale(30%);
        }
        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }
        .card-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
        }
        .card-text {
            color: var(--text-color);
        }
        .text-muted {
            color: var(--accent-color) !important;
        }
        .badge {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .alert {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
        }
        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
            color: var(--text-color);
        }
        .btn-success:hover {
            background-color: #45a049;
            border-color: #45a049;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="display-4 mb-4 animate-fade-in">My Books</h1>

        <!-- Borrowed Books Section -->
        <div class="mb-5 animate-fade-in" style="animation-delay: 0.2s;">
            <h2 class="section-title">Borrowed Books</h2>
            <?php if (empty($borrowed_books)): ?>
                <div class="alert animate-slide-in">
                    <i class="fas fa-info-circle me-2"></i>You haven't borrowed any books yet.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($borrowed_books as $index => $book): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card book-card animate-fade-in <?php echo $book['borrow_status'] === 'returned' ? 'returned-book' : ''; ?>" 
                                 style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                <span class="status-badge">
                                    <i class="fas <?php 
                                        echo $book['borrow_status'] === 'borrowed' ? 'fa-book' : 
                                            ($book['borrow_status'] === 'overdue' ? 'fa-exclamation-circle' : 'fa-check-circle'); 
                                    ?> me-2"></i>
                                    <?php echo ucfirst($book['borrow_status']); ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover" alt="Book Cover">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="card-text">
                                        <small>
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            Borrowed: <?php echo date('M d, Y', strtotime($book['borrow_date'])); ?>
                                        </small>
                                    </p>
                                    <?php if ($book['return_date']): ?>
                                        <p class="card-text">
                                            <small>
                                                <i class="fas fa-calendar-check me-2"></i>
                                                Returned: <?php echo date('M d, Y', strtotime($book['return_date'])); ?>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <?php if ($book['borrow_status'] === 'borrowed'): ?>
                                        <a href="read-book.php?id=<?php echo $book['id']; ?>" class="btn btn-success w-100 mb-2">
                                            <i class="fas fa-book-reader me-2"></i>Read Book
                                        </a>
                                    <?php endif; ?>
                                    <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Books Section -->
        <div class="mb-5 animate-fade-in" style="animation-delay: 0.3s;">
            <h2 class="section-title">Favorite Books</h2>
            <?php if (empty($favorite_books)): ?>
                <div class="alert animate-slide-in">
                    <i class="fas fa-heart me-2"></i>You haven't added any books to your favorites yet.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorite_books as $index => $book): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card book-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover" alt="Book Cover">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <?php if($book['category']): ?>
                                        <span class="badge"><?php echo htmlspecialchars($book['category']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 