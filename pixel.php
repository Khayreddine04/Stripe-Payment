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

$idInvoice = $c->esc('invoice');
$a = $c->esc('a');

$invoice = new invoiceModel();
$invoice->setHashNumber($idInvoice);
if ($invoice->setInvoiceData() !== false) {
    if (!empty($a) && $a=='open_mail') {

        $invoice->addHistory('view','Customer open email');
    }
}

header("Content-type: image/png");
echo file_get_contents('assets/images/pixel.png');
