<?php
$current_page = basename($_SERVER['PHP_SELF']);
$logo_path = "assets/images/tagpiv-logo.png";
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--dark-accent); border-bottom: 2px solid var(--accent-color);">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="<?php echo $logo_path; ?>" alt="TAGPIV Logo" height="35" class="rounded-logo" onerror="this.style.display='none'">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php" style="color: var(--text-color);">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>" href="books.php" style="color: var(--text-color);">
                        <i class="fas fa-book me-1"></i>Books
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="categories.php" style="color: var(--text-color);">
                        <i class="fas fa-tags me-1"></i>Categories
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my-books.php' ? 'active' : ''; ?>" href="my-books.php" style="color: var(--text-color);">
                            <i class="fas fa-bookmark me-1"></i>My Books
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--text-color);">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--secondary-color); border: 1px solid var(--accent-color);">
                            <li><a class="dropdown-item" href="profile.php" style="color: var(--text-color);">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider" style="border-color: var(--accent-color);"></li>
                            <li><a class="dropdown-item" href="#" onclick="confirmLogout()" style="color: var(--text-color);">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php" style="color: var(--text-color);">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php" style="color: var(--text-color);">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar {
        transition: all 0.3s ease;
    }
    .navbar:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }
    .nav-link {
        transition: all 0.3s ease;
        position: relative;
    }
    .nav-link:hover {
        color: var(--light-accent) !important;
        transform: translateY(-2px);
    }
    .nav-link.active {
        color: var(--light-accent) !important;
        font-weight: 600;
    }
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--light-accent);
        transform: scaleX(1);
        transition: transform 0.3s ease;
    }
    .dropdown-item {
        transition: all 0.3s ease;
    }
    .dropdown-item:hover {
        background-color: var(--dark-accent);
        color: var(--light-accent) !important;
        transform: translateX(5px);
    }
    .navbar-toggler {
        border-color: var(--accent-color);
    }
    .navbar-toggler:focus {
        box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
    }
    .rounded-logo {
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    .rounded-logo:hover {
        transform: scale(1.05);
    }
</style>

<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script> 