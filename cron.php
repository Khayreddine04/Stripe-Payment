<?php
/**
 * Author:     CriticalGears (http://www.convergine.io)
 * Website:    http://www.criticalgears.io
 * Support:    http://criticalgears.io/support-tickets
 *
 * Copyright:   (c)    CriticalGears.io
 *
 */

use Stripe\Stripe;

include_once "includes/bootstrap.php";


$payment = new PT_Stripe_Payment();
$importedRecords = $payment->importTransactions();

echo "{$importedRecords} payments has been successfully imported";

st_do_action("cron");
