<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'elibrary');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Library Management System');
define('APP_URL', 'http://localhost/libraryproject');
define('APP_VERSION', '1.0.0');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Security configuration
define('HASH_COST', 12);
define('TOKEN_EXPIRY', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@library.com');
define('SMTP_FROM_NAME', APP_NAME);

// Library settings
define('MAX_BORROW_DAYS', 14);
define('MAX_BORROW_BOOKS', 5);
define('FINE_PER_DAY', 1.00);
define('MAX_FINE', 50.00);

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_PATH', __DIR__ . '/../cache');
define('CACHE_LIFETIME', 3600); // 1 hour

// API configuration
define('API_KEY', 'your-api-key');
define('API_RATE_LIMIT', 100); // requests per hour
define('API_RATE_WINDOW', 3600); // 1 hour

// Logging configuration
define('LOG_PATH', __DIR__ . '/../logs');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR, CRITICAL

// Timezone
date_default_timezone_set('UTC');

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Function to get environment variable with fallback
function env($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Function to check if application is in development mode
function isDevelopment() {
    return env('APP_ENV', 'production') === 'development';
}

// Function to get base URL
function baseUrl() {
    return rtrim(APP_URL, '/');
}

// Function to get asset URL
function assetUrl($path) {
    return baseUrl() . '/assets/' . ltrim($path, '/');
}

// Function to get upload URL
function uploadUrl($path) {
    return baseUrl() . '/uploads/' . ltrim($path, '/');
}

// Function to format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Function to generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to validate CSRF token
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Function to redirect
function redirect($path) {
    header('Location: ' . baseUrl() . '/' . ltrim($path, '/'));
    exit;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Function to require admin
function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}

// Function to log activity
function logActivity($action, $entityType, $entityId, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
} 