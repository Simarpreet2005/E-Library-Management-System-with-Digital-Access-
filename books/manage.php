<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Book class demonstrating OOP concepts
class Book {
    private $id;
    private $name;
    private $price;
    private $description;
    private $pdo;

    public function __construct($pdo, $id = null) {
        $this->pdo = $pdo;
        if ($id) {
            $this->id = $id;
            $this->loadBookData();
        }
    }

    private function loadBookData() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$this->id]);
            $bookData = $stmt->fetch();

            if ($bookData) {
                $this->name = $bookData['name'];
                $this->price = $bookData['price'];
                $this->description = $bookData['description'];
            }
        } catch (PDOException $e) {
            error_log("Error loading book data: " . $e->getMessage());
        }
    }

    public function getAllBooks() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM products ORDER BY id DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching books: " . $e->getMessage());
            return [];
        }
    }

    public function addBook($name, $price, $description) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO products (name, price, description) VALUES (?, ?, ?)");
            return $stmt->execute([$name, $price, $description]);
        } catch (PDOException $e) {
            error_log("Error adding book: " . $e->getMessage());
            return false;
        }
    }

    public function updateBook($name, $price, $description) {
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?");
            return $stmt->execute([$name, $price, $description, $this->id]);
        } catch (PDOException $e) {
            error_log("Error updating book: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBook() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            return $stmt->execute([$this->id]);
        } catch (PDOException $e) {
            error_log("Error deleting book: " . $e->getMessage());
            return false;
        }
    }
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book = new Book($pdo);

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

                if (empty($name) || $price === false || empty($description)) {
                    $error = "All fields are required and price must be a number";
                }
                else {
                    if ($book->addBook($name, $price, $description)) {
                        $message = "Book added successfully";
                    } 
                    else {
                        $error = "Error adding book";
                    }
                }
                break;

            case 'delete':
                $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
                if ($bookId) {
                    $book = new Book($pdo, $bookId);
                   
                    if ($book->deleteBook()) {
                        $message = "Book deleted successfully";
                    } else {
                        $error = "Error deleting book";
                    }
                }
                break;
        }
    }
}

// Get all books
$book = new Book($pdo);
$books = $book->getAllBooks();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Add New Book</h3>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo sanitizeOutput($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo sanitizeOutput($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Book Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Manage Books</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo sanitizeOutput($book['name']); ?></td>
                                    <td>$<?php echo number_format($book['price'], 2); ?></td>
                                    <td><?php echo sanitizeOutput($book['description']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this book?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
