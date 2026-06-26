<?php



error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Include your original install.php
include 'install.php';

// debug.php
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Payment Terminal Debug Page ===\n\n";

// Test service ID resolution
echo "Service ID Resolution Test:\n";
$sources = [
    'GET[service]' => $_GET['service'] ?? 'none',
    'POST[pt_service]' => $_POST['pt_service'] ?? 'none', 
    'POST[service]' => $_POST['service'] ?? 'none',
    'SESSION[pt_service]' => $_SESSION['pt_service'] ?? 'none'
];

foreach ($sources as $source => $value) {
    echo "$source: $value\n";
}

echo "\n=== Session Data ===\n";
print_r($_SESSION);

echo "\n=== POST Data ===\n";
print_r($_POST);

echo "\n=== Log File Status ===\n";
$logDir = __DIR__ . '/log';
$logFile = $logDir . '/payment_errors.log';

if (is_dir($logDir)) {
    echo "Log directory exists: Yes\n";
    echo "Log directory writable: " . (is_writable($logDir) ? "Yes" : "No") . "\n";
    
    // Test writing to the log directory
    $testFile = $logDir . '/test_write.tmp';
    if (@file_put_contents($testFile, 'test')) {
        echo "✓ Can write to log directory\n";
        @unlink($testFile);
    } else {
        echo "✗ Cannot write to log directory\n";
        echo "Directory permissions: " . substr(sprintf('%o', fileperms($logDir)), -4) . "\n";
    }
} else {
    echo "Log directory exists: No\n";
    echo "Attempting to create log directory...\n";
    if (@mkdir($logDir, 0755, true)) {
        echo "✓ Log directory created successfully\n";
    } else {
        echo "✗ Failed to create log directory\n";
    }
}

if (file_exists($logFile)) {
    echo "Log file exists: Yes\n";
    echo "Log file size: " . filesize($logFile) . " bytes\n";
    echo "Log file writable: " . (is_writable($logFile) ? "Yes" : "No") . "\n";
    
    echo "\n=== Last 10 Log Entries ===\n";
    $lines = file($logFile);
    if ($lines) {
        $lastLines = array_slice($lines, -10);
        foreach ($lastLines as $line) {
            echo $line;
        }
    } else {
        echo "Could not read log file. Check permissions.\n";
    }
} else {
    echo "Log file exists: No\n";
    echo "Attempting to create log file...\n";
    if (@file_put_contents($logFile, "=== Log file created at " . date('Y-m-d H:i:s') . " ===\n")) {
        echo "✓ Log file created successfully\n";
    } else {
        echo "✗ Failed to create log file. Check directory permissions.\n";
    }
}

// Test logging
ErrorLogger::log("Debug page accessed successfully", "INFO", [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'test_data' => 'This is a test log entry'
]);

echo "\n=== Test log entry written ===\n";
?>
