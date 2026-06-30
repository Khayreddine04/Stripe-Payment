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

$countryId      = $c->esc( "countryId",0,true);
$pt_state      = $c->esc( "pt_state");
if(is_numeric($countryId)){
    $payment = new PT_Stripe_Payment();
    $html = $payment->getStatesListJSON($countryId);
    echo $html;
} else {  echo null; }
