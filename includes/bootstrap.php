<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 */

// Include error logger first
require_once __DIR__ . '/ErrorLogger.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    ErrorLogger::logRequest();
}

define("HOME_DIR",dirname(dirname(__FILE__)));

include_once "dbconnect.php";
include_once "config.php";
include_once "_config.php";
include_once "functions.php";

// Include currency utilities
if (file_exists(__DIR__ . '/utils/currency_utils.php')) {
    include_once __DIR__ . '/utils/currency_utils.php';
}

$user = PT_User::instance();
$c = PT_Core::instance();
$settings = PT_Settings::instance();

if($settings->timezone === false) {
    date_default_timezone_set(date_default_timezone_get());
}else{
    date_default_timezone_set($settings->timezone);
}

define("NOW_DATE_TIME",date("Y-m-d H:i:s"));
define("NOW_DATE",date("Y-m-d"));

include_once HOME_DIR."/includes/plugin.functions.php";

load_plugins();

// Function to check if HTTPS is properly configured
function isSecure() {
    // Check for direct HTTPS
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    
    // Check for standard port
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        return true;
    }
    
    // Check for Cloudflare headers
    if (isset($_SERVER['HTTP_CF_VISITOR'])) {
        $cf_visitor = @json_decode($_SERVER['HTTP_CF_VISITOR'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($cf_visitor)) {
            if (isset($cf_visitor['scheme']) && $cf_visitor['scheme'] === 'https') {
                return true;
            }
        } elseif (stripos($_SERVER['HTTP_CF_VISITOR'] ?? '', 'https') !== false) {
            return true;
        }
    }
    
    // Check for common proxy/load balancer headers (case-insensitive)
    $headers = [
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_SSL' => 'on',
        'HTTP_X_FORWARDED_PROTOCOL' => 'https',
        'HTTP_X_FORWARDED_SCHEME' => 'https',
        'HTTP_X_URL_SCHEME' => 'https',
        'HTTP_X_FORWARDED_PORT' => '443'
    ];
    
    foreach ($headers as $header => $value) {
        if (isset($_SERVER[$header]) && 
            (strtolower($_SERVER[$header]) === strtolower($value) || 
             $_SERVER[$header] === '443')) {
            return true;
        }
    }
    
    // Check for Cloudflare specific headers
    if (isset($_SERVER['HTTP_CF_RAY']) || isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return true;
    }
    
    // Check for AWS Elastic Load Balancer
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && 
        (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], 'ELB-') === 0 || 
         strpos($_SERVER['HTTP_X_FORWARDED_FOR'], 'AWS-') === 0)) {
        return true;
    }
    
    return false;
}

// Only attempt HTTPS redirect if it's enabled in settings
if (isset($settings->redirect_https) && $settings->redirect_https === 'y') {
    // Get the current script name
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    
    // List of all possible index filenames
    $index_files = ['index.php', 'index1.php', 'index2.php', 'index3.php', 'index4.php'];
    
    // Check if current script is one of the index files
    if (in_array($current_script, $index_files)) {
        $is_secure = isSecure();
        
        // Debug logging
        error_log('=== HTTPS DEBUG ===');
        error_log('Current Script: ' . $current_script);
        error_log('Is Secure: ' . ($is_secure ? 'Yes' : 'No'));
        error_log('SERVER: ' . print_r([
            'HTTPS' => $_SERVER['HTTPS'] ?? 'Not set',
            'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'Not set',
            'HTTP_X_FORWARDED_SSL' => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'Not set',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'Not set',
            'HTTP_CF_VISITOR' => $_SERVER['HTTP_CF_VISITOR'] ?? 'Not set',
            'HTTP_X_FORWARDED_PROTOCOL' => $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] ?? 'Not set',
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not set',
            'HTTP_CF_RAY' => $_SERVER['HTTP_CF_RAY'] ?? 'Not set',
            'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Not set'
        ], true));
        
        if (!$is_secure && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
            // Ensure we have a valid host
            $host = $_SERVER['HTTP_HOST'];
            $request_uri = $_SERVER['REQUEST_URI'];
            
            // Check for Cloudflare's HTTPS header first
            if (isset($_SERVER['HTTP_CF_VISITOR'])) {
                $cf_visitor = @json_decode($_SERVER['HTTP_CF_VISITOR'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($cf_visitor) && isset($cf_visitor['scheme'])) {
                    $scheme = $cf_visitor['scheme'];
                }
            }
            
            // If no scheme from Cloudflare, use HTTP_X_FORWARDED_PROTO if available
            if (!isset($scheme) && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            }
            
            // Default to https if no scheme detected
            $scheme = $scheme ?? 'https';
            
            // Only redirect if not already on HTTPS
            if ($scheme === 'http') {
                $redirect_url = "https://$host$request_uri";
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $redirect_url");
                exit();
            }
        }
    }
}

if (!function_exists('pt_base64url_encode')) {
    function pt_base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('pt_base64url_decode')) {
    function pt_base64url_decode($data)
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('pt_normalize_host')) {
    function pt_normalize_host($host)
    {
        if (!is_string($host) || $host === '') {
            return '';
        }

        $host = trim(strtolower($host));
        if (strpos($host, ',') !== false) {
            $parts = explode(',', $host);
            $host = trim($parts[0]);
        }

        $parsedHost = '';
        $parsedPort = '';

        if (strpos($host, '://') !== false) {
            $parsedHost = (string)parse_url($host, PHP_URL_HOST);
            $parsedPort = (string)parse_url($host, PHP_URL_PORT);
            if ($parsedHost !== '') {
                $host = $parsedHost;
            }
        } else {
            // Parse host:port when no scheme is present.
            if (preg_match('/^\[(.+)\](?::(\d+))?$/', $host, $m)) {
                $host = $m[1];
                $parsedPort = $m[2] ?? '';
            } elseif (preg_match('/^([^:]+):(\d+)$/', $host, $m)) {
                $host = $m[1];
                $parsedPort = $m[2];
            }
        }

        $host = trim($host, " \t\n\r\0\x0B.");
        $host = strtolower($host);

        // Preserve explicit port for local testing hosts only.
        $isLocalHost = in_array($host, array('localhost', '127.0.0.1', '::1'), true);
        if ($isLocalHost && $parsedPort !== '' && ctype_digit($parsedPort)) {
            return $host . ':' . (int)$parsedPort;
        }

        return $host;
    }
}

if (!function_exists('pt_split_host_port')) {
    function pt_split_host_port($host)
    {
        $host = pt_normalize_host($host);
        if ($host === '') {
            return array('', '');
        }

        if (preg_match('/^(.+):(\d+)$/', $host, $m)) {
            return array($m[1], $m[2]);
        }

        return array($host, '');
    }
}

if (!function_exists('pt_hosts_match')) {
    function pt_hosts_match($hostA, $hostB)
    {
        $a = pt_normalize_host($hostA);
        $b = pt_normalize_host($hostB);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        list($aHost, $aPort) = pt_split_host_port($a);
        list($bHost, $bPort) = pt_split_host_port($b);

        if ($aHost !== $bHost) {
            return false;
        }

        // For localhost testing, allow matching with or without explicit port.
        if (in_array($aHost, array('localhost', '127.0.0.1', '::1'), true)) {
            if ($aPort === '' || $bPort === '') {
                return true;
            }
            return $aPort === $bPort;
        }

        return false;
    }
}

if (!function_exists('pt_get_request_host')) {
    function pt_get_request_host()
    {
        global $pt;

        $raw = '';

        // Prefer direct host header so local host:port (e.g. 127.0.0.1:8080) is preserved.
        if (!empty($_SERVER['HTTP_HOST'])) {
            $raw = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $raw = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $raw = $_SERVER['SERVER_NAME'];
        }

        $host = pt_normalize_host($raw);
        if ($host === '') {
            return '';
        }

        list($hostOnly, $hostPort) = pt_split_host_port($host);
        if ($hostPort === '' && in_array($hostOnly, array('localhost', '127.0.0.1', '::1'), true)) {
            $forwardedPort = trim((string)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
            if ($forwardedPort !== '' && ctype_digit($forwardedPort)) {
                return $hostOnly . ':' . (int)$forwardedPort;
            }

            $siteUrl = isset($pt->config->site_url) ? (string)$pt->config->site_url : '';
            $siteHost = pt_normalize_host($siteUrl);
            if ($siteHost !== '') {
                list($siteHostOnly, $siteHostPort) = pt_split_host_port($siteHost);
                if ($siteHostPort !== '' && $siteHostOnly === $hostOnly) {
                    return $hostOnly . ':' . $siteHostPort;
                }
            }

            $serverPort = trim((string)($_SERVER['SERVER_PORT'] ?? ''));
            if ($serverPort !== '' && ctype_digit($serverPort) && !in_array((int)$serverPort, array(80, 443), true)) {
                return $hostOnly . ':' . (int)$serverPort;
            }
        }

        return $host;
    }
}

if (!function_exists('pt_table_exists')) {
    function pt_table_exists($tableName)
    {
        static $cache = array();

        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            $cache[$tableName] = false;
            return false;
        }

        $safeTableName = addslashes($tableName);
        $res = $db->query("SHOW TABLES LIKE '{$safeTableName}'");
        $cache[$tableName] = $res && !$res->error && $res->count > 0;

        return $cache[$tableName];
    }
}

if (!function_exists('pt_column_exists')) {
    function pt_column_exists($tableName, $columnName)
    {
        static $cache = array();
        $key = $tableName . ':' . $columnName;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if (!pt_table_exists($tableName)) {
            $cache[$key] = false;
            return false;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            $cache[$key] = false;
            return false;
        }

        $safeColumnName = addslashes($columnName);
        $res = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$safeColumnName}'");
        $cache[$key] = $res && !$res->error && $res->count > 0;

        return $cache[$key];
    }
}

if (!function_exists('pt_get_setting_option')) {
    function pt_get_setting_option($optionName, $default = '')
    {
        $settingsInstance = PT_Settings::instance();
        $value = $settingsInstance->get($optionName);
        if ($value !== false && $value !== '') {
            return $value;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return $default;
        }

        $settingsTable = $db->db_pr . 'settings';
        if (!pt_table_exists($settingsTable)) {
            return $default;
        }

        $safeOptionName = addslashes($optionName);
        $res = $db->query("SELECT option_value FROM `{$settingsTable}` WHERE option_name = '{$safeOptionName}' LIMIT 1");
        if ($res && !$res->error && $res->count > 0) {
            $row = $res->result_row();
            if (isset($row['option_value']) && $row['option_value'] !== '') {
                return $row['option_value'];
            }
        }

        return $default;
    }
}

if (!function_exists('pt_get_item_id_by_invoice')) {
    function pt_get_item_id_by_invoice($idInvoice)
    {
        $idInvoice = (int)$idInvoice;
        if ($idInvoice <= 0) {
            return false;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $invoiceItemsTable = $db->db_pr . 'invoice_items';
        if (!pt_table_exists($invoiceItemsTable)) {
            return false;
        }

        $res = $db->query("SELECT itemItem FROM `{$invoiceItemsTable}` WHERE idInvoice = '{$idInvoice}' ORDER BY idItem ASC LIMIT 1");
        if ($res && !$res->error && $res->count > 0) {
            return trim((string)$res->result_row('itemItem'));
        }

        return false;
    }
}

if (!function_exists('pt_get_invoice_checkout_domain')) {
    function pt_get_invoice_checkout_domain($idInvoice)
    {
        $idInvoice = (int)$idInvoice;
        if ($idInvoice <= 0) {
            return false;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $invoiceTable = $db->db_pr . 'invoices';
        if (!pt_table_exists($invoiceTable) || !pt_column_exists($invoiceTable, 'checkout_domain')) {
            return false;
        }

        $res = $db->query("SELECT checkout_domain FROM `{$invoiceTable}` WHERE idInvoice = '{$idInvoice}' LIMIT 1");
        if ($res && !$res->error && $res->count > 0) {
            $value = pt_normalize_host((string)$res->result_row('checkout_domain'));
            return $value !== '' ? $value : false;
        }

        return false;
    }
}

if (!function_exists('pt_set_invoice_checkout_domain')) {
    function pt_set_invoice_checkout_domain($idInvoice, $domain)
    {
        $idInvoice = (int)$idInvoice;
        $domain = pt_normalize_host($domain);

        if ($idInvoice <= 0 || $domain === '') {
            return false;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $invoiceTable = $db->db_pr . 'invoices';
        if (!pt_table_exists($invoiceTable) || !pt_column_exists($invoiceTable, 'checkout_domain')) {
            return false;
        }

        $safeDomain = addslashes($domain);
        $res = $db->query("UPDATE `{$invoiceTable}` SET checkout_domain = '{$safeDomain}' WHERE idInvoice = '{$idInvoice}'");

        return $res && !$res->error;
    }
}

if (!function_exists('pt_get_active_domains_for_item')) {
    function pt_get_active_domains_for_item($itemId)
    {
        $itemId = trim((string)$itemId);
        if ($itemId === '') {
            return array();
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return array();
        }

        $domainsTable = $db->db_pr . 'domains';
        $itemDomainsTable = $db->db_pr . 'item_domains';

        if (!pt_table_exists($domainsTable) || !pt_table_exists($itemDomainsTable)) {
            return array();
        }

        $safeItemId = addslashes($itemId);
        $sql = "SELECT d.domain
                FROM `{$itemDomainsTable}` AS i
                INNER JOIN `{$domainsTable}` AS d ON d.id = i.domain_id
                WHERE i.item_id = '{$safeItemId}' AND d.is_active = '1'";

        $res = $db->query($sql);
        if (!$res || $res->error || $res->count < 1) {
            return array();
        }

        $domains = array();
        foreach ($res->result_array() as $row) {
            $host = pt_normalize_host($row['domain'] ?? '');
            if ($host !== '') {
                $domains[$host] = $host;
            }
        }

        return array_values($domains);
    }
}

if (!function_exists('pt_is_domain_allowed_for_item')) {
    function pt_is_domain_allowed_for_item($host, $itemId)
    {
        $host = pt_normalize_host($host);
        if ($host === '') {
            return false;
        }

        $domains = pt_get_active_domains_for_item($itemId);
        if (empty($domains)) {
            return false;
        }

        foreach ($domains as $domain) {
            if (pt_hosts_match($host, $domain)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('pt_get_default_checkout_domain')) {
    function pt_get_default_checkout_domain()
    {
        $domain = pt_normalize_host((string)pt_get_setting_option('primary_checkout_domain', ''));
        if ($domain === '') {
            return false;
        }

        $db = new PT_Db();
        if (!$db->is_connected) {
            return $domain;
        }

        $domainsTable = $db->db_pr . 'domains';
        if (!pt_table_exists($domainsTable)) {
            return $domain;
        }

        $safeDomain = addslashes($domain);
        $res = $db->query("SELECT id FROM `{$domainsTable}` WHERE domain = '{$safeDomain}' AND is_active = '1' LIMIT 1");
        if ($res && !$res->error && $res->count > 0) {
            return $domain;
        }

        return false;
    }
}

if (!function_exists('pt_select_rotated_domain')) {
    function pt_select_rotated_domain($itemId, $idInvoice = 0)
    {
        $itemId = trim((string)$itemId);
        $idInvoice = (int)$idInvoice;

        if ($itemId === '') {
            return false;
        }

        $domains = pt_get_active_domains_for_item($itemId);
        if (empty($domains)) {
            $fallback = pt_get_default_checkout_domain();
            if ($fallback !== false) {
                $domains = array($fallback);
            }
        }

        if (empty($domains)) {
            return false;
        }

        if ($idInvoice > 0) {
            $existing = pt_get_invoice_checkout_domain($idInvoice);
            if ($existing !== false) {
                foreach ($domains as $domain) {
                    if (pt_hosts_match($existing, $domain)) {
                        return $existing;
                    }
                }
            }
        }

        $selected = $domains[array_rand($domains)];
        if ($idInvoice > 0) {
            pt_set_invoice_checkout_domain($idInvoice, $selected);
        }

        return $selected;
    }
}

if (!function_exists('pt_get_domain_token_secret')) {
    function pt_get_domain_token_secret()
    {
        $secret = (string)pt_get_setting_option('domain_rotation_token_secret', '');
        if ($secret === '') {
            $secret = defined('SALT') ? SALT : 'domain-rotation-secret';
        }
        return $secret;
    }
}

if (!function_exists('pt_build_domain_redirect_token')) {
    function pt_build_domain_redirect_token($itemId, $idInvoice, $domain)
    {
        $itemId = trim((string)$itemId);
        $idInvoice = (int)$idInvoice;
        $domain = pt_normalize_host($domain);

        if ($itemId === '' || $domain === '') {
            return false;
        }

        $payload = array(
            'item_id' => $itemId,
            'id_invoice' => $idInvoice,
            'domain' => $domain,
            'nonce' => bin2hex(random_bytes(8))
        );

        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            return false;
        }

        $encodedPayload = pt_base64url_encode($payloadJson);
        $signature = hash_hmac('sha256', $encodedPayload, pt_get_domain_token_secret(), true);

        return $encodedPayload . '.' . pt_base64url_encode($signature);
    }
}

if (!function_exists('pt_verify_domain_redirect_token')) {
    function pt_verify_domain_redirect_token($token, $expectedItemId = '', $expectedDomain = '', $expectedInvoiceId = null)
    {
        if (!is_string($token) || strpos($token, '.') === false) {
            return false;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }

        list($encodedPayload, $encodedSignature) = $parts;
        $rawSignature = pt_base64url_decode($encodedSignature);
        if ($rawSignature === false || $rawSignature === '') {
            return false;
        }

        $expectedRawSignature = hash_hmac('sha256', $encodedPayload, pt_get_domain_token_secret(), true);
        if (!hash_equals($expectedRawSignature, $rawSignature)) {
            return false;
        }

        $payloadJson = pt_base64url_decode($encodedPayload);
        if ($payloadJson === false || $payloadJson === '') {
            return false;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return false;
        }

        if ($expectedItemId !== '' && (string)($payload['item_id'] ?? '') !== (string)$expectedItemId) {
            return false;
        }

        if ($expectedDomain !== '' && !pt_hosts_match((string)($payload['domain'] ?? ''), $expectedDomain)) {
            return false;
        }

        if ($expectedInvoiceId !== null && (int)($payload['id_invoice'] ?? 0) !== (int)$expectedInvoiceId) {
            return false;
        }

        return $payload;
    }
}

if (!function_exists('pt_get_request_protocol')) {
    function pt_get_request_protocol()
    {
        $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto !== '') {
            $parts = explode(',', $forwardedProto);
            $forwardedProto = strtolower(trim($parts[0]));
            if (in_array($forwardedProto, array('http', 'https'), true)) {
                return $forwardedProto;
            }
        }

        return (($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http';
    }
}

if (!function_exists('pt_get_current_request_path')) {
    function pt_get_current_request_path($fallback = '/index.php')
    {
        $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path === '') {
            $path = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        }
        if ($path === '') {
            $path = $fallback;
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }
}

if (!function_exists('pt_redirect_to_checkout_domain_or_false')) {
    function pt_redirect_to_checkout_domain_or_false($itemId, $idInvoice = 0, $targetHost = false, $path = null)
    {
        $itemId = trim((string)$itemId);
        $idInvoice = (int)$idInvoice;
        $currentHost = pt_get_request_host();
        $targetHost = $targetHost === false ? pt_select_rotated_domain($itemId, $idInvoice) : pt_normalize_host($targetHost);

        if ($itemId === '' || $currentHost === '' || $targetHost === false || $targetHost === '') {
            return false;
        }

        if (pt_hosts_match($currentHost, $targetHost) || headers_sent()) {
            return false;
        }

        $token = pt_build_domain_redirect_token($itemId, $idInvoice, $targetHost);
        if ($token === false) {
            return false;
        }

        $query = array();
        $passthroughKeys = array('service', 'item_id', 'idInvoice', 'lp', 'country', 'ctc', 'clickid', 'source', 'from_go');
        foreach ($passthroughKeys as $key) {
            if (isset($_GET[$key]) && trim((string)$_GET[$key]) !== '') {
                $query[$key] = trim((string)$_GET[$key]);
            }
        }

        $query['service'] = $itemId;
        if ($idInvoice > 0) {
            $query['idInvoice'] = $idInvoice;
        } else {
            unset($query['idInvoice']);
        }
        $query['drt'] = $token;
        $query['from_go'] = 1;

        $redirectPath = $path === null ? pt_get_current_request_path('/index.php') : (string)$path;
        if ($redirectPath === '') {
            $redirectPath = '/index.php';
        }
        if ($redirectPath[0] !== '/') {
            $redirectPath = '/' . $redirectPath;
        }

        $destination = pt_get_request_protocol() . '://' . $targetHost . $redirectPath . '?' . http_build_query($query);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Location: ' . $destination, true, 302);
        exit;
    }
}

if (!function_exists('pt_forbidden_exit')) {
    function pt_forbidden_exit()
    {
        http_response_code(403);
        exit;
    }
}

if (!function_exists('pt_enforce_domain_access_or_exit')) {
    function pt_enforce_domain_access_or_exit($itemId = '', $idInvoice = 0, $token = '', $requireToken = true)
    {
        $itemId = trim((string)$itemId);
        $idInvoice = (int)$idInvoice;
        $token = trim((string)$token);

        if ($itemId === '' && $idInvoice > 0) {
            $resolvedItemId = pt_get_item_id_by_invoice($idInvoice);
            if ($resolvedItemId !== false) {
                $itemId = $resolvedItemId;
            }
        }

        if ($itemId === '') {
            return true;
        }

        $currentHost = pt_get_request_host();
        if ($currentHost === '') {
            pt_forbidden_exit();
        }

        $allowedDomains = pt_get_active_domains_for_item($itemId);
        if (empty($allowedDomains)) {
            $fallback = pt_get_default_checkout_domain();
            if ($fallback !== false) {
                $allowedDomains = array($fallback);
            }
        }

        // If no domain rotation records exist yet, keep legacy flow active.
        if (empty($allowedDomains)) {
            return true;
        }

        $isAllowed = false;
        foreach ($allowedDomains as $allowedDomain) {
            if (pt_hosts_match($currentHost, $allowedDomain)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            if (pt_redirect_to_checkout_domain_or_false($itemId, $idInvoice)) {
                return true;
            }
            pt_forbidden_exit();
        }

        if ($idInvoice > 0) {
            $savedHost = pt_get_invoice_checkout_domain($idInvoice);
            if ($savedHost !== false && !pt_hosts_match($savedHost, $currentHost)) {
                $savedHostAllowed = false;
                foreach ($allowedDomains as $allowedDomain) {
                    if (pt_hosts_match($savedHost, $allowedDomain)) {
                        $savedHostAllowed = true;
                        break;
                    }
                }

                if ($savedHostAllowed) {
                    if (pt_redirect_to_checkout_domain_or_false($itemId, $idInvoice, $savedHost)) {
                        return true;
                    }
                    pt_forbidden_exit();
                }

                pt_set_invoice_checkout_domain($idInvoice, $currentHost);
            }
        }

        if ($requireToken) {
            if ($token === '') {
                pt_forbidden_exit();
            }

            $payload = pt_verify_domain_redirect_token(
                $token,
                $itemId,
                $currentHost,
                $idInvoice > 0 ? $idInvoice : null
            );

            if ($payload === false) {
                pt_forbidden_exit();
            }
        }

        if ($idInvoice > 0) {
            $savedHost = pt_get_invoice_checkout_domain($idInvoice);
            if ($savedHost === false) {
                pt_set_invoice_checkout_domain($idInvoice, $currentHost);
            }
        }

        return true;
    }
}
