<?php
session_start();
require_once 'config/database.php';

// Check if book_id is provided
if (!isset($_GET['id'])) {
    header('Location: books.php');
    exit();
}

$book_id = $_GET['id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // Get book details
    $book_sql = "SELECT b.*, c.name as category_name, 
                (SELECT COUNT(*) FROM book_borrows WHERE book_id = b.id AND status = 'borrowed') as borrowed_count
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.id = :book_id";
    $stmt = $pdo->prepare($book_sql);
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        header('Location: books.php');
        exit();
    }

    // Check if user has borrowed the book
    $borrowed = false;
    $has_reservation = false;
    $reservation_expiry = null;
    if ($user_id) {
        $borrow_sql = "SELECT id, status, due_date FROM book_borrows 
                      WHERE user_id = :user_id AND book_id = :book_id 
                      ORDER BY borrow_date DESC LIMIT 1";
        $stmt = $pdo->prepare($borrow_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        $borrow = $stmt->fetch();
        $borrowed = ($borrow && $borrow['status'] == 'borrowed');

        // Check if book is in favorites
        $favorite_sql = "SELECT id FROM book_favorites WHERE user_id = :user_id AND book_id = :book_id";
        $stmt = $pdo->prepare($favorite_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        $is_favorite = $stmt->fetch() ? true : false;

        // Check if user has a reservation
        $reservation_sql = "SELECT id, status, expiry_date FROM book_reservations 
                          WHERE user_id = :user_id AND book_id = :book_id 
                          AND status IN ('pending', 'ready')";
        $stmt = $pdo->prepare($reservation_sql);
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        $reservation = $stmt->fetch();
        $has_reservation = ($reservation !== false);
        $reservation_expiry = $reservation ? $reservation['expiry_date'] : null;
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .book-details-section {
            background-color: var(--secondary-color);
            padding: 3rem 0;
            min-height: calc(100vh - 76px);
        }
        .book-cover {
            max-width: 400px;
            width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease;
            object-fit: cover;
        }
        .book-cover:hover {
            transform: translateY(-5px);
        }
        .cover-container {
            display: flex;
            justify-content: center;
            align-items: start;
            padding: 1rem;
        }
        .book-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: var(--light-accent);
            margin-bottom: 0.5rem;
        }
        .book-author {
            color: var(--text-color);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .book-info {
            background-color: var(--dark-accent);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid var(--accent-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .book-description {
            color: var(--text-color);
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 1.5rem 0;
        }
        .badge {
            background-color: var(--accent-color);
            color: var(--text-color);
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 5px;
        }
        .action-buttons {
            margin-top: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .btn-primary:hover {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
            transform: translateY(-2px);
        }
        .btn-danger {
            background-color: #8B0000;
            border-color: #8B0000;
        }
        .btn-danger:hover {
            background-color: #A52A2A;
            border-color: #A52A2A;
            transform: translateY(-2px);
        }
        .btn-warning {
            background-color: #B8860B;
            border-color: #B8860B;
            color: white;
        }
        .btn-warning:hover {
            background-color: #DAA520;
            border-color: #DAA520;
            color: white;
            transform: translateY(-2px);
        }
        .btn-outline-danger {
            border-color: #8B0000;
            color: #8B0000;
        }
        .btn-outline-danger:hover {
            background-color: #8B0000;
            color: white;
            transform: translateY(-2px);
        }
        .btn-outline-danger.active {
            background-color: #8B0000;
            color: white;
        }
        .info-label {
            color: var(--light-accent);
            font-weight: 600;
            font-family: 'Cormorant Garamond', serif;
        }
        .due-date, .reservation-info {
            color: #DC143C;
            font-weight: 600;
            margin-top: 1rem;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section class="book-details-section">
        <div class="container">
            <div class="row">
                <div class="col-md-5">
                    <div class="cover-container">
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="img-fluid book-cover mb-4">
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="book-info">
                        <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        
                        <div class="mb-4">
                            <span class="badge">
                                <i class="fas fa-books me-2"></i>
                                Available: <?php echo $book['available_quantity']; ?>/<?php echo $book['total_quantity']; ?>
                            </span>
                        </div>

                        <?php if (!empty($book['category_name'])): ?>
                            <p><span class="info-label">Category:</span> <?php echo htmlspecialchars($book['category_name']); ?></p>
                        <?php endif; ?>
                        
                        <p><span class="info-label">ISBN:</span> <?php echo htmlspecialchars($book['isbn']); ?></p>
                        
                        <p class="info-label">Description:</p>
                        <p class="book-description"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>

                        <div class="action-buttons">
                            <?php if ($user_id): ?>
                                <?php if ($borrowed): ?>
                                    <button class="btn btn-danger return-btn" 
                                            data-book-id="<?php echo $book_id; ?>"
                                            <?php echo $book['available_quantity'] >= $book['total_quantity'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-undo-alt me-2"></i> Return Book
                                    </button>
                                    <p class="due-date">
                                        Due Date: <?php echo date('M d, Y', strtotime($borrow['due_date'])); ?>
                                    </p>
                                <?php else: ?>
                                    <?php if ($book['available_quantity'] > 0): ?>
                                        <button class="btn btn-primary borrow-btn" 
                                                data-book-id="<?php echo $book_id; ?>">
                                            <i class="fas fa-book-reader me-2"></i> Borrow Book
                                        </button>
                                    <?php else: ?>
                                        <?php if (!$has_reservation): ?>
                                            <button class="btn btn-warning reserve-btn" 
                                                    data-book-id="<?php echo $book_id; ?>">
                                                <i class="fas fa-clock me-2"></i> Reserve Book
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-warning reserve-btn active" disabled>
                                                <i class="fas fa-clock me-2"></i> Reserved
                                            </button>
                                            <p class="reservation-info">
                                                Reservation expires: <?php echo date('M d, Y', strtotime($reservation_expiry)); ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn btn-outline-danger favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" 
                                        data-book-id="<?php echo $book_id; ?>">
                                    <i class="fas fa-heart<?php echo $is_favorite ? ' me-2' : ' me-2'; ?>"></i>
                                    <?php echo $is_favorite ? 'Remove from Favorites' : 'Add to Favorites'; ?>
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login to Borrow
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle borrow button click
            $('.borrow-btn').click(function() {
                const bookId = $(this).data('book-id');
                $.post('borrow-book.php', { book_id: bookId })
                    .done(function(response) {
                        if (response.success) {
                            alert('Book borrowed successfully! Due date: ' + response.due_date);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    })
                    .fail(function() {
                        alert('Error borrowing book. Please try again.');
                    });
            });

            // Handle return button click
            $('.return-btn').click(function() {
                const bookId = $(this).data('book-id');
                $.post('return-book.php', { book_id: bookId })
                    .done(function(response) {
                        if (response.success) {
                            alert('Book returned successfully!');
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    })
                    .fail(function() {
                        alert('Error returning book. Please try again.');
                    });
            });

            // Handle favorite button click
            $('.favorite-btn').click(function() {
                const bookId = $(this).data('book-id');
                const $btn = $(this);
                
                $.post('toggle-favorite.php', { book_id: bookId })
                    .done(function(response) {
                        if (response.success) {
                            if (response.action === 'added') {
                                $btn.addClass('active');
                                $btn.html('<i class="fas fa-heart-fill me-2"></i> Remove from Favorites');
                            } else {
                                $btn.removeClass('active');
                                $btn.html('<i class="fas fa-heart me-2"></i> Add to Favorites');
                            }
                        } else {
                            alert(response.message);
                        }
                    })
                    .fail(function() {
                        alert('Error updating favorites. Please try again.');
                    });
            });

            // Handle reserve button click
            $('.reserve-btn').click(function() {
                const bookId = $(this).data('book-id');
                const $btn = $(this);
                
                $.post('reserve-book.php', { book_id: bookId })
                    .done(function(response) {
                        if (response.success) {
                            $btn.addClass('active');
                            $btn.html('<i class="fas fa-clock me-2"></i> Reserved');
                            $btn.after('<p class="reservation-info">Reservation expires: ' + 
                                     new Date(response.expiry_date).toLocaleDateString() + '</p>');
                            $btn.prop('disabled', true);
                        } else {
                            alert(response.message);
                        }
                    })
                    .fail(function() {
                        alert('Error reserving book. Please try again.');
                    });
            });
        });
    </script>
</body>
</html> 