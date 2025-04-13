<?php
session_start();
require_once 'config/database.php';

// Function to generate random avatar URL
function getRandomAvatar($seed) {
    // Use DiceBear Avatars API with a random seed
    return "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($seed);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        // Delete user from database
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = "User deleted successfully";
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Handle role update
if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        $success = "User role updated successfully";
    } catch (PDOException $e) {
        $error = "Error updating user role: " . $e->getMessage();
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - E-Library Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .user-card {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 2px solid var(--accent-color);
            background-color: var(--dark-accent);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-avatar svg {
            width: 100%;
            height: 100%;
            padding: 5px;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            margin-bottom: 0.5rem;
        }
        .user-email {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 1rem;
        }
        .user-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: var(--dark-accent);
            color: var(--light-accent);
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .action-buttons .btn {
            flex: 1;
            padding: 0.5rem;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }
        .btn-edit {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: var(--text-color);
        }
        .role-select {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: 'Cormorant Garamond', serif;
        }
        .role-select:focus {
            border-color: var(--light-accent);
            box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="text-light mb-4" style="font-family: 'Cormorant Garamond', serif;">Manage Users</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($users as $user): ?>
                <div class="col-md-4 animate-fade-in">
                    <div class="user-card">
                        <div class="user-avatar">
                            <?php
                            // Generate avatar URL using username as seed
                            $avatarUrl = getRandomAvatar($user['username']);
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="User Avatar">
                        </div>
                        <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="user-role"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                        
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" class="role-select w-100 mb-2">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit" name="update_role" class="btn btn-edit w-100">
                                <i class="fas fa-save me-2"></i>Update Role
                            </button>
                        </form>

                        <div class="action-buttons">
                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-edit">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-delete">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 