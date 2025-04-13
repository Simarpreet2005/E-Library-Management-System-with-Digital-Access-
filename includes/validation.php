<?php
function validateUsername($username, $pdo) {
    // Check if username is empty
    if (empty($username)) {
        return ['valid' => false, 'message' => 'Username is required'];
    }

    // Check username length (between 3 and 20 characters)
    if (strlen($username) < 3 || strlen($username) > 20) {
        return ['valid' => false, 'message' => 'Username must be between 3 and 20 characters'];
    }

    // Check if username contains only alphanumeric characters and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        return ['valid' => false, 'message' => 'Username already exists'];
    }

    return ['valid' => true];
}

function validatePassword($password) {
    // Check if password is empty
    if (empty($password)) {
        return ['valid' => false, 'message' => 'Password is required'];
    }

    // Check password length (minimum 8 characters)
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
    }

    // Check for uppercase letters
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }

    // Check for lowercase letters
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }

    // Check for numbers
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }

    // Check for special characters
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one special character'];
    }

    return ['valid' => true];
}

function validateEmail($email, $pdo) {
    // Check if email is empty
    if (empty($email)) {
        return ['valid' => false, 'message' => 'Email is required'];
    }

    // Check email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format'];
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return ['valid' => false, 'message' => 'Email already exists'];
    }

    return ['valid' => true];
}
?> 