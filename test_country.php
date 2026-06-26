<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Use consistent log directory - FIXED PATH
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/debug.log';

// Don't try to create directories in PHP - they're created in Dockerfile
ini_set('error_log', $logFile);

// Simple test to see if PHP is working
echo "<h1>PHP is working!</h1>";

// Check if logs directory is writable
if (!is_writable($logDir)) {
    echo "<div style='color:orange;'>Warning: Logs directory not writable. Check permissions.</div>";
}

// Define ABSPATH if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include Composer's autoloader
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('<div style="color: red;">Error: Composer dependencies not installed. Please run "composer install" in the project root.</div>');
}
require $autoloadPath;

// Test file inclusion
$included = @include __DIR__ . '/includes/country_detector.php';

// Log the result of the include
if (is_writable($logFile)) {
    file_put_contents($logFile, "Include result: " . ($included === false ? 'failed' : 'success') . "\n", FILE_APPEND);
}

if ($included === false) {
    echo "<div style='color:red;'>Error: Could not include country_detector.php. Check the file path.</div>";
    echo "<div>Current directory: " . __DIR__ . "</div>";
    echo "<div>Trying to include: " . __DIR__ . "/includes/country_detector.php</div>";
    
    // Check if file exists
    $path = __DIR__ . '/includes/country_detector.php';
    if (file_exists($path)) {
        echo "<div>File exists but couldn't be included. Check for syntax errors.</div>";
    } else {
        echo "<div>File does not exist at: $path</div>";
    }
    exit;
}

// Test function existence
if (!function_exists('get_user_country')) {
    $error = "Error: get_user_country() function is not defined.\n";
    $error .= "Defined functions: " . print_r(get_defined_functions()['user'], true) . "\n";
    if (is_writable($logFile)) {
        file_put_contents($logFile, $error, FILE_APPEND);
    }
    echo "<div style='color:red;'>$error</div>";
    exit;
}

// Get country
try {
    $country = get_user_country();
    if (is_writable($logFile)) {
        file_put_contents($logFile, "Country detected: $country\n", FILE_APPEND);
    }
} catch (Exception $e) {
    $error = "Error in get_user_country(): " . $e->getMessage() . "\n";
    $error .= $e->getTraceAsString() . "\n";
    if (is_writable($logFile)) {
        file_put_contents($logFile, $error, FILE_APPEND);
    }
    echo "<div style='color:red;'>$error</div>";
    $country = 'Error';
}

$ip = $_SERVER['REMOTE_ADDR'];
if (is_writable($logFile)) {
    file_put_contents($logFile, "Client IP: $ip\n", FILE_APPEND);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Country Detection Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .info { background: #e7f3fe; border-left: 6px solid #2196F3; padding: 10px; margin: 10px 0; }
        .error { background: #ffebee; border-left: 6px solid #f44336; padding: 10px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 6px solid #ffc107; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Country Detection Test</h1>
    
    <?php if (!is_writable($logDir)): ?>
    <div class="warning">
        <h3>Permission Notice</h3>
        <p>Logs directory is not writable. Some debug information may not be saved.</p>
    </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Your Information</h3>
        <p>Detected Country: <strong><?php echo htmlspecialchars($country ?? 'Not detected'); ?></strong></p>
        <p>Your IP: <strong><?php echo htmlspecialchars($ip); ?></strong></p>
    </div>
    
    <div class="info">
        <h3>PHP Information</h3>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>Error Reporting: <?php echo ini_get('error_reporting'); ?></p>
        <p>Display Errors: <?php echo ini_get('display_errors'); ?></p>
        <p>Logs Writable: <?php echo is_writable($logDir) ? 'Yes' : 'No'; ?></p>
    </div>
</body>
</html>