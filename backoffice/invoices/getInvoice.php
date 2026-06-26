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

include_once "../includes/bootstrap.php";
include_once "settings.php";

$idInvoice = $a->esc('idInvoice');


$invoice = new invoiceModel();
$invoice->setID($idInvoice);
if($invoice->setInvoiceData()===false)
    exit("Invoice not found");

$invoice->generateInvoice();
