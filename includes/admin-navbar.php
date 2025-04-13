<?php
$current_page = basename($_SERVER['PHP_SELF']);
$logo_path = "../assets/images/tagpiv-logo.png";
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
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-books.php' ? 'active' : ''; ?>" href="manage-books.php" style="color: var(--text-color);">
                        <i class="fas fa-book me-1"></i>Manage Books
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-users.php' ? 'active' : ''; ?>" href="manage-users.php" style="color: var(--text-color);">
                        <i class="fas fa-users me-1"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-borrows.php' ? 'active' : ''; ?>" href="manage-borrows.php" style="color: var(--text-color);">
                        <i class="fas fa-exchange-alt me-1"></i>Manage Borrows
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-reservations.php' ? 'active' : ''; ?>" href="manage-reservations.php" style="color: var(--text-color);">
                        <i class="fas fa-calendar-check me-1"></i>Manage Reservations
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item admin-dropdown">
                    <a class="nav-link admin-btn" href="#" style="color: var(--text-color);">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <div class="logout-dropdown">
                        <a href="#" class="logout-btn" onclick="confirmLogout()">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </li>
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
    .navbar-toggler {
        border-color: var(--accent-color);
    }
    .navbar-toggler:focus {
        box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
    }

    /* Admin Dropdown Styles */
    .admin-dropdown {
        position: relative;
    }
    .admin-btn {
        background-color: transparent;
        border: none;
        padding: 0.5rem 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .admin-btn:hover {
        color: var(--light-accent) !important;
    }
    .logout-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 150px;
        background-color: #E6D3B3;
        border: 1px solid var(--accent-color);
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        display: none;
        overflow: hidden;
    }
    .logout-btn {
        display: block;
        padding: 0.75rem 1rem;
        color: #4B3621;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .logout-btn:hover {
        background-color: #D4C1A3;
        color: #4B3621;
        transform: translateX(5px);
    }
    .logout-btn i {
        transition: transform 0.3s ease;
    }
    .logout-btn:hover i {
        transform: translateX(3px);
    }

    .rounded-logo {
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    .rounded-logo:hover {
        transform: scale(1.05);
    }
</style>

<!-- Required JavaScript files -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Handle admin dropdown
    $('.admin-btn').hover(
        function() {
            $('.logout-dropdown').stop().slideDown(300);
        },
        function() {
            $('.logout-dropdown').stop().slideUp(300);
        }
    );

    // Keep dropdown open when hovering over it
    $('.logout-dropdown').hover(
        function() {
            $(this).stop().slideDown(300);
        },
        function() {
            $(this).stop().slideUp(300);
        }
    );

    // Handle navbar toggler
    $('.navbar-toggler').on('click', function() {
        $(this).toggleClass('active');
        $('#navbarNav').toggleClass('show');
    });

    // Handle active state for nav links
    $('.nav-link').on('click', function() {
        $('.nav-link').removeClass('active');
        $(this).addClass('active');
    });
});

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script> 