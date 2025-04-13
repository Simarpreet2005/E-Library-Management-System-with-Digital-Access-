<?php
class Logger {
    private static $instance = null;
    private $logFile;
    private $logLevel;

    private function __construct() {
        $this->logFile = LOG_PATH . '/app.log';
        $this->logLevel = LOG_LEVEL;
        
        // Create logs directory if it doesn't exist
        if (!file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0777, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($level, $message, $context = []) {
        $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
        if (!in_array($level, $levels)) {
            throw new InvalidArgumentException('Invalid log level');
        }

        if (array_search($level, $levels) < array_search($this->logLevel, $levels)) {
            return;
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];

        $logMessage = sprintf(
            "[%s] %s: %s %s %s\n",
            $logEntry['timestamp'],
            $logEntry['level'],
            $logEntry['message'],
            json_encode($logEntry['context']),
            json_encode([
                'ip' => $logEntry['ip'],
                'user_id' => $logEntry['user_id'],
                'url' => $logEntry['url']
            ])
        );

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }

    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }

    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }

    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }

    public function getLogFile() {
        return $this->logFile;
    }

    public function clearLog() {
        file_put_contents($this->logFile, '');
    }

    public function getLogContents($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = [];
        $file = new SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - $lines);

        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $logs[] = $file->current();
        }

        return array_filter($logs);
    }
}

// Error handler
function errorHandler($errno, $errstr, $errfile, $errline) {
    $logger = Logger::getInstance();
    
    $context = [
        'file' => $errfile,
        'line' => $errline,
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
    ];

    switch ($errno) {
        case E_ERROR:
            $logger->critical($errstr, $context);
            break;
        case E_WARNING:
            $logger->warning($errstr, $context);
            break;
        case E_NOTICE:
            $logger->info($errstr, $context);
            break;
        default:
            $logger->debug($errstr, $context);
    }

    return true;
}

// Exception handler
function exceptionHandler($exception) {
    $logger = Logger::getInstance();
    
    $context = [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];

    $logger->critical($exception->getMessage(), $context);

    if (isDevelopment()) {
        echo "<h1>Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        echo "An error occurred. Please try again later.";
    }
}

// Set error handlers
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler'); 