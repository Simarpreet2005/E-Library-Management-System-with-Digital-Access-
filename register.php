<?php
session_start();
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password strength
    $password_errors = [];
    if (strlen($password) < 6) {
        $password_errors[] = "Password must be at least 6 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $password_errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $password_errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $password_errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $password_errors[] = "Password must contain at least one special character";
    }
    if ($password !== $confirm_password) {
        $password_errors[] = "Passwords do not match";
    }

    if (empty($password_errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed_password]);
            $_SESSION['success'] = "Registration successful! Please login.";
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--dark-accent);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-container {
            background-color: var(--secondary-color);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--accent-color);
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            margin-bottom: 1rem;
        }
        .register-header p {
            color: var(--text-color);
            opacity: 0.8;
        }
        .form-control {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            background-color: var(--dark-accent);
            border-color: var(--light-accent);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(139, 69, 19, 0.25);
        }
        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
            transform: translateY(-2px);
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-color);
        }
        .login-link a {
            color: var(--light-accent);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .login-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--accent-color);
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .registration-form {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--accent-color);
        }
        .form-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            color: var(--light-accent);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-control {
            background-color: var(--dark-accent);
            border: 1px solid var(--accent-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            background-color: var(--dark-accent);
            border-color: var(--light-accent);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
        }
        .form-label {
            color: var(--text-color);
            font-family: 'Cormorant Garamond', serif;
        }
        .btn-register {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background-color: var(--light-accent);
            transform: translateY(-2px);
        }
        .validation-message {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .valid {
            color: #28a745;
        }
        .invalid {
            color: #dc3545;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: var(--text-color);
            margin-top: 0.5rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        .requirement i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }
        .requirement.valid i {
            color: #28a745;
        }
        .requirement.invalid i {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join our library community</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div id="usernameFeedback" class="validation-message"></div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div id="emailFeedback" class="validation-message"></div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="password-requirements">
                    <div class="requirement" id="length">
                        <i class="fas fa-times"></i>
                        <span>At least 6 characters</span>
                    </div>
                    <div class="requirement" id="uppercase">
                        <i class="fas fa-times"></i>
                        <span>At least one uppercase letter</span>
                    </div>
                    <div class="requirement" id="lowercase">
                        <i class="fas fa-times"></i>
                        <span>At least one lowercase letter</span>
                    </div>
                    <div class="requirement" id="number">
                        <i class="fas fa-times"></i>
                        <span>At least one number</span>
                    </div>
                    <div class="requirement" id="special">
                        <i class="fas fa-times"></i>
                        <span>At least one special character</span>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div id="confirmPasswordFeedback" class="validation-message"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        // Username availability check
        $('#username').on('input', function() {
            const username = $(this).val();
            if (username.length >= 3) {
                $.ajax({
                    url: 'check-username.php',
                    method: 'POST',
                    data: { username: username },
                    success: function(response) {
                        const feedback = $('#usernameFeedback');
                        if (response.available) {
                            feedback.removeClass('invalid').addClass('valid').text('Username is available');
                        } else {
                            feedback.removeClass('valid').addClass('invalid').text('Username is already taken');
                        }
                    }
                });
            } else {
                $('#usernameFeedback').removeClass('valid invalid').text('');
            }
        });

        // Email validation
        $('#email').on('input', function() {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const feedback = $('#emailFeedback');
            
            if (emailRegex.test(email)) {
                feedback.removeClass('invalid').addClass('valid').text('Valid email format');
            } else if (email.length > 0) {
                feedback.removeClass('valid').addClass('invalid').text('Invalid email format');
            } else {
                feedback.removeClass('valid invalid').text('');
            }
        });

        // Password validation
        $('#password').on('input', function() {
            const password = $(this).val();
            
            // Check length
            $('#length').toggleClass('valid', password.length >= 6)
                .find('i').toggleClass('fa-check', password.length >= 6)
                .toggleClass('fa-times', password.length < 6);
            
            // Check uppercase
            $('#uppercase').toggleClass('valid', /[A-Z]/.test(password))
                .find('i').toggleClass('fa-check', /[A-Z]/.test(password))
                .toggleClass('fa-times', !/[A-Z]/.test(password));
            
            // Check lowercase
            $('#lowercase').toggleClass('valid', /[a-z]/.test(password))
                .find('i').toggleClass('fa-check', /[a-z]/.test(password))
                .toggleClass('fa-times', !/[a-z]/.test(password));
            
            // Check number
            $('#number').toggleClass('valid', /[0-9]/.test(password))
                .find('i').toggleClass('fa-check', /[0-9]/.test(password))
                .toggleClass('fa-times', !/[0-9]/.test(password));
            
            // Check special character
            $('#special').toggleClass('valid', /[^A-Za-z0-9]/.test(password))
                .find('i').toggleClass('fa-check', /[^A-Za-z0-9]/.test(password))
                .toggleClass('fa-times', !/[^A-Za-z0-9]/.test(password));
        });

        // Confirm password validation
        $('#confirm_password').on('input', function() {
            const password = $('#password').val();
            const confirmPassword = $(this).val();
            const feedback = $('#confirmPasswordFeedback');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    feedback.removeClass('invalid').addClass('valid').text('Passwords match');
                } else {
                    feedback.removeClass('valid').addClass('invalid').text('Passwords do not match');
                }
            } else {
                feedback.removeClass('valid invalid').text('');
            }
        });

        // Form submission validation
        $('#registerForm').on('submit', function(e) {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const usernameFeedback = $('#usernameFeedback');
            const emailFeedback = $('#emailFeedback');
            
            if (!usernameFeedback.hasClass('valid')) {
                e.preventDefault();
                alert('Please choose an available username');
                return false;
            }
            
            if (!emailFeedback.hasClass('valid')) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            if (password.length < 6 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || 
                !/[0-9]/.test(password) || !/[^A-Za-z0-9]/.test(password)) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
        });
    });
    </script>
</body>
</html> 