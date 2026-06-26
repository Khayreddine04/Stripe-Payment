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
    header("Location: ../index.php?rd=settings/terminal_settings.php");
    exit();
}
$settings->set("admin_section",$pt_section);
$settings_section = "terminal_settings";
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
$live_public_key =  trim($a->esc("live_public_key"));
$live_secret_key =  trim($a->esc("live_secret_key"));
$test_public_key =  trim($a->esc("test_public_key"));
$test_secret_key =  trim($a->esc("test_secret_key"));
$terminal_payment_mode = $a->esc("terminal_payment_mode","test");
$webhook_secret_key = $a->esc("webhook_secret_key");
$action =  $a->esc("action");

$webhook_url = $settings->site_url . '/webhook.php';

if($action=='save_settings'){
    $settings->updateOption("live_public_key",$live_public_key);
    $settings->updateOption("live_secret_key",$live_secret_key);
    $settings->updateOption("test_public_key",$test_public_key);
    $settings->updateOption("test_secret_key",$test_secret_key);
    $settings->updateOption("terminal_payment_mode",$terminal_payment_mode);
	$settings->updateOption("webhook_secret_key",$webhook_secret_key);
    //if admin is setting mode to LIVE - let's enable SSL, however if switching to TEST - don't switch off HTTPS redirection
    if($terminal_payment_mode=="live"){
        $settings->updateOption("redirect_https","y");
    }
    $a->addSuccess("Settings have been successfully updated");

    st_do_action('add_user_log',"Edited system settings");
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
                <h2>Payment Mode</h2>
                <hr>
                <div class="form-group  col-md-4">
                <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->terminal_payment_mode=='live')?"active":"" ?>">
                            <input type="radio" id="terminal_payment_mode1" name="terminal_payment_mode" value="live" <?php echo($settings->terminal_payment_mode=='live')?"checked":"" ?>/> Live
                        </label>
                        <label class="btn btn-default <?php echo($settings->terminal_payment_mode=='test')?"active":"" ?>">
                            <input type="radio" id="terminal_payment_mode2" name="terminal_payment_mode" value="test" <?php echo($settings->terminal_payment_mode=='test')?"checked":"" ?>/> Test
                        </label>
                    </div>
                </div>
                <div class="clearfix"></div>
                <h2>Live Credentials</h2>
                <hr>
                <div class="form-group col-md-4">
                    <label for="email_name"><span class="live-req <?php echo($settings->terminal_payment_mode=='live')?"":"hide" ?>">*</span>Secret Key</label>
                    <input type="text" class="form-control" name="live_secret_key" id="live_secret_key" placeholder="" value="<?php echo(htmlspecialchars($settings->live_secret_key)) ?>"
                           data-rule-required="<?php echo($settings->terminal_payment_mode=='live')?"true":"false" ?>" >
                    <small>Enter your Stripe <b>live</b> secret key</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="email"><span class="live-req <?php echo($settings->terminal_payment_mode=='live')?"":"hide" ?>">*</span>Public Key</label>
                    <input type="text" class="form-control" name="live_public_key" id="live_public_key" placeholder="" value="<?php echo($settings->live_public_key) ?>"
data-rule-required="<?php echo($settings->terminal_payment_mode=='live')?"true":"false" ?>" >
                    <small>Enter your Stripe <b>live</b> public key</small>
                </div>
                <div class="clearfix"></div>
                <h2>Test Credentials</h2>
                <hr/>
                <div class="form-group col-md-4">
                    <label for="email_name"><span class="test-req <?php echo($settings->terminal_payment_mode=='test')?"":"hide" ?>">*</span>Secret Key</label>
                    <input type="text" class="form-control" name="test_secret_key" id="test_secret_key" placeholder="" value="<?php echo(htmlspecialchars($settings->test_secret_key)) ?>"
                           data-rule-required="<?php echo($settings->terminal_payment_mode=='test')?"true":"false" ?>" >
                    <small>Enter your Stripe <b>test</b> secret key</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="email"><span class="test-req <?php echo($settings->terminal_payment_mode=='test')?"":"hide" ?>">*</span>Public Key</label>
                    <input type="text" class="form-control" name="test_public_key" id="test_public_key" placeholder="" value="<?php echo($settings->test_public_key) ?>"
                           data-rule-required="<?php echo($settings->terminal_payment_mode=='test')?"true":"false" ?>" >
                    <small>Enter your Stripe <b>test</b> public key</small>
                </div>
                <div class="clearfix"></div>
                <h2>Webhook</h2>
                <hr/>
                <div class="form-group col-md-4">
                    <label for="webhook_secret_key"><span class="test-req" ></span>Webhook Signing Secret</label>
                    <input type="text" class="form-control" name="webhook_secret_key" id="webhook_secret_key" placeholder=""
                           value="<?php echo(htmlspecialchars($settings->webhook_secret_key)) ?>">
                    <small>Your webhook endpoint signing secret.</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="webhook_secret_key"><span class="test-req" ></span>Webhook URL</label>
                    <input type="text" class="form-control" placeholder="" readonly
                           value="<?php echo(htmlspecialchars($webhook_url)) ?>">
                    <small>The webhook URL</small>
                </div>
                <div class="clearfix"></div>
                <hr/>
                <button type="submit" class="btn btn-success btn-lg">Save</button>
            </form>
            <?php }else{ ?>
                You have no permissions to view this section
            <?php } ?>
        </div>
    </div>
</div>
<?php echo($a->getFooter()) ?>
