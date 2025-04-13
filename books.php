<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get user's favorite books if logged in
$favorite_books = [];
$reserved_books = [];
if ($user_id) {
    // Get favorites
    $favorites_sql = "SELECT book_id FROM book_favorites WHERE user_id = :user_id";
    $stmt = $pdo->prepare($favorites_sql);
    $stmt->execute(['user_id' => $user_id]);
    $favorite_books = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get reservations
    $reservations_sql = "SELECT book_id FROM book_reservations WHERE user_id = :user_id AND status IN ('pending', 'approved')";
    $stmt = $pdo->prepare($reservations_sql);
    $stmt->execute(['user_id' => $user_id]);
    $reserved_books = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get all active reservations for availability check
    $active_reservations_sql = "SELECT book_id FROM book_reservations WHERE status IN ('pending', 'approved')";
    $stmt = $pdo->prepare($active_reservations_sql);
    $stmt->execute();
    $all_reserved_books = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE :search OR author LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "category = :category";
    $params[':category'] = $category;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total books count
    $count_sql = "SELECT COUNT(*) FROM books $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_books = $stmt->fetchColumn();
    $total_pages = ceil($total_books / $per_page);

    // Get books for current page
    $offset = ($page - 1) * $per_page;
    $sql = "SELECT * FROM books $where_clause ORDER BY title LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Add pagination parameters
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    // Add search and category parameters if they exist
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $books = $stmt->fetchAll();

    // Get unique categories
    $categories = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - E-Library</title>
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
        .favorite-btn, .reserve-btn {
            position: absolute;
            top: 10px;
            background: rgba(26, 18, 11, 0.8);
            border: 1px solid var(--accent-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }
        .favorite-btn {
            right: 10px;
        }
        .reserve-btn {
            right: 60px;
        }
        .favorite-btn:hover, .reserve-btn:hover {
            background: var(--accent-color);
            transform: scale(1.1);
        }
        .favorite-btn i, .reserve-btn i {
            font-size: 1.2rem;
            color: var(--text-color);
        }
        .favorite-btn.active i {
            color: #dc3545;
        }
        .reserve-btn.active i {
            color: #198754;
        }
        .reserve-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .card-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
        }
        .card-text {
            color: var(--text-color);
        }
        .badge {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .search-form {
            background-color: var(--secondary-color);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--accent-color);
        }
        .search-form input, .search-form select {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
        }
        .search-form input:focus, .search-form select:focus {
            background-color: var(--dark-accent);
            border-color: var(--light-accent);
            color: var(--text-color);
        }
        .pagination .page-link {
            background-color: var(--secondary-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .pagination .page-item.active .page-link {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .pagination .page-link:hover {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
            color: var(--dark-accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4 animate-fade-in">
            <div class="col-md-6">
                <h1 class="display-4">Browse Books</h1>
            </div>
            <div class="col-md-6">
                <form class="search-form animate-fade-in" style="animation-delay: 0.2s;" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php foreach($books as $index => $book): ?>
            <div class="col-md-3 mb-4">
                <div class="card book-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <?php if ($user_id): ?>
                    <button class="favorite-btn <?php echo in_array($book['id'], $favorite_books) ? 'active' : ''; ?>" 
                            data-book-id="<?php echo $book['id']; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button class="reserve-btn <?php echo in_array($book['id'], $reserved_books) ? 'active' : ''; ?> <?php echo in_array($book['id'], $all_reserved_books) ? 'disabled' : ''; ?>" 
                            data-book-id="<?php echo $book['id']; ?>"
                            <?php echo in_array($book['id'], $all_reserved_books) ? 'disabled' : ''; ?>>
                        <i class="fas fa-bookmark"></i>
                    </button>
                    <?php endif; ?>
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

        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4 animate-fade-in" style="animation-delay: 0.4s;">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle favorite buttons
            const favoriteButtons = document.querySelectorAll('.favorite-btn');
            favoriteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const bookId = this.dataset.bookId;
                    
                    fetch('toggle-favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `book_id=${bookId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.toggle('active');
                        } else {
                            if (data.message === 'Please login first') {
                                window.location.href = 'login.php';
                            } else {
                                alert(data.message);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating favorites');
                    });
                });
            });

            // Handle reservation buttons
            const reserveButtons = document.querySelectorAll('.reserve-btn');
            reserveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.disabled) return;
                    
                    const bookId = this.dataset.bookId;
                    
                    fetch('toggle-reservation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `book_id=${bookId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.toggle('active');
                            if (data.is_reserved) {
                                this.classList.add('disabled');
                                this.disabled = true;
                            } else {
                                this.classList.remove('disabled');
                                this.disabled = false;
                            }
                        } else {
                            if (data.message === 'Please login first') {
                                window.location.href = 'login.php';
                            } else {
                                alert(data.message);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating reservation');
                    });
                });
            });
        });
    </script>
</body>
</html> 