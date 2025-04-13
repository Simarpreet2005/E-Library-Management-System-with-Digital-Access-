<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle reservation status update
if (isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $new_status = $_POST['status'];
    try {
        // Update reservation status
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $reservation_id]);
        
        // If status is approved, update book availability
        if ($new_status === 'approved') {
            $stmt = $pdo->prepare("UPDATE books SET available = 0 WHERE id = (SELECT book_id FROM reservations WHERE id = ?)");
            $stmt->execute([$reservation_id]);
        }
        
        $success = "Reservation status updated successfully";
    } catch (PDOException $e) {
        $error = "Error updating reservation: " . $e->getMessage();
    }
}

// Fetch all reservations with user and book details
try {
    $stmt = $pdo->query("
        SELECT r.*, u.username, bk.title, bk.cover_image
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN books bk ON r.book_id = bk.id
        ORDER BY r.created_at DESC
    ");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching reservations: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - E-Library Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        .reservation-card {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .book-cover {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid var(--accent-color);
        }
        .reservation-details {
            font-family: 'Cormorant Garamond', serif;
        }
        .reservation-title {
            color: var(--light-accent);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .reservation-info {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        .reservation-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .status-pending {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        .status-approved {
            background-color: #28a745;
            color: var(--text-color);
        }
        .status-rejected {
            background-color: #dc3545;
            color: var(--text-color);
        }
        .status-completed {
            background-color: var(--dark-accent);
            color: var(--light-accent);
        }
        .status-select {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: 'Cormorant Garamond', serif;
            width: 100%;
            margin-bottom: 1rem;
        }
        .btn-update {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            background-color: var(--light-accent);
            border-color: var(--light-accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="text-light mb-4" style="font-family: 'Cormorant Garamond', serif;">Manage Reservations</h1>

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
            <?php foreach ($reservations as $reservation): ?>
                <div class="col-md-6 animate-fade-in">
                    <div class="reservation-card">
                        <div class="row">
                            <div class="col-md-3">
                                <img src="assets/images/books/<?php echo htmlspecialchars($reservation['cover_image']); ?>" 
                                     alt="Book Cover" class="book-cover">
                            </div>
                            <div class="col-md-9">
                                <div class="reservation-details">
                                    <h3 class="reservation-title"><?php echo htmlspecialchars($reservation['title']); ?></h3>
                                    <p class="reservation-info">
                                        <i class="fas fa-user me-2"></i>
                                        <?php echo htmlspecialchars($reservation['username']); ?>
                                    </p>
                                    <p class="reservation-info">
                                        <i class="fas fa-calendar me-2"></i>
                                        Reserved: <?php echo date('M d, Y', strtotime($reservation['created_at'])); ?>
                                    </p>
                                    <span class="reservation-status status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                                    </span>
                                    
                                    <?php if ($reservation['status'] === 'pending'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <select name="status" class="status-select">
                                                <option value="approved">Approve</option>
                                                <option value="rejected">Reject</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-update">
                                                <i class="fas fa-save me-2"></i>Update Status
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
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