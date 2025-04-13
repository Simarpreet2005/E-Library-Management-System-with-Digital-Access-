<?php
session_start();
require_once 'config/database.php';

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get books count for each category
$category_counts = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
    $stmt->execute([$category['id']]);
    $category_counts[$category['id']] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <link href="assets/css/library-components.css" rel="stylesheet">
    <style>
        .category-card {
            transition: all 0.3s ease;
            border: 1px solid var(--accent-color);
            background-color: var(--secondary-color);
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
        }
        .category-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            transition: transform 0.3s ease;
        }
        .category-card:hover .category-icon {
            transform: scale(1.2);
        }
        .category-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
        }
        .category-count {
            color: var(--text-color);
            font-size: 0.9rem;
        }
        .category-description {
            color: var(--text-color);
            font-size: 0.95rem;
        }
        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: var(--light-accent);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .breadcrumb {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
        }
        .breadcrumb-item a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .breadcrumb-item a:hover {
            color: var(--light-accent);
        }
        .breadcrumb-item.active {
            color: var(--light-accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        

        <h1 class="text-center mb-5 page-title animate-fade-in">Book Categories</h1>

        <div class="row">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4 mb-4">
                    <div class="card category-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="card-body text-center">
                            <div class="category-icon mb-3">
                                <i class="fas fa-bookmark"></i>
                            </div>
                            <h3 class="category-title mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="category-count mb-2">
                                <i class="fas fa-book me-1"></i>
                                <?php echo $category_counts[$category['id']]; ?> books
                            </p>
                            <?php if (!empty($category['description'])): ?>
                                <p class="category-description">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php endif; ?>
                            <a href="books.php?category=<?php echo $category['id']; ?>" class="btn btn-primary mt-3">
                                <i class="fas fa-eye me-2"></i>View Books
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/library-components.js"></script>

    <!-- Sliding Panel Trigger -->
    <button class="book-details-trigger">View Details</button>

    <!-- Sliding Panel -->
    <div class="sliding-panel book-details-panel">
        <!-- Panel content -->
    </div>

    <!-- Book Cards Container -->
    <div class="books-container">
        <!-- Book cards will be loaded here -->
    </div>

    <!-- Reading Time Counter -->
    <div class="reading-time-container"></div>

    <!-- Gradient Background Section -->
    <div class="header-section gradient-bg">
        <!-- Header content -->
    </div>
</body>
</html> 