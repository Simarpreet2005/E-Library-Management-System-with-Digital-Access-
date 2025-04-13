<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$is_admin_dir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base_path = $is_admin_dir ? '' : 'admin/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/library-shelves.jpg');
            background-size: cover;
            background-position: center;
            min-height: 80vh;
            display: flex;
            align-items: center;
            color: var(--text-color);
            position: relative;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .hero-content h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: var(--light-accent);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 1.5rem;
        }
        .hero-content p {
            font-size: 1.25rem;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            margin: 0 auto 2rem auto;
            max-width: 600px;
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
        .btn-outline-light {
            border-color: var(--light-accent);
            color: var(--light-accent);
        }
        .btn-outline-light:hover {
            background-color: var(--light-accent);
            color: var(--dark-accent);
            transform: translateY(-2px);
        }
        .book-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .book-card img {
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .book-card:hover img {
            transform: scale(1.05);
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
        .feature-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .feature-card i {
            color: var(--accent-color);
            transition: transform 0.3s ease;
        }
        .feature-card:hover i {
            transform: scale(1.2);
        }
        .feature-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
        }
        .bg-light {
            background-color: var(--secondary-color) !important;
        }
        .stats-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .stats-card h5 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
        }
        .stats-card .display-4 {
            color: var(--accent-color);
        }
        .stats-card a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .stats-card a:hover {
            color: var(--light-accent);
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
        .welcome-box {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/library-shelves.jpg');
            background-size: cover;
            background-position: center;
            padding: 3rem;
            border-radius: 15px;
            margin: 2rem 0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .welcome-box:hover {
            transform: translateY(-5px);
        }
        .welcome-box h1 {
            color: var(--light-accent);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 3rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .welcome-box p {
            color: #fff;
            font-size: 1.2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            margin-bottom: 2rem;
            max-width: 800px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }
        .welcome-box .btn {
            font-size: 1.1rem;
            padding: 0.75rem 2rem;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php if($is_admin): ?>
        <?php include 'includes/admin-navbar.php'; ?>
                    <?php else: ?>
        <?php include 'includes/navbar.php'; ?>
                    <?php endif; ?>

    <?php if($is_admin): ?>
        <!-- Admin Dashboard -->
        <div class="container py-5">
            <h1 class="text-center mb-5 animate-fade-in" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Admin Dashboard</h1>
            
            <div class="row">
                <!-- Quick Stats -->
                <div class="col-md-3 mb-4">
                    <div class="card stats-card animate-fade-in">
                        <div class="card-body">
                            <h5 class="card-title">Total Books</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM books");
                            $total_books = $stmt->fetchColumn();
                            ?>
                            <h2 class="display-4"><?php echo $total_books; ?></h2>
                            <a href="admin/manage-books.php">Manage Books <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stats-card animate-fade-in" style="animation-delay: 0.1s;">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                            $total_users = $stmt->fetchColumn();
                            ?>
                            <h2 class="display-4"><?php echo $total_users; ?></h2>
                            <a href="admin/manage-users.php">Manage Users <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stats-card animate-fade-in" style="animation-delay: 0.2s;">
                        <div class="card-body">
                            <h5 class="card-title">Active Borrows</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM book_borrows WHERE return_date IS NULL");
                            $active_borrows = $stmt->fetchColumn();
                            ?>
                            <h2 class="display-4"><?php echo $active_borrows; ?></h2>
                            <a href="admin/manage-borrows.php">View Borrows <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card stats-card animate-fade-in" style="animation-delay: 0.3s;">
                        <div class="card-body">
                            <h5 class="card-title">Pending Reservations</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM book_reservations WHERE status = 'pending'");
                            $pending_reservations = $stmt->fetchColumn();
                            ?>
                            <h2 class="display-4"><?php echo $pending_reservations; ?></h2>
                            <a href="admin/manage-reservations.php">View Reservations <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mt-5">
                <div class="col-md-6">
                    <div class="card animate-fade-in" style="animation-delay: 0.4s;">
                        <div class="card-header" style="background-color: var(--secondary-color); border-bottom: 2px solid var(--accent-color);">
                            <h5 class="card-title mb-0" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Recent Book Additions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Added Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 5");
                                        while($book = $stmt->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <tr class="animate-fade-in">
                                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card animate-fade-in" style="animation-delay: 0.5s;">
                        <div class="card-header" style="background-color: var(--secondary-color); border-bottom: 2px solid var(--accent-color);">
                            <h5 class="card-title mb-0" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Recent User Registrations</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Joined Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                                        while($user = $stmt->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <tr class="animate-fade-in">
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Regular User View -->
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
                <div class="hero-content text-center animate-fade-in">
                <h1 class="display-4 fw-bold mb-4">Welcome to E-Library</h1>
                <p class="lead mb-4">Access thousands of books at your fingertips. Read, learn, and explore the world of knowledge.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="books.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-book me-2"></i>Browse Books
                    </a>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Books -->
    <section class="py-5">
        <div class="container">
                <h2 class="text-center mb-4 animate-fade-in" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Featured Books</h2>
            <div class="row">
                <?php
                $stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 4");
                while($book = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="col-md-3 mb-4">
                        <div class="card book-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top" alt="Book Cover">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($book['author']); ?></p>
                            <?php if($book['category']): ?>
                                    <span class="badge"><?php echo htmlspecialchars($book['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
                <h2 class="text-center mb-5 animate-fade-in" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Why Choose E-Library?</h2>
            <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card animate-fade-in" style="animation-delay: 0.1s;">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x mb-3"></i>
                            <h3 class="card-title">Digital Books</h3>
                            <p class="card-text">Access a vast collection of digital books anytime, anywhere.</p>
                        </div>
                    </div>
                </div>
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card animate-fade-in" style="animation-delay: 0.2s;">
                            <div class="card-body text-center">
                                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                            <h3 class="card-title">Mobile Friendly</h3>
                            <p class="card-text">Read books on any device with our responsive design.</p>
                        </div>
                    </div>
                </div>
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card animate-fade-in" style="animation-delay: 0.3s;">
                            <div class="card-body text-center">
                                <i class="fas fa-heart fa-3x mb-3"></i>
                            <h3 class="card-title">Personal Library</h3>
                            <p class="card-text">Save your favorite books and track your reading history.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 