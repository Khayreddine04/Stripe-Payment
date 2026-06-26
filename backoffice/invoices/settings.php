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

$pt_section = "invoices";
$pt_table = $db_pr . "invoices";
$pt_table_items = $db_pr . "invoice_items";
$pt_id = "idInvoice";
$pt_title = "Invoices";

$pt_table_data = array(

    array(
        'field' => $pt_id,
        'title' => "<input type='checkbox' >",
        "formatter" => "pt_id_formatter"),
    array('field' => 'invoiceNumber',
        'title' => "Invoice #"),
    array('field' => 'paymentDate',
        'title' => "&nbsp;",
        'formatter' => "paymentDate_formatter"),
    array('field' => 'customerName',
        'title' => "Customer"),
    array('field' => 'invoiceTotal',
        'title' => "Amount",
        'formatter' => "invoiceTotal_formatter"),

    array(
        'field' => 'dateCreated',
        'title' => "Date Created",
        "formatter" => "dateCreated_formatter1"),
    array(
        'field' => 'dateCreated',
        'title' => "Due In",
        "formatter" => "dateCreated_formatter"),
    array(
        'field' => 'invoiceStatus',
        'title' => "Status",
        "formatter" => "invoiceStatus_formatter"),
    array(
        'field' => 'invoiceCurrency',
        'title' => "Manage",
        "formatter" => function($d, $row){
            global $pt_id;
            $link = $row['invoiceType'] =='single'?"edit":"edit_recurring";
            return "<a class='btn btn-transparent-green ' href='{$link}.php?{$pt_id}={$row[$pt_id]}'>" . __tr("Details") . "</a>";
        }),

    array('field'=>'invoiceCurrencyPosition','hidden'=>true),
    array('field'=>'invoiceCurrencySymbol','hidden'=>true),
    array('field'=>'invoiceType','hidden'=>true)

);

function pt_id_formatter($d, $row)
{
    return "<input type='checkbox' name='del_id[]' value='{$d}'>&nbsp;#{$d}";
}

function paymentDate_formatter($d, $row) {
    global $settings, $pt_id;
    return "<a href='getInvoice.php?idInvoice=" . $row[$pt_id] . "' target='_blank'><img src='../assets/images/icons/pdf.png'/></a>";
}

function invoiceTotal_formatter($d, $row) {
    global $settings;

    return PT_Core::_getCurrencyText($d,$row['invoiceCurrencyPosition'],$row['invoiceCurrencySymbol']);
}

function dateCreated_formatter($d, $row) {
    global $pt_id;
    $invoice = new invoiceModel();
    $invoice->setID($row[$pt_id]);
    $invoice->setInvoiceData();
    return $invoice->getDueText();
}

function dateCreated_formatter1($d, $row) {
    global $a;
    return $a->getDateFormat($d);
}

function invoiceStatus_formatter($d, $row) {

    return getStatus($d, $row) . "</span>";
}


function getProcessor($k)
{
    switch ($k) {
        case "paypal":
            return "Pay Pal";
            break;
        case "stripe":
            return "Stripe";
            break;
    }
}

function getStatus($status, $row)
{
    global $a;

    $str = "";
    switch ($status) {
        case "unpaid":
            $str = "<span class='warning'><i></i>Unpaid";
            break;
        case "paid":
            $str = "<span class='active'><i></i>Paid<img src='../assets/images/icons/calendar.png'
         data-toggle='tooltip'
         data-placement='bottom'
         title='" . htmlspecialchars($a->getDateFormat($row['paymentDate'])) . "'/> ";
            break;
        case "refunded":
            $str = "<span class='canceled'><i></i>Refunded<img src='../assets/images/icons/calendar.png'
         data-toggle='tooltip'
         data-placement='bottom'
         title='" . htmlspecialchars($a->getDateFormat($row['paymentDate'])) . "'/> ";
            break;
    }

    return $str;
}
