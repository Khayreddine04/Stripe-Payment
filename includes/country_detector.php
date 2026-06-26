<?php
// Enable error reporting at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set up error logging
$logFile = dirname(__DIR__) . '/logs/country_detector_errors.log';
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Only set error log if directory is writable
if (is_writable($logDir)) {
    ini_set('error_log', $logFile);
}

// Log that the file was included
error_log('country_detector.php included from: ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'unknown'));

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__));
}

class Country_Detector {
    private static $instance = null;
    private $database_path = '';
    private $default_country = 'US';

    private function __construct() {
        error_log('Country_Detector constructor called from: ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'unknown'));
        
        // Set the path to the GeoLite2 database
        $this->database_path = dirname(__DIR__) . '/geoip/GeoLite2-Country.mmdb';
        
        // Create the geoip directory if it doesn't exist
        $geoip_dir = dirname($this->database_path);
        if (!file_exists($geoip_dir)) {
            @mkdir($geoip_dir, 0755, true);
        }
        
        // Check if database exists and is readable
        if (!file_exists($this->database_path) || !is_readable($this->database_path)) {
            error_log('WARNING: GeoIP2 database not found or not readable at: ' . $this->database_path);
        } else {
            error_log('GeoIP2 database found at: ' . $this->database_path);
        }
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_country() {
        error_log('get_country() called');
        
        // Ensure session is started before using it
        if (session_status() === PHP_SESSION_NONE) {
            error_log('Session not active, starting new session');
            if (!session_start()) {
                error_log('Failed to start session');
                return $this->default_country;
            }
        }
        
        // Check if we already have the country in the session
        if (isset($_SESSION['detected_country'])) {
            error_log('Returning country from session: ' . $_SESSION['detected_country']);
            return $_SESSION['detected_country'];
        }

        // Check if the database file exists and is readable
        if (!file_exists($this->database_path) || !is_readable($this->database_path)) {
            $error = 'GeoIP2 database not found or not readable at: ' . $this->database_path;
            error_log($error);
            error_log('Current working directory: ' . getcwd());
            error_log('Directory exists: ' . (is_dir(dirname($this->database_path)) ? 'Yes' : 'No'));
            error_log('File exists: ' . (file_exists($this->database_path) ? 'Yes' : 'No'));
            error_log('Is readable: ' . (is_readable($this->database_path) ? 'Yes' : 'No'));
            return $this->default_country;
        }

        try {
            // Get the client IP
            $ip = $this->get_client_ip();
            error_log('Detected IP for GeoIP lookup: ' . $ip);
            
            // For local testing, use the default country
            if (in_array($ip, ['127.0.0.1', '::1'])) {
                error_log('Localhost detected, using default country: ' . $this->default_country);
                return $this->default_country;
            }

            // Load the MaxMind GeoIP2 database
            $reader = new \GeoIp2\Database\Reader($this->database_path);
            
            try {
                // Get the country from the IP using MaxMind
                $record = $reader->country($ip);
                $countryCode = $record->country->isoCode;
                
                // Store in session for future use
                $_SESSION['detected_country'] = $countryCode;
                
                error_log('Successfully detected country from MaxMind: ' . $countryCode . ' for IP: ' . $ip);
                return $countryCode;
                
            } catch (\Exception $e) {
                error_log('MaxMind lookup failed for IP ' . $ip . ': ' . $e->getMessage());
                // Fallback to default country if MaxMind lookup fails
                return $this->default_country;
            }
        } catch (\Exception $e) {
            error_log('GeoIP2 Error: ' . $e->getMessage());
            return $this->default_country;
        }
    }

    private function get_client_ip() {
        error_log('Getting client IP address');
        $ip = '';
        
        // Cloudflare provides the real client IP in CF-Connecting-IP header
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            error_log('Using Cloudflare CF-Connecting-IP: ' . $ip);
        }
        // Check for True-Client-IP (another Cloudflare header)
        elseif (!empty($_SERVER['HTTP_TRUE_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_TRUE_CLIENT_IP'];
            error_log('Using True-Client-IP: ' . $ip);
        }
        // Standard X-Forwarded-For header
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            error_log('Using X-Forwarded-For: ' . $ip);
        }
        // X-Real-IP header
        elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
            error_log('Using X-Real-IP: ' . $ip);
        }
        // Client IP header
        elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
            error_log('Using Client-IP: ' . $ip);
        }
        // Fallback to REMOTE_ADDR
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
            error_log('Using REMOTE_ADDR: ' . $ip);
        }
        
        // Handle multiple IPs in X-Forwarded-For (take the first one, which is the client)
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
            error_log('Multiple IPs found, using first: ' . $ip);
        }
        
        // Log all available headers for debugging
        error_log('All IP-related headers:');
        error_log('  REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR'] ?? 'not set'));
        error_log('  HTTP_CF_CONNECTING_IP: ' . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'not set'));
        error_log('  HTTP_TRUE_CLIENT_IP: ' . ($_SERVER['HTTP_TRUE_CLIENT_IP'] ?? 'not set'));
        error_log('  HTTP_X_FORWARDED_FOR: ' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set'));
        error_log('  HTTP_X_REAL_IP: ' . ($_SERVER['HTTP_X_REAL_IP'] ?? 'not set'));
        error_log('  HTTP_CLIENT_IP: ' . ($_SERVER['HTTP_CLIENT_IP'] ?? 'not set'));
        
        return $ip;
    }
}

// Initialize the country detector
function get_user_country() {
    error_log('get_user_country() called');
    
    try {
        error_log('Creating Country_Detector instance');
        $detector = Country_Detector::get_instance();
        error_log('Calling get_country()');
        $country = $detector->get_country();
        error_log('get_user_country() returning: ' . var_export($country, true));
        return $country;
    } catch (Exception $e) {
        $errorMsg = 'Error in get_user_country(): ' . $e->getMessage() . 
                   ' in ' . $e->getFile() . ' on line ' . $e->getLine() . 
                   '\nStack trace: ' . $e->getTraceAsString();
        error_log($errorMsg);
        return 'US'; // Default country
    }
}