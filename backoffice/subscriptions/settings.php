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

$pt_section = "subscriptions";
$pt_table = $db_pr . "subscriptions";
$pt_id = "idSubscription";
$pt_title = "Subscriptions";

$pt_table_data = array(

    array(
        'field' => $pt_id,
        'title' => "<input type='checkbox' >",
        "formatter" => "pt_id_formatter"),
    array('field' => 'customerName',
        'title' => "Customer"),
    array(
        'field' => 'amount',
        'title' => "Amount",
        "formatter" => "amount_formatter"),

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
        'field' => 'status',
        'title' => "Status",
        "formatter" => "status_formatter"),
    array(
        'field' => 'status',
        'title' => "Manage",
        "formatter" => function($d, $row){
            global $pt_id;
            return "<a class='btn btn-transparent-green ' href='view.php?{$pt_id}={$row[$pt_id]}'>" . __tr("Details") . "</a>";
        }),

    array('field'=>'dateCancelation','hidden'=>true),
    //array('field'=>'currency','hidden'=>true),
    array('field'=>'currency_symbol','hidden'=>true),
    array('field'=>'currency_position','hidden'=>true)

);
function pt_id_formatter($d, $row)
{
    return "<input type='checkbox' name='del_id[]' value='{$d}'>&nbsp;#{$d}";
}

function amount_formatter($d, $row) {
    global $settings;
    return PT_Core::_getCurrencyText($d,$row['currency_position'],$row['currency_symbol']);
}

function processor_formatter($d, $row) {
    return getProcessor($d);
}

function comments_formatter($d, $row) {
    return getNotes($d);
}

function dateCreated_formatter($d,$row){
    global $a;
    return $a->getDateFormat($d)." " .PT_Core::_getTimeFormat($d);
}
function status_formatter($d, $row) {
    global $pt_id;
    return getStatus($d,$row) . "</span>";
}

function getProcessor($k){
    return "<div style='text-align:center'><img src='../assets/images/icons/{$k}.png' style='width: 48px'/>
                <span style='display: none'>{$k}</span></div>";
}

function getNotes($k){
    if(!empty($k))
        return "<div style='text-align:center'><img src='../assets/images/icons/notes.png'
         style='width: 13px'
         data-toggle='tooltip'
         data-placement='left'
         title='".htmlentities($k,ENT_QUOTES)."'/><span style='display: none'>".htmlentities($k,ENT_QUOTES)."</span></div>";
}

function getStatus($status,$row){
    global $a;

    $str = "";
    switch($status){
        case "active": $str = "<span class='active'><i></i>Active";
            break;
        case "canceled": $str = "<span class='canceled'><i></i>Canceled<img src='../assets/images/icons/calendar.png'
         data-toggle='tooltip'
         data-placement='bottom'
         title='".htmlspecialchars($a->getDateFormat($row['dateCancelation']))."'/> ";
            break;
        case "pending": $str = "<span class='canceled'><i></i>Pending ";
            break;
	    case "payment_failed": $str = "<span class='warning'><i></i>Payment Failed <img src='../assets/images/icons/calendar.png'
         data-toggle='tooltip'
         data-placement='bottom'
         title='".htmlspecialchars($a->getDateFormat($row['dateCancelation']))."'/> ";
		    break;
    }

    return $str;
}
