<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up error logging to a file
$logDir = sys_get_temp_dir();  // Use system temp directory which is always writable
$logFile = $logDir . '/class_loader_errors.log';

// Ensure the log file is writable
if (!is_writable($logDir)) {
    die("Error: Cannot write to log directory: $logDir");
}

ini_set('log_errors', 1);
ini_set('error_log', $logFile);

// Function to log messages with timestamp
function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Try to write to log file, fall back to error_log if needed
    if (($fp = @fopen($logFile, 'a')) !== false) {
        fwrite($fp, $logMessage);
        fclose($fp);
    } else {
        error_log("Failed to write to log file: $logFile. Message: $message");
    }
    
    return $logMessage;
}

echo "<h1>Class Loader Test</h1>";
echo "<p>Logging to: $logFile</p>";

// List of classes to test
$classes = [
    'PT_User',
    'PT_Core',
    'PT_Settings',
    'PT_Template',
];

// Track class loading sources
$classSources = [];

// Custom autoloader for testing
spl_autoload_register(function($class) use (&$classSources) {
    // Skip if class already exists (prevents multiple attempts to load the same class)
    if (class_exists($class, false)) {
        return true;
    }

    $possiblePaths = [
        'backoffice' => __DIR__ . '/backoffice/includes/classes/' . strtolower($class) . '.class.php',
        'main' => __DIR__ . '/includes/classes/' . strtolower($class) . '.class.php',
        'models' => __DIR__ . '/includes/classes/models/' . strtolower($class) . '.class.php',
        'models_alt' => __DIR__ . '/includes/classes/models/' . str_replace('_', '/', $class) . '.class.php'
    ];
    
    foreach ($possiblePaths as $source => $path) {
        if (file_exists($path)) {
            try {
                require_once $path;
                if (class_exists($class, false)) {
                    $classSources[$class] = [
                        'source' => $source,
                        'path' => $path,
                        'loaded' => true
                    ];
                    return true;
                }
            } catch (Exception $e) {
                // Log the error but continue to next path
                log_message("Error loading class $class from $path: " . $e->getMessage());
            }
        }
    }
    
    // If we get here, the class wasn't found
    $classSources[$class] = [
        'source' => 'not_found',
        'path' => 'N/A',
        'loaded' => false,
        'tried_paths' => $possiblePaths
    ];
    
    // Don't throw an error, just return false to let other autoloaders try
    return false;
}, true, true);

echo "<h2>Testing class autoloading...</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse: collapse;'>";
echo "<tr><th>Class</th><th>Status</th><th>Source</th><th>Path</th></tr>";

foreach ($classes as $class) {
    $exists = class_exists($class);
    $source = $classSources[$class] ?? ['source' => 'unknown', 'path' => 'unknown'];
    
    $status = $exists ? "<span style='color:green'>✓ Loaded</span>" : "<span style='color:red'>✗ Not found</span>";
    $sourceType = $source['source'] ?? 'unknown';
    $sourcePath = htmlspecialchars($source['path'] ?? 'N/A');
    
    echo "<tr>";
    echo "<td>$class</td>";
    echo "<td>$status</td>";
    echo "<td>$sourceType</td>";
    echo "<td>$sourcePath</td>";
    echo "</tr>";
    
    // Log the result
    $logMessage = "Class: $class | Status: " . ($exists ? 'Loaded' : 'Not Found') . " | Source: $sourceType | Path: $sourcePath";
    if (!$exists) {
        $logMessage .= " | Tried paths: " . json_encode($source['tried_paths'] ?? []);
    }
    log_message($logMessage);
}

echo "</table>";

// Display registered autoloaders
echo "<h2>Registered Autoloaders</h2>";
$autoloaders = spl_autoload_functions();
if (empty($autoloaders)) {
    echo "No autoloaders registered!<br>";
} else {
    echo "<ul>";
    foreach ($autoloaders as $i => $loader) {
        $loaderInfo = is_array($loader) 
            ? (is_object($loader[0]) ? get_class($loader[0]) : $loader[0]) . '->' . $loader[1]
            : (is_string($loader) ? $loader : 'Closure');
        echo "<li>" . ($i+1) . ". $loaderInfo</li>";
    }
    echo "</ul>";
}

// Display any errors that occurred
echo "<h2>Error Log</h2>";
if (file_exists($logFile)) {
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>" . 
         htmlspecialchars(file_get_contents($logFile)) . 
         "</pre>";
} else {
    echo "<p>No errors logged yet. The log file will be created at: <code>$logFile</code> when errors occur.</p>";
}
?>
