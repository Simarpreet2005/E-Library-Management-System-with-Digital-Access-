<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// User class demonstrating OOP concepts
class User {
    private $id;
    private $username;
    private $email;
    private $pdo;

    public function __construct($pdo, $id) {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->loadUserData();
    }

    private function loadUserData() {
        try {
            $stmt = $this->pdo->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $userData = $stmt->fetch();

            if ($userData) {
                $this->username = $userData['username'];
                $this->email = $userData['email'];
            }
        } catch (PDOException $e) {
            error_log("Error loading user data: " . $e->getMessage());
        }
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function updateProfile($newUsername, $newEmail) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            return $stmt->execute([$newUsername, $newEmail, $this->id]);
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return false;
        }
    }
}

// Create user object
$user = new User($pdo, $_SESSION['user_id']);

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($newUsername) || empty($newEmail)) {
        $error = "All fields are required";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        if ($user->updateProfile($newUsername, $newEmail)) {
            $message = "Profile updated successfully";
            $_SESSION['username'] = $newUsername;
        } else {
            $error = "Error updating profile";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">User Profile</h3>
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
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo sanitizeOutput($user->getUsername()); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo sanitizeOutput($user->getEmail()); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 