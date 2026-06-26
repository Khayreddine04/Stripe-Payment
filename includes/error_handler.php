<?php
/**
 * Error Handler for Stripe Payment Terminal
 * Logs all errors, warnings, and notices to a file
 */

// Define the log file path (using the existing error_log in the root directory)
$logFile = __DIR__ . '/../error_log';

// Ensure the log file exists and is writable
if (!file_exists($logFile)) {
    file_put_contents($logFile, '');
    chmod($logFile, 0666); // Make it writable
}

// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $logFile;
    
    // Define error types
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    // Get the error type name
    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : "Unknown Error ($errno)";
    
    // Format the error message
    $errorMessage = sprintf(
        "[%s] %s: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errorType,
        $errstr,
        $errfile,
        $errline
    );
    
    // Log the error to the file
    error_log($errorMessage, 3, $logFile);
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set error handler
set_error_handler('customErrorHandler');

// Set exception handler
function customExceptionHandler($exception) {
    global $logFile;
    
    $errorMessage = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    // Log the exception
    error_log($errorMessage, 3, $logFile);
    
    // Display a generic error message in production
    if (ini_get('display_errors')) {
        echo "<pre>$errorMessage</pre>";
    } else {
        echo "An error occurred. Please check the error log for more details.\n";
    }
}

set_exception_handler('customExceptionHandler');

// Set PHP configuration
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', $logFile);

// Log script start/end
register_shutdown_function(function() use ($logFile) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $errorMessage = sprintf(
            "[%s] Fatal Error: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($errorMessage, 3, $logFile);
    }
});

// Log script start
error_log("\n=== Script started at " . date('Y-m-d H:i:s') . " ===\n", 3, $logFile);
