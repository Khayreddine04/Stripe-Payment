<?php
/**
 * Error Logger Class
 * 
 * Handles logging of errors, exceptions, and debug information
 */
class ErrorLogger {
    private static $logDir = __DIR__ . '/../log';
    private static $logFile = 'payment_errors.log';
    private static $maxLogSize = 10485760; // 10MB
    
    /**
     * Initialize error logging
     */
    public static function init() {
        // Create log directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
            @chmod(self::$logDir, 0777);
        }
        
        // Ensure log file exists and is writable
        $logPath = self::$logDir . '/' . self::$logFile;
        if (!file_exists($logPath)) {
            @touch($logPath);
            @chmod($logPath, 0666);
        }
        
        // Set custom error and exception handlers
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        
        // Log script start
        self::log('=== Script Started ===', 'INFO');
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorType = self::getErrorType($errno);
        $message = "$errorType: $errstr in $errfile on line $errline";
        self::log($message, 'ERROR');
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $message = "EXCEPTION: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . 
                  " on line " . $exception->getLine() . 
                  "\nStack Trace:\n" . $exception->getTraceAsString();
        self::log($message, 'CRITICAL');
    }
    
    /**
     * Log a message
     */
    public static function log($message, $level = 'INFO', $data = null) {
        $logPath = self::$logDir . '/' . self::$logFile;
        
        // Rotate log if it's too large
        if (file_exists($logPath) && filesize($logPath) > self::$maxLogSize) {
            self::rotateLog();
        }
        
        // Format the log message
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logMessage = "[$timestamp] [$level] [$ip] $requestUri - $message\n";
        
        if ($data !== null) {
            $logMessage .= "Data: " . print_r($data, true) . "\n";
        }
        
        $logMessage .= str_repeat('-', 80) . "\n";
        
        // Ensure directory exists and is writable
        if (!is_dir(dirname($logPath))) {
            @mkdir(dirname($logPath), 0777, true);
        }
        
        // Write to log file
        $result = @file_put_contents($logPath, $logMessage, FILE_APPEND);
        if ($result === false) {
            error_log("Failed to write to log file: $logPath");
        }
        @chmod($logPath, 0666);
        
        // Also log to PHP error log for visibility
        error_log(trim(str_replace("\n", " | ", $logMessage)));
    }
    
    /**
     * Rotate log file
     */
    private static function rotateLog() {
        $logPath = self::$logDir . '/' . self::$logFile;
        $backupPath = self::$logDir . '/' . self::$logFile . '.' . date('Y-m-d_His');
        
        if (file_exists($logPath)) {
            @rename($logPath, $backupPath);
        }
    }
    
    /**
     * Get error type string from error number
     */
    private static function getErrorType($errno) {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        return $types[$errno] ?? 'UNKNOWN_ERROR';
    }
    
    /**
     * Log request data
     */
    public static function logRequest() {
        $requestData = [
            'GET' => $_GET,
            'POST' => $_POST,
            'SESSION' => isset($_SESSION) ? array_keys($_SESSION) : [],
            'SERVER' => [
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
                'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
                'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        ];
        
        self::log('Request data captured', 'DEBUG', $requestData);
    }
}

// Initialize error logging
ErrorLogger::init();
?>
