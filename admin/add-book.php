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
    <style>
        body {
            background-image: url('https://tse4.mm.bing.net/th?id=OIP.h4i-GV4on1w2UmRJSerAbgHaFj&pid=Api&P=0&h=180');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
        }
    </style>
</head>
<body class="text-white p-6 font-sans min-h-screen">
    <!-- Logo and Name -->
    <header class="flex justify-end items-center p-4">
        <img src="https://img.icons8.com/?size=128&id=D45ofLrj1Mp5&format=png" alt="Logo" class="w-6 h-6 mr-2 rounded-full">
        <h1 class="text-lg font-bold">TAGTOPIA</h1>
    </header>

    <div class="max-w-4xl mx-auto mt-8 bg-gray-900 bg-opacity-90 p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-extrabold text-center mb-6 text-white">➕ Add New Book</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-500 text-white text-sm p-3 rounded mb-4 text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-500 text-white text-sm p-3 rounded mb-4 text-center">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block mb-2 text-sm font-medium">Title *</label>
                    <input type="text" id="title" name="title" required
                           class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" />
                </div>
                <div>
                    <label for="author" class="block mb-2 text-sm font-medium">Author *</label>
                    <input type="text" id="author" name="author" required
                           class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                           value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>" />
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="isbn" class="block mb-2 text-sm font-medium">ISBN</label>
                    <input type="text" id="isbn" name="isbn"
                           class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                           value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>" />
                </div>
                <div>
                    <label for="category" class="block mb-2 text-sm font-medium">Category</label>
                    <input type="text" id="category" name="category"
                           class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                           value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>" />
                </div>
            </div>

            <div>
                <label for="description" class="block mb-2 text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="total_quantity" class="block mb-2 text-sm font-medium">Total Quantity *</label>
                    <input type="number" id="total_quantity" name="total_quantity" min="1" required
                           class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                           value="<?php echo isset($_POST['total_quantity']) ? htmlspecialchars($_POST['total_quantity']) : ''; ?>" />
                </div>
                <div>
                    <label for="cover_image" class="block mb-2 text-sm font-medium">Cover Image</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*"
                           class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition" />
                </div>
            </div>

            <div>
                <label for="file_path" class="block mb-2 text-sm font-medium">Book File (PDF)</label>
                <input type="file" id="file_path" name="file_path" accept=".pdf"
                       class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition" />
            </div>

            <div class="flex justify-between items-center mt-8">
                <a href="admin-dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition">← Cancel</a>
                <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-8 py-3 rounded-lg font-semibold transition">Add Book</button>
            </div>
        </form>
    </div>
</body>
</html> 