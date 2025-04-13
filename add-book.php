<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $total_quantity = intval($_POST['total_quantity'] ?? 0);

    // Validate required fields
    if (empty($title) || empty($author) || $total_quantity <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            // Handle file uploads
            $cover_image = '';
            $file_path = '';

            // Handle cover image upload
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $cover_image = 'uploads/covers/' . basename($_FILES['cover_image']['name']);
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_image);
            }

            // Handle PDF file upload
            if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
                $file_path = 'uploads/books/' . basename($_FILES['file_path']['name']);
                move_uploaded_file($_FILES['file_path']['tmp_name'], $file_path);
            }

            // Insert book into database
            $stmt = $pdo->prepare("
                INSERT INTO books (title, author, isbn, category, description, total_quantity, available_quantity, cover_image, file_path)
                VALUES (:title, :author, :isbn, :category, :description, :total_quantity, :available_quantity, :cover_image, :file_path)
            ");

            $stmt->execute([
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'category' => $category,
                'description' => $description,
                'total_quantity' => $total_quantity,
                'available_quantity' => $total_quantity,
                'cover_image' => $cover_image,
                'file_path' => $file_path
            ]);

            $success = 'Book added successfully!';
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Book - TAGTOPIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-sepia: #704214;
            --color-parchment: #f4e4bc;
            --color-ink: #2c1810;
            --color-gold: #bf9b30;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?q=80&w=228&auto=format&fit=crop');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3, .font-serif {
            font-family: 'Cormorant Garamond', serif;
        }

        .form-container {
            background: rgba(28, 25, 23, 0.95);
            border: 1px solid var(--color-gold);
        }

        .input-style {
            background: rgba(41, 37, 36, 0.8);
            border: 1px solid var(--color-sepia);
            transition: all 0.3s ease;
        }

        .input-style:focus {
            border-color: var(--color-gold);
            box-shadow: 0 0 0 2px rgba(191, 155, 48, 0.2);
        }

        .btn-academia {
            background: var(--color-sepia);
            border: 1px solid var(--color-gold);
            transition: all 0.3s ease;
        }

        .btn-academia:hover {
            background: var(--color-gold);
            transform: translateY(-1px);
        }

        .file-input-academia {
            border: 1px dashed var(--color-sepia);
            transition: all 0.3s ease;
        }

        .file-input-academia:hover {
            border-color: var(--color-gold);
        }
    </style>
</head>
<body class="text-stone-200 min-h-screen bg-stone-900 bg-opacity-50">
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto mt-8 form-container p-8 rounded-xl shadow-2xl">
            <h2 class="text-4xl font-serif font-bold text-center mb-8 text-stone-100">
                <i class="fas fa-book-medical mr-3 text-stone-400"></i>Add New Book
            </h2>
            
            <?php if ($error): ?>
                <div class="bg-red-900 bg-opacity-50 text-stone-100 p-4 rounded mb-6 text-center border border-red-700">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-900 bg-opacity-50 text-stone-100 p-4 rounded mb-6 text-center border border-green-700">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <label for="title" class="block mb-2 text-sm font-medium text-stone-300">Title <span class="text-stone-500">*</span></label>
                        <input type="text" id="title" name="title" required
                               class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" />
                    </div>
                    <div>
                        <label for="author" class="block mb-2 text-sm font-medium text-stone-300">Author <span class="text-stone-500">*</span></label>
                        <input type="text" id="author" name="author" required
                               class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"
                               value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>" />
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <label for="isbn" class="block mb-2 text-sm font-medium text-stone-300">ISBN</label>
                        <input type="text" id="isbn" name="isbn"
                               class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"
                               value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>" />
                    </div>
                    <div>
                        <label for="category" class="block mb-2 text-sm font-medium text-stone-300">Category</label>
                        <input type="text" id="category" name="category"
                               class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"
                               value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>" />
                    </div>
                </div>

                <div>
                    <label for="description" class="block mb-2 text-sm font-medium text-stone-300">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <label for="total_quantity" class="block mb-2 text-sm font-medium text-stone-300">Total Quantity <span class="text-stone-500">*</span></label>
                        <input type="number" id="total_quantity" name="total_quantity" min="1" required
                               class="input-style w-full p-3 rounded-lg text-stone-100 placeholder-stone-500 focus:outline-none"
                               value="<?php echo isset($_POST['total_quantity']) ? htmlspecialchars($_POST['total_quantity']) : ''; ?>" />
                    </div>
                    <div>
                        <label for="cover_image" class="block mb-2 text-sm font-medium text-stone-300">Cover Image</label>
                        <div class="file-input-academia rounded-lg p-4">
                            <input type="file" id="cover_image" name="cover_image" accept="image/*"
                                   class="w-full text-sm text-stone-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border file:border-stone-500 file:text-sm file:font-medium file:bg-stone-800 file:text-stone-200 hover:file:bg-stone-700" />
                        </div>
                    </div>
                </div>

                <div>
                    <label for="file_path" class="block mb-2 text-sm font-medium text-stone-300">Book File (PDF)</label>
                    <div class="file-input-academia rounded-lg p-4">
                        <input type="file" id="file_path" name="file_path" accept=".pdf"
                               class="w-full text-sm text-stone-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border file:border-stone-500 file:text-sm file:font-medium file:bg-stone-800 file:text-stone-200 hover:file:bg-stone-700" />
                    </div>
                </div>

                <div class="flex justify-between items-center mt-12 pt-6 border-t border-stone-700">
                    <a href="index.php" class="btn-academia px-6 py-3 rounded-lg text-stone-200 hover:text-white flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn-academia px-8 py-3 rounded-lg text-stone-200 hover:text-white flex items-center">
                        <i class="fas fa-plus mr-2"></i>Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 