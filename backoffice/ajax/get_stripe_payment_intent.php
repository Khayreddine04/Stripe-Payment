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

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

$response = array( "res" => false, "msg" => "", "intent" => 0 );

// Extract API currency data from session and override POST data if available
$api_currency_data = $_SESSION['api_currency_data'] ?? null;

if ($api_currency_data && isset($api_currency_data['subscription_amount']['amount_numeric'])) {
    error_log("Using API currency data for payment intent from session");
    
    // Override POST data with API currency values
    $amount = $api_currency_data['subscription_amount']['amount_numeric'];
    $currency = $api_currency_data['subscription_amount']['currency_code'];
    $upfront_fee = $api_currency_data['upfront_amount']['amount_numeric'] ?? 0;
    
    // Update the POST data
    $_POST['pt_amount'] = $amount;
    $_POST['pt_currency'] = $currency;
    $_POST['pt_upfront'] = $upfront_fee;
    
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
    $countryCode = $c->_esc("pt_country");
    $ctc = $c->_esc("pt_ctc");
    $serviceId = $c->_esc("pt_service");

    try {
        $fetchedCurrencyData = getConvenientCurrencyData($countryCode, $ctc, $serviceId);
        if (isset($fetchedCurrencyData['success']) && $fetchedCurrencyData['success']) {
            error_log("Successfully fetched API currency data for payment intent");
            $amount = $fetchedCurrencyData['subscription_amount']['amount_numeric'];
            $currency = $fetchedCurrencyData['subscription_amount']['currency_code'];
            $upfront_fee = $fetchedCurrencyData['upfront_amount']['amount_numeric'];

            // Update the POST data
            $_POST['pt_amount'] = $amount;
            $_POST['pt_currency'] = $currency;
            $_POST['pt_upfront'] = $upfront_fee;

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
        $upfront_fee = $_POST['upfront_fee'] ?? 0;
    }
}

$payment = new PT_Stripe_Payment();

if ( $payment->getPaymentIntent() ) {
    //PT_Core::_dump($payment);
    $response['intent']      = $payment->intent;
    $response['setupIntent'] = $payment->setup_intent;
    $response['processing']  = $payment->payment_mode;
    $response['res']         = true;
} else {
    $response['msg'] = $payment->error;
}

echo json_encode( $response );
