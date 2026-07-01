<?php

/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c) CriticalGears.io
 */

// Include error handler first
@include_once "includes/error_handler.php";

// Include bootstrap
include_once "includes/bootstrap.php";
include_once "includes/countries.php";

// Defensive initialization to avoid partial rendering when globals are not pre-bound.
if (!isset($settings) || !is_object($settings)) {
    $settings = PT_Settings::instance();
}
if (!isset($user) || !is_object($user)) {
    $user = PT_User::instance();
}

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
            if (!empty($country) && preg_match('/^[A-Z]{2}$/i', trim($country))) {
                $detectedCountry = strtoupper(trim($country));
                error_log("Using country from detector: " . $detectedCountry);
            } else {
                error_log("Invalid country from detector, using IP detection");
                $detectedCountry = getCountryFromIP();
            }
        } catch (Exception $e) {
            error_log("Error in get_user_country(): " . $e->getMessage());
            $detectedCountry = getCountryFromIP();
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
    error_log("Invalid country code detected: " . $detectedCountry . ", defaulting to US");
    $detectedCountry = 'US';
}

// Country detection is now handled by the code above

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
            "<h3>Error: " . htmlspecialchars($message) . "</h3>" .
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

// Include bootstrap file
if (!@include_once("includes/bootstrap.php")) {
    handleError("System configuration error", 500);
}

if (!isset($settings) || !is_object($settings)) {
    $settings = PT_Settings::instance();
}
if (!isset($user) || !is_object($user)) {
    $user = PT_User::instance();
}

// =====================
// Theme router (early)
// =====================
// Mapping: CardStyle => index.php, Minimalist => index2.php, Colorful => index1.php
// This file handles the CardStyle theme by default
try {
    $effectiveTheme = $settings->selected_theme; // Default theme

    // Only check service-specific theme if we're not in custom mode
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

    $isCustomMode = ($settings->theme_type === 'custom');

    // Handle theme-specific routing
    if ($effectiveTheme === 'adaptive-lp' && basename($_SERVER['PHP_SELF']) !== 'index6.php') {
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $redirectUrl = '/index6.php' . $queryString;
        error_log("REDIRECT TO: " . $redirectUrl);

        header('Location: ' . $redirectUrl);
        exit;
    } elseif ($effectiveTheme === 'Minimalist' && basename($_SERVER['PHP_SELF']) !== 'index2.php') {
        // Redirect to index2.php for Minimalist theme
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        header('Location: index2.php' . $queryString);
        exit;
    } elseif ($effectiveTheme === 'Colorful' && basename($_SERVER['PHP_SELF']) !== 'index1.php') {
        // Redirect to index1.php for Colorful theme
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        header('Location: index1.php' . $queryString);
        exit;
    } elseif ($effectiveTheme === 'PhysicalProduct') {
        // For PhysicalProduct theme, ensure we stay on index3.php
        if (basename($_SERVER['PHP_SELF']) !== 'index3.php') {
            $query = $_GET;
            $queryString = !empty($query) ? '?' . http_build_query($query) : '';
            header('Location: index3.php' . $queryString);
            exit;
        }
        $settings->selected_theme = $effectiveTheme;
    } elseif ($effectiveTheme === 'Normal' && basename($_SERVER['PHP_SELF']) !== 'index4.php') {
        // For Normal theme, ensure we stay on index4.php
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
    } elseif (!in_array($effectiveTheme, ['adaptive-lp', 'Minimalist', 'Colorful', 'PhysicalProduct', 'Normal']) && basename($_SERVER['PHP_SELF']) !== 'index.php') {
        // Default to index.php for all other themes
        $query = $_GET;
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        header('Location: index.php' . $queryString);
        exit;
    }

    // Set the theme in settings for consistency
    $settings->selected_theme = $effectiveTheme;
} catch (Throwable $e) {
    // Log error but continue
    error_log("Theme routing error: " . $e->getMessage());
}

// Validate service parameter
if (!isset($_GET['service']) || empty(trim($_GET['service']))) {
    handleError("Service parameter is missing or empty");
}

// Get and sanitize the service ID from URL
$serviceId = trim($_GET['service']);

// Validate UUID format
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $serviceId)) {
    handleError("Invalid service ID format");
}

// Check database connection
global $mysqli;
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    handleError("Database connection error. Please check your database configuration.", 500);
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
            handleError("Service not found or inactive. Service ID: " . htmlspecialchars($serviceId), 404);
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

$notice = $settings->terminal_payment_mode == 'test' ? "Test Mode Enabled. No real transactions will happen - all transaction will be charged in sandbox mode." : "";

if ($settings->terminal_payment_mode == 'test') {
    if (strlen($settings->test_secret_key) < 10 || strlen($settings->test_public_key) < 10) {
        $notice .= "<br><strong>API Test credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a></strong>";
    } elseif ($settings->test_secret_key == 'YOUR STRIPE SECRET KEY FOR TEST MODE') {
        $notice .= "<br><strong>API Test credentials are missing! Please set up credentials on includes/config.php.</a></strong>";
    }
}

$https = true;
if ($settings->terminal_payment_mode != 'test' && !isSecure()) {
    $notice = "HTTPS isn't properly configured. Please ensure your server is configured to handle HTTPS connections.";
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

// Enhanced amount handling with better fallback logic
$amount = $c->_esc("amount");
$pt_amount = $c->_esc("pt_amount");

// If pt_amount is empty, try to get it from amount or service database
if (empty($pt_amount) && !empty($amount)) {
    $pt_amount = $amount;
} elseif (empty($pt_amount) && !empty($serviceId)) {
    // Fetch from database as last resort
    $query = "SELECT itemAmount, itemTrialUpfront, itemTrial FROM " . DB_PREFIX . "items WHERE idItem = ?";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('s', $serviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $is_trial = ($row['itemTrial'] == 'y');
            $pt_amount = $is_trial && $row['itemTrialUpfront'] > 0
                ? $row['itemTrialUpfront']
                : $row['itemAmount'];
        }
        $stmt->close();
    }
}

// Ensure pt_amount is set in POST data for payment processing
if (!empty($pt_amount)) {
    $_POST['pt_amount'] = $pt_amount;
    $c->post['pt_amount'] = $pt_amount;
}

error_log("Amount resolution - amount: " . ($amount ?? 'none') . ", pt_amount: " . ($pt_amount ?? 'none'));

$currency = $c->_esc("currency");
$pt_currency = $c->_esc("pt_currency", $currency);
$pt_currency_symbol = $c->_esc("pt_currency_symbol", "");
$pt_currency_position = $c->_esc("pt_currency_position", "");
$pt_service = $c->_esc("pt_service");
$service = $c->_esc("service");

// Ensure pt_service is set if service parameter exists
if (empty($pt_service) && !empty($service)) {
    $pt_service = $service;
    $_POST['pt_service'] = $service;
    $c->post['pt_service'] = $service;
}

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

// ADD THESE TWO LINES:
$clickid = $c->_esc("clickid", "");
$source = $c->_esc("source", "");

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
$currency_rates = false;
if ($settings->multiple_currencies == 'y') {
    $currency_rates = PT_Payment::get_currency_exchange_rates();
}
$payment_info_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post,
    'payment' => $payment,
    'settings' => $settings,
    'currency_rates' => $currency_rates,
    'lang' => $lang,
    'detectedCountry' => $detectedCountry
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
            $c->addWarning("Invoice was already paid!");
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
    // Enhanced debugging for payment processing
    error_log("=== PAYMENT PROCESSING DEBUG ===");
    error_log("Service from GET: " . ($_GET['service'] ?? 'Not set'));
    error_log("Service from POST: " . ($_POST['service'] ?? 'Not set'));
    error_log("pt_service from POST: " . ($_POST['pt_service'] ?? 'Not set'));
    error_log("Amount: " . ($_POST['pt_amount'] ?? 'Not set'));
    error_log("Full POST data: " . print_r($_POST, true));

    // Get click ID from either 'clickid' or 'cid' parameter
    $clickid = !empty($_GET['clickid']) ? $_GET['clickid'] : (!empty($_GET['cid']) ? $_GET['cid'] : '');
    $source = !empty($_GET['source']) ? $_GET['source'] : '';

    if ($c->checkCaptcha()) {
        // Ensure pt_service is set from service parameter if missing
        if (empty($_POST['pt_service']) && !empty($_POST['service'])) {
            $_POST['pt_service'] = $_POST['service'];
            error_log("Setting pt_service from service parameter: " . $_POST['pt_service']);
        }

        error_log("About to call doPayment()...");
        error_log("Payment method from POST: " . ($_POST['payment_method'] ?? 'NOT SET'));
        error_log("stripeToken from POST: " . ($_POST['stripeToken'] ?? 'NOT SET'));

        $paymentResult = $payment->doPayment();

        error_log("Payment result: " . ($paymentResult ? 'TRUE' : 'FALSE'));
        error_log("Payment error: " . ($payment->error ?? 'None'));

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
            // Enhanced error logging
            $errorDetails = "Payment failed: " . ($payment->error_message ?? 'Unknown error');
            $errorDetails .= " | Payment ID: " . ($payment->payment_id ?? 'N/A');
            $errorDetails .= " | Service: " . ($_POST['pt_service'] ?? $_POST['service'] ?? 'Not set');
            $errorDetails .= " | Amount: " . ($_POST['pt_amount'] ?? 'Not set');

            error_log($errorDetails);
            $c->addError($errorDetails);
        }
    } else {
        $c->addError("CAPTCHA verification failed. Please try again.");
    }
}

$header->render(true);

// Hide header and footer visually for index2.php
echo '<style>.header { display: none !important; } .alert.alert-danger.alert-dismissible { display: none !important; }</style>';

if ($https) {
    ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/css/bootstrap-slider.min.css"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/bootstrap-slider.min.js"></script>
    <div class="container main" role="main">
        <?php echo ($c->getMessages()) ?>
        <?php if ($show_form) { ?>
            <div class="product-section">
                <div class="product-info">
                    <div class="form-columns">
                        <!-- Payment Details Column (Right) -->
                        <div class="payment-details-column">
                            <form class="validate payment_form" role="form" id="payment_form" method="post">
                                <input type="hidden" name="pt_action" value="do_payment">
                                <input type="hidden" name="pt_tax_rate" id="pt_tax_rate" readonly
                                    value="<?php echo $settings->tax_enable == 'y' ? $settings->tax_rate : 0; ?>" />
                                <input type="hidden" name="pt_tax_exempt" id="pt_tax_exempt" value="n" />
                                <input type="hidden" name="pt_name" id="form_full_name" value="">
                                <input type="hidden" name="pt_action" value="do_payment">
                                <input type="hidden" name="pt_tax_rate" id="pt_tax_rate" readonly
                                    value="<?php echo $settings->tax_enable == 'y' ? $settings->tax_rate : 0; ?>" />
                                <input type="hidden" name="pt_tax_exempt" id="pt_tax_exempt" value="n" />
                                <input type="hidden" name="pt_name" id="form_full_name" value="">
                                <input type="hidden" name="pt_email" id="form_email" value="">
                                <input type="hidden" name="pt_phone" id="form_phone" value="">
                                <input type="hidden" name="pt_address" id="form_address" value="">
                                <input type="hidden" name="pt_city" id="form_city" value="">
                                <input type="hidden" name="pt_zip" id="form_zip" value="">
                                <input type="hidden" name="pt_country" id="form_country" value="">
                                <input type="hidden" name="pt_service"
                                    value="<?php echo htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES); ?>">
                                <input type="hidden" name="amount"
                                    value="<?php echo htmlspecialchars($_GET['amount'] ?? '', ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_amount"
                                    value="<?php echo htmlspecialchars($pt_amount ?? '', ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_currency"
                                    value="<?php echo htmlspecialchars($settings->currency_text ?? 'USD', ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_currency_symbol"
                                    value="<?php echo htmlspecialchars($settings->display_currency ?? '$', ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_currency_position"
                                    value="<?php echo htmlspecialchars($settings->currency_position ?? 'before', ENT_QUOTES); ?>">
                                <input type="hidden" name="currency"
                                    value="<?php echo htmlspecialchars($pt_currency, ENT_QUOTES); ?>">
                                <input type="hidden" name="cid"
                                    value="<?php echo htmlspecialchars($_GET['cid'] ?? '', ENT_QUOTES); ?>">
                                <input type="hidden" name="service"
                                    value="<?php echo htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES); ?>">
                                <input type="hidden" name="language"
                                    value="<?php echo htmlspecialchars($_GET['language'] ?? 'en', ENT_QUOTES); ?>">
                                <input type="hidden" name="productname"
                                    value="<?php echo htmlspecialchars(urldecode($_GET['productname'] ?? ''), ENT_QUOTES); ?>">
                                <input type="hidden" name="price"
                                    value="<?php echo htmlspecialchars(urldecode($_GET['price'] ?? ''), ENT_QUOTES); ?>">
                                <input type="hidden" name="clickid"
                                    value="<?php echo htmlspecialchars($_GET['clickid'] ?? ($_GET['cid'] ?? ''), ENT_QUOTES); ?>">
                                <input type="hidden" name="source"
                                    value="<?php echo htmlspecialchars($_GET['source'] ?? '', ENT_QUOTES); ?>">

                                <div style="display:none">
                                    <?php $order_info->render(true) ?>
                                </div>

                                <?php $payment_type->render(true) ?>
                                <div class="clearfix"></div>
                                <?php $shipping_info->render(true); ?>
                                <?php $payment_info->render(true); ?>
                                <?php $bottom_info->render(true); ?>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php $footer->render(true); ?>
        <?php
        $popup->render(true);
} // End of if ($https)
?>

    <link rel="stylesheet" href="assets/css/Normal/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Validation -->
    <script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
    <!-- Credit Card Validations -->
    <script src="assets/js/ccvalidations.js" type="application/javascript"></script>
    <!-- Stripe.js -->
    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>

    <style>
        #step1,
        #step2 {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            padding: 0.5rem 1.5rem;
        }

        #back-btn {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        #back-btn:hover {
            color: #0d6efd;
        }

        #back-btn i {
            font-size: 1rem;
        }
    </style>

    <script src="assets/bootstrap/js/bootstrap.min.js" type="application/javascript"></script>

    <script type="text/javascript">
        // Make Stripe public key available to the payment form
        window.stripePublicKey = '<?php echo $payment->public_key; ?>';
        var stripe = Stripe('<?php echo $payment->public_key; ?>');
        var script_url = "";
        var currency_rate = <?php echo json_encode($currency_rates) ?>;
        var fee_enabled = "<?php echo $settings->fee_enable ?>";
        var fee_type = <?php echo empty($settings->fee_type) ? 0 : $settings->fee_type ?>;
        var fee_amount = <?php echo empty($settings->fee_amount) ? 0 : $settings->fee_amount ?>;
        var tax_rate = <?php echo empty($settings->tax_rate) ? 0 : $settings->tax_rate / 100 ?>;
        var tax_exempt = 'n';
        var enable_buttons = '<?php echo $settings->buttons_enable ?>';
        var buttons_country = '<?php echo $settings->buttons_country ?>';

        $(document).ready(function () {
            // Initialize jQuery Validation
            // Initialize jQuery Validation
            $("#payment_form").validate({
                errorClass: "error",
                errorElement: "label",
                // Only validate on submit generally, or be lazy about it
                onfocusout: function (element) {
                    this.element(element);
                },
                onkeyup: false,
                onclick: false,
                highlight: function (element) {
                    $(element).addClass("error issue");
                    $(element).closest('.form-group').addClass("has-error");
                },
                unhighlight: function (element) {
                    $(element).removeClass("error issue");
                    $(element).closest('.form-group').removeClass("has-error");
                }
            });

            // Override updatePaymentForm to trigger validation UI
            // This hook ensures that when the external payment_form.js calls updatePaymentForm(),
            // we sync the data. We DO NOT trigger full form validation here because this function
            // is called on every field blur/change, and we don't want to validate the whole form then.
            var originalUpdatePaymentForm = window.updatePaymentForm;
            window.updatePaymentForm = function () {
                var result = true;
                if (typeof originalUpdatePaymentForm === 'function') {
                    result = originalUpdatePaymentForm();
                }
                return result;
            };

            // Flag to track if validation has been initialized
            var validationInitialized = false;

            // Function to get translated validation messages
            function getTranslatedValidationMessages() {
                // Default English messages as fallback
                const defaultMessages = {
                    first_name: {
                        required: "Please enter your first name",
                        minlength: "Name must be at least 2 characters"
                    },
                    last_name: {
                        required: "Please enter your last name",
                        minlength: "Name must be at least 2 characters"
                    },
                    email: {
                        required: "Please enter your email address",
                        email: "Please enter a valid email address"
                    },
                    phone: {
                        minlength: "Phone number is too short",
                        maxlength: "Phone number is too long"
                    },
                    address: {
                        minlength: "Address is too short"
                    },
                    city: {
                        minlength: "City name is too short"
                    },
                    zip: {
                        minlength: "ZIP code is too short",
                        maxlength: "ZIP code is too long"
                    },
                    country: {
                        required: "Please select a country"
                    }
                };

                // If translations are available, use them
                if (window.siteTranslations) {
                    console.log("Using translations for validation messages:", window.siteTranslations);
                    return {
                        first_name: {
                            required: window.siteTranslations.fieldRequired || defaultMessages.first_name.required,
                            minlength: window.siteTranslations.first_name_length || defaultMessages.first_name.minlength
                        },
                        last_name: {
                            required: window.siteTranslations.fieldRequired || defaultMessages.last_name.required,
                            minlength: window.siteTranslations.last_name_length || defaultMessages.last_name.minlength
                        },
                        email: {
                            required: window.siteTranslations.fieldRequired || defaultMessages.email.required,
                            email: window.siteTranslations.fieldEmail || defaultMessages.email.email
                        },
                        phone: {
                            minlength: window.siteTranslations.phone_min_length || defaultMessages.phone.minlength,
                            maxlength: window.siteTranslations.phone_max_length || defaultMessages.phone.maxlength
                        },
                        address: {
                            minlength: window.siteTranslations.address_min_length || defaultMessages.address.minlength
                        },
                        city: {
                            minlength: window.siteTranslations.city_min_length || defaultMessages.city.minlength
                        },
                        zip: {
                            minlength: window.siteTranslations.zip_min_length || defaultMessages.zip.minlength,
                            maxlength: window.siteTranslations.zip_max_length || defaultMessages.zip.maxlength
                        },
                        country: {
                            required: window.siteTranslations.select_country || defaultMessages.country.required
                        }
                    };
                }

                console.log("Using default English validation messages");
                return defaultMessages;
            }

            // Main initialization function
            function initializeFormValidation() {
                if (validationInitialized) {
                    console.log("Validation already initialized, skipping...");
                    return;
                }

                console.log("Initializing form validation with translations", window.siteTranslations);

                // Check if validator exists on payment_form
                const form = $("#payment_form");
                if (form.length === 0) {
                    console.log("Payment form not found");
                    return;
                }

                // Get the validator instance
                const validator = form.data("validator");
                if (!validator) {
                    console.log("Validator not yet initialized on #payment_form. Will retry.");
                    return;
                }

                const msgs = getTranslatedValidationMessages();

                // Helper to add rules safely
                function addRules(selector, rulesObj) {
                    if ($(selector).length) {
                        try {
                            $(selector).rules("add", rulesObj);
                        } catch (e) {
                            console.warn("Could not add rules for " + selector, e);
                        }
                    }
                }

                // Add rules for customer fields if they exist
                addRules("#first_name", {
                    required: true,
                    minlength: 2,
                    messages: {
                        required: msgs.first_name.required,
                        minlength: msgs.first_name.minlength
                    }
                });

                addRules("#last_name", {
                    required: true,
                    minlength: 2,
                    messages: {
                        required: msgs.last_name.required,
                        minlength: msgs.last_name.minlength
                    }
                });

                addRules("#email", {
                    required: true,
                    email: true,
                    messages: {
                        required: msgs.email.required,
                        email: msgs.email.email
                    }
                });

                addRules("#zip", {
                    minlength: 3,
                    maxlength: 10,
                    messages: {
                        minlength: msgs.zip.minlength,
                        maxlength: msgs.zip.maxlength
                    }
                });

                addRules("#country", {
                    required: true,
                    messages: { required: msgs.country.required }
                });

                // Add rules for shipping fields if present
                addRules("#pt_address1_s", {
                    minlength: 5,
                    messages: { minlength: msgs.address ? msgs.address.minlength : "Address is too short" }
                });

                addRules("#pt_city_s", {
                    minlength: 2,
                    messages: { minlength: msgs.city ? msgs.city.minlength : "City name is too short" }
                });

                addRules("#pt_postal_s", {
                    minlength: 3,
                    maxlength: 10,
                    messages: {
                        minlength: msgs.zip.minlength,
                        maxlength: msgs.zip.maxlength
                    }
                });

                addRules("#pt_country_s", {
                    required: true,
                    messages: { required: msgs.country.required }
                });

                validationInitialized = true;
                console.log("Form validation rules updated successfully");
            }

            console.log("Document ready, checking for translations...");

            // Function to handle translations loaded
            function handleTranslationsLoaded() {
                console.log("Translations loaded event received");
                setTimeout(function () {
                    // We need to loop/retry until validator is ready
                    let attempts = 0;
                    const initInterval = setInterval(function () {
                        if ($("#payment_form").data("validator")) {
                            clearInterval(initInterval);
                            initializeFormValidation();
                        } else {
                            attempts++;
                            if (attempts > 20) { // 2 seconds
                                clearInterval(initInterval);
                                console.log("Timed out waiting for validator (init logic)");
                                // Fallback: try to init anyway if just data-validator is missing but plugin is loaded
                                if ($.fn.validate) {
                                    initializeFormValidation();
                                }
                            }
                        }
                    }, 100);
                }, 100);
            }

            // Check if translations are already loaded
            if (window.siteTranslations && Object.keys(window.siteTranslations).length > 0) {
                console.log("Translations already loaded on page load");
                handleTranslationsLoaded();
            } else {
                console.log("Waiting for translations to load...");
                $(document).on('translationsLoaded', handleTranslationsLoaded);

                // Also check periodically
                var translationCheckInterval = setInterval(function () {
                    if (window.siteTranslations && Object.keys(window.siteTranslations).length > 0) {
                        console.log("Translations found via interval check");
                        clearInterval(translationCheckInterval);
                        handleTranslationsLoaded();
                    }

                    // Safety check multiple times
                    if (validationInitialized) {
                        clearInterval(translationCheckInterval);
                    }
                }, 200);

                // Timeout fallback
                setTimeout(function () {
                    clearInterval(translationCheckInterval);
                    if (!validationInitialized) {
                        console.log("Fallback: Initializing validation (best effort)");
                        handleTranslationsLoaded();
                    }
                }, 5000);
            }
        });
    </script>

    <script src="assets/js/PhysicalProduct/payment_form.js?v=<?php echo rand(1, 9999) ?>"
        type="application/javascript"></script>
    <script src="assets/js/translations.js?v=<?php echo rand(1, 9999) ?>" type="application/javascript"></script>

    </body>
    <?php echo ($c->getDebug()) ?>

    </html>
