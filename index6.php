<?php

/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c) CriticalGears.io
 */

$__adaptiveLpId = trim((string)($_GET['lp'] ?? ''));
$__adaptiveLpDataFile = __DIR__ . '/templates/form/adaptive-lp/data/landing-pages.json';
$__adaptiveLpExists = false;

if ($__adaptiveLpId !== '' && is_file($__adaptiveLpDataFile)) {
    $__adaptiveLpPayload = json_decode(file_get_contents($__adaptiveLpDataFile), true);
    $__adaptiveLpPages = isset($__adaptiveLpPayload['landingPages']) && is_array($__adaptiveLpPayload['landingPages'])
        ? $__adaptiveLpPayload['landingPages']
        : [];

    foreach ($__adaptiveLpPages as $__adaptiveLpPage) {
        if ((string)($__adaptiveLpPage['id'] ?? '') === $__adaptiveLpId) {
            $__adaptiveLpExists = true;
            break;
        }
    }
}

if (!$__adaptiveLpExists) {
    http_response_code(200);
    exit;
}

// Load core functions before enforcing rotated-domain access.
@include_once "includes/error_handler.php";
if (!@include_once("includes/bootstrap.php")) {
    header('HTTP/1.1 404 Not Found');
    exit;
}
include_once "includes/countries.php";

$__ptDomainItemId = trim((string)($_GET['service'] ?? $_GET['item_id'] ?? ''));
$__ptDomainInvoiceId = isset($_GET['idInvoice']) ? (int)$_GET['idInvoice'] : 0;
$__ptDomainToken = trim((string)($_GET['drt'] ?? $_POST['drt'] ?? ''));
pt_enforce_domain_access_or_exit($__ptDomainItemId, $__ptDomainInvoiceId, $__ptDomainToken, true);

// 1. Language and Country Detection
// This logic is now centralized in includes/language_utils.php
include_once "includes/language_utils.php";

// Ensure global variables expected by the rest of the script are set
if (!isset($lang)) {
    $lang = $_SESSION['site_lang'] ?? 'en';
}
if (!isset($detectedCountry)) {
    $detectedCountry = getCountryFromIP();
}

// Include country detector
$detectorPath = __DIR__ . '/includes/country_detector.php';
$detectedCountry = 'US'; // Default country

// Try to include and use the country detector
if (file_exists($detectorPath) && is_readable($detectorPath)) {
    if (!function_exists('get_user_country')) {
        @include_once $detectorPath;
    }

    if (function_exists('get_user_country')) {
        try {
            $country = get_user_country();
            if (!empty($country) && preg_match('/^[A-Z]{2}$/', strtoupper(trim($country)))) {
                $detectedCountry = strtoupper(trim($country));
                error_log("Using country from detector: " . $detectedCountry);
            } else {
                error_log("Invalid country from detector, using default (US)");
            }
        } catch (Exception $e) {
            error_log("Error in get_user_country(): " . $e->getMessage());
        }
    } else {
        error_log("get_user_country() function not found, using IP detection");
        $detectedCountry = getCountryFromIP();
    }
} else {
    error_log("Country detector not found at: " . $detectorPath . ", using IP detection");
    $detectedCountry = getCountryFromIP();
}

// Final validation
if (!preg_match('/^[A-Z]{2}$/', $detectedCountry)) {
    $detectedCountry = 'US'; // Fallback to US if still invalid
}

// Enable error reporting for development (disable in production)
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent headers already sent issues
ob_start();

// Define debug mode (set to false in production)
define('DEBUG_MODE', false);

// Function to handle errors consistently
function handleError($message, $code = 400, $logError = true)
{
    if ($logError) {
        error_log("Payment Terminal Error [$code]: " . $message);
    }

    if (DEBUG_MODE) {
        die("<div style='padding:20px;font-family:Arial;color:#721c24;background-color:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;margin:20px;'>" .
            "<h3 data-i18n=\"error_message\">Error: " . htmlspecialchars($message) . "</h3>" .
            "<p>Service ID: " . htmlspecialchars($_GET['service'] ?? 'Not provided') . "</p>" .
            "<p>Time: " . date('Y-m-d H:i:s') . "</p>" .
            "</div>");
    }

    // In production, show a generic error or redirect to an error page
    header('HTTP/1.1 404 Not Found');
    exit;
}

// Initialize custom mode flag
$isCustomMode = false;

if (!isset($settings) || !is_object($settings)) {
    $settings = PT_Settings::instance();
}
if (!isset($user) || !is_object($user)) {
    $user = PT_User::instance();
}

// =====================
// DEBUGGING - Check server variables
// =====================error_log("=== SERVER VARIABLE DEBUG ===");
error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET'));
error_log("HTTP_X_FORWARDED_HOST: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'NOT SET'));
error_log("SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET'));
error_log("HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'NOT SET'));
error_log("HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET'));
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET'));
error_log("PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET'));

// =====================
// Theme router (early)
// =====================
try {
    $effectiveTheme = $settings->selected_theme;

    if (!$isCustomMode && !empty($_GET['service'])) {
        $serviceIdForTheme = trim($_GET['service']);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $serviceIdForTheme)) {
            $stmtTheme = $mysqli->prepare("SELECT itemDesign FROM " . DB_PREFIX . "items WHERE LOWER(idItem)=LOWER(?) LIMIT 1");
            if ($stmtTheme) {
                $stmtTheme->bind_param("s", $serviceIdForTheme);
                $stmtTheme->execute();
                $resTheme = $stmtTheme->get_result();
                if ($rowTheme = $resTheme->fetch_assoc()) {
                    if (!empty($rowTheme['itemDesign'])) {
                        $effectiveTheme = $rowTheme['itemDesign'];
                    }
                }
                $stmtTheme->close();
            }
        }
    }

    if (basename($_SERVER['PHP_SELF']) === 'index6.php') {
        $effectiveTheme = 'adaptive-lp';
    }

    $isCustomMode = ($settings->theme_type === 'custom');

    error_log("Effective Theme: " . $effectiveTheme);
    error_log("Current File: " . basename($_SERVER['PHP_SELF']));

    // Handle theme-specific routing
    if ($effectiveTheme === 'adaptive-lp' && basename($_SERVER['PHP_SELF']) !== 'index6.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $redirectUrl = '/index6.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    } elseif ($effectiveTheme === 'Minimalist' && basename($_SERVER['PHP_SELF']) !== 'index2.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';

        // Use X-Forwarded-Host if available, otherwise fall back to HTTP_HOST
        $host = pt_get_request_host();
        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
        $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';

        $redirectUrl = '/index2.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    } elseif ($effectiveTheme === 'Colorful' && basename($_SERVER['PHP_SELF']) !== 'index1.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';

        $host = pt_get_request_host();
        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
        $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';

        $redirectUrl = '/index1.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    } elseif ($effectiveTheme === 'Normal' && basename($_SERVER['PHP_SELF']) !== 'index4.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';

        $host = pt_get_request_host();
        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
        $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';

        $redirectUrl = '/index4.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    } elseif ($effectiveTheme === 'PhysicalProduct' && basename($_SERVER['PHP_SELF']) === 'index.php') {
        // Only redirect if we're not already on index3.php
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $redirectUrl = 'index3.php' . $queryString;
        header('Location: ' . $redirectUrl);
        exit;
    } elseif (!in_array($effectiveTheme, ['adaptive-lp', 'Minimalist', 'Colorful']) && basename($_SERVER['PHP_SELF']) !== 'index.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';

        $host = pt_get_request_host();
        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
        $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';

        $redirectUrl = '/index.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    }

    $settings->selected_theme = $effectiveTheme;
} catch (Throwable $e) {
    error_log("Theme routing error: " . $e->getMessage());
}

// Validate service parameter
if (!isset($_GET['service']) || empty(trim($_GET['service']))) {
    handleError("<span data-i18n=\"service_parameter_missing\">Service parameter is missing or empty</span>");
}

// Get and sanitize the service ID from URL
$serviceId = trim($_GET['service']);

// Validate UUID format
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $serviceId)) {
    handleError("<span data-i18n=\"invalid_service_id\">Invalid service ID format</span>");
}

// Check database connection
global $mysqli;
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    handleError("<span data-i18n=\"database_connection_error\">Database connection error. Please check your database configuration.</span>", 500);
}

// Check if service exists in the database
try {
    // Using LOWER() for case-insensitive comparison
    $query = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "items WHERE LOWER(idItem) = LOWER(?) AND itemStatus = 'y' LIMIT 1";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("s", $serviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (DEBUG_MODE) {
            error_log("Service check - ID: $serviceId, Found: " . ($row['count'] > 0 ? 'Yes' : 'No'));
        }

        if (!($row && $row['count'] > 0)) {
            handleError("<span data-i18n=\"service_not_found\">Service not found or inactive.</span> Service ID: " . htmlspecialchars($serviceId), 404);
        }

        $stmt->close();
    } else {
        handleError("Database query preparation failed: " . $mysqli->error, 500);
    }
} catch (Exception $e) {
    handleError("Database error while checking service: " . $e->getMessage(), 500);
}

$payment = new PT_Stripe_Payment();

// Set the theme identifier based on the effective theme
$__theme = $effectiveTheme;

echo "<script>window.currentTheme = '" . addslashes($__theme) . "';</script>" . "\n";


// Helper to resolve themed template with fallback
if (!function_exists('pt_resolve_template')) {
    function pt_resolve_template($path, $theme)
    {
        global $settings;

        // If no theme specified, use the current theme from settings
        if (empty($theme)) {
            $theme = !empty($settings->selected_theme) ? $settings->selected_theme : 'CardStyle';
        }

        // Check if this is a form template that might have a theme override
        if (strpos($path, 'form/') === 0) {
            $filename = basename($path);

            // First try the theme-specific template
            $themedPath = HOME_DIR . "/templates/form/{$theme}/{$filename}";
            if (is_file($themedPath)) {
                return "form/{$theme}/{$filename}";
            }

            // Then try the default template location
            $defaultPath = HOME_DIR . "/templates/{$path}";
            if (is_file($defaultPath)) {
                return $path;
            }
        }

        // If not a form template or no theme override found, return original path
        return $path;
    }
}

// Create header with theme info
$header = new PT_Template("header.php", [
    'title' => $settings->page_title,
    'logo' => '' !== $settings->terminal_logo ? $settings->siteUrl() . $settings->terminal_logo : "",
    'terminal_payment_mode' => $settings->terminal_payment_mode,
    'selected_theme' => $effectiveTheme,
    'custom_theme' => false
]);

$notice = $settings->terminal_payment_mode == 'test' ? "<span data-i18n=\"test_mode_enabled\">Test Mode Enabled. No real transactions will happen - all transaction will be charged in sandbox mode.</span>" : "";

if ($settings->terminal_payment_mode == 'test') {
    if (strlen($settings->test_secret_key) < 10 || strlen($settings->test_public_key) < 10) {
        $notice .= "<br><strong data-i18n=\"api_credentials_missing\">API Test credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a></strong>";
    } elseif ($settings->test_secret_key == 'YOUR STRIPE SECRET KEY FOR TEST MODE') {
        $notice .= "<br><strong data-i18n=\"api_credentials_missing\">API Test credentials are missing! Please set up credentials on includes/config.php.</a></strong>";
    }
} elseif ($settings->terminal_payment_mode == 'live') {
    if (strlen($settings->live_secret_key) < 10 || strlen($settings->live_public_key) < 10) {
        $notice .= "<br><strong data-i18n=\"api_credentials_missing\">API Live credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a></strong>";
    } elseif ($settings->live_public_key == 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE') {
        $notice .= "<br><strong data-i18n=\"api_credentials_missing\">API Live credentials are missing! Please set up credentials on includes/config.php.</a></strong>";
    }
}

$https = true;
if ($settings->terminal_payment_mode != 'test' && !isSecure()) {
    $notice = "<span data-i18n=\"https_not_configured\">HTTPS isn't properly configured. Please ensure your server is configured to handle HTTPS connections.</span>";
    error_log('HTTPS Check Failed - Headers: ' . print_r([
        'HTTPS' => $_SERVER['HTTPS'] ?? 'Not set',
        'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'Not set',
        'HTTP_X_FORWARDED_SSL' => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'Not set',
        'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'Not set',
        'HTTP_CF_VISITOR' => $_SERVER['HTTP_CF_VISITOR'] ?? 'Not set'
    ], true));
    $https = false;
}

$header->notice = $notice;

$cvv_info = new PT_Template("cvv_info.php");
$popup = new PT_Template("popup.php");

$pt_action = $c->_esc("pt_action");
$invoice = $c->_esc("invoice", 0);
$idInvoice = $c->_esc("idInvoice", 0);
$amount = $c->_esc("amount");
$pt_amount = $c->_esc("pt_amount", $amount);
$currency = $c->_esc("currency");
$pt_currency = $c->_esc("pt_currency", $currency);
$pt_currency_symbol = $c->_esc("pt_currency_symbol", "");
$pt_currency_position = $c->_esc("pt_currency_position", "");
$pt_service = $c->_esc("pt_service");
$service = $c->_esc("service");

// Load service details if service ID is provided
$is_trial = false;
$service_data = null;

if (!empty($service)) {
    $itemModel = new itemModel();
    $itemModel->setID($service);
    if ($itemModel->getItem()) {
        $service_data = $itemModel->itemData;
        $is_trial = isset($service_data['itemTrial']) && $service_data['itemTrial'] === 'y' && !empty($service_data['itemTrialDays']);
    }
}


// Fetch dynamic currency data if available
$countryCodeForCurrency = !empty($_GET['country']) ? $_GET['country'] : $detectedCountry;
$ctcForCurrency = !empty($_GET['ctc']) ? $_GET['ctc'] : '2'; // Default to '2' as discussed
$serviceIdForCurrency = $serviceId;

$fetchedCurrencyData = null;
if (!empty($countryCodeForCurrency) && !empty($ctcForCurrency) && !empty($serviceIdForCurrency)) {
    try {
        $fetchedCurrencyData = getConvenientCurrencyData($countryCodeForCurrency, $ctcForCurrency, $serviceIdForCurrency);
        $_SESSION['api_currency_data'] = $fetchedCurrencyData;
        error_log('Fetched adaptive LP currencyData in index6.php: ' . print_r($fetchedCurrencyData, true));

        if (isset($fetchedCurrencyData['subscription_amount']['currency_code'])) {
            $pt_currency = $fetchedCurrencyData['subscription_amount']['currency_code'];
        }
        if (isset($fetchedCurrencyData['subscription_amount']['currency_symbol'])) {
            $pt_currency_symbol = $fetchedCurrencyData['subscription_amount']['currency_symbol'];
        }
    } catch (Throwable $e) {
        error_log('Error fetching convenient currency data in index6.php: ' . $e->getMessage());
        $c->addWarning('<span data-i18n="currency_data_unavailable">Could not fetch real-time currency data. Using default values.</span>');
    }
}

error_log('index6.php - pt_currency: ' . $pt_currency);
error_log('index6.php - pt_currency_symbol: ' . $pt_currency_symbol);
error_log('index6.php - pt_currency_position: ' . $pt_currency_position);


// Create footer with trial information after service is loaded
$footer = new PT_Template("footer.php", [
    'is_trial' => $is_trial,
    'service' => $service_data
]);
$pt_payment_type = $c->_esc("pt_payment_type", 'once');
$pt_payments_count = $c->_esc("pt_payments_count", 0);

// Optional image URL from query (?image=...), fallback to default
$image_param = $c->_esc("image", "");
$image_src = $image_param !== "" ? $image_param : "assets/images/hand.png";

// Get customer identity from URL to use in payment when billing section is hidden
$url_first = $c->_esc("first", "");
$url_last = $c->_esc("last", "");
$url_full_name = $c->_esc("full_name", "");
$url_name_primary = $c->_esc("name", "");
$url_name_alt = $c->_esc("customer_name", "");
$url_email_primary = $c->_esc("email", "");
$url_email_alt = $c->_esc("customer_email", "");
$url_name = trim($url_full_name !== '' ? $url_full_name : ($url_name_primary !== '' ? $url_name_primary : ($url_name_alt !== '' ? $url_name_alt : trim($url_first . ' ' . $url_last))));
$url_email = ($url_email_primary !== '' ? $url_email_primary : $url_email_alt);

$pt_name = $c->_esc("pt_name", st_apply_filter('form_customer_name', ''));
$pt_email = $c->_esc("pt_email", st_apply_filter('form_customer_email', ''));

// Resolve final name/email: prefer existing pt_* values, then GET pt_*, then GET name/email
$pt_name_resolved = !empty($pt_name) ? $pt_name : ($c->_esc("pt_name", $url_name));
$pt_email_resolved = !empty($pt_email) ? $pt_email : ($c->_esc("pt_email", $url_email));

$pt_description = $c->_esc("pt_description");
$pt_address1 = $c->_esc("pt_address1", st_apply_filter('form_customer_address1', ''));
$pt_address2 = $c->_esc("pt_address2", st_apply_filter('form_customer_address2', ''));
$pt_city = $c->_esc("pt_city", st_apply_filter('form_customer_city', ''));
$pt_country = $c->_esc("pt_country", st_apply_filter('form_customer_country', ''));
$pt_state = $c->_esc("pt_state", st_apply_filter('form_customer_state', ''));
$pt_postal = $c->_esc("pt_postal", st_apply_filter('form_customer_postal', ''));
$pt_country = $c->_esc("pt_country", st_apply_filter('form_customer_country', ''));

$pt_shipping_same = $c->_esc("pt_shipping_same", "n");

if ($pt_shipping_same == 'y') {
    $pt_address1_s = $c->post['pt_address1_s'] = $pt_address1;
    $pt_address2_s = $c->post['pt_address2_s'] = $pt_address2;
    $pt_city_s = $c->post['pt_city_s'] = $pt_city;
    $pt_country_s = $c->post['pt_country_s'] = $pt_country;
    $pt_state_s = $c->post['pt_state_s'] = $pt_state;
    $pt_postal_s = $c->post['pt_postal_s'] = $pt_postal;
    $pt_country_s = $c->post['pt_country_s'] = $pt_country;
} else {
    $pt_address1_s = $c->_esc("pt_address1_s");
    $pt_address2_s = $c->_esc("pt_address2_s");
    $pt_city_s = $c->_esc("pt_city_s");
    $pt_country_s = $c->_esc("pt_country_s");
    $pt_state_s = $c->_esc("pt_state_s");
    $pt_postal_s = $c->_esc("pt_postal_s");
    $pt_country_s = $c->_esc("pt_country_s");
}

$pt_type = $c->_esc("pt_type", "card");
$pt_card_name = $c->_esc("pt_card_name");

$stripeToken = $c->_esc("stripeToken");
$stripeIntent = $c->_esc("stripeIntent");
$stripeSource = $c->_esc("stripeSource");
$stripeButton = $c->_esc("stripeButton");

$stripeSubscriptionId = $c->_esc("subscription_id");

$pt_terms = $c->_esc("pt_terms", 0);

// Billing section removed for 'Minimalist' theme
$billing_info = null;

// shipping information template
$shipping_info_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post,
    'countriesList' => $payment->getCountriesListJSON(),
    'statesList' => $payment->getStatesListJSON()
]);
$shipping_info = new PT_Template(pt_resolve_template("form/shipping_info.php", $__theme), $shipping_info_data);

// Get the service ID from the URL
$serviceId = isset($_GET['service']) ? trim($_GET['service']) : '';
$pt_amount = 0;

// If we have a service ID, fetch the amount from the database
if (!empty($serviceId)) {
    global $mysqli;
    $query = "SELECT itemAmount, itemTrialUpfront, itemTrial, itemTrialDays FROM " . DB_PREFIX . "items WHERE idItem = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Store the original amount and trial settings
        $regularAmount = $row['itemAmount'];
        $upfrontFee = $row['itemTrialUpfront'];
        $hasTrial = ($row['itemTrial'] == 'y');
        $trialDays = isset($row['itemTrialDays']) ? (int) $row['itemTrialDays'] : 0;

        // Set the appropriate amount based on trial and upfront fee settings
        if ($hasTrial && $upfrontFee > 0) {
            // For trials with upfront fee, show the upfront fee initially
            $pt_amount = $upfrontFee;
            error_log("Using trial upfront amount: " . $pt_amount . " (trial period: " . $trialDays . " days)");

            // Store the regular amount for later use after trial
            $GLOBALS['regular_amount'] = $regularAmount;
            $GLOBALS['has_trial'] = true;
            $GLOBALS['trial_days'] = $trialDays;
        } else if ($hasTrial) {
            // For trials without upfront fee, show $0.00 initially
            $pt_amount = 0.00;
            error_log("Using trial with no upfront fee (trial period: " . $trialDays . " days)");

            // Store the regular amount for after trial
            $GLOBALS['regular_amount'] = $regularAmount;
            $GLOBALS['has_trial'] = true;
            $GLOBALS['trial_days'] = $trialDays;
        } else {
            // No trial, use the regular amount
            $pt_amount = $regularAmount;
            error_log("Using regular amount (no trial): " . $pt_amount);
        }

        // Format the amount and store it in the post data
        $c->post['pt_amount'] = number_format($pt_amount, 2, '.', '');
        $GLOBALS['pt_amount'] = $c->post['pt_amount'];

        error_log("Amount from database for service {$serviceId}: " . $pt_amount);
    } else {
        error_log("No service found with ID: " . $serviceId);
    }
}


// Use the fetched currency data if available
if (isset($fetchedCurrencyData['success']) && $fetchedCurrencyData['success']) {
    $hasTrial = isset($fetchedCurrencyData['subscription_amount']['period']);
    $upfrontFee = (float) $fetchedCurrencyData['upfront_amount']['amount_numeric'];
    $regularAmount = (float) $fetchedCurrencyData['subscription_amount']['amount_numeric'];

    if ($hasTrial && $upfrontFee > 0) {
        // For trials with upfront fee, show the upfront fee initially
        $pt_amount = $upfrontFee;
        error_log("Using trial upfront amount from API: " . $pt_amount);

        // Store the regular amount for later use after trial
        $GLOBALS['regular_amount'] = $regularAmount;
        $GLOBALS['has_trial'] = true;
        // Assuming trial days are not in the API response, you might need to get them from DB or set a default
        // $GLOBALS['trial_days'] = $trialDays; 
    } else if ($hasTrial) {
        // For trials without upfront fee, show $0.00 initially
        $pt_amount = 0.00;
        error_log("Using trial with no upfront fee from API");

        // Store the regular amount for after trial
        $GLOBALS['regular_amount'] = $regularAmount;
        $GLOBALS['has_trial'] = true;
        // $GLOBALS['trial_days'] = $trialDays;
    } else {
        // No trial, use the regular amount
        $pt_amount = $regularAmount;
        error_log("Using regular amount from API: " . $pt_amount);
    }

    // Format the amount and store it in the post data
    $c->post['pt_amount'] = number_format($pt_amount, 2, '.', '');
    $GLOBALS['pt_amount'] = $c->post['pt_amount'];

    error_log("Amount from API for service {$serviceId}: " . $pt_amount);
} else if (!empty($serviceId)) {
    // If we have a service ID, fetch the amount from the database (fallback)
    global $mysqli;
    $query = "SELECT itemAmount, itemTrialUpfront, itemTrial, itemTrialDays FROM " . DB_PREFIX . "items WHERE idItem = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Store the original amount and trial settings
        $regularAmount = $row['itemAmount'];
        $upfrontFee = $row['itemTrialUpfront'];
        $hasTrial = ($row['itemTrial'] == 'y');
        $trialDays = isset($row['itemTrialDays']) ? (int) $row['itemTrialDays'] : 0;

        // Set the appropriate amount based on trial and upfront fee settings
        if ($hasTrial && $upfrontFee > 0) {
            // For trials with upfront fee, show the upfront fee initially
            $pt_amount = $upfrontFee;
            error_log("Using trial upfront amount: " . $pt_amount . " (trial period: " . $trialDays . " days)");

            // Store the regular amount for later use after trial
            $GLOBALS['regular_amount'] = $regularAmount;
            $GLOBALS['has_trial'] = true;
            $GLOBALS['trial_days'] = $trialDays;
        } else if ($hasTrial) {
            // For trials without upfront fee, show $0.00 initially
            $pt_amount = 0.00;
            error_log("Using trial with no upfront fee (trial period: " . $trialDays . " days)");

            // Store the regular amount for after trial
            $GLOBALS['regular_amount'] = $regularAmount;
            $GLOBALS['has_trial'] = true;
            $GLOBALS['trial_days'] = $trialDays;
        } else {
            // No trial, use the regular amount
            $pt_amount = $regularAmount;
            error_log("Using regular amount (no trial): " . $pt_amount);
        }

        // Format the amount and store it in the post data
        $c->post['pt_amount'] = number_format($pt_amount, 2, '.', '');
        $GLOBALS['pt_amount'] = $c->post['pt_amount'];

        error_log("Amount from database for service {$serviceId}: " . $pt_amount);
    } else {
        error_log("No service found with ID: " . $serviceId);
    }
}

// payment information template
$adaptiveUpfrontAmount = $fetchedCurrencyData['upfront_amount']['amount_numeric'] ?? null;
$adaptiveUpfrontCurrencySymbol = $fetchedCurrencyData['upfront_amount']['currency_symbol'] ?? null;

$payment_info_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post,
    'pt_currency' => $pt_currency,
    'pt_currency_symbol' => $pt_currency_symbol,
    'pt_currency_position' => $pt_currency_position,
    'adaptive_upfront_amount' => $adaptiveUpfrontAmount,
    'adaptive_upfront_currency_symbol' => $adaptiveUpfrontCurrencySymbol
]);

// Add amount to payment info data if set
if (isset($pt_amount)) {
    $payment_info_data['pt_amount'] = $pt_amount;
    $payment_info_data['post']['pt_amount'] = $pt_amount;
}

$payment_info = new PT_Template(pt_resolve_template("form/payment_info.php", $__theme), $payment_info_data);

// payment type template
$payment_type_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post,
    'userLogon' => $user->logon
]);
$payment_type = new PT_Template(pt_resolve_template("form/payment_type.php", $__theme), $payment_type_data);

// Prepare bottom info template data
$bottom_info_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post,
    'pt_currency' => $pt_currency,
    'pt_currency_symbol' => $pt_currency_symbol,
    'pt_currency_position' => $pt_currency_position,
    'amount' => '0',
    'billing_period' => '',
    'trial_text' => '',
    'invoice' => $invoice
]);

// Add amount to bottom info data if set
if (isset($pt_amount)) {
    $bottom_info_data['pt_amount'] = $pt_amount;
    $bottom_info_data['post']['pt_amount'] = $pt_amount;
}

// form bottom section template
$bottom_info = new PT_Template(pt_resolve_template("form/payment_form_bottom.php", $__theme), $bottom_info_data);
$bottom_info->amount = '0';
$bottom_info->billing_period = '';
$bottom_info->trial_text = '';
$bottom_info->invoice = $invoice;
$bottom_info->selected_theme = $__theme;

$show_form = true;
$show_single_service = false;
if ($settings->payment_type != 'input') {
    if (!empty($service)) {
        $selected_service = new itemModel();
        $selected_service->setID($service);
        if ($selected_service->getItem()) {
            $show_single_service = true;
        }
    } else {
        $itemModel = new itemModel();
        $allServices = $itemModel->getItems();
        if (is_array($allServices) && count($allServices) == 1) {
            $selected_service = new itemModel();
            $selected_service->setID($allServices[0]['idItem']);
            if ($selected_service->getItem()) {
                $show_single_service = true;
            }
        }
    }
}
$currency_rates = false;
if ($settings->multiple_currencies == 'y') {
    $currency_rates = PT_Payment::get_currency_exchange_rates();
}

if ($show_single_service) {
    $order_info = new PT_Template(pt_resolve_template("form/single_service_info.php", $__theme), array("post" => $c->post));
    $order_info->service = $selected_service->itemData;
    $order_info->currency = $settings->currency_text;
    $order_info->currency_position = $settings->currency_position;
    if ($settings->multiple_currencies == 'y') {
        $order_info->show_currency_selector = $settings->multiple_currency_selector == 'y' ? true : false;
        $order_info->currency_selector_html = $payment->getHTMLCurrenciesList();
    } else {
        $order_info->show_currency_selector = false;
    }
    $order_info->currency = $settings->currency_text;
    $order_info->currency_symbol = $settings->display_currency;
    $order_info->selected_theme = $__theme;

    if ($selected_service->itemData['itemType'] == 'product') {
        if ($selected_service->itemData['itemPlan'] == 'y') {
            $bottom_info->billing_period = 'monthly';
        } else {
            $bottom_info->billing_period = '';
        }
    } elseif (!empty($selected_service->itemData['itemFrequency']) && $selected_service->itemData['itemType'] == 'service') {
        $bottom_info->billing_period = $selected_service->itemData['itemFrequency'];
    }

    if ($selected_service->itemData['itemTrial'] == 'y') {
        $bottom_info->trial_text = 'after ' . $selected_service->itemData['itemTrialDays'] . ' day(s) trial';
    }
} elseif ($settings->payment_type != 'input') {
    $order_info = new PT_Template(pt_resolve_template("form/service_info.php", $__theme), array("post" => $c->post));
    $order_info->services_list = $payment->getHTMLServicesList();
    $order_info->currency = $settings->currency_text;
    $order_info->currency_position = $settings->currency_position;
    if ($settings->multiple_currencies == 'y') {
        $order_info->show_currency_selector = $settings->multiple_currency_selector == 'y' ? true : false;
        $order_info->currency_selector_html = $payment->getHTMLCurrenciesList();
        ;
    } else {
        $order_info->show_currency_selector = false;
    }
    $order_info->currency = $settings->currency_text;
    $order_info->currency_symbol = $settings->display_currency;
    $order_info->selected_theme = $__theme;
} else {
    $order_info = new PT_Template(pt_resolve_template("form/amount_info.php", $__theme), array("post" => $c->post));
    $order_info->show_description = $settings->show_description;
    $order_info->currency_position = $settings->currency_position;
    if ($settings->multiple_currencies == 'y') {
        $order_info->currencies = true;
        $order_info->currency_selector_html = $payment->getHTMLCurrenciesList();
        ;
        $order_info->show_currency_selector = $settings->multiple_currency_selector == 'y' ? true : false;
    } else {
        $order_info->show_currency_selector = false;
    }
    $order_info->currency = $settings->currency_text;
    $order_info->currency_symbol = $settings->display_currency;
    $order_info->selected_theme = $__theme;
}

// invoice
$isInvoice = false;
if (!empty($invoice) && $https) {
    $invoiceModel = new invoiceModel();
    $invoiceModel->setHashNumber($invoice);
    if ($invoiceModel->setInvoiceData()) {
        $isInvoice = true;
        if ($invoiceModel->invoiceData['invoiceStatus'] == 'paid') {
            $c->addWarning("<span data-i18n=\"invoice_already_paid\">Invoice was already paid!</span>");
            $show_form = false;
        }
        $invoiceData = $invoiceModel->invoiceData;

        $order_info = new PT_Template("form/invoice_info.php");
        $order_info->idInvoice = $invoiceData['idInvoice'];

        $order_info->due_date = PT_Core::_getDateFormat($invoiceData['invoiceDueDate']);
        $order_info->number = $invoiceData['invoiceNumber'];

        $order_info->amount = PT_Core::_getCurrencyText($invoiceData['invoiceTotal'], $invoiceData['invoiceCurrencyPosition'], $invoiceData['invoiceCurrencySymbol']);
        $order_info->tax = PT_Core::_getCurrencyText($invoiceData['invoiceTax'], $invoiceData['invoiceCurrencyPosition'], $invoiceData['invoiceCurrencySymbol']);
        $order_info->_amount = $invoiceData['invoiceTotal'];
        $order_info->is_recurring = $invoiceModel->invoiceType == 'recurring';

        $bottom_info->amount = $invoiceData['invoiceTotal'];

        $order_info->currency_position = $invoiceData['invoiceCurrencyPosition'];
        $order_info->display_currency = $invoiceData['invoiceCurrencySymbol'];
        $order_info->currency_text = $invoiceData['invoiceCurrency'];
        $order_info->currency = trim($invoiceData['invoiceCurrency']);

        $bottom_info->currency_position = $invoiceData['invoiceCurrencyPosition'];
        $bottom_info->display_currency = $invoiceData['invoiceCurrencySymbol'];
        $bottom_info->currency_text = $invoiceData['invoiceCurrency'];
        if ($invoiceModel->invoiceType == 'recurring') {
            $bottom_info->billing_period = $invoiceModel->billingPeriod;
        }
        $invoiceModel->addHistory("view", "Customer clicked pay invoice button");
    }
}


if ($pt_action == 'do_payment') {
    // Get click ID from either 'clickid' or 'cid' parameter
    $clickid = !empty($_GET['clickid']) ? $_GET['clickid'] : (!empty($_GET['cid']) ? $_GET['cid'] : '');
    $source = !empty($_GET['source']) ? $_GET['source'] : '';

    if ($c->checkCaptcha()) {
        $paymentResult = $payment->doPayment();
        if ($paymentResult === true) {
            $show_form = false;
            if ($pt_type != 'paypal') {
                if (!empty($settings->thank_you_redirect)) {
                    $redirectUrl = $settings->thank_you_redirect;
                    // Add click ID if present in the URL
                    if (!empty($clickid)) {
                        $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == null) ? '?' : '&';
                        $redirectUrl .= $separator . 'clickid=' . urlencode($clickid);
                    }
                    // Add source if present
                    if (!empty($source)) {
                        $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == null) ? '?' : '&';
                        $redirectUrl .= $separator . 'source=' . urlencode($source);
                    }
                    error_log("Redirecting to: " . $redirectUrl);
                    header('Location: ' . $redirectUrl);
                    exit();
                } else {
                    $submit_data = array();
                    if (!empty($payment->payment_id))
                        $submit_data['pt_payment'] = $payment->payment_id;
                    if (!empty($payment->payment_id))
                        $submit_data['pt_subscription'] = $payment->subscription_id;

                    // Add click ID if present
                    if (!empty($clickid)) {
                        $submit_data['clickid'] = $clickid;
                    }

                    // Add source if present
                    if (!empty($source)) {
                        $submit_data['source'] = $source;
                    }

                    $redirectUrl = 'payment_confirmation.php?' . http_build_query($submit_data);
                    error_log("Redirecting to: " . $redirectUrl);
                    header('Location: ' . $redirectUrl);
                    exit();
                }
            }
        } else {
            // Payment failed, show error message
            $c->setAlert("danger", "<span data-i18n=\"payment_failed\">Payment failed</span>: " . ($payment->error_message ?? 'Unknown error occurred'));
            error_log("Payment failed: " . ($payment->error_message ?? 'Unknown error') . " - Payment ID: " . ($payment->payment_id ?? 'N/A'));
        }
    } else {
        $c->setAlert("danger", "<span data-i18n=\"captcha_failed\">CAPTCHA verification failed. Please try again.</span>");
    }
}

$header->render(true);

// Hide header and adaptive warning notices on this dedicated LP route.
echo '<style>.header, .alert.alert-warning.alert-dismissible.fade.in { display: none !important; }</style>';

if ($https) {
    ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/css/bootstrap-slider.min.css"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/bootstrap-slider.min.js"></script>
    <main class="adaptive-checkout" role="main">
        <?php echo ($c->getMessages()) ?>
        <?php if ($show_form) { ?>
            <form class="validate payment_form" role="form" id="payment_form" method="post">
                <input type="hidden" name="pt_action" value="do_payment">
                <input type="hidden" name="pt_tax_rate" id="pt_tax_rate" readonly
                    value="<?php echo $settings->tax_enable == 'y' ? $settings->tax_rate : 0; ?>" />
                <input type="hidden" name="pt_tax_exempt" id="pt_tax_exempt" value="n" />
                <input type="hidden" name="currency" value="<?php echo htmlspecialchars($pt_currency, ENT_QUOTES); ?>">
                <input type="hidden" name="cid" value="<?php echo htmlspecialchars($_GET['cid'] ?? '', ENT_QUOTES); ?>">
                <input type="hidden" name="service" value="<?php echo htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES); ?>">
                <input type="hidden" name="language" value="<?php echo htmlspecialchars($_GET['language'] ?? 'en', ENT_QUOTES); ?>">
                <input type="hidden" name="productname" value="<?php echo htmlspecialchars(urldecode($_GET['productname'] ?? ''), ENT_QUOTES); ?>">
                <input type="hidden" name="price" value="<?php echo htmlspecialchars(urldecode($_GET['price'] ?? ''), ENT_QUOTES); ?>">

                <div style="display:none">
                    <?php $order_info->render(true) ?>
                    <?php $payment_type->render(true) ?>
                </div>

                <?php $payment_info->render(true); ?>
                <?php $bottom_info->render(true); ?>
            </form>
        <?php } ?>
    </main>
    <?php $footer->render(true); ?>
    </div>
    <?php
    $popup->render(true);
} // End of if ($https)
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Stripe.js -->
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- jQuery Validation -->
<script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
<!-- Translations FIRST -->
<script src="assets/js/translations.js?v=<?php echo rand(1, 9999) ?>" type="application/javascript"></script>
<!-- Then the main scripts -->
<script src="assets/js/ccvalidations.js" type="application/javascript"></script>
<script src="assets/js/payment_form.js?v=<?php echo rand(1, 9999) ?>" type="application/javascript"></script>


<script type="text/javascript">
    var stripe = Stripe('<?php echo $payment->public_key; ?>');
    var script_url = "<?php echo $settings->siteUrl() ?>";
    var currency_rate = <?php echo json_encode($currency_rates) ?>;
    var fee_enabled = "<?php echo $settings->fee_enable ?>";
    var fee_type = <?php echo empty($settings->fee_type) ? 0 : $settings->fee_type ?>;
    var fee_amount = <?php echo empty($settings->fee_amount) ? 0 : $settings->fee_amount ?>;
    var tax_rate = <?php echo empty($settings->tax_rate) ? 0 : $settings->tax_rate / 100 ?>;
    var tax_exempt = 'n';
    var enable_buttons = '<?php echo $settings->buttons_enable ?>';
    var buttons_country = '<?php echo $settings->buttons_country ?>';


    function syncAdaptiveCustomerFields() {
        var firstName = $('#first_name').val() || '';
        var lastName = $('#last_name').val() || '';
        var fullName = $.trim((firstName + ' ' + lastName).replace(/\s+/g, ' '));

        $('#pt_name').val(fullName);
        $('#pt_email').val($('#email').val() || '');
        $('#pt_phone').val($('#phone').val() || '');
        $('#pt_address1').val($('#address').val() || '');
        $('#pt_address').val($('#address').val() || '');
        $('#pt_address2').val($('#address2').val() || '');
        $('#pt_city').val($('#city').val() || '');
        $('#pt_postal').val($('#zip').val() || '');
        $('#pt_zip').val($('#zip').val() || '');
        $('#pt_state').val($('#state').val() || '');
        $('#pt_country').val($('#country').val() || $('#pt_country').val());
    }

    $(document).ready(function () {
        $('#payment_form').on('input change', '.adaptive-customer-field', syncAdaptiveCustomerFields);
        $('#payment_form').on('submit', syncAdaptiveCustomerFields);
        syncAdaptiveCustomerFields();
    });
</script>


</body>
<?php echo ($c->getDebug()) ?>

</html>

