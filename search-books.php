<?php
session_start();
require_once 'config/database.php';

// Get search query and category from GET parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Get books from database
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
    $sql = "SELECT * FROM books $where_clause ORDER BY title";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    <title>Search Books - E-Library</title>
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
    <header style="display: flex; justify-content: flex-end; align-items: center; padding: 10px;">
        <img src="https://img.icons8.com/?size=128&id=D45ofLrj1Mp5&format=png" alt="Logo" class="w-6 h-6 mr-2 rounded-full" style="width: 24px; height: 24px; margin-right: 8px;">
        <h1 style="font-size: 18px; margin: 0;">TAGTOPIA</h1>
    </header>
    
    <header class="text-center mb-12">
        <h1 class="text-5xl font-extrabold text-gray-100 drop-shadow-md">📚 E-Library</h1>
        <p class="text-gray-300 text-lg mt-2">Browse and explore your favorite books online</p>
        <form method="GET" class="mt-6 flex justify-center items-center gap-4">
            <input type="text" name="search" placeholder="Search books..." 
                   class="w-80 p-4 rounded-lg border border-gray-600 bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-gray-700 text-white font-semibold px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                🔍 Search
            </button>
        </form>
    </header>

    <main class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 px-4 max-w-7xl mx-auto" id="bookContainer">
        <?php foreach($books as $book): ?>
            <div class="bg-gray-900 p-6 rounded-2xl shadow-lg hover:shadow-gray-600 transition duration-300 min-h-[220px]">
                <div class="flex items-center mb-4">
                    <i class="fa-solid fa-book text-3xl text-gray-400 mr-4"></i>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($book['title']); ?></h2>
                        <p class="text-gray-400 text-lg">by <?php echo htmlspecialchars($book['author']); ?></p>
                    </div>
                </div>
                
                <?php if($book['category']): ?>
                    <span class="inline-block bg-gray-800 text-gray-300 text-sm px-3 py-1 rounded-full mb-6">
                        <?php echo htmlspecialchars($book['category']); ?>
                    </span>
                <?php endif; ?>
                
                <a href="book-details.php?id=<?php echo $book['id']; ?>" 
                   class="block bg-gray-700 text-white font-medium px-5 py-2 rounded-lg w-full text-center hover:bg-gray-600 transition">
                    📖 Read Now
                </a>
            </div>
        <?php endforeach; ?>
    </main>

    <script>
        // Add any additional JavaScript functionality here if needed
    </script>
</body>
</html> 