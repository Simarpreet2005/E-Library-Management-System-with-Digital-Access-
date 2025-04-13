<?php
session_start();
require_once 'config/database.php';
require_once 'includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: books.php');
    exit();
}

$book_id = $_GET['id'];

// Get book details
$stmt = $pdo->prepare("
    SELECT b.*, 
           AVG(r.rating) as average_rating,
           COUNT(r.id) as review_count,
           c.name as category_name
    FROM books b
    LEFT JOIN book_reviews r ON b.id = r.book_id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('Location: books.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $review = trim($_POST['review']);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO book_reviews (book_id, user_id, rating, review)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            review = VALUES(review)
        ");
        
        if ($stmt->execute([$book_id, $_SESSION['user_id'], $rating, $review])) {
            $_SESSION['success'] = "Review submitted successfully!";
            header('Location: book-reviews.php?id=' . $book_id);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting review: " . $e->getMessage();
    }
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username
    FROM book_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.book_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's review if exists
$user_review = null;
foreach ($reviews as $review) {
    if ($review['user_id'] == $_SESSION['user_id']) {
        $user_review = $review;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .review-card {
            transition: transform 0.2s;
        }
        .review-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <p class="card-text">
                            <strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?><br>
                            <strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?><br>
                            <strong>Average Rating:</strong>
                            <?php
                            $rating = round($book['average_rating'], 1);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            echo " ($rating)";
                            ?>
                            <br>
                            <strong>Total Reviews:</strong> <?php echo $book['review_count']; ?>
                        </p>
                        <a href="read-book.php?id=<?php echo $book_id; ?>" class="btn btn-primary">
                            Read Book
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
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

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Write a Review</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating-stars">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                               id="rating<?php echo $i; ?>" 
                                               <?php echo ($user_review && $user_review['rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="rating<?php echo $i; ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="review" class="form-label">Review</label>
                                <textarea class="form-control" id="review" name="review" rows="3" required><?php 
                                    echo $user_review ? htmlspecialchars($user_review['review']) : ''; 
                                ?></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                <?php echo $user_review ? 'Update Review' : 'Submit Review'; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <h4 class="mb-4">Reviews</h4>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet. Be the first to review this book!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="card review-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="card-subtitle mb-0"><?php echo htmlspecialchars($review['username']); ?></h6>
                                    <div class="rating-stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                <small class="text-muted">
                                    <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rating stars interaction
        document.querySelectorAll('.rating-stars input').forEach(input => {
            input.addEventListener('change', function() {
                const stars = this.parentElement.querySelectorAll('label');
                stars.forEach((star, index) => {
                    star.style.color = index < this.value ? '#ffc107' : '#ddd';
                });
            });
        });
    </script>
</body>
</html> 