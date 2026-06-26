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

$settings->set("admin_section", $pt_section);

if (!$user->logon) {
    header("Location: ../index.php");
    exit();
}
$a->addStyle($a->getSiteUrl()."/assets/js/data_table/css/dataTable.bootstrap.css");
$a->addStyle("//cdn.datatables.net/responsive/1.0.7/css/responsive.dataTables.min.css");
$a->addStyle("../../assets/js/typeahead/css/typeahead.css");
$a->addStyle("../../assets/bootstrap/css/datepicker3.css");
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-datepicker.js", false);
$a->addScripts("../../assets/js/typeahead/js/typeahead.bundle.min.js");
$a->addScripts("../../assets/js/date.format.js");
$a->addScripts("script_recurring.js", false);

$itemModel = new itemModel();
$customerModel = new customerModel();
$invoice = new invoiceModel();
$action = $a->esc("action");
$$pt_id = (int)$a->esc($pt_id);
$customerName = $a->esc("customerName");
$customerEmail = $a->esc("customerEmail");
$send_cc = $a->esc("send_cc");
$send_bcc = $a->esc("send_bcc");
$invoiceNumber = $a->esc("invoiceNumber");
$orderNumber = $a->esc("orderNumber");
$invoiceBillTo = $a->esc("invoiceBillTo");
$invoiceDate = $a->esc("invoiceDate", $a->dateToIso(date("Y-m-d")));
$invoiceTerm = $a->esc("invoiceTerm");
$invoiceDueDate = $a->esc("invoiceDueDate", $a->dateToIso(date("Y-m-d")));
$invoiceLateFee = (int)$a->esc("invoiceLateFee",0);
$invoiceNotes = $a->esc("invoiceNotes");
$invoiceTerms = $a->esc("invoiceTerms");
$invoiceCurrency = $a->esc("invoiceCurrency", $settings->currency_text);
$idCustomer = $a->esc("idCustomer");


if ($settings->multiple_currencies == 'y') {
    if ($invoiceCurrency == $settings->default_terminal_currency) {
        $invoiceCurrencySymbol = $settings->display_currency;
        $invoiceCurrencyPosition = $settings->currency_position;
    } else {
        $invoiceCurrencySymbol = " " . $invoiceCurrency;
        $invoiceCurrencyPosition = "after";
    }
} else {
    $invoiceCurrencyPosition = $a->esc("invoiceCurrencyPosition", $settings->currency_position);
    $invoiceCurrencySymbol = $a->esc("invoiceCurrencySymbol", $settings->display_currency);
}


$itemItem = $a->esc('itemItem', array());
$itemName = $a->esc('itemName', array());
$itemDescription = $a->esc('itemDescription', array());
$itemQty = $a->esc('itemQty', array());
$itemRate = $a->esc('itemRate', array());
$itemDiscount = $a->esc('itemDiscount', array());
$itemTax = $a->esc('itemTax', array());
$itemTotal = $a->esc('itemTotal', array());
$_itemItem = $a->esc('_itemItem', array());
$_idItem = $a->esc('_idItem', array());
$_itemName = $a->esc('_itemName', array());
$_itemDescription = $a->esc('_itemDescription', array());
$_itemQty = $a->esc('_itemQty', array());
$_itemRate = $a->esc('_itemRate', array());
$_itemDiscount = $a->esc('_itemDiscount', array());
$_itemTax = $a->esc('_itemTax', array());
$_itemTotal = $a->esc('_itemTotal', array());
$now = NOW_DATE_TIME;

$invoiceSubTotal = 0;
$invoiceTax = 0;
$invoiceLateFeeCalc = 0;
$invoiceDiscount = 0;
$invoiceTotal = 0;

if ($action == 'update') {

    $invoiceDate = $a->dateFromIso($invoiceDate);
    $invoiceDueDate = $a->dateFromIso($invoiceDueDate);

    $checkCustomer = $customerModel->getCustomerByEmail( $customerEmail );
    if ( empty($idCustomer) && $checkCustomer !== false ) {
        $idCustomer = $checkCustomer['idCustomer'];
    }

    if (empty($$pt_id)) {

        if ( empty( $idCustomer ) ) {

            if ( ! $a->error ) {

                $sql = "INSERT INTO {$customerModel->table} SET
                            customerName = '{$customerName}',
                            customerEmail = '{$customerEmail}',
                            customerTerm = '{$invoiceTerm}',
                            customerBill = '{$invoiceBillTo}',
                            dateCreated = '" . NOW_DATE_TIME . "'";
                $res = $a->query( $sql );
                if ( $res->count ) {
                    $idCustomer = $res->insert_id;
                    $a->addSuccess( "Customer '" . stripslashes( $customerName ) . "' has been successfully created" );
                } else {
                    $a->addError( "Error creating Customer" );
                }

            }

        }
        if (!$a->error) {
            $invoiceNumber = $invoice->getUniqueInvoiceNumber($invoiceNumber, 0);
            $sql = "INSERT INTO `{$pt_table}` SET
            `dateCreated` = '{$now}',
            `invoiceType` = 'recurring',
            `idCustomer` = '{$idCustomer}',
            `customerName` = '{$customerName}',
            `customerEmail` = '{$customerEmail}', 
            `invoiceNumber` = '{$invoiceNumber}',
            `invoiceBillTo` = '{$invoiceBillTo}',
            `orderNumber` = '{$orderNumber}',
            `invoiceCurrency` = '{$invoiceCurrency}',
            `invoiceCurrencyPosition` = '{$invoiceCurrencyPosition}',
            `invoiceCurrencySymbol` = '{$invoiceCurrencySymbol}',
            `invoiceDate` = '{$invoiceDate}', 
            `invoiceTerm` = '{$invoiceTerm}', 
            `invoiceDueDate` = '{$invoiceDueDate}', 
            `invoiceLateFee` = '{$invoiceLateFee}', 
            `invoiceNotes` = '{$invoiceNotes}', 
            `invoiceTerms` = '{$invoiceTerms}'";

            $res = $a->query($sql);

            if ($res->count) {
                $$pt_id = $res->insert_id;
                $a->addSuccess("Invoice has been created");
                st_do_action('add_user_log',"Created recurring invoice #{$invoiceNumber}");
            } else {
                $a->addError("Invoice not added. Undefined error");
            }

            $invoice->setID($$pt_id);
            $invoice->addHistory('create', "Invoice created");
        }
    } else {
        $invoiceNumber = $invoice->getUniqueInvoiceNumber($invoiceNumber, $$pt_id);
        $sql = "UPDATE `{$pt_table}` SET
                `idCustomer` = '{$idCustomer}',
                `customerName` = '{$customerName}', 
                `customerEmail` = '{$customerEmail}', 
                `invoiceNumber` = '{$invoiceNumber}',
                `invoiceBillTo` = '{$invoiceBillTo}',
                `orderNumber` = '{$orderNumber}',
                `invoiceCurrency` = '{$invoiceCurrency}',
                `invoiceCurrencyPosition` = '{$invoiceCurrencyPosition}',
                `invoiceCurrencySymbol` = '{$invoiceCurrencySymbol}',
                `invoiceDate` = '{$invoiceDate}', 
                `invoiceTerm` = '{$invoiceTerm}', 
                `invoiceDueDate` = '{$invoiceDueDate}', 
                `invoiceLateFee` = '{$invoiceLateFee}', 
                `invoiceNotes` = '{$invoiceNotes}', 
                `invoiceTerms` = '{$invoiceTerms}' 
                WHERE  `{$pt_id}` = '{$$pt_id}'";
        $res = $a->query($sql);
        if ($res->count) {
            $a->addSuccess("Invoice has been updated");
        }

        $invoice->setID($$pt_id);
        $invoice->addHistory('update',"Invoice updated");
    }


    $invoiceSubTotal = 0;
    $invoiceTax = 0;
    $invoiceLateFeeCalc = 0;
    $invoiceDiscount = 0;
    // insert new items
    if (!$a->error && !empty($$pt_id)) {
        foreach ($itemItem as $k => $v) {

            if (empty($itemQty[$k]) || !is_numeric($itemQty[$k])) {
                $a->addError("Incorrect item qty");
            }
            if (!empty($itemDiscount[$k]) && !is_numeric($itemDiscount[$k])) {
                $a->addError("Incorrect item discount");
            } elseif (!empty($itemTax[$k]) && ($itemTax[$k] < 1 || $itemTax[$k] >= 100)) {
                $a->addError("Discount must be more than 1 and less than 100");
            }
            if (empty($itemRate[$k]) || !is_numeric($itemRate[$k])) {
                $a->addError("Incorrect item rate");
            }
            if (!empty($itemTax[$k]) && !is_numeric($itemTax[$k])) {
                $a->addError("Incorrect item tax");
            } elseif (!empty($itemTax[$k]) && ($itemTax[$k] < 1 || $itemTax[$k] >= 100)) {
                $a->addError("Tax must be more than 1 and less than 100");
            }

            if (!$a->error) {


                $__itemName = addslashes($itemModel->_getItem($itemItem[$k],'itemName' ));
                $__itemDescription = addslashes($itemDescription[$k]);
                // add new item, if not selected
                /*if (empty($itemItem[$k])) {
                    $itemData = array(
                        'itemName' => $__itemName,
                        'itemDescription' => $__itemDescription,
                        'itemType' => 'product',
                        'itemFrequency' => '',
                        'itemAmount' => $itemRate[$k]
                    );

                    $itemItem[$k] = $itemModel->insertItem($itemData);

                }*/

                $itemDiscount[$k] = floatval($itemDiscount[$k]);
                $itemTotal = round((($itemRate[$k] * $itemQty[$k])) , 2);
                $__itemDiscount = round($itemTotal * ($itemDiscount[$k] / 100),2);
                $itemTotal = $itemTotal - $__itemDiscount;
                $itemTaxValue = round($itemTotal * (floatval($itemTax[$k]) / 100), 2);

                $invoiceSubTotal += $itemTotal;
                $invoiceTax += $itemTaxValue;
                $itemTax[$k] = (int)$itemTax[$k];

                $sql = "INSERT INTO  `{$db_pr}invoice_items` SET
                    `idInvoice` = {$$pt_id},
                    `itemItem` = '{$itemItem[$k]}',
                    `itemName` = '{$__itemName}',
                    `itemDescription` = '{$__itemDescription}',
                    `itemQty` = '{$itemQty[$k]}',
                    `itemRate` = '{$itemRate[$k]}',
                    `itemDiscount` = '{$itemDiscount[$k]}',
                    `itemTax` = '{$itemTax[$k]}',
                    `itemTotal` = '{$itemTotal}'";
                $a->error = false;
                $res = $a->query($sql);


            }

        }
    }
    if (!$a->error) {
        foreach ($_itemItem as $k => $v) {

            if (empty($_itemQty[$k]) || !is_numeric($_itemQty[$k])) {
                $a->addError("Incorrect item qty");
            }
            if (!empty($_itemDiscount[$k]) && !is_numeric($_itemDiscount[$k])) {
                $a->addError("Incorrect item discount");
            } elseif (!empty($_itemTax[$k]) && ($_itemTax[$k] < 1 || $_itemTax[$k] >= 100)) {
                $a->addError("Discount must be more than 1 and less than 100");
            }
            if (empty($_itemRate[$k]) || !is_numeric($_itemRate[$k])) {
                $a->addError("Incorrect item rate");
            }
            if (!empty($_itemTax[$k]) && !is_numeric($_itemTax[$k])) {
                $a->addError("Incorrect item tax");
            } elseif (!empty($_itemTax[$k]) && ($_itemTax[$k] < 1 || $_itemTax[$k] >= 100)) {
                $a->addError("Tax must be more than 1 and less than 100");
            }

            if (!$a->error) {

                $itemTotal = round((($_itemRate[$k] * $_itemQty[$k])) , 2);
                $__itemDiscount = round($itemTotal * ((double)$_itemDiscount[$k] / 100),2);
                $itemTotal = $itemTotal - $__itemDiscount;

                $itemTaxValue = round($itemTotal * ((double)$_itemTax[$k] / 100), 2);

                $invoiceSubTotal += $itemTotal;
                $invoiceTax += $itemTaxValue;

                $__itemName = addslashes($itemModel->_getItem($_itemItem[$k],'itemName' ));
                $__itemDescription = addslashes($_itemDescription[$k]);
                $_itemItem[$k] = (int) $_itemItem[$k];
                $_itemTax[$k] = (int) $_itemTax[$k];
                $sql = "UPDATE  `{$db_pr}invoice_items` SET
                    `itemItem` = '{$_itemItem[$k]}',
                    `itemName` = '{$__itemName}',
                    `itemDescription` = '{$__itemDescription}',
                    `itemQty` = '{$_itemQty[$k]}',
                    `itemRate` = '{$_itemRate[$k]}',
                    `itemDiscount` = '{$_itemDiscount[$k]}',
                    `itemTax` = '{$_itemTax[$k]}',
                    `itemTotal` = '{$itemTotal}'
                    WHERE idItem = '{$k}'";
                $a->error = false;
                $res = $a->query($sql);

            }

        }


    }

    if (!$a->error) {
        $invoiceLateFeeCalc = round($invoiceSubTotal * ($invoiceLateFee / 100), 2);
        $invoiceTotal = $invoiceSubTotal + $invoiceTax + $invoiceLateFeeCalc;

        $sql = "UPDATE `{$pt_table}` SET
                    `invoiceSubTotal` = '{$invoiceSubTotal}',
                    `invoiceTotal` = '{$invoiceTotal}',
                    `invoiceTax` = '{$invoiceTax}'
                    WHERE  `{$pt_id}` = '{$$pt_id}'";
        $res = $a->query($sql);
    }

    if (isset($_REQUEST['sendInvoice']) && !$a->error) {
        $invoice->setID($$pt_id);
        $invoice->setInvoiceData();
        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70" alt="logo"/></td></tr>';
        }
        $_invoiceNumber = stripslashes($invoiceNumber);
        $mailData = array(
            "{%invoice_id%}" => $$pt_id,
            "{%payment_url%}" => $invoice->getPaymentLink(),
            "{%invoice_number%}" => $_invoiceNumber,
            "{%invoice_total%}" => $invoice->formattedAmount($invoiceTotal),
            "{%invoice_due%}" => $a->getDateFormat($invoiceDueDate, "Y-m-d"),
            "{%invoice_date%}" => $a->getDateFormat($invoiceDate, "Y-m-d"),
            "{%print_url%}" => $invoice->getViewLink(),
            "{%company_logo%}" => $settings->terminal_logo,
            "{%company_name%}" => $settings->email_name,
            "{%customer_name%}" => stripslashes($customerName),
            "{%site_url%}" => $settings->site_url,
            "{%issue_email%}" => $settings->email_from,
            "{%logo_block%}" => $logoBlock,
            "{%paypal_logo%}"=>$settings->enable_paypal=='y'?"<img src=\"{$settings->site_url}/assets/images/icons/paypal.png\" title=\"Payment by PayPal\">":""
        );

        $mailData['{%track_img%}'] = $settings->track_invoice == 'y'?'<img src="'.$invoice->getTrackLink().'" alt="pixel"/>':'';

        if ($settings->attach_pdf_invoice == 'y') {
            $invoiceFileName = "Invoice-".preg_replace('/[^\w]/',"",$invoiceNumber) . ".pdf";
            $res = $a->sendMailFile($customerEmail, "New invoice #{$_invoiceNumber} to pay", "send_invoice.html", $mailData,
                array($invoiceFileName => $invoice->generateInvoice(false)), false, $send_cc, $send_bcc);
        } else {
            $res = $a->sendMail($customerEmail, "New invoice #{$_invoiceNumber} to pay", "send_invoice.html", $mailData, false);
        }
        if($res===true) {
            $a->addSuccess( "Invoice has been successfully sent to '{$customerEmail}'" );
            if(!empty($send_cc)){ $a->addSuccess( "Invoice has been successfully sent to '{$send_cc}' (CC)" );}
            if(!empty($send_bcc)){ $a->addSuccess( "Invoice has been successfully sent to '{$send_bcc}' (BCC)" );}

            st_do_action('add_user_log',"Sent invoice #{$invoiceNumber} to {$customerEmail}; cc:{$send_cc}; bcc:{$send_bcc}");
        }else{
            $a->addError( "ERROR: {$res}" );
        }
        $invoice->addHistory('send',"Invoice sent to '{$customerEmail}'");
    }


}

if (!empty($$pt_id)) {
    $sql = "SELECT * FROM $pt_table WHERE {$pt_id} = {$$pt_id}";
    foreach ($a->query($sql)->result_row() as $k => $v)
        $$k = $v;

}

$itemsArray = $itemModel->getRecurringItemsWithoutTrial();
$customersArray = $customerModel->getCustomersListForTypeahead();

if (!empty($$pt_id)) {
    $invoice->setID($$pt_id);
    $paymentLink = $invoice->getPaymentLink();
    if($invoiceStatus == 'paid'){
        $paidInvoice = true;
        $a->addInfo( "The invoice was already paid by the customer.  Invoice adjustments are not recommended." );
        $payments = $invoice->getPayments();
        $subscriptions = $invoice->getSubscription();
        if(!empty($subscriptions[0])){
            $currentSubscription = $subscriptions[0]['idSubscription'];
            $subscription = new subscriptionModel();
            $subscription->setID($currentSubscription);
            $subscription->getSubscription();
            $payments = $subscription->getPayments();
        }
        //PT_Core::_dump($subscriptions);
    }
}


$invoiceDueDate = $a->dateToIso($invoiceDueDate);
$a->getHeader();
?>
<div class="container" role="main">
    <form class=" validate" role="form" action="" method="post" id="invoiceForm">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="<?php echo($pt_id) ?>" value="<?php echo($$pt_id) ?>">
        <?php /*<input type="hidden" name="idCustomer" id="idCustomer" value="<?php echo($idCustomer) ?>">*/?>
        <div class="row">
            <div class="col-md-9 col-lg-9 col-sm-9 col-xs-6 vcenter"><h2><?php echo(!empty($$pt_id)?"Edit":"Add") ?> Recurring Invoice</h2></div><!--
            --><div class="col-md-3 col-lg-3 col-sm-3 col-xs-6 vcenter text-right">
                <?php if(!empty($$pt_id)){?>
                    <a  href="javascript:;" data-toggle="modal" data-target="#invoiceHistory">Invoice history</a>&nbsp;&nbsp;&nbsp;
                <?php }?>
                <span class="back_to_list">&larr;<a href="index.php">Back to list</a></span> </div>
        </div>


        <?php echo($a->getMessages()) ?>
        <div class="col-md-12 col-lg-11 col-sm-12 col-xs-12 ">
            <hr>

            <div class="form-group col-md-3">
                <label for="customerName"><span>*</span>Customer Name</label>
                <?php /*<input type="text" class="form-control typeaheadCustomer" name="customerName" id="customerName" placeholder="" value="<?php echo $a->crl($customerName) ?>"
                       data-rule-required="true" >*/?>
                <input type="hidden" name="customerName" id="customerName" value="<?php echo $a->crl($customerName) ?>">
                <select name="idCustomer" id="idCustomer" class="form-control">
                    <option value="">Please Select</option>
                    <?php foreach ($customerModel->getCustomers() as $customer){
                        if(!empty($customer['first_name'])){?>
                        <option value="<?php echo $customer['idCustomer'] ?>"
                            <?php echo $idCustomer==$customer['idCustomer']?"selected":"" ?>
                            data-email="<?php echo $customer['email'] ?>"
                            data-name="<?php echo $customer['first_name'] ?> <?php echo $customer['last_name'] ?>"
                            ><?php echo $customer['first_name'] ?> <?php echo $customer['last_name'] ?></option>
                    <?php }else{?>
                            <option value="<?php echo $customer['idCustomer'] ?>"
                                <?php echo $idCustomer==$customer['idCustomer']?"selected":"" ?>
                                    data-email="<?php echo $customer['customerEmail'] ?>"
                                    data-name="<?php echo $customer['customerName'] ?>"
                            ><?php echo $customer['customerName'] ?></option>
                        <?php }?>


                        <?php }?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="customerEmail"><span>*</span>E-mail</label>
                <div class="input-group">
                    <div class="input-group-addon">@</div>
                    <input type="text" class="form-control" name="customerEmail" id="customerEmail" placeholder="" value="<?php echo $a->crl($customerEmail) ?>"
                           data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email">

                </div>
            </div>

            <div class="form-group col-md-3  col-md-push-3">
                <label for="invoiceNumber"><span>*</span>Invoice Number</label>
                <input type="text" class="form-control" name="invoiceNumber" id="invoiceNumber" placeholder="" value="<?php echo $a->crl($invoiceNumber) ?>"
                       data-rule-required="true" >
            </div>

            <div class="clearfix"></div>

            <div class="form-group col-md-3">
                <label for="customerEmail">Send Copy (CC):</label>
                <div class="input-group">
                    <div class="input-group-addon">@</div>
                    <input type="text" class="form-control" name="send_cc" id="send_cc" placeholder="" value="<?php echo $a->crl($send_cc) ?>"
                           data-rule-email="true" data-msg-email="Incorrect Email">

                </div>
            </div>
            <div class="form-group col-md-3">
                <label for="customerEmail">Send Blind Copy (BCC):</label>
                <div class="input-group">
                    <div class="input-group-addon">@</div>
                    <input type="text" class="form-control" name="send_bcc" id="send_bcc" placeholder="" value="<?php echo $a->crl($send_bcc) ?>"
                           data-rule-email="true" data-msg-email="Incorrect Email">

                </div>
            </div>
            <div class="form-group col-md-3 col-md-push-3">
                <label for="orderNumber">P.O. Number</label>
                <input type="text" class="form-control" name="orderNumber" id="orderNumber" placeholder="" value="<?php echo $a->crl($orderNumber) ?>">
            </div>
            <div class="clearfix"></div>
            <div class="form-group col-md-6">
                <label form="invoiceBillTo">Bill To</label>
                <textarea name="invoiceBillTo" id="invoiceBillTo" class="form-control twoRows" placeholder=""><?php echo $a->crl($invoiceBillTo) ?></textarea>
            </div>



            <div class="form-group col-md-3">
                <label for="invoiceDate"><span>*</span>Invoice Date</label>
                <input type="text" class="form-control" name="invoiceDate" id="invoiceDate" placeholder="" value="<?php echo($a->dateToIso($invoiceDate)) ?>"
                       data-rule-required="true" >
            </div>
            <div class="form-group col-md-2">
                <label for="invoiceDate">Late Fee</label>
                <div class="input-group">

                    <input type="text" class="form-control" name="invoiceLateFee" id="invoiceLateFee" placeholder="0" value="<?php echo $a->crl($invoiceLateFee) ?>">
                    <div class="input-group-addon">%</div>
                </div>
            </div>

            <div class="form-group col-md-3">
                <label for="email_name">Terms</label>

                <select class="form-control" name="invoiceTerm" id="invoiceTerm">
                    <option value="0">Due upon receipt</option>
                    <option value="due15" <?php echo($invoiceTerm=="due15"?"selected":"") ?> data-days="15">Due withing 15 days</option>
                    <option value="due30" <?php echo($invoiceTerm=="due30"?"selected":"") ?> data-days="30">Due withing 30 days</option>
                    <option value="due45" <?php echo($invoiceTerm=="due45"?"selected":"") ?> data-days="45">Due withing 45 days</option>
                    <option value="custom" <?php echo($invoiceTerm=="custom"?"selected":"") ?>>Custom</option>
                </select>

            </div>

            <div class="form-group col-md-3" id="dueDateText" style="display: block">
                <label for="orderDueText">&nbsp;</label>
                <div class="checkbox">
                    Due Date&nbsp;<label id="orderDueText"><?php echo($a->getDateFormat($a->dateFromIso($invoiceDueDate),"Y-m-d")) ?></label>
                </div>
            </div>
            <div class="form-group col-md-3"  id="dueDateControl" style="display: none">
                <label for="invoiceDueDate">Due Date</label>
                <div class="input-group">
                    <div class="input-group-addon"><span aria-hidden="true" class="glyphicon glyphicon-calendar"></span></div>
                    <input type="text" class="form-control" name="invoiceDueDate" id="invoiceDueDate" placeholder="" value="<?php echo $a->crl($invoiceDueDate) ?>">
                </div>
            </div>

            <div class="clearfix"></div>
            <?php if($settings->multiple_currencies=='y'){?>
                <div class="form-group col-md-2">
                    <label for="invoiceCurrency">Currency</label>

                    <select class="form-control" name="invoiceCurrency" id="invoiceCurrency">
                        <?php foreach($settings->multiple_currency_list as $currency){?>
                            <option value="<?php echo($currency) ?>" <?php echo($currency==$invoiceCurrency?"selected":"") ?>
                                    data-symbol="<?php echo(PT_Core::getCurSymb($currency)) ?>"><?php echo($currency) ?></option>
                        <?php }?>
                    </select>
                    <input type="hidden" name="invoiceCurrencyPosition" value="<?php echo($invoiceCurrencyPosition) ?>">
                    <input type="hidden" name="invoiceCurrencySymbol" value="<?php echo($invoiceCurrencySymbol) ?>">
                </div>
            <?php }else{?>
                <input type="hidden" name="invoiceCurrency" value="<?php echo($invoiceCurrency) ?>">
                <input type="hidden" name="invoiceCurrencyPosition" value="<?php echo($invoiceCurrencyPosition) ?>">
                <input type="hidden" name="invoiceCurrencySymbol" value="<?php echo($invoiceCurrencySymbol) ?>">
            <?php }?>

            <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section">
                <div id="itemsCont">
                    <?php
                    if (!empty($$pt_id)) {
                        $sql = "SELECT * FROM {$pt_table_items} WHERE idInvoice = '{$$pt_id}'";
                        $res = $a->query($sql);
                        foreach ($res->result_array() as $item) {
                            ?>



                            <div class="row blue_section">
                                <?php /*<a class="removeIcon" href="javascript:;" onclick="deleteRow(this,<?php echo($item['idItem']) ?>)">
                                    <img src="../assets/images/icons/delete_icon.png"/>
                                </a>*/?>
                                <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                                    <div class="form-group col-md-4 col-sm-12 col-xs-12">
                                        <label for="itemItem"><span>*</span>Item</label>
                                        <?php /*<input type="text" class="form-control itemName typeahead"
                                               name="_itemName[<?php echo($item['idItem']) ?>]"
                                               id="_itemName_<?php echo($item['idItem']) ?>" placeholder=""
                                               value="<?php echo $a->crl($item['itemName']) ?>"
                                               data-rule-required="true"
                                               data-msg-required="Item name are required">
                                        <input type="hidden" name="_itemItem[<?php echo($item['idItem']) ?>]"
                                               value="<?php echo($item['itemItem']) ?>">*/?>
                                        <select name="_itemItem[<?php echo($item['idItem']) ?>]" id="_itemItem_<?php echo($item['idItem']) ?>" class="form-control itemSelect"
                                                data-rule-required="true"
                                                data-msg-required="Select service"
                                        >
                                            <option value="">Please select item</option>
                                            <?php  foreach ($itemsArray as $line_item){?>
                                                <option value="<?php echo $line_item['idItem'] ?>"
                                                        <?php echo $item['itemItem'] == $line_item['idItem']?"selected":""?>
                                                        data-amount="<?php echo $line_item['itemAmount'] ?>"
                                                        data-period="<?php echo $line_item['itemFrequency'] ?>"

                                                ><?php echo $line_item['itemName'] ?></option>
                                            <?php }?>
                                        </select>
                                        <label>Description</label>
                                        <textarea class='form-control' rows='1' name="_itemDescription[<?php echo $a->crl($item['idItem']) ?>]"
                                                  id="_itemDescription[<?php echo($item['idItem']) ?>]" ><?php echo $a->crl($item['itemDescription']) ?></textarea>
                                    </div>
                                    <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                        <label for="itemName"><span>*</span>Quantity</label>
                                        <input type="text" class="form-control itemQty" readonly
                                               name="_itemQty[<?php echo($item['idItem']) ?>]"
                                               id="_itemQty_<?php echo($item['idItem']) ?>" placeholder=""
                                               value="<?php echo($item['itemQty']) ?>" data-rule-required="true"
                                               data-rule-number="true"
                                               data-msg-number="Only numbers" data-msg-required="Required">

                                    </div>
                                    <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                        <label for="itemName"><span>*</span>Rate</label>

                                        <div class="input-group">

                                            <input type="text" class="form-control itemRate"
                                                   name="_itemRate[<?php echo($item['idItem']) ?>]"
                                                   id="_itemRate_<?php echo($item['idItem']) ?>"
                                                   placeholder="0.00"
                                                   value="<?php echo($item['itemRate']) ?>"
                                                   data-rule-required="true" data-rule-number="true"
                                                   data-msg-required="Required"
                                                   data-msg-number="Only numbers">
                                        </div>

                                    </div>
                                    <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                        <label for="itemName"><span>*</span>Discount</label>

                                        <div class="input-group">
                                            <input type="text" class="form-control itemDiscount"
                                                   name="_itemDiscount[<?php echo($item['idItem']) ?>]"
                                                   id="_itemDiscount_<?php echo($item['idItem']) ?>"
                                                   placeholder="0"
                                                   value="<?php echo($item['itemDiscount']) ?>">

                                            <div class="input-group-addon">%</div>
                                        </div>

                                    </div>
                                    <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                        <label for="itemName"><span>*</span>Tax</label>

                                        <div class="input-group">
                                            <input type="text" class="form-control itemTax"
                                                   name="_itemTax[<?php echo($item['idItem']) ?>]"
                                                   id="_itemTax_<?php echo($item['idItem']) ?>" placeholder=""
                                                   value="<?php echo($item['itemTax']) ?>" oncanplay="0">
                                            <div class="input-group-addon">%</div>
                                        </div>
                                    </div>
                                </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter">
                                    <label>Amount</label><br>
                                    <p class="text-center txt-amount">
                                        <?php if($invoiceCurrencyPosition=='before'){?>
                                            <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                                        <?php }?>
                                        <span><?php echo(number_format($item['itemTotal'], 2)) ?></span>
                                        <?php if($invoiceCurrencyPosition=='after'){?>
                                            <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                                        <?php }?>
                                    </p>
                                </div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="clearfix"></div>
                        <div class="row blue_section">
                            <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                                <div class="form-group col-md-4 col-sm-12 col-xs-12">
                                    <label for="itemName"><span>*</span>Item</label>
                                    <?php /*<input type="text" class="form-control itemName typeahead" name="itemName[]"
                                           id="itemName_0" placeholder=""
                                           value="" data-rule-required="true"
                                           data-msg-required="Item name are required">*/?>
                                    <select name="itemItem[]" id="itemItem_0" class="form-control itemSelect"
                                            data-rule-required="true"
                                            data-msg-required="Select service"
                                    >
                                        <option value="">Please select item</option>
                                        <?php  foreach ($itemsArray as $line_item){?>
                                            <option value="<?php echo $line_item['idItem'] ?>"
                                                data-amount="<?php echo $line_item['itemAmount'] ?>"
                                                data-period="<?php echo $line_item['itemFrequency'] ?>"

                                                ><?php echo $line_item['itemName'] ?></option>
                                        <?php }?>
                                    </select>
                                    <label>Description</label>
                                    <textarea class='form-control' rows='1' name="itemDescription[]"
                                              id="itemDescription_0" ></textarea>
                                </div>
                                <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                    <label for="itemName"><span>*</span>Quantity</label>
                                    <input type="text" class="form-control itemQty" name="itemQty[]" readonly
                                           id="itemQty_0" placeholder=""
                                           value="1" data-rule-required="true" data-rule-number="true"
                                           data-msg-number="Only numbers" data-msg-required="Required">
                                </div>
                                <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                    <label for="itemName"><span>*</span>Rate</label>

                                    <input type="text" class="form-control itemRate" name="itemRate[]"
                                           id="itemRate_0" placeholder="0.00"
                                           value="" data-rule-required="true" data-rule-number="true"
                                           data-msg-required="Required"
                                           data-msg-number="Only numbers">

                                </div>
                                <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                    <label for="itemName"><span>*</span>Discount</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control itemDiscount"
                                               name="itemDiscount[]" id="itemDiscount_0" placeholder="0"
                                               value="">
                                        <div class="input-group-addon">%</div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2 col-sm-12 col-xs-12">
                                    <label for="itemName"><span>*</span>Tax</label>

                                    <div class="input-group">
                                        <input type="text" class="form-control itemTax" name="itemTax[]"
                                               id="itemTax_0" placeholder="" value="" oncanplay="0">

                                        <div class="input-group-addon">%</div>
                                    </div>
                                </div>
                            </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter">

                                <label>Amount</label><br>

                                <p class="text-center txt-amount">

                                    <?php if($invoiceCurrencyPosition=='before'){?>
                                        <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                                    <?php }?>
                                    <span>0.00</span>
                                    <?php if($invoiceCurrencyPosition=='after'){?>
                                        <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                                    <?php }?>
                                </p>


                            </div>
                        </div>

                    <?php } ?>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="clearfix"></div>
            <?php /*<div class="col-lg-12">
                <br>
                <a href="javascript:;" class="btn btn-blue btn-sm" id="addItem">
                    <span aria-hidden="true" class="glyphicon glyphicon-plus"></span>Add another line</a>
            </div>
            <div class="clearfix"></div>*/?>
            <hr/>

            <div class="total-items-rows row">
                <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section" id="invoiceTotalRow">

                    <div class="row invoiceSubtotals" >
                        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                            <div class="form-group col-md-10 col-sm-10 col-xs-6">

                            </div>

                            <div class="form-group col-md-2 col-sm-2 col-xs-6 vcenter">
                                <label>Sub Total</label>

                            </div>
                        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter total">
                            <?php if($invoiceCurrencyPosition=='before'){?>
                                <span class="invoiceCurrency">
                                    <?php echo($invoiceCurrencySymbol) ?>
                                </span>
                            <?php }?>
                            <span id="invoiceSubTotal"><?php echo(number_format($invoiceSubTotal,2)) ?></span>
                            <?php if($invoiceCurrencyPosition=='after'){?>
                                <span class="invoiceCurrency">
                                    <?php echo($invoiceCurrencySymbol) ?>
                                </span>
                            <?php }?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section" style="display: none" id="invoiceDiscountRow">

                    <div class="row invoiceSubtotals" >
                        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                            <div class="form-group col-md-10 col-sm-10 col-xs-6">

                            </div>

                            <div class="form-group col-md-2 col-sm-2 col-xs-6 vcenter">
                                <label>Total Discount</label>

                            </div>
                        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter total">
                            <?php if($invoiceCurrencyPosition=='before'){?>
                                <span class="invoiceCurrency">
                                            <?php echo($invoiceCurrencySymbol) ?>
                                        </span>
                            <?php }?>
                            <span id="invoiceDiscount"></span>
                            <?php if($invoiceCurrencyPosition=='after'){?>
                                <span class="invoiceCurrency">
                                            <?php echo($invoiceCurrencySymbol) ?>
                                        </span>
                            <?php }?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section" style="display: none" id="invoiceTaxRow">

                    <div class="row invoiceSubtotals odd">
                        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                            <div class="form-group col-md-10 col-sm-10 col-xs-6">

                            </div>

                            <div class="form-group col-md-2 col-sm-2 col-xs-6 vcenter">
                                <label>Tax</label>

                            </div>
                        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter total">


                            <?php if($invoiceCurrencyPosition=='before'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>
                            <span id="invoiceTax"><?php echo(number_format($invoiceTax,2)) ?></span>
                            <?php if($invoiceCurrencyPosition=='after'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section" style="display: none" id="invoiceFeeRow">

                    <div class="row invoiceSubtotals">
                        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                            <div class="form-group col-md-10 col-sm-10 col-xs-6">

                            </div>

                            <div class="form-group col-md-2 col-sm-2 col-xs-6 vcenter">
                                <label>Late Fee</label>

                            </div>
                        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter total">

                            <?php if($invoiceCurrencyPosition=='before'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>
                            <span id="invoiceTotalLateFee"><?php echo(number_format($invoiceLateFeeCalc,2)) ?></span>
                            <?php if($invoiceCurrencyPosition=='after'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>

                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12 form_section" id="invoiceTaxRow">

                    <div class="row invoiceSubtotals odd">
                        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
                            <div class="form-group col-md-10 col-sm-10 col-xs-6">

                            </div>

                            <div class="form-group col-md-2 col-sm-2 col-xs-6 vcenter">
                                <label>TOTAL</label>

                            </div>
                        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter total">

                            <?php if($invoiceCurrencyPosition=='before'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>
                            <span  id="invoiceTotal"><?php echo(number_format($invoiceTotal,2)) ?></span>
                            <?php if($invoiceCurrencyPosition=='after'){?>
                                <span class="invoiceCurrency">
                                        <?php echo($invoiceCurrencySymbol) ?>
                                    </span>
                            <?php }?>

                        </div>
                    </div>
                </div>
            </div>
            <p>&nbsp;</p>
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" name="invoiceNotes" rows="6"><?php echo $a->crl($invoiceNotes) ?></textarea>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label>Terms & Conditions</label>
                    <textarea class="form-control" name="invoiceTerms" id="invoiceTerms" rows="6"><?php echo $a->crl($invoiceTerms) ?></textarea>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <button class="btn btn-default btn-lg" type="submit">Save</button>
                </div>

            </div>
            <div class="col-md-3">
                <div class="form-group">

                    <button name="sendInvoice" class="btn btn-success btn-lg" type="submit">Save &amp; Send
                    </button>

                </div>
            </div>
            <?php if(!empty($$pt_id)){?>
                <div class="col-md-2">
                    <div class="checkbox">
                        <a href="<?php echo($paymentLink) ?>" target="_blank">Direct Payment Link</a>
                    </div>
                </div>
            <?php }?>
            <div class="clearfix"></div>

        </div>
        <?php if(!empty($subscriptions) && count($subscriptions)){ ?>
            <div class="row">
                <div class="col-xs-12">
                    <h2>Subscription</h2>
                    <table class="dataTable" style="width: 100%">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Date Created</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i=1;foreach ($subscriptions as $payment){ ?>
                            <tr>
                                <td><?php echo $i ?></td>
                                <td><?php echo $a->getDateFormat($payment['dateCreated'])." " .PT_Core::_getTimeFormat($payment['dateCreated']) ?></td>
                                <td>

                                    <?php echo PT_Core::_getCurrencyText($payment['amount'],$payment['currency_position'],$payment['currency_symbol']) ?>

                                </td>
                                <td><a href="<?php echo $settings->admin_url?>/subscriptions/view.php?idSubscription=<?php echo $payment['idSubscription'] ?>"
                                       style="text-decoration: underline"><?php echo $payment['idTransaction'] ?></a></td>
                                <td>
                                    <?php switch($payment['status']){
                                        case "active": echo "<span class='active'><i></i>Active</span>";
                                            break;
                                        case "canceled": echo "<span class='canceled'><i></i>Canceled<img src='../assets/images/icons/calendar.png'
                                                 data-toggle='tooltip'
                                                 data-placement='bottom'
                                                 title='".htmlspecialchars($a->getDateFormat($payment['dateCancelation']))."'/> </span>";
                                            break;
                                        case "pending": echo "<span class='canceled'><i></i>Pending </span>";
                                            break;
                                    }?>

                                </td>
                            </tr>
                            <?php $i++;} ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Date Created</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                        </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        <?php } ?>
        <?php if(!empty($payments) && count($payments)){ ?>
            <div class="row">
                <div class="col-xs-12">
                    <h2>Payments</h2>
                    <table class="dataTable" style="width: 100%">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Date Created</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i=1;foreach ($payments as $payment){ ?>
                            <tr>
                                <td><?php echo $i ?></td>
                                <td><?php echo $a->getDateFormat($payment['dateCreated'])." " .PT_Core::_getTimeFormat($payment['dateCreated']) ?></td>
                                <td>
                                    <?php echo $payment['paypalStatus']=='refunded'?"<del>":"" ?>
                                    <?php echo PT_Core::_getCurrencyText($payment['amount'],$payment['currency_position'],$payment['currency_symbol']) ?>
                                    <?php echo $payment['paypalStatus']=='refunded'?"</del>":"" ?>
                                </td>
                                <td><a href="<?php echo $settings->admin_url?>/payments/view.php?idPayment=<?php echo $payment['idPayment'] ?>"
                                       style="text-decoration: underline"><?php echo $payment['idTransaction'] ?></a></td>
                                <td>
                                    <?php switch($payment['paypalStatus']){
                                        case "paid": echo "<span class='active'><i></i>Paid</span>";
                                            break;
                                        case "refunded": echo "<span class='warning'><i></i>Refunded</span>";
                                            break;
                                        case "pending": echo "<span class='canceled'><i></i>Pending</span>";
                                            break;
                                    } ?>

                                </td>
                            </tr>
                            <?php $i++;} ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Date Created</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                        </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        <?php } ?>
    </form>
</div>
<div id="template" style="display: none">
    <div class="row blue_section">
        <a class="removeIcon" href="javascript:;" onclick="removeRow(this)">
            <img src="../assets/images/icons/delete_icon.png"/>
        </a>
        <div class="col-md-10 col-sm-10 col-xs-9 form_left_section vcenter">
            <div class="form-group col-md-4 col-sm-12 col-xs-12" >
                <label for="itemName"><span>*</span>Item</label>
                <input type="text" class="form-control itemName typeahead" name="itemName[{0}]" id="itemName_{0}" placeholder=""
                       value="" data-rule-required="true" data-msg-required="Item name are required">
                <input type="hidden" name="itemItem[{0}]" value="">
                <label>Description</label>
                <textarea class='form-control' rows='1' name="itemDescription[{0}]"
                          id="itemDescription_{0}" ></textarea>
            </div>
            <div class="form-group col-md-2 col-sm-12 col-xs-12">
                <label for="itemName"><span>*</span>Quantity</label>
                <input type="text" class="form-control itemQty" name="itemQty[{0}]" id="itemQty_{0}" placeholder=""
                       value="1" data-rule-required="true" data-rule-number="true"
                       data-msg-number="Only numbers"  data-msg-required="Required">

            </div>
            <div class="form-group col-md-2 col-sm-12 col-xs-12">
                <label for="itemName"><span>*</span>Rate</label>

                <input type="text" class="form-control itemRate" name="itemRate[{0}]" id="itemRate_{0}" placeholder="0.00"
                       value="" data-rule-required="true" data-rule-number="true"  data-msg-required="Required"
                       data-msg-number="Only numbers">


            </div>
            <div class="form-group col-md-2 col-sm-12 col-xs-12">
                <label for="itemName"><span>*</span>Discount</label>
                <div class="input-group">
                    <input type="text" class="form-control itemDiscount" name="itemDiscount[{0}]" id="itemDiscount_{0}" placeholder="0"
                           value="">
                    <div class="input-group-addon">%</div>
                </div>

            </div>
            <div class="form-group col-md-2 col-sm-12 col-xs-12">
                <label for="itemName"><span>*</span>Tax</label>

                <div class="input-group">
                    <input type="text" class="form-control itemTax" name="itemTax[{0}]" id="itemTax_{0}" placeholder="" value="">

                    <div class="input-group-addon">%</div>
                </div>

            </div>
        </div><div class="col-md-2  col-sm-2  col-xs-3 form_right_section vcenter">

            <label>Amount</label><br>
            <p class="text-center txt-amount">

                <?php if($invoiceCurrencyPosition=='before'){?>
                    <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                <?php }?>
                <span>0.00</span>
                <?php if($invoiceCurrencyPosition=='after'){?>
                    <b class="invoiceCurrency"><?php echo($invoiceCurrencySymbol) ?></b>
                <?php }?>
            </p>


        </div>
    </div>
</div>
<?php if(!empty($$pt_id)){?>
    <!-- Modal Invoice history-->
    <div class="modal fade" id="invoiceHistory" tabindex="-1" role="dialog" aria-labelledby="invoiceHistory">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Invoice History</h4>
                </div>

                <table class="table">
                    <thead>
                    <tr>
                        <th>Action</th>
                        <th>Date Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($invoice->getHistory() as $history) {?>
                        <tr>
                            <td><?php echo $a->crl($history['text'])?></td>
                            <td><?php echo PT_Core::_getDateFormat($history['dateCreated'])?> <?php echo PT_Core::_getTimeFormat($history['dateCreated'])?></td>
                        </tr>
                    <?php }?>
                    </tbody>
                </table>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>
<?php }?>

<script>


    var items = <?php echo(json_encode($itemsArray,true))?>;
    var customers = <?php echo(json_encode($customersArray,true))?>;
    var lateFeeText = 'Late fee in the amount of {1}% will be added if invoice is past due';
</script>
<?php echo($a->getFooter()) ?>
