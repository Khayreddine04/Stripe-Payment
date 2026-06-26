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
$payment  = new PT_Stripe_Payment();

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