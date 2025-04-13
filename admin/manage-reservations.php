<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle reservation status update
if (isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE book_reservations SET status = ? WHERE id = ?");
    $stmt->execute([$status, $reservation_id]);
    
    header('Location: manage-reservations.php');
    exit();
}

// Get all reservations with book and user details
$stmt = $pdo->query("
    SELECT r.*, bk.title as book_title, bk.cover_image, u.username, u.email
    FROM book_reservations r
    JOIN books bk ON r.book_id = bk.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.reservation_date DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Manage Reservations</h1>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>User</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($reservation['cover_image']); ?>" 
                                                 alt="Book Cover" 
                                                 style="width: 40px; height: 60px; object-fit: cover; margin-right: 10px;">
                                            <?php echo htmlspecialchars($reservation['book_title']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($reservation['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $reservation['status'] === 'approved' ? 'success' : 
                                                ($reservation['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($reservation['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" 
                                                        name="update_status" 
                                                        class="btn btn-sm btn-success"
                                                        onclick="return confirm('Approve this reservation?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" 
                                                        name="update_status" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Reject this reservation?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 