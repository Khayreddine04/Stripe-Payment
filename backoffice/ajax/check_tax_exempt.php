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
$id_item      = $c->esc( "undefined");
if(is_numeric($id_item)){
    $item = new itemModel();
    $item->setID($id_item);
    $exemption_status = $item::getTaxExemption($id_item);
    echo $exemption_status;
} else {  echo "n"; }