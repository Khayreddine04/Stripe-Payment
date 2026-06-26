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

//REQUIRE CONFIGURATION FILE
require("includes/bootstrap.php"); //important file. Don't forget to edit it!
require('includes/processor/paypal.class.php');  // include the class file
$paypal = new paypal_class;             // initiate an instance of the class

if ($settings->paypal_payment_mode == 'live') {
    $paypal->paypal_url = "https://www.paypal.com/cgi-bin/webscr";     // paypal url
} else {
    $paypal->paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";     // paypal url
}
if (!empty($settings->terminal_logo)) {
    $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
}
if ($paypal->validate_ipn()) {
    print_r($paypal->pp_data["txn_type"]);
    if (isset($paypal->pp_data["txn_type"]) && strtolower($paypal->pp_data["txn_type"]) == "subscr_payment") {

        if (PT_Settings::type() == 'var') {

            $data = $paypal->pp_data['custom'];
            PT_Core::_dump($paypal->pp_data);
            if ($dataArray = json_decode($data, true)) {
                //print_r($dataArray);
                $payment_data = array(
                    'paypalStatus' => 'paid',
                    'processor' => 'paypal',
                    'idItem' => '',
                    'idInvoice' => 0,
                    'billingAddress1' => '',
                    'billingAddress2' => '',
                    'billingCity' => '',
                    'billingCountry' => '',
                    'billingState' => '',
                    'billingZip' => '',
                    'shippingAddress1' => '',
                    'shippingAddress2' => '',
                    'shippingCity' => '',
                    'shippingCountry' => '',
                    'shippingState' => '',
                    'shippingZip' => '',
                    'stripeCharge' => '',
                    'stripeCustomer' => '',
                    'stripeSubscription' => '',
                    'customerName' => $paypal->pp_data['first_name'].' '.$paypal->pp_data['last_name'],
                    'customerEmail' => $paypal->pp_data['payer_email'],
                    'amount' => $paypal->pp_data['payment_gross'],
                    'currency' => $paypal->pp_data['mc_currency'],
                    'currency_symbol' => isset($CURRENCY_SYMBOLS[$paypal->pp_data['mc_currency']]) ? $CURRENCY_SYMBOLS[$paypal->pp_data['mc_currency']] : '',
                    'currency_position' => 'before',
                    'comments' => '',
                    'idTransaction' => $paypal->pp_data['txn_id'],
                    'paypalSubscription' => $paypal->pp_data['subscr_id'],
                    'dateCreated' => date("Y-m-d H:i" ,strtotime($paypal->pp_data['payment_date']))
                );

                $paymentModel = new paymentModel();
                $paymentModel->importPayPalPayment($payment_data);
            }
        }


    }elseif (isset($paypal->pp_data["txn_type"]) && strtolower($paypal->pp_data["txn_type"]) == "subscr_cancel") {

        if (PT_Settings::type() == 'var') {
            $subscription = new subscriptionModel();
            $subscription->cancelSubscription($paypal->pp_data['subscr_id']);
        }

        $mailData = array(
            "{%logo_block%}"=>$logoBlock,
            "{%site_url%}" => $settings->site_url,
            "{%fname%}" => $paypal->pp_data["first_name"],
            "{%lname%}" => $paypal->pp_data["last_name"],
            "{%email%}" => $paypal->pp_data['payer_email'],
            "{%subscription_name%}" => $paypal->pp_data["item_name"],
            "{%subscription_id%}" => $paypal->pp_data['subscr_id']
        );
        //paypal subscription cancellation email to admin and customer
        $c->sendMail($paypal->pp_data['payer_email'], "Customer cancelled subscription", "paypal/cancel_subscription_customer.html", $mailData,false);
        $c->sendMail($settings->email, "Customer cancelled subscription", "paypal/cancel_subscription_admin.html", $mailData,false);



    } else if (isset($paypal->pp_data["payment_status"]) && strtolower($paypal->pp_data["payment_status"]) == "refunded") {
        //paypal process refund email here.

        $mailData = array(
            "{%logo_block%}"=>$logoBlock,
            "{%site_url%}" => $settings->site_url,
            "{%fname%}" => $paypal->pp_data["first_name"],
            "{%lname%}" => $paypal->pp_data["last_name"],
            "{%email%}" => $paypal->pp_data['payer_email'],
            "{%amount%}" => PT_Core::_getCurrencyText($paypal->pp_data["mc_gross"],"after",$paypal->pp_data["mc_currency"]),
            "{%transaction_id%}" => $paypal->pp_data["txn_id"]
        );

        if (PT_Settings::type() == 'var') {

            $data = $paypal->pp_data['custom'];

            if ($dataArray = json_decode($data, true)) {
                print_r($dataArray);
                $idSubscription = isset($dataArray['idSubscription']) ? $dataArray['idSubscription'] : 0;
                $idInvoice = isset($dataArray['idInvoice']) ? $dataArray['idInvoice'] : 0;
                $id = isset($dataArray['id']) ? $dataArray['id'] : 0;

                if (!empty($idSubscription)) {
                    $subscription = new subscriptionModel();
                    $trnId = $paypal->pp_data['subscr_id'];
                    $subscription->activatePaypalSubscription($idSubscription, $trnId);
                } elseif (!empty($id)) {
                    $trnId = $paypal->pp_data['txn_id'];
                    $payment = new paymentModel();
                    $payment->refundPayment($id, $trnId);
                    if (!empty($idInvoice)) {
                        $invoice = new invoiceModel();
                        $invoice->setID($idInvoice);
                        $invoice->setUsRefunded();
                    }
                }
            }
        }
        $c->sendMail($paypal->pp_data['payer_email'], "Payment Refund", "paypal/refund.html", $mailData,false);


    } else {
        #**********************************************************************************************#
        #  THIS IS THE PLACE WHERE YOU WOULD INSERT ORDER TO DATABASE OR UPDATE ORDER STATUS FOR PAYPAL
        #**********************************************************************************************#
        //you can use $paypal->pp_data['XXXX'] -> where XXXX is any variable which you will see in
        //confirmation email which is sent below (you will need to do a test transaction to receive this email)

        if (PT_Settings::type() == 'var') {

            $data = $paypal->pp_data['custom'];

            if ($dataArray = json_decode($data, true)) {
                //print_r($dataArray);
                $idSubscription = isset($dataArray['idSubscription']) ? $dataArray['idSubscription'] : 0;
                $idInvoice = isset($dataArray['idInvoice']) ? $dataArray['idInvoice'] : 0;
                $id = isset($dataArray['id']) ? $dataArray['id'] : 0;

                if (!empty($idSubscription)) {
                    $subscription = new subscriptionModel();
                    $trnId = $paypal->pp_data['subscr_id'];
                    $subscription->activatePaypalSubscription($idSubscription, $trnId);
                } elseif (!empty($id)) {
                    $trnId = $paypal->pp_data['txn_id'];
                    $payment = new paymentModel();
                    $payment->activatePaypalPayment($id, $trnId);

                }
                if (!empty($idInvoice)) {
                    $invoice = new invoiceModel();
                    $invoice->setID($idInvoice);
                    $invoice->setUsPaid();
                    $invoice->addHistory("paid","Invoice paid by PayPal, Trn ID #{$trnId}");
                }
            }
        }

        #**********************************************************************************************#
        //creating message for sending

        $message = "";
        foreach ($paypal->pp_data as $k => $v) {
            $message .= "<br /><strong>" . $k . "</strong>: " . $v;
        }

        $trx_tax_amount = "";
        $trx_tax_abbreviation = "";
        $trx_tax_rate = "";
        if(!empty($id)){
            $paymentInfo = new paymentModel();
            $paymentInfo->getPayment($id);
            if($paymentInfo->paymentData["tax_abbreviation"]!=="" && $paymentInfo->paymentData["tax_abbreviation"]!==NULL ) {
                $trx_tax_amount = $paymentInfo->paymentData["tax_amount"];
                $trx_tax_abbreviation = $paymentInfo->paymentData["tax_abbreviation"] . " Paid: ";
                $trx_tax_rate = $paymentInfo->paymentData["tax_rate"] . "%";
            }
        }
        $mailData = array(
            "{%site_url%}" => $settings->site_url,
            "{%logo_block%}"=>$logoBlock,
            "{%fname%}" => $paypal->pp_data["first_name"],
            "{%lname%}" => $paypal->pp_data["last_name"],
            "{%email%}" => $paypal->pp_data['payer_email'],
            "{%tax_amount%}" => $trx_tax_amount,
            "{%tax_abbreviation%}" => $trx_tax_abbreviation,
            "{%tax_rate%}" => $trx_tax_rate,
            "{%amount%}" => PT_Core::_getCurrencyText($paypal->pp_data["mc_gross"],"after",$paypal->pp_data["mc_currency"]),
            "{%transaction_id%}" => $paypal->pp_data["txn_id"],
            "{%ipn_message%}" => $message,
            "{%date%}" => $c->_getTimeFormat(NOW_DATE_TIME),
            "{%time%}" => $c->getDateFormat(NOW_DATE_TIME)
        );

        $c->sendMail($settings->email, "PayPal Payment Received", "paypal/payment_received_admin.html", $mailData,false);
        $c->sendMail($paypal->pp_data['payer_email'], "PayPal Payment Received", "paypal/payment_received_customer.html", $mailData,false);
    }
}
