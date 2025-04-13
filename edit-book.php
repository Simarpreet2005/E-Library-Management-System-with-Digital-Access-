<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$book = null;

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    header('Location: manage-books.php');
    exit();
}

// Fetch book details
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $error = "Book not found.";
    }
} catch (PDOException $e) {
    $error = "Error fetching book details: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $book) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $total_quantity = (int)$_POST['total_quantity'];
    $available_quantity = (int)$_POST['available_quantity'];

    // Validate input
    if (empty($title) || empty($author) || empty($isbn) || empty($description) || empty($category) || $total_quantity <= 0 || $available_quantity < 0) {
        $error = "All fields are required, total quantity must be greater than 0, and available quantity cannot be negative";
    } else if ($available_quantity > $total_quantity) {
        $error = "Available quantity cannot be greater than total quantity";
    } else {
        try {
            // Handle file upload
            $cover_image = $book['cover_image']; // Keep existing image by default
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'assets/images/books/';
                $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                    // Delete old image if exists
                    if ($book['cover_image'] && file_exists($upload_dir . $book['cover_image'])) {
                        unlink($upload_dir . $book['cover_image']);
                    }
                    $cover_image = $file_name;
                }
            }

            // Update book in database
            $stmt = $pdo->prepare("
                UPDATE books 
                SET title = ?, author = ?, isbn = ?, description = ?, category = ?, 
                    total_quantity = ?, available_quantity = ?, cover_image = ?, status = ?
                WHERE id = ?
            ");
            $status = $available_quantity > 0 ? 'available' : 'borrowed';
            $stmt->execute([$title, $author, $isbn, $description, $category, $total_quantity, $available_quantity, $cover_image, $status, $book_id]);
            
            $success = "Book updated successfully!";
            
            // Refresh book data
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error updating book: " . $e->getMessage();
        }
    }
}

// Fetch unique categories for dropdown
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Error fetching categories: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - E-Library Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .form-container {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .form-label {
            color: var(--light-accent);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
        }
        .form-control {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
        }
        .form-control:focus {
            background-color: var(--dark-accent);
            border-color: var(--light-accent);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
        }
        .btn-submit {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            background-color: var(--light-accent);
            border-color: var(--light-accent);
        }
        .book-cover-preview {
            max-width: 200px;
            max-height: 300px;
            object-fit: cover;
            border: 2px solid var(--accent-color);
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="text-light mb-4" style="font-family: 'Cormorant Garamond', serif;">Edit Book</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($book): ?>
            <div class="form-container animate-fade-in">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category == $book['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($book['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="total_quantity" class="form-label">Total Quantity</label>
                            <input type="number" class="form-control" id="total_quantity" name="total_quantity" min="1" value="<?php echo $book['total_quantity']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="available_quantity" class="form-label">Available Quantity</label>
                            <input type="number" class="form-control" id="available_quantity" name="available_quantity" min="0" value="<?php echo $book['available_quantity']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cover_image" class="form-label">Cover Image</label>
                            <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                            <?php if ($book['cover_image']): ?>
                                <img src="assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="Current Cover" class="book-cover-preview mt-2">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-submit btn-lg">
                            <i class="fas fa-save me-2"></i>Update Book
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning animate-fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Book not found. Please return to the book management page.
            </div>
            <div class="text-center mt-4">
                <a href="manage-books.php" class="btn btn-submit">
                    <i class="fas fa-arrow-left me-2"></i>Back to Manage Books
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 