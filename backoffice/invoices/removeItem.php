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

$invoice = new invoiceModel();
$res = false;

$idItem = $a->esc('idItem');

if($invoice->deleteItem($idItem))
    $res = true;

echo json_encode(array("res"=>$res));
