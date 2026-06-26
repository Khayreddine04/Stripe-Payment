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

if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$settings->set("admin_section",$pt_section);
$settings_section = "paypal_settings";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("scripts.js");
$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;
$email_name = $a->esc("email_name");
$email =  $a->esc("email");
$paypal_merchant =  $a->esc("paypal_merchant");
$paypal_payment_mode = $a->esc("paypal_payment_mode");


$paypal_currency_converter = $a->esc("paypal_currency_converter");
$paypal_currency_converter_api = $a->esc("paypal_currency_converter_api");
$paypal_currency_converter_api_key = $a->esc("paypal_currency_converter_api_key");
$paypal_currency_converter_to = $a->esc("paypal_currency_converter_to");


$action =  $a->esc("action");

if($action=='save_settings'){
    $settings->updateOption("paypal_merchant",$paypal_merchant);
    $settings->updateOption("paypal_payment_mode",$paypal_payment_mode);

    $settings->updateOption("paypal_currency_converter",$paypal_currency_converter);
    $settings->updateOption("paypal_currency_converter_api",$paypal_currency_converter_api);
    $settings->updateOption("paypal_currency_converter_api_key",$paypal_currency_converter_api_key);
    $settings->updateOption("paypal_currency_converter_to",$paypal_currency_converter_to);

    $a->addSuccess("Settings have been successfully updated");

    st_do_action('add_user_log',"Edited system settings");
}

$testRequest="";
if($settings->paypal_currency_converter=='y'){
    $payment = new PT_Stripe_Payment();

    $result = $payment->currencyConverter(1000,$settings->terminal_currency);
    //print_r($result);
    if($result['res']){
        $amount = round($result['amount'],2);
        $testRequest="<p class='text-success'>1000.00 {$settings->terminal_currency} = {$amount} {$settings->paypal_currency_converter_to}</p>";

    }else{
        $testRequest="<p class='text-danger'> {$result['mess']}</p>";

    }


}



$a->getHeader();
?>
    <div class="container" role="main">
        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                <?php $settings_menu->render(true)?>
            </div>
            <div class="clearfix visible-xs-block"></div>
            <div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
                <?php echo($a->getMessages()) ?>
                <?php if($can_view){ ?>
                <form class=" validate"  role="form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_settings" >
                    <h2>PayPal Settings</h2>
                    <hr>
                    <div class="form-group  col-md-4">

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->paypal_payment_mode=='live')?"active":"" ?>">
                                <input type="radio" id="paypal_payment_mode1" name="paypal_payment_mode" value="live" <?php echo($settings->paypal_payment_mode=='live')?"checked":"" ?>/> Live
                            </label>
                            <label class="btn btn-default <?php echo($settings->paypal_payment_mode=='test')?"active":"" ?>">
                                <input type="radio" id="paypal_payment_mode2" name="paypal_payment_mode" value="test" <?php echo($settings->paypal_payment_mode=='test')?"checked":"" ?>/> Test
                            </label>
                        </div>
                        <small>You can disable paypal payments in <a href="index.php">general settings</a></small>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group col-md-4">
                        <label for="email_name"><span>*</span>Merchant Email</label>
                        <input type="text" class="form-control" name="paypal_merchant" id="paypal_merchant" placeholder="" value="<?php echo(htmlspecialchars($settings->paypal_merchant)) ?>"
                               data-rule-required="true" >

                    </div>

                    <div class="clearfix"></div>
                    <h2>Currency Converter</h2>
                    <hr>
                    <div class="form-group  col-md-4">
                        <label>Convert not supported by PayPal currencies?</label>
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->paypal_currency_converter=='y')?"active":"" ?>">
                                <input type="radio" id="paypal_currency_converter1" name="paypal_currency_converter" value="y" <?php echo($settings->paypal_currency_converter=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->paypal_currency_converter=='n')?"active":"" ?>">
                                <input type="radio" id="paypal_currency_converter2" name="paypal_currency_converter" value="n" <?php echo($settings->paypal_currency_converter=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                        <small>If NO is selected - system will return error in case system currency is not supported by PayPal</small>
                    </div>



                    <div class="clearfix"></div>

                    <div id="convert" style="display: <?php echo $settings->paypal_currency_converter=="n"?"none":"block"?>">
                        <div class="form-group col-md-4">
                            <label for="email_name"><span>*</span>Convert using following API</label>
                            <select class="form-control" name="paypal_currency_converter_api">
                                <option value="currency_layer" <?php echo ($settings->paypal_currency_converter_api=="currency_layer"?"selected":"")?>>CurrencyLayer.com</option>
                                <option value="open_exchange" <?php echo ($settings->paypal_currency_converter_api=="open_exchange"?"selected":"")?>>OpenExchangeRates.org</option>
                            </select>

                        </div>

                        <div class="form-group col-md-4">
                            <label for="email_name"><span>*</span>Convert to</label>
                            <select class="form-control" name="paypal_currency_converter_to">
                                <?php foreach ($PAYPAL_CURRENCIES_LIST as $cur=>$title) {?>
                                    <option value="<?php echo ($cur)?>" <?php echo $settings->paypal_currency_converter_to==$cur?"selected":""?>><?php echo ($title)?></option>
                                <?php }?>
                            </select>

                        </div>
                        <div class="form-group col-md-4" id="api_key" style="display: <?php echo $settings->paypal_currency_converter_api=="fixer"?"none":"block"?>">
                            <label for="email_name"><span>*</span>API Key</label>
                            <input type="text" name="paypal_currency_converter_api_key" value="<?php echo ($settings->paypal_currency_converter_api_key)?>" class="form-control">

                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12">
                        <?php echo $testRequest ?>
                    </div>

                    <div class="clearfix"></div>
                    <hr/>
                    <div class="form-group col-md-12">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                    </div>
                </form>
                <?php }else{ ?>
                    You have no permissions to view this section
                <?php } ?>
            </div>


        </div>
    </div>

<?php echo($a->getFooter()) ?>
