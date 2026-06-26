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

$pt_section = "customers";
$pt_table = $db_pr . "customers";
$pt_id = "idCustomer";
$pt_title = "Customers";

$pt_table_data = array(

    array(
        'field' => $pt_id,
        'title' => "<input type='checkbox' >",
        "formatter" => "pt_id_formatter"),
    array('field' => 'customerName', 'title' => "Customer Name"),
    array('field' => 'customerEmail', 'title' => "Customer Email",
        "formatter" => "customerEmail_formatter"),
    array(
        'field' => 'dateCreated',
        'title' => "Manage",
        "formatter" => function($d, $row){
            global $pt_id,$a;
            return "<a class='btn btn-transparent-green ' href='edit.php?{$pt_id}={$row[$pt_id]}'>" . __tr("Details") . "</a>";
        }),

);

function pt_id_formatter($d, $row){
    return "<input type='checkbox' name='del_id[]' value='{$d}'>&nbsp;#{$d}";
}

function customerEmail_formatter($d, $row){
    global $pt_id,$settings;
    return "<span>" . $d."</span>";
}
