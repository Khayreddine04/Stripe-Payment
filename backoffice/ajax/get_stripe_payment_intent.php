<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */

include_once "../../includes/bootstrap.php";
pt_send_checkout_cors_headers();
header('Content-Type: application/json; charset=utf-8');
$checkoutTimingStart = microtime(true);
pt_checkout_timing_log('payment_intent_endpoint_start', null, array('file' => 'get_stripe_payment_intent.php'));

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('pt_ajax_apply_payment_amounts')) {
    function pt_ajax_apply_payment_amounts($amount, $currency, $upfrontFee = 0)
    {
        global $c;

        $amount = is_numeric($amount) ? (string)$amount : '0';
        $currency = strtoupper(trim((string)$currency));
        if ($currency === '') {
            $currency = 'USD';
        }

        $_POST['amount'] = $amount;
        $_POST['pt_amount'] = $amount;
        $_POST['pt_currency'] = $currency;
        $_POST['pt_upfront'] = is_numeric($upfrontFee) ? (string)$upfrontFee : '0';
        $_REQUEST['amount'] = $amount;
        $_REQUEST['pt_amount'] = $amount;
        $_REQUEST['pt_currency'] = $currency;
        $_REQUEST['pt_upfront'] = $_POST['pt_upfront'];

        if (isset($c) && is_object($c) && property_exists($c, 'post')) {
            $c->post['amount'] = $amount;
            $c->post['pt_amount'] = $amount;
            $c->post['pt_currency'] = $currency;
            $c->post['pt_upfront'] = $_POST['pt_upfront'];
        }
    }
}

if (!function_exists('pt_ajax_currency_session_matches_checkout')) {
    function pt_ajax_currency_session_matches_checkout($data, $serviceId, $ctc)
    {
        if (!is_array($data) || empty($data['subscription_amount']['amount_numeric'])) {
            return false;
        }

        $sessionServiceId = trim((string)($data['service_id'] ?? ''));
        $sessionCtc = trim((string)($data['ctc'] ?? ''));

        return $sessionServiceId !== ''
            && $sessionServiceId === trim((string)$serviceId)
            && ($sessionCtc === '' || $sessionCtc === trim((string)$ctc));
    }
}

// Log session contents for debugging
error_log("=== SESSION DATA AT PAYMENT INTENT ===");
error_log("Session ID: " . session_id());
error_log("API Currency Data: " . print_r($_SESSION['api_currency_data'] ?? 'NOT SET', true));
error_log("====================================");

$amount      = $c->_esc( "amount" );
$pt_amount   = $c->_esc( "pt_amount", $amount );
$currency    = $c->_esc( "currency" );
$pt_currency = $c->_esc( "pt_currency", $currency );

$pt_type = $c->_esc( "pt_type", "card" );

$pt_currency_symbol   = $c->_esc( "pt_currency_symbol", "" );
$pt_currency_position = $c->_esc( "pt_currency_position", "" );

$pt_service      = $c->_esc( "pt_service" );
$pt_payment_type = $c->_esc( "pt_payment_type", 'once' );
$pt_payments_count = $c->_esc( "pt_payments_count", 0 );
$pt_name         = $c->_esc( "pt_name" );
$pt_email        = $c->_esc( "pt_email" );
$pt_description  = $c->_esc( "pt_description" );

$invoice   = $c->_esc( "invoice", 0 );
$idInvoice = $c->_esc( "idInvoice", 0 );
$stripeButton = $c->_esc("stripeButton", 'n');
$pt_ctc = $c->_esc("pt_ctc");

$response = array( "res" => false, "msg" => "", "intent" => 0, "checkout_trace" => pt_checkout_timing_trace() );

// Extract API currency data from session and override POST data if available
$currencyTimingStart = microtime(true);
$api_currency_data = $_SESSION['api_currency_data'] ?? null;
$api_currency_data = pt_ajax_currency_session_matches_checkout($api_currency_data, $pt_service, $pt_ctc) ? $api_currency_data : null;

if ($api_currency_data && isset($api_currency_data['subscription_amount']['amount_numeric'])) {
    error_log("Using API currency data for payment intent from session");
    
    // Override POST data with API currency values
    $amount = $api_currency_data['subscription_amount']['amount_numeric'];
    $currency = $api_currency_data['subscription_amount']['currency_code'];
    $upfront_fee = $api_currency_data['upfront_amount']['amount_numeric'] ?? 0;
    pt_ajax_apply_payment_amounts($amount, $currency, $upfront_fee);
    
    // Also update the amount and currency variables used below
    $pt_amount = $amount;
    $pt_currency = $currency;
    
    // Log the overridden values
    error_log("Payment amounts set to - Amount: $amount, Currency: $currency, Upfront: $upfront_fee");
    
    // Store the upfront fee in the session for later use
    if (!isset($_SESSION['payment_data'])) {
        $_SESSION['payment_data'] = [];
    }
    $_SESSION['payment_data']['upfront_fee'] = (float)$upfront_fee;
    
} else {
    error_log("WARNING: No API currency data found in session, attempting to fetch from API");
    
    // Include the functions file
    include_once "../../includes/functions.php";

    // Get parameters from POST
    $countryCode = "";
    $ctc = $c->_esc("pt_ctc");
    $serviceId = $c->_esc("pt_service");

    try {
        $fetchedCurrencyData = getConvenientCurrencyData($countryCode, $ctc, $serviceId);
        if (isset($fetchedCurrencyData['success']) && $fetchedCurrencyData['success']) {
            error_log("Successfully fetched API currency data for payment intent");
            $amount = $fetchedCurrencyData['subscription_amount']['amount_numeric'];
            $currency = $fetchedCurrencyData['subscription_amount']['currency_code'];
            $upfront_fee = $fetchedCurrencyData['upfront_amount']['amount_numeric'];
            pt_ajax_apply_payment_amounts($amount, $currency, $upfront_fee);

            // Also update the amount and currency variables used below
            $pt_amount = $amount;
            $pt_currency = $currency;

            // Log the overridden values
            error_log("Payment amounts set to - Amount: $amount, Currency: $currency, Upfront: $upfront_fee");

            // Store the upfront fee in the session for later use
            if (!isset($_SESSION['payment_data'])) {
                $_SESSION['payment_data'] = [];
            }
            $_SESSION['payment_data']['upfront_fee'] = (float)$upfront_fee;
        } else {
            throw new Exception("Failed to fetch currency data from API.");
        }
    } catch (Exception $e) {
        error_log("ERROR: Failed to fetch API currency data for payment intent. " . $e->getMessage());
        // If no API data, try to get values from POST or use defaults
        $amount = $_POST['pt_amount'] ?? $amount ?? 0;
        $currency = $_POST['pt_currency'] ?? $currency ?? 'USD';
        $upfront_fee = $_POST['pt_upfront'] ?? $_POST['upfront_fee'] ?? 0;
        pt_ajax_apply_payment_amounts($amount, $currency, $upfront_fee);
    }
}
pt_checkout_timing_log('payment_intent_currency_ready', $currencyTimingStart, array('currency' => $pt_currency, 'amount' => $pt_amount));

$payment = new PT_Stripe_Payment();

$paymentIntentTimingStart = microtime(true);
if ( $payment->getPaymentIntent() ) {
    //PT_Core::_dump($payment);
    $response['intent']      = $payment->intent;
    $response['setupIntent'] = $payment->setup_intent;
    $response['processing']  = $payment->payment_mode;
    $response['res']         = true;
} else {
    $response['msg'] = $payment->error;
}
pt_checkout_timing_log('payment_intent_getPaymentIntent', $paymentIntentTimingStart, array('res' => $response['res'] ? '1' : '0'));
pt_checkout_timing_log('payment_intent_endpoint_total', $checkoutTimingStart, array('res' => $response['res'] ? '1' : '0'));

echo json_encode( $response );
