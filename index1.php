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
include_once "includes/functions.php";

$__ptDomainItemId = trim((string)($_GET['service'] ?? $_GET['item_id'] ?? ''));
$__ptDomainInvoiceId = isset($_GET['idInvoice']) ? (int)$_GET['idInvoice'] : 0;
$__ptDomainToken = trim((string)($_GET['drt'] ?? $_POST['drt'] ?? ''));
pt_enforce_domain_access_or_exit($__ptDomainItemId, $__ptDomainInvoiceId, $__ptDomainToken, true);


/**
 * Fallback function to get country code from IP address
 * This is used only if the country detector is not available
 */
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
    if ($effectiveTheme === 'Minimalist' && basename($_SERVER['PHP_SELF']) !== 'index2.php') {
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
    } elseif ($effectiveTheme === 'Normal' && basename($_SERVER['PHP_SELF']) !== 'index4.php') {
        // For Normal theme, redirect to index4.php
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
    } elseif (!in_array($effectiveTheme, ['Minimalist', 'Colorful', 'Normal']) && basename($_SERVER['PHP_SELF']) !== 'index.php') {
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

// Pass the current theme to JavaScript
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
} elseif ($settings->terminal_payment_mode == 'live') {
    if (strlen($settings->live_secret_key) < 10 || strlen($settings->live_public_key) < 10) {
        $notice .= "<br><strong>API Live credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a></strong>";
    } elseif ($settings->live_public_key == 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE') {
        $notice .= "<br><strong>API Live credentials are missing! Please set up credentials on includes/config.php.</a></strong>";
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
if (!empty($_SESSION['api_currency_data'])) {
    $fetchedCurrencyData = $_SESSION['api_currency_data'];
    error_log('index1.php: $fetchedCurrencyData from session: ' . print_r($fetchedCurrencyData, true));
} else if (!empty($countryCodeForCurrency) && !empty($ctcForCurrency) && !empty($serviceIdForCurrency)) {
    try {
        $fetchedCurrencyData = getConvenientCurrencyData($countryCodeForCurrency, $ctcForCurrency, $serviceIdForCurrency);
        $_SESSION['api_currency_data'] = $fetchedCurrencyData;
        error_log('Fetched currencyData in index1.php: ' . print_r($fetchedCurrencyData, true));

        // Override pt_currency, pt_currency_symbol, pt_currency_position with fetched data
        if (isset($fetchedCurrencyData['subscription_amount']['currency_code'])) {
            $pt_currency = $fetchedCurrencyData['subscription_amount']['currency_code'];
        }
        if (isset($fetchedCurrencyData['subscription_amount']['currency_symbol'])) {
            $pt_currency_symbol = $fetchedCurrencyData['subscription_amount']['currency_symbol'];
        }
        // Assuming currency_position is 'before' or 'after' and not directly returned by getConvenientCurr.php
        // If it is returned, use it. Otherwise, keep the default or derive it.
        // For now, we'll assume it's not directly returned and keep the existing logic for position.
    } catch (Exception $e) {
        error_log('Error fetching convenient currency data in index1.php: ' . $e->getMessage());
        // Optionally, add a user-facing error message
        $c->addWarning('Could not fetch real-time currency data. Using default values.');
    }
}

error_log('index1.php - pt_currency: ' . $pt_currency);
error_log('index1.php - pt_currency_symbol: ' . $pt_currency_symbol);
error_log('index1.php - pt_currency_position: ' . $pt_currency_position);

$header->render(true);
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
$payment_method = $c->_esc("payment_method");

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
    $query = "SELECT itemAmount, itemTrialUpfront, itemTrial, itemTrialDays FROM vcp_pt_items WHERE idItem = ?";
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
$payment_info_data = array_merge($c->post, [
    'selected_theme' => $__theme,
    'post' => $c->post
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
        if ($invoiceModel->invoiceData['invoiceStatus'] == 'paid' && empty($pt_action)) {
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
            $c->setAlert("danger", "Payment failed: " . ($payment->error_message ?? 'Unknown error occurred'));
            error_log("Payment failed: " . ($payment->error_message ?? 'Unknown error') . " - Payment ID: " . ($payment->payment_id ?? 'N/A'));
        }
    } else {
        $c->setAlert("danger", "CAPTCHA verification failed. Please try again.");
    }
}

$header->render(true);

// Hide header and footer visually for index2.php
echo '<style>.header { display: none !important; }</style>';

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
                        <!-- Personal Info Column (Left) -->
                        <div class="personal-info-column">
                            <form id='personal_info_form'>
                                <div class="section-header">
                                    <h3 class="section-subtitle" data-i18n="enter_personal_details">Please enter your personal
                                        details</h3>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="first_name" class="form-label" data-i18n="first_name">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="John"
                                        required data-i18n-placeholder="enter_first_name"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['first'] ?? ''), ENT_QUOTES); ?>">
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="last_name" class="form-label" data-i18n="last_name">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Doe"
                                        required data-i18n-placeholder="enter_last_name"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['last'] ?? ''), ENT_QUOTES); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label" data-i18n="email_address">Email Address <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="your@email.com" required data-i18n-placeholder="enter_email_address"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['email'] ?? $pt_email_resolved), ENT_QUOTES); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label" data-i18n="phone_number">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="+1 (___) ___-____" data-i18n-placeholder="phone_number"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['phone'] ?? ''), ENT_QUOTES); ?>"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]*"
                                        inputmode="numeric">
                                </div>

                                <div class="form-group">
                                    <label for="address" class="form-label" data-i18n="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address"
                                        placeholder="123 Main St" data-i18n-placeholder="address"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['address'] ?? ''), ENT_QUOTES); ?>">
                                </div>

                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="city" class="form-label" data-i18n="city">City</label>
                                        <input type="text" class="form-control" id="city" name="city" placeholder="City"
                                            data-i18n-placeholder="city"
                                            value="<?php echo htmlspecialchars(urldecode($_GET['city'] ?? ''), ENT_QUOTES); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="zip" class="form-label" data-i18n="zip_postal_code">ZIP/Postal Code</label>
                                        <input type="text" class="form-control" id="zip" name="zip" placeholder="10001"
                                            data-i18n-placeholder="zip_postal_code"
                                            value="<?php echo htmlspecialchars(urldecode($_GET['zip'] ?? ''), ENT_QUOTES); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="country" class="form-label" data-i18n="country">Country <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="country" name="country" required>
                                        <option value="" data-i18n="select_country" style="font-weight: 800; font-size: 17px;">Select Country</option>
                                        <?php
                                        // Get translations for the current language
                                        // This returns a flat array: ['US' => 'United States', ...]
                                        $translatedCountries = getAllCountriesInLanguage($lang);

                                        // Use the structure from includes/countries.php which is now flat
                                        $countries = $GLOBALS['countries'];

                                        foreach ($countries as $code => $englishName) {
                                            // Use translation if available, otherwise fallback to English name
                                            $displayName = $translatedCountries[$code] ?? $englishName;

                                            $selected = ($detectedCountry === $code) ? 'selected' : '';
                                            echo "<option value='$code' $selected>$displayName</option>\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <!-- End personal-info-column -->

                        <!-- Payment Details Column (Right) -->
                        <div class="payment-details-column">
                            <div class="section-header">
                                <h3 class="section-subtitle" data-i18n="enter_payment_info">Enter your payment information</h3>
                            </div>
                            <form class="validate payment_form" role="form" id="payment_form" method="post">
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
                                <input type="hidden" name="pt_currency" id="form_currency"
                                    value="<?php echo htmlspecialchars($pt_currency, ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_currency_symbol" id="form_currency_symbol"
                                    value="<?php echo htmlspecialchars($pt_currency_symbol, ENT_QUOTES); ?>">
                                <input type="hidden" name="pt_currency_position" id="form_currency_position"
                                    value="<?php echo htmlspecialchars($pt_currency_position, ENT_QUOTES); ?>">

                                <div style="display:none">
                                    <?php $order_info->render(true) ?>
                                </div>

                                <?php $payment_type->render(true) ?>
                                <div class="clearfix"></div>
                                <?php $shipping_info->render(true); ?>
                                <!-- Star Ratings -->
                                <div class="stars-container">
                                    <div class="stars-wrapper">
                                        <!-- Full star -->
                                        <svg class="star-icon" viewBox="0 0 20 20" width="24" height="24">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                fill="#fbbf24" />
                                        </svg>
                                        <!-- Full star -->
                                        <svg class="star-icon" viewBox="0 0 20 20" width="24" height="24">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                fill="#fbbf24" />
                                        </svg>
                                        <!-- Full star -->
                                        <svg class="star-icon" viewBox="0 0 20 20" width="24" height="24">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                fill="#fbbf24" />
                                        </svg>
                                        <!-- Full star -->
                                        <svg class="star-icon" viewBox="0 0 20 20" width="24" height="24">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                fill="#fbbf24" />
                                        </svg>
                                        <!-- Half star -->
                                        <div style="position: relative; display: inline-block; width: 24px; height: 24px;">
                                            <!-- Gray background star -->
                                            <svg viewBox="0 0 20 20" width="24" height="24"
                                                style="position: absolute; top: 0; left: 0;">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                    fill="#e5e7eb" />
                                            </svg>
                                            <!-- Yellow half star -->
                                            <div
                                                style="position: absolute; top: 0; left: 0; width: 12px; height: 24px; overflow: hidden;">
                                                <svg viewBox="0 0 20 20" width="24" height="24"
                                                    style="position: absolute; top: 0; left: 0;">
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                        fill="#fbbf24" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rating-text" data-i18n="reviews">4.5 (100+ reviews)</div>
                                </div>
                                <!-- End Star Ratings -->
                                <?php $payment_info->render(true); ?>
                                <?php $bottom_info->render(true); ?>
                            </form>

                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php $footer->render(true); ?>
        </div>
        <?php
        $popup->render(true);
} // End of if ($https)
?>


    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Validation -->
    <script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
    <!-- Translations FIRST -->
    <script src="assets/js/translations.js?v=<?php echo rand(1, 9999) ?>" type="application/javascript"></script>
    <!-- Then the main scripts -->
    <script src="assets/js/ccvalidations.js" type="application/javascript"></script>
    <script src="assets/js/payment_form.js?v=<?php echo rand(1, 9999) ?>" type="application/javascript"></script>

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

        // Flag to track if validation has been initialized
        var validationInitialized = false;

        // Handle form submission
        function updatePaymentForm() {
            // Update hidden fields in payment form with personal info
            const formData = {
                'form_full_name': $('#first_name').val() + ' ' + $('#last_name').val(),
                'form_email': $('#email').val(),
                'form_phone': $('#phone').val(),
                'form_address': $('#address').val(),
                'form_city': $('#city').val(),
                'form_zip': $('#zip').val(),
                'form_country': $('#country').val()
            };

            // Set values for all form fields
            Object.keys(formData).forEach(function (key) {
                $(`#${key}`).val(formData[key]);
            });

            return true;
        }

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
                        required: window.siteTranslations.enter_first_name || defaultMessages.first_name.required,
                        minlength: window.siteTranslations.first_name_length || defaultMessages.first_name.minlength
                    },
                    last_name: {
                        required: window.siteTranslations.enter_last_name || defaultMessages.last_name.required,
                        minlength: window.siteTranslations.last_name_length || defaultMessages.last_name.minlength
                    },
                    email: {
                        required: window.siteTranslations.enter_email_address || defaultMessages.email.required,
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

            console.log("Initializing form validation with translations");

            function updateFullName() {
                const firstName = $("#first_name").val() || "";
                const lastName = $("#last_name").val() || "";
                $("#form_full_name").val((firstName + " " + lastName).trim());
            }

            $("#first_name, #last_name").on("input", updateFullName);

            // First, destroy any existing validation
            if ($.validator && $('#personal_info_form').validate()) {
                $('#personal_info_form').validate().destroy();
            }

            // Initialize form validation for personal info
            $("#personal_info_form").validate({
                errorPlacement: function (error, element) {
                    error.addClass("invalid-feedback");
                    element.after(error);
                },
                highlight: function (element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function (element) {
                    $(element).removeClass("is-invalid");
                },
                rules: {
                    first_name: {
                        required: true,
                        minlength: 2,
                    },
                    last_name: {
                        required: true,
                        minlength: 2,
                    },
                    email: {
                        required: true,
                        email: true,
                    },
                    phone: {
                        minlength: 8,
                        maxlength: 20,
                    },
                    address: {
                        minlength: 5,
                    },
                    city: {
                        minlength: 2,
                    },
                    zip: {
                        minlength: 3,
                        maxlength: 10,
                    },
                    country: {
                        required: true
                    }
                },
                messages: getTranslatedValidationMessages(),
                submitHandler: function (form) {
                    return false; // Prevent form submission
                }
            });

            // Handle payment form submission
            $("#payment_form").on("submit", function (e) {
                // Validate all fields
                const isPersonalInfoValid = $("#personal_info_form").valid();

                if (!isPersonalInfoValid) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Find first error and scroll to it
                    const firstError = $(".is-invalid").first();
                    if (firstError.length) {
                        $("html, body").animate(
                            {
                                scrollTop: firstError.offset().top - 100,
                            },
                            500
                        );
                    }
                    return false;
                }

                // Update hidden fields before submission
                updatePaymentForm();
                return true;
            });

            // Update payment form on any field change and validate
            $("#personal_info_form input, #personal_info_form select").on(
                "change blur",
                function () {
                    updatePaymentForm();
                    $(this).valid();
                }
            );

            validationInitialized = true;
            console.log("Form validation initialized successfully");
        }

        // Progress Bar State Management
        function updateProgressBar(step) {
            const progressBar = document.querySelector('.progressbar');
            const step1Indicator = document.getElementById('step1-indicator');
            const step2Indicator = document.getElementById('step2-indicator');

            if (step === 1) {
                // Reset to step 1
                if (progressBar) progressBar.classList.remove('step2');
                if (step1Indicator) {
                    step1Indicator.classList.add('active');
                    step1Indicator.classList.remove('completed');
                }
                if (step2Indicator) step2Indicator.classList.remove('active', 'completed');

                // Reset progress line
                if (progressBar) {
                    progressBar.style.setProperty('--progress-width', '0%');
                    progressBar.style.setProperty('--progress-color', '#4f46e5');
                }
            } else if (step === 2) {
                // Move to step 2
                if (progressBar) progressBar.classList.add('step2');
                if (step1Indicator) {
                    step1Indicator.classList.remove('active');
                    step1Indicator.classList.add('completed');
                }
                if (step2Indicator) step2Indicator.classList.add('active');

                // Animate progress line
                if (progressBar) {
                    progressBar.style.setProperty('--progress-width', '100%');
                    progressBar.style.setProperty('--progress-color', '#4f46e5');
                }
            }
        }

        $(document).ready(function () {
            console.log("Document ready, checking for translations...");

            // Function to handle translations loaded
            function handleTranslationsLoaded() {
                console.log("Translations loaded event received");
                setTimeout(function () {
                    initializeFormValidation();
                }, 100); // Small delay to ensure translations are fully loaded
            }

            // Check if translations are already loaded
            if (window.siteTranslations && Object.keys(window.siteTranslations).length > 0) {
                console.log("Translations already loaded on page load");
                // Small delay to ensure all elements are ready
                setTimeout(function () {
                    initializeFormValidation();
                }, 500);
            } else {
                console.log("Waiting for translations to load...");
                // Listen for translations loaded event
                $(document).on('translationsLoaded', handleTranslationsLoaded);

                // Also check periodically (fallback in case event doesn't fire)
                var translationCheckInterval = setInterval(function () {
                    if (window.siteTranslations && Object.keys(window.siteTranslations).length > 0) {
                        console.log("Translations found via interval check");
                        clearInterval(translationCheckInterval);
                        handleTranslationsLoaded();
                    }
                }, 200);

                // Timeout after 5 seconds as fallback
                setTimeout(function () {
                    clearInterval(translationCheckInterval);
                    if (!validationInitialized) {
                        console.log("Fallback: Initializing validation without translations");
                        initializeFormValidation();
                    }
                }, 5000);
            }

            // Next button click handler
            $('#next-btn').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Trigger validation for all fields
                const isFormValid = $("#personal_info_form").valid();

                if (isFormValid) {
                    // Update all hidden fields in payment form with personal info
                    const formData = {
                        'form_full_name': $('#first_name').val() + ' ' + $('#last_name').val(),
                        'form_email': $('#email').val(),
                        'form_phone': $('#phone').val(),
                        'form_address': $('#address').val(),
                        'form_city': $('#city').val(),
                        'form_zip': $('#zip').val(),
                        'form_country': $('#country').val()
                    };

                    // Set values for all form fields
                    Object.keys(formData).forEach(function (key) {
                        $(`#${key}`).val(formData[key]);
                    });

                    // Also ensure these values are passed as URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const additionalParams = {};

                    // Add URL parameters that should be preserved
                    ['cid', 'service', 'language', 'productname', 'price'].forEach(param => {
                        if (urlParams.has(param)) {
                            additionalParams[param] = urlParams.get(param);
                        }
                    });

                    // Update hidden inputs for URL parameters
                    Object.entries(additionalParams).forEach(([key, value]) => {
                        $(`input[name="${key}"]`).val(value);
                    });

                    updateProgressBar(2);

                    // Scroll to top of form
                    $('html, body').animate({
                        scrollTop: $('.product-info').offset().top - 20
                    }, 300);
                } else {
                    // Find first error and scroll to it
                    const firstError = $(".is-invalid").first();
                    if (firstError.length) {
                        $("html, body").animate(
                            {
                                scrollTop: firstError.offset().top - 100,
                            },
                            500
                        );
                    }
                }
            });

            // Back button click handler
            $('#back-btn').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Reset progress bar to step 1
                updateProgressBar(1);

                // Scroll to top of form
                $('html, body').animate({
                    scrollTop: $('.product-info').offset().top - 20
                }, 300);
            });

            // Initialize Stripe elements when document is ready
            $(window).on('load', function () {
                if (typeof initStripeElements === 'function') {
                    // We'll initialize Stripe elements when showing the payment form
                    // to avoid potential issues with hidden elements
                }
            });

            // Also try to initialize validation when the page is fully loaded
            $(window).on('load', function () {
                console.log("Window loaded, checking validation status");
                if (!validationInitialized && window.siteTranslations && Object.keys(window.siteTranslations).length > 0) {
                    console.log("Initializing validation on window load");
                    setTimeout(function () {
                        initializeFormValidation();
                    }, 300);
                }
            });
        });

    </script>

    </body>
    <?php echo ($c->getDebug()) ?>

    </html>