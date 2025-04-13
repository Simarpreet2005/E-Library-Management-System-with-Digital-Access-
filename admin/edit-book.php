<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$book_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $total_quantity = $_POST['total_quantity'];

    $errors = [];
    $update_fields = [];
    $params = [];

    // Calculate the difference in total quantity
    $quantity_diff = $total_quantity - $book['total_quantity'];

    // Validate cover image if uploaded
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_image = $_FILES['cover_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($cover_image['type'], $allowed_types)) {
            $errors[] = "Cover image must be a JPEG, PNG, or GIF file";
        }

        if ($cover_image['size'] > $max_size) {
            $errors[] = "Cover image must be less than 5MB";
        }
    }

    // Validate book file if uploaded
    if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
        $book_file = $_FILES['file_path'];
        $allowed_types = ['application/pdf'];
        $max_size = 50 * 1024 * 1024; // 50MB

        if (!in_array($book_file['type'], $allowed_types)) {
            $errors[] = "Book file must be a PDF";
        }

        if ($book_file['size'] > $max_size) {
            $errors[] = "Book file must be less than 50MB";
        }
    }

    if (empty($errors)) {
        // Start a transaction
        $pdo->beginTransaction();

        try {
            // Update basic fields
            $update_fields[] = "title = ?";
            $update_fields[] = "author = ?";
            $update_fields[] = "isbn = ?";
            $update_fields[] = "category = ?";
            $update_fields[] = "description = ?";
            $update_fields[] = "total_quantity = ?";
            
            $params[] = $title;
            $params[] = $author;
            $params[] = $isbn;
            $params[] = $category;
            $params[] = $description;
            $params[] = $total_quantity;

            // Handle cover image upload
            if (isset($cover_image)) {
                $cover_dir = '../uploads/covers/';
                if (!file_exists($cover_dir)) {
                    mkdir($cover_dir, 0777, true);
                }

                $cover_ext = pathinfo($cover_image['name'], PATHINFO_EXTENSION);
                $cover_filename = uniqid() . '.' . $cover_ext;
                $cover_path = $cover_dir . $cover_filename;

                if (move_uploaded_file($cover_image['tmp_name'], $cover_path)) {
                    // Delete old cover image
                    if (file_exists('../' . $book['cover_image'])) {
                        unlink('../' . $book['cover_image']);
                    }

                    $update_fields[] = "cover_image = ?";
                    $params[] = 'uploads/covers/' . $cover_filename;
                }
            }

            // Handle book file upload
            if (isset($book_file)) {
                $book_dir = '../uploads/books/';
                if (!file_exists($book_dir)) {
                    mkdir($book_dir, 0777, true);
                }

                $book_ext = pathinfo($book_file['name'], PATHINFO_EXTENSION);
                $book_filename = uniqid() . '.' . $book_ext;
                $book_path = $book_dir . $book_filename;

                if (move_uploaded_file($book_file['tmp_name'], $book_path)) {
                    // Delete old book file
                    if (file_exists('../' . $book['file_path'])) {
                        unlink('../' . $book['file_path']);
                    }

                    $update_fields[] = "file_path = ?";
                    $params[] = 'uploads/books/' . $book_filename;
                }
            }

            // Add book ID to params
            $params[] = $book_id;

            // Update database
            $sql = "UPDATE books SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Update available quantity based on the difference
            if ($quantity_diff != 0) {
                $new_available = $book['available_quantity'] + $quantity_diff;
                // Ensure available quantity doesn't go below 0
                $new_available = max(0, $new_available);
                
                $stmt = $pdo->prepare("UPDATE books SET available_quantity = ? WHERE id = ?");
                $stmt->execute([$new_available, $book_id]);
            }

            // Commit the transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Book updated successfully!";
            header('Location: manage-books.php');
            exit();
        } catch (PDOException $e) {
            // Rollback the transaction if something goes wrong
            $pdo->rollBack();
            $errors[] = "Failed to update book: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Edit Book</h2>

                        <?php if(!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

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
                                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($book['category']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="total_quantity" class="form-label">Total Quantity</label>
                                    <input type="number" class="form-control" id="total_quantity" name="total_quantity" value="<?php echo htmlspecialchars($book['total_quantity']); ?>" min="1" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cover_image" class="form-label">Cover Image (JPEG, PNG, GIF, max 5MB)</label>
                                    <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                                    <small class="text-muted">Current: <?php echo basename($book['cover_image']); ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="file_path" class="form-label">Book File (PDF, max 50MB)</label>
                                    <input type="file" class="form-control" id="file_path" name="file_path" accept=".pdf">
                                    <small class="text-muted">Current: <?php echo basename($book['file_path']); ?></small>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="manage-books.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 