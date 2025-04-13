<?php
require_once 'config/database.php';

// Cache functions
function get_cache($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT cache_value FROM cache WHERE cache_key = ? AND expires_at > NOW()");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? json_decode($result['cache_value'], true) : null;
}

function set_cache($key, $value, $expiry = 3600) {
    global $pdo;
    $expires_at = date('Y-m-d H:i:s', time() + $expiry);
    $cache_value = json_encode($value);
    
    $stmt = $pdo->prepare("INSERT INTO cache (cache_key, cache_value, expires_at) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          cache_value = VALUES(cache_value), 
                          expires_at = VALUES(expires_at)");
    return $stmt->execute([$key, $cache_value, $expires_at]);
}

// Rate limiting functions
function check_rate_limit($action, $max_attempts = 5, $time_window = 3600) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("SELECT attempts, last_attempt 
                          FROM rate_limits 
                          WHERE ip_address = ? AND action = ?");
    $stmt->execute([$ip, $action]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $time_diff = time() - strtotime($result['last_attempt']);
        if ($time_diff < $time_window && $result['attempts'] >= $max_attempts) {
            return false;
        }
        
        if ($time_diff >= $time_window) {
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = NOW() 
                                  WHERE ip_address = ? AND action = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() 
                                  WHERE ip_address = ? AND action = ?");
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, action) VALUES (?, ?)");
    }
    
    $stmt->execute([$ip, $action]);
    return true;
}

// Activity logging
function log_activity($user_id, $action, $description = '') {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action, $description, $ip, $user_agent]);
}

// Password validation
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match("/[^A-Za-z0-9]/", $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// Two-factor authentication
function generate_2fa_secret() {
    require_once 'vendor/autoload.php';
    $g = new \PHPGangsta_GoogleAuthenticator();
    return $g->createSecret();
}

function verify_2fa_code($secret, $code) {
    require_once 'vendor/autoload.php';
    $g = new \PHPGangsta_GoogleAuthenticator();
    return $g->verifyCode($secret, $code, 2);
}

// Image optimization
function optimize_image($source_path, $target_path, $max_width = 800, $max_height = 800, $quality = 80) {
    list($width, $height, $type) = getimagesize($source_path);
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Load source image based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    // Resize and save
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $target_path, min(9, $quality / 10));
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $target_path);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($new_image);
    
    return true;
}

// Dark mode support
function get_theme_preference() {
    if (isset($_COOKIE['theme'])) {
        return $_COOKIE['theme'];
    }
    return 'light'; // Default theme
}

function set_theme_preference($theme) {
    setcookie('theme', $theme, time() + (86400 * 30), "/"); // 30 days
}

// Search and filtering
function search_books($query, $category = null, $author = null, $rating = null) {
    global $pdo;
    
    $sql = "SELECT b.*, 
            AVG(r.rating) as average_rating,
            COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN book_reviews r ON b.id = r.book_id
            WHERE 1=1";
    
    $params = [];
    
    if ($query) {
        $sql .= " AND (b.title LIKE ? OR b.description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    if ($category) {
        $sql .= " AND b.category_id = ?";
        $params[] = $category;
    }
    
    if ($author) {
        $sql .= " AND b.author LIKE ?";
        $params[] = "%$author%";
    }
    
    if ($rating) {
        $sql .= " HAVING average_rating >= ?";
        $params[] = $rating;
    }
    
    $sql .= " GROUP BY b.id ORDER BY b.title";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Email notifications
function send_notification($user_id, $subject, $message) {
    global $pdo;
    
    // Get user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // In a real application, you would use a proper email library
        // This is just a placeholder
        $headers = "From: noreply@elibrary.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($user['email'], $subject, $message, $headers);
    }
    
    return false;
}
?> 