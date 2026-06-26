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


$settings_section = "currency_settings";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("../../assets/js/chosen/chosen.jquery.js");
$a->addStyle("../../assets/js/chosen/chosen.css");
$a->addScripts("scripts.js");

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;
$multiple_currencies = $a->esc("multiple_currencies");
$terminal_currency =  $a->esc("terminal_currency");
$default_terminal_currency =  $a->esc("default_terminal_currency");
$multiple_currency_selector =  $a->esc("multiple_currency_selector");
$multiple_currency_list =  $a->esc("multiple_currency_list",array());

$paypal_currency_converter_api = $a->esc("paypal_currency_converter_api");
$paypal_currency_converter_api_key = $a->esc("paypal_currency_converter_api_key");

$currency_position =  $a->esc("currency_position");
$display_currency =  !empty($_REQUEST["display_currency"])?addslashes($_REQUEST["display_currency"]):"";

$action =  $a->esc("action");

if($action=='save_settings'){

    if($multiple_currency_selector=='y' && count($multiple_currency_list)<2){
        $a->addWarning("You must select more than one currency at the 'Available Currencies' ");
    }

    $settings->updateOption("multiple_currencies",$multiple_currencies);
    $settings->updateOption("terminal_currency",$terminal_currency);
    $settings->updateOption("default_terminal_currency",$default_terminal_currency);
    $settings->updateOption("multiple_currency_selector",$multiple_currency_selector);
    $settings->updateOption("multiple_currency_list",$multiple_currency_list,true);

    $settings->updateOption("paypal_currency_converter_api",$paypal_currency_converter_api);
    $settings->updateOption("paypal_currency_converter_api_key",$paypal_currency_converter_api_key);

    $settings->updateOption("display_currency",$display_currency);
    $settings->updateOption("currency_position",$currency_position);

    $a->addSuccess("Settings have been successfully updated");

    st_do_action('add_user_log',"Edited system settings");
}
$settings->set("multiple_currency_list",is_array($settings->multiple_currency_list)?$settings->multiple_currency_list:array());

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
                    <h2>Currency Settings</h2>
                    <hr>
                    <div class="form-group  col-md-4">
                        <label>Do you deal with multiple currencies?</label>
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->multiple_currencies=='y')?"active":"" ?>">
                                <input type="radio" id="multiple_currencies1" name="multiple_currencies" value="y" <?php echo($settings->multiple_currencies=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->multiple_currencies=='n')?"active":"" ?>">
                                <input type="radio" id="multiple_currencies2" name="multiple_currencies" value="n" <?php echo($settings->multiple_currencies=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div id="single_currency" style="display: <?php echo($settings->multiple_currencies=='n')?"block":"none" ?>">
                        <h2>Currency Options</h2>
                        <hr>
                        <div class="form-group  col-md-3">
                            <label for="terminal_currency"><span>*</span>System Currency</label>
                            <select class="form-control" name="terminal_currency" id="terminal_currency"
                                    data-rule-required="true" >
                                <option value="">Currency</option>
                                <?php foreach($CURRENCY_CODES as $k=>$v){ ?>
                                    <option value="<?php echo($k) ?>" <?php echo($settings->terminal_currency==$k?"selected":"")?>><?php echo($k) ?> ( <?php echo($v) ?> )</option>
                                <?php }?>

                            </select>

                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div id="multiple_currency" style="display: <?php echo($settings->multiple_currencies=='y')?"block":"none" ?>">

                        <h2>Currency Options</h2>
                        <hr>
                        <div class="form-group  col-md-3">
                            <label for="default_terminal_currency"><span>*</span>Default Currency</label>
                            <select class="form-control" name="default_terminal_currency" id="default_terminal_currency"
                                    data-rule-required="true" >
                                <option value="">Currency</option>
                                <?php foreach($CURRENCY_CODES as $k=>$v){ ?>
                                    <option value="<?php echo($k) ?>" <?php echo($settings->default_terminal_currency==$k?"selected":"")?>><?php echo($k) ?> ( <?php echo($v) ?> )</option>
                                <?php }?>

                            </select>
                            <small>Currencies marked with * are unsupported on American Express.</small>
                        </div>
                        <div class="form-group  col-md-4">
                            <label>Show currency selector on terminal?</label>
                            <div class="clearfix"></div>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default <?php echo($settings->multiple_currency_selector=='y')?"active":"" ?>">
                                    <input type="radio" id="multiple_currency_selector1" name="multiple_currency_selector" value="y" <?php echo($settings->multiple_currency_selector=='y')?"checked":"" ?>/> Yes
                                </label>
                                <label class="btn btn-default <?php echo($settings->multiple_currency_selector=='n')?"active":"" ?>">
                                    <input type="radio" id="multiple_currency_selector2" name="multiple_currency_selector" value="n" <?php echo($settings->multiple_currency_selector=='n')?"checked":"" ?>/> No
                                </label>
                            </div>
                        </div>

                        <div class="form-group  col-md-4" id="multiple_currency_list" style="display: <?php echo($settings->multiple_currency_selector=='y')?"block":"none" ?>">
                            <label for="multiple_currency_list"><span>*</span>Available Currencies</label>
                            <div class="clearfix"></div>
                            <select multiple="" class="form-control chosen-select" name="multiple_currency_list[]" id="default_terminal_currency"
                                    data-rule-required="true">

                                <?php foreach($CURRENCY_CODES as $k=>$v){ ?>
                                    <option value="<?php echo($k) ?>" <?php echo(in_array($k,$settings->multiple_currency_list)?"selected":"")?>><?php echo($k)?></option>
                                <?php }?>

                            </select>
                            <small>Your DEFAULT currency must be present in this list as well.</small>
                        </div>

                        <div class="clearfix"></div>
                        
                        <h2>Currency Converter</h2>

                        <div class="form-group col-md-4">
                            <label><span>*</span>Convert using following API</label>
                            <select class="form-control" name="paypal_currency_converter_api">
                                <option value="currency_layer" <?php echo ($settings->paypal_currency_converter_api=="currency_layer"?"selected":"")?>>CurrencyLayer.com</option>
                                <option value="open_exchange" <?php echo ($settings->paypal_currency_converter_api=="open_exchange"?"selected":"")?>>OpenExchangeRates.org</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label><span>*</span>API Key</label>
                            <input type="text" name="paypal_currency_converter_api_key" value="<?php echo ($settings->paypal_currency_converter_api_key)?>" class="form-control">
                        </div>

                        <div class="clearfix"></div>


                    </div>

                    <div>
                        <div class="form-group  col-md-3">
                            <label for="display_currency">Currency symbol</label>
                            <input type="text" name="display_currency" id="display_currency" value="<?php echo $settings->display_currency?>" class="form-control">

                        </div>

                        <div class="form-group  col-md-3">
                            <label for="currency_position">Currency Position</label>
                            <select class="form-control" name="currency_position" id="currency_position"
                                    data-rule-required="true" >

                                <option value="before" <?php echo($settings->currency_position=="before"?"selected":"")?>>Before Amount</option>
                                <option value="after" <?php echo($settings->currency_position=="after"?"selected":"")?>>After Amount</option>

                            </select>

                        </div>
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
<script>
    $(function(){
        $(".chosen-select").chosen();
    })
</script>
<?php echo($a->getFooter()) ?>
