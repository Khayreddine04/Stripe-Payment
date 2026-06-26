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

$pt_section = "items";
$pt_table = $db_pr . "items";
$pt_id = "idItem";
$pt_title = "Items";

$pt_table_data = array(

    array(
        'field' => $pt_id,
        'title' => "<input type='checkbox' >",
        "formatter" => "pt_id_formatter"
    ),
    array('field' => 'itemName', 'title' => "Item Name"),
    array(
        'field' => 'itemType',
        'title' => "Type",
        "formatter" => "itemType_formatter"
    ),
    array(
        'field' => 'itemPlan',
        'title' => "Payment Plan",
        "formatter" => "itemPlan_formatter"
    ),
    array(
        'field' => 'itemAmount',
        'title' => "Amount",
        "formatter" => "itemAmount_formatter"
    ),
    array(
        'field' => 'itemTrial',
        'title' => "Manage",
        "formatter" => function ($d, $row) {
            global $pt_id;
            return "
                <span class='glyphicon glyphicon-off' style='color:" . ($row['itemStatus'] == 'y' ? 'green' : 'red') . "' data-placement='right' data-toggle='tooltip' title='Item is " . ($row['itemStatus'] == 'y' ? 'enabled' : 'disabled') . "'></span>
                <a class='btn btn-transparent-green ' href='edit.php?{$pt_id}={$row[$pt_id]}'>" . __tr("Details") . "</a>&nbsp;
                <a class='btn btn-transparent-black ' target='_blank' href='../../go.php?item_id={$row[$pt_id]}'>" . __tr("Link") . "</a>&nbsp;";
        }
    ),
    array('field' => 'itemStatus', 'hidden' => true)

);

function pt_id_formatter($d, $row)
{
    $truncatedId = strlen($d) > 8 ? substr($d, 0, 8) . '...' : $d;
    return "<span style='white-space: nowrap' title='{$d}'><input type='checkbox' name='del_id[]' value='{$d}'>&nbsp;#{$truncatedId}</span>";
}

function itemPlan_formatter($d, $row)
{
    return $d == 'y' ? '<span class="text-success">On</span>' : '<span>Off</span>';
}

function itemType_formatter($d, $row)
{
    return $d == 'product' ? 'Fixed Price' : 'Recurring';
}

function itemAmount_formatter($d, $row)
{
    global $pt_id, $settings;
    return "<span>" . PT_Core::getCurrencyText($d, false) . "</span>";
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
