<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get borrowed books
$stmt = $pdo->prepare("
    SELECT b.*, bb.borrow_date 
    FROM books b 
    JOIN book_borrows bb ON b.id = bb.book_id 
    WHERE bb.user_id = ? AND bb.status = 'borrowed'
    ORDER BY bb.borrow_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$borrowed_books = $stmt->fetchAll();

// Get favorite books
$stmt = $pdo->prepare("
    SELECT b.* 
    FROM books b 
    JOIN book_favorites bf ON b.id = bf.book_id 
    WHERE bf.user_id = ?
    ORDER BY bf.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$favorite_books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .profile-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .card-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .profile-info p {
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        .profile-info strong {
            color: var(--light-accent);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
        }
        .table {
            color: var(--text-color);
        }
        .table thead th {
            color: var(--light-accent);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            border-bottom: 2px solid var(--accent-color);
        }
        .table tbody tr {
            border-bottom: 1px solid var(--accent-color);
        }
        .table tbody tr:hover {
            background-color: var(--dark-accent);
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 4px;
        }
        .btn-info {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .btn-info:hover {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
            transform: translateY(-2px);
        }
        .favorite-book-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
        }
        .favorite-book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .favorite-book-card img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .favorite-book-card:hover img {
            transform: scale(1.05);
        }
        .empty-state {
            color: var(--text-color);
            text-align: center;
            padding: 2rem;
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
        }
        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card profile-card animate-fade-in">
                    <div class="card-body">
                        <h3 class="card-title">Profile Information</h3>
                        <div class="profile-info">
                            <p><strong><i class="fas fa-user me-2"></i>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong><i class="fas fa-user-tag me-2"></i>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                            <p><strong><i class="fas fa-calendar-alt me-2"></i>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <!-- Borrowed Books -->
                <div class="card profile-card mb-4 animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="card-body">
                        <h3 class="card-title">Borrowed Books</h3>
                        <?php if($borrowed_books): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Borrowed Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($borrowed_books as $index => $book): ?>
                                            <tr class="animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo date('F j, Y', strtotime($book['borrow_date'])); ?></td>
                                                <td>
                                                    <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                    <a href="<?php echo htmlspecialchars($book['file_path']); ?>" class="btn btn-sm btn-info" target="_blank">Read</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state animate-slide-in">
                                <i class="fas fa-book"></i>
                                <p>You haven't borrowed any books yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Favorite Books -->
                <div class="card profile-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="card-body">
                        <h3 class="card-title">Favorite Books</h3>
                        <?php if($favorite_books): ?>
                            <div class="row">
                                <?php foreach($favorite_books as $index => $book): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card favorite-book-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top" alt="Book Cover">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($book['author']); ?></p>
                                                <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state animate-slide-in">
                                <i class="fas fa-heart"></i>
                                <p>You haven't added any books to your favorites yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 