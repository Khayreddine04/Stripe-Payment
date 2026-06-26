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

include_once "includes/bootstrap.php";

$idInvoice = $_SERVER['QUERY_STRING'];

$print = $c->esc('print', true);
$invoice = new invoiceModel();
$invoice->setHashNumber($idInvoice);
if ($invoice->setInvoiceData() === false)
    exit("Invoice not found");

$invoice->addHistory("view","Customer printed the invoice");

$invoice_template = new PT_Template("invoice_template.php");
$invoice_template->invoiceData = $invoice->invoiceData;
$invoice_template->invoiceAmount = $invoice->formattedAmount($invoice->invoiceData['invoiceTotal']);
$invoice_template->invoiceItems = $invoice->invoiceItemsData;
$invoice_template->viewLink = $invoice->getPaymentLink();
$invoice_template->paid = $invoice->isPaid();

$invoice_template->currency_symbol = $invoice->currencySymbol;
$invoice_template->c = empty($invoice->currencySymbol) ? " " . $invoice->invoiceData['invoiceCurrency'] : "";

$invoice_layout = new PT_Template("view_invoice_layout.php");
$invoice_layout->invoice_template = $invoice_template->render();
$invoice_layout->invoice_print = $print;

$invoice_layout->render(true);
