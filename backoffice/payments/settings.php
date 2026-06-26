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

$pt_section = "payments";
$pt_table = $db_pr . "payments";
$pt_id = "idPayment";
$pt_title = "Payments";

$pt_order_col = 7;
$pt_order_type = 'desc';

$pt_table_data = array(

    array(
        'field' => $pt_id,
        'title' => "<input type='checkbox' >",
        "formatter" => "pt_id_formatter"),
    array(
        'field' => 'paypalStatus',
        'title' => "Status",
        "formatter" => "status_formatter"),
    array('field' => 'customerName', 'title' => "Customer"),

    array(
        'field' => 'amount',
        'title' => "Amount",
        "formatter" => "amount_formatter"),
    array(
        'field' => 'idTransaction',
        'title' => "Transaction ID",
        "formatter" => "idTransaction_formatter"),
    array(
        'field' => 'processor',
        'title' => "Processor",
        "formatter" => "processor_formatter"),
    array(
        'field' => 'comments',
        'title' => "Notes",
        "formatter" => "comments_formatter"),
    array(
        'field' => 'dateCreated',
        'title' => "Date Created",
        "formatter" => "dateCreated_formatter"),
    array(
        'field' => 'currency',
        'title' => "Manage",
        "formatter" => function($d, $row){
            global $pt_id,$a;
            return "<a class='btn btn-transparent-green ' href='view.php?{$pt_id}={$row[$pt_id]}'>" . __tr("Details") . "</a>";
        }),
    array('field'=>'currency_symbol','hidden'=>true),
    array('field'=>'imported','hidden'=>true),
    array('field'=>'currency_position','hidden'=>true),
    array('field'=>'refundDate','hidden'=>true)

);

function pt_id_formatter($d, $row) {
    return "<input type='checkbox' name='del_id[]' value='{$d}'>";
}

function amount_formatter ($d, $row) {
    global $settings;
    $amount = PT_Core::_getCurrencyText($d,$row['currency_position'],$row['currency_symbol']);
    return $row['paypalStatus'] == 'refunded'?'<del>' . $amount .'</del>':$amount;
}

function status_formatter ($d, $row){
    global $a;

    $str = "";
    switch($d){
        case "paid": $str = "<span class='active'><i></i>Paid";
            break;
        case "refunded": $str = "<span class='warning'><i></i>Refunded ";
            break;
        case "pending": $str = "<span class='canceled'><i></i>Pending ";
            break;
	    case "partial_refund": $str = "<span class='refunded'><i></i>Partial Refund";
		    break;
    }

    return $str.'</span>';
}

function idTransaction_formatter ($d, $row) {
    return (empty($d) || $d=='cash'?"N/A":$d)
    . ($row['imported']=='y'?'<img src=\'../assets/images/icons/import.svg\' style="margin-left: 5px;height: 16px"/>':'');
}

function processor_formatter($d, $row) {
    return $row['idTransaction']=='cash'?getProcessor('cash'):getProcessor($d);
}

function comments_formatter($d, $row) {
    return getNotes($d);
}

function dateCreated_formatter($d, $row) {
    global $pt_id,$a;

    return "<span>".$a->getDateFormat($d) ." " .PT_Core::_getTimeFormat($d). "</span>";
}

function getProcessor($k){
    return "<div style='text-align:center'><img src='../assets/images/icons/{$k}.png'/></div>
<span style=\"display: none\">{$k}</span>";
}



function getNotes($k){
    if(!empty($k))
     return "<div style='text-align:center'><img src='../assets/images/icons/notes.png'
         data-toggle='tooltip'
         data-placement='left'
         title='".htmlentities($k,ENT_QUOTES)."'/></div>
         <span style='display: none'>".htmlentities($k,ENT_QUOTES)."</span>";
}
