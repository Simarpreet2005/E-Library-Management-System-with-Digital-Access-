<?php
spl_autoload_register(function ($class) {
    // Base directory for includes
    $baseDir = __DIR__;
    
    // Remove namespace prefix if exists
    $class = ltrim($class, '\\');
    $class = str_replace('App\\Models\\', '', $class);
    $class = str_replace('App\\Core\\', '', $class);
    
    // Try to load the file
    $file = $baseDir . DIRECTORY_SEPARATOR . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Only load configuration
require_once __DIR__ . '/../config/database.php'; 