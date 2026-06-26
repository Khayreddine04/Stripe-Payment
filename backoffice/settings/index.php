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


$settings_section = "general";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap3-wysihtml5.js",false);
$a->addStyle("../../assets/bootstrap/css/bootstrap3-wysihtml5.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("scripts.js?v=".rand(0,99999));

$email_name = $a->esc("email_name");
$email =  $a->esc("email");
$email_from =  $a->esc("email_from");
$email_signature =  $a->esc("email_signature");
$billing_info = $a->esc("billing_info");
$page_title =  $a->esc("page_title");
$payment_type =  $a->esc("payment_type");
$enable_paypal = $a->esc("enable_paypal");
$show_description =  $a->esc("show_description");
$thank_you_message =  $a->esc("thank_you_message");
$thank_you_redirect =  $a->esc("thank_you_redirect");
$show_billing =  $a->esc("show_billing");
$show_shipping =  $a->esc("show_shipping");
$redirect_https =  $a->esc("redirect_https");
$site_url =  $a->esc("site_url");
$site_ssl =  $a->esc("site_ssl");
$action =  $a->esc("action");

$recaptcha_site_key =  $a->esc("recaptcha_site_key");
$recaptcha_secret_key =  $a->esc("recaptcha_secret_key");
$use_recaptcha =  $a->esc("use_recaptcha");

$custom_action =  $a->esc("custom_action");
$custom_action_code =  $a->esc("custom_action_code");

$show_terms =  $a->esc("show_terms");
$terms_and_conditions =  $a->esc("terms_and_conditions");

$send_mail =  $a->esc("send_mail");
$smtp_host =  $a->esc("smtp_host");
$smtp_port =  $a->esc("smtp_port");
$smtp_secure =  $a->esc("smtp_secure");
$smtp_username =  $a->esc("smtp_username");
$smtp_password =  $a->esc("smtp_password");
$timezone =  $a->esc("timezone");


if($action=="delete_logo"){
    if($settings->terminal_logo !=='/uploads/terminal_logo1.png')
        @unlink(HOME_DIR.$settings->terminal_logo);
    $settings->updateOption("terminal_logo","");
}
if($action=='save_settings'){

    $settings->updateOption("email_name",$email_name);
    $settings->updateOption("email",$email);
    $settings->updateOption("email_signature",$email_signature);
    $settings->updateOption("email_from",$email_from);
    $settings->updateOption("page_title",$page_title);
    $settings->updateOption("payment_type",$payment_type);
    $settings->updateOption("enable_paypal",$enable_paypal);
    $settings->updateOption("show_description",$show_description);
    $settings->updateOption("redirect_https",$redirect_https);
    $settings->updateOption("show_billing",$show_billing);
    $settings->updateOption("show_shipping",$show_shipping);
    $settings->updateOption("site_ssl",$site_ssl);
    $settings->updateOption("site_url",$site_url);
    $settings->updateOption("billing_info",$billing_info);
    $settings->updateOption("thank_you_message",$thank_you_message);
    $settings->updateOption("thank_you_redirect",$thank_you_redirect);
    $settings->updateOption("recaptcha_site_key",$recaptcha_site_key);
    $settings->updateOption("recaptcha_secret_key",$recaptcha_secret_key);
    $settings->updateOption("use_recaptcha",$use_recaptcha);

    $settings->updateOption("custom_action",$custom_action);
    $settings->updateOption("custom_action_code",$custom_action_code);

    $settings->updateOption("show_terms",$show_terms);
    $settings->updateOption("terms_and_conditions",$terms_and_conditions);

	$settings->updateOption("send_mail",$send_mail);
	$settings->updateOption("smtp_host",$smtp_host);
	$settings->updateOption("smtp_port",$smtp_port);
	$settings->updateOption("smtp_secure",$smtp_secure);
	$settings->updateOption("smtp_username",$smtp_username);
	$settings->updateOption("smtp_password",$smtp_password);

    $settings->updateOption("timezone",$timezone);


    if(!empty($_FILES['terminal_logo']['name'])){
        $result = $a->uploadFile($_FILES['terminal_logo'],"terminal_logo",2);
        if(!$result['error']){
            $settings->updateOption("terminal_logo",$result['imgPath']);
        }else{
            $a->addError($result['error']);
        }
    }

    $a->addSuccess("Settings have been successfully updated");

    if(isset($_REQUEST['test_mail']) && $_REQUEST['test_mail'] === 'y'){
        $res = $a->sendMail($email,"Test mail","","This is test mail from {$_SERVER['SERVER_NAME']}",true);
        if($res===true){
	        $a->addSuccess("Test message successfully sent to '{$email}'");
        }else{
            $a->addError($res);
        }
    }
    st_do_action('add_user_log',"Edited system settings");
}

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$settings->timezone = $settings->timezone === false?date_default_timezone_get():$settings->timezone;

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
                <input type="hidden" name="test_mail" value="" id="test_mail">
                <input type="hidden" name="action" value="save_settings" >
                    <h2>Company Details</h2>
                    <hr>
                    <div class="form-group col-md-4">
                        <label for="email_name"><span>*</span>Company Name</label>
                        <input type="text" class="form-control" name="email_name" id="email_name" placeholder="" value="<?php echo(htmlspecialchars($settings->email_name)) ?>"
                               data-rule-required="true" >
                        <small>Enter your name or your business name. This will be used for all emails that get sent out as sender name.</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="email"><span>*</span>Notifications Email</label>
                        <div class="input-group">
                            <div class="input-group-addon">@</div>
                            <input type="text" class="form-control" name="email" id="email" placeholder="" value="<?php echo($settings->email) ?>"
                                   data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email">

                        </div>
                        <small>Email address where payment confirmation emails will be sent to for administrator.</small>
                    </div>

                    <div class="clearfix"></div>
                    <div class="form-group col-md-8">
                        <label for="email">Email Signature</label>
                        <textarea class="form-control" rows="4" name="email_signature" id="email_signature"><?php echo($settings->email_signature) ?></textarea>
                    </div>

                    <div class="form-group  col-md-4">
                        <label for="email">Billing Information</label>
                        <textarea class="form-control" rows="6" name="billing_info" id="billing_info"><?php echo($settings->billing_info) ?></textarea>

                    </div>
                    <div class="clearfix"></div>
                    <h2>Email Settings</h2>
                    <hr/>
                    <div class="form-group  col-md-4">
                        <label for="email"><span>*</span>Sender Email</label>
                        <div class="input-group">
                            <div class="input-group-addon">@</div>
                            <input type="text" class="form-control" name="email_from" id="email_from" placeholder="" value="<?php echo($settings->email_from) ?>"
                                   data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email">

                        </div>
                        <small>Your customers will see this EMAIL as 'FROM:' in all associated notifications.</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Mail Service</label>
                        <select class="form-control" name="send_mail" id="send_mail">
                            <option value="php"  <?php echo($settings->send_mail=="php"?"selected":"") ?>>PHP mail</option>
                            <option value="smtp"  <?php echo($settings->send_mail=="smtp"?"selected":"") ?>>SMTP server</option>

                        </select>
                    </div>
                    <div class="form-group  col-md-4" style="padding-top: 25px">

                        <button type="button" name="test_mail" class="btn btn-success btn-lg"
                            onclick="$('#test_mail').val('y');this.form.submit()">Send test mail</button>
                    </div>
                    <div class="clearfix"></div>

                    <div id="smtp_cont" style="display: <?php echo $settings->send_mail=="smtp"?"block":"none"?>">
                        <div class="form-group  col-md-4">
                            <label for="email"><span>*</span>SMTP host</label>
                            <input type="text" class="form-control" name="smtp_host" id="smtp_host" placeholder="" value="<?php echo($settings->smtp_host) ?>"
                                       data-rule-required="true">
                            <small>Set the SMTP server to send through</small>
                        </div>
                        <div class="form-group  col-md-4">
                            <label for="email"><span>*</span>SMTP port</label>
                            <input type="text" class="form-control" name="smtp_port" id="smtp_port" placeholder="" value="<?php echo($settings->smtp_port) ?>"
                                   data-rule-required="true">
                            <small>TCP port to connect to</small>
                        </div>
                        <div class="form-group  col-md-4">
                            <label for="page_title"><span>*</span>SMTP encryption</label>
                            <select class="form-control" name="smtp_secure" id="smtp_secure">
                                <option value="ssl"  <?php echo($settings->smtp_secure=="ssl"?"selected":"") ?>>SSL</option>
                                <option value="tls"  <?php echo($settings->smtp_secure=="tls"?"selected":"") ?>>TLS</option>

                            </select>

                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group  col-md-4">
                            <label for="email"><span>*</span>SMTP username</label>
                            <input type="text" class="form-control" name="smtp_username" id="smtp_username" placeholder="" value="<?php echo($settings->smtp_username) ?>"
                                   data-rule-required="true">
                            <small>SMTP username</small>
                        </div>
                        <div class="form-group  col-md-4">
                            <label for="email"><span>*</span>SMTP password</label>
                            <input type="password" class="form-control" name="smtp_password" id="smtp_password" placeholder="" value="<?php echo($settings->smtp_password) ?>"
                                   data-rule-required="true">
                            <small>SMTP password</small>
                        </div>

                    </div>
                    <div class="clearfix"></div>


                    <h2>Terminal Settings</h2>
                    <hr/>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Page Heading</label>
                        <input type="text" class="form-control" name="page_title" id="page_title" placeholder=""  value="<?php echo(htmlspecialchars($settings->page_title)) ?>">
                        <small>Title text to be displayed at the top of the payment terminal page, on the right side from logo.</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Terminal Type</label>
                        <select class="form-control" name="payment_type">
                            <option value="">Please Select</option>
                            <option value="item"  <?php echo($settings->payment_type=="item"?"selected":"") ?>>All Items (Products and Services)</option>
                            <option value="product"  <?php echo($settings->payment_type=="product"?"selected":"") ?>>Products (one-time)</option>
                            <option value="service"  <?php echo($settings->payment_type=="service"?"selected":"") ?>>Services (recurring)</option>
                            <option value="input" <?php echo($settings->payment_type=="input"?"selected":"") ?>>Customers Input Amount</option>
                            <option value="donation" <?php echo($settings->payment_type=="donation"?"selected":"") ?>>Donations</option>
                        </select>
                        <small>"All Items" will show the list of pre-configured items that you have set. "Customer Inputs Amount" will show amount input field instead of products dropdown. For other options - please see the manual.</small>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="attach_pdf_invoice">PayPal Payments</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->enable_paypal=='y')?"active":"" ?>">
                                <input type="radio" id="enable_paypal1" name="enable_paypal" value="y" <?php echo($settings->enable_paypal=='y')?"checked":"" ?>/> Enabled
                            </label>
                            <label class="btn btn-default <?php echo($settings->enable_paypal=='n')?"active":"" ?>">
                                <input type="radio" id="enable_paypal2" name="enable_paypal" value="n" <?php echo($settings->enable_paypal=='n')?"checked":"" ?>/> Disabled
                            </label>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <div class="form-group col-md-4">
                        <label for="site_url"><span>*</span>Terminal URL</label>
                        <input type="text" class="form-control" name="site_url" id="site_url" placeholder="" value="<?php echo($settings->site_url) ?>"
                               data-rule-required="true" data-rule-url="true">
                        <small>Automatically detected. <b>DO NOT CHANGE</b> unless advised by criticalgears support staff or if it was incorrectly auto-detected initially.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="site_url">Terminal logo</label>
                        <input type="file" name="terminal_logo" id="terminal_logo" data-rule-extension="jpg|jpeg|png" data-msg-extension="Allowed only .jpg, .jpeg, .png">
                        <small>Will be displayed on header and emails.</small>
                    </div>
                    <?php if('' !== $settings->terminal_logo){?>
                        <div class="form-group col-md-4">
                            <label for="site_url">Logo Preview</label>
                            <img src="<?php echo($settings->site_url.$settings->terminal_logo) ?>"/>
                            <small><a href="?action=delete_logo"><span aria-hidden="true" class="glyphicon glyphicon-remove" style="color: red"></span>Delete</a></small>
                        </div>
                    <?php }?>
                    <div class="clearfix"></div>


                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>HTTPs redirect</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->redirect_https=='y')?"active":"" ?>">
                                <input type="radio" id="redirect_https1" name="redirect_https" value="y" <?php echo($settings->redirect_https=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->redirect_https=='n')?"active":"" ?>">
                                <input type="radio" id="redirect_https2" name="redirect_https" value="n" <?php echo($settings->redirect_https=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                        <small>Automatically redirect non-https requests to https (you must set this to YES if terminal is going live).</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="site_ssl">SSL Seal Code</label>
                        <textarea type="text" class="form-control" name="site_ssl" id="site_ssl" placeholder=""><?php echo(htmlspecialchars($settings->site_ssl)) ?></textarea>
                        <small>You can paste your SSL site seal code here, if you have one.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="site_url">Timezone</label>
                        <select name="timezone" class="form-control">
                            <?php foreach ( PT_Core::getTimeZonesList() as $k => $v ) { ?>
                                <option value="<?php echo $k ?>" <?php echo($settings->timezone==$k?"selected":"") ?>><?php echo $v ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="clearfix"></div>
                    <div class="form-group col-md-8">
                        <label for="thank_you_message">Thank You message after successful payment</label>
                        <textarea class="form-control" rows="4" name="thank_you_message" id="thank_you_message"><?php echo($settings->thank_you_message) ?></textarea>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="site_url">Redirect after payment</label>
                        <input type="text" class="form-control" name="thank_you_redirect" id="thank_you_redirect" placeholder="http://" value="<?php echo($settings->thank_you_redirect) ?>"
                               data-rule-url="true">
                        <small>URL of your custom "Thank You" page</small>
                    </div>
                    <div class="clearfix"></div>

                    <h2>Terminal Fields Settings</h2>
                    <hr/>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Show description</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->show_description=='y')?"active":"" ?>">
                                <input type="radio" id="show_description1" name="show_description" value="y" <?php echo($settings->show_description=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->show_description=='n')?"active":"" ?>">
                                <input type="radio" id="show_description2" name="show_description" value="n" <?php echo($settings->show_description=='n')?"checked":"" ?>/> No
                            </label>
                        </div>

                        <small>Whether or not to show the description field. This only applies if you have "Customer Input" set as the payment type.</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Show billing address fields</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->show_billing=='y')?"active":"" ?>">
                                <input type="radio" id="show_billing1" name="show_billing" value="y" <?php echo($settings->show_billing=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->show_billing=='n')?"active":"" ?>">
                                <input type="radio" id="show_billing2" name="show_billing" value="n" <?php echo($settings->show_billing=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                        <small>Whether or not to show the billing address fields on the payment terminal page.</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Show shipping info block</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->show_shipping=='y')?"active":"" ?>">
                                <input type="radio" id="show_shipping1" name="show_shipping" value="y" <?php echo($settings->show_shipping=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->show_shipping=='n')?"active":"" ?>">
                                <input type="radio" id="show_shipping2" name="show_shipping" value="n" <?php echo($settings->show_shipping=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                        <small>Whether or not to show the shipping address fields on the payment terminal page.</small>
                    </div>
                    <div class="clearfix"></div>
                    <h2>reCAPTCHA</h2>
                    <hr/>
                    <div class="form-group col-md-4">
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->use_recaptcha=='y')?"active":"" ?>">
                                <input type="radio" id="use_recaptcha1" name="use_recaptcha" value="y" <?php echo($settings->use_recaptcha=='y')?"checked":"" ?>/> Enabled
                            </label>
                            <label class="btn btn-default <?php echo($settings->use_recaptcha=='n')?"active":"" ?>">
                                <input type="radio" id="use_recaptcha2" name="use_recaptcha" value="n" <?php echo($settings->use_recaptcha=='n')?"checked":"" ?>/> Disabled
                            </label>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div id="recapcha" style="display: <?php echo($settings->use_recaptcha=='y')?"block":"none" ?>">
                        <div class="form-group col-md-6 ">
                            <label for="recaptcha_site_key">Site key</label>
                            <input type="text" class="form-control" name="recaptcha_site_key" id="recaptcha_site_key"
                                   value="<?php echo($settings->recaptcha_site_key) ?>">
                            <small>If you don't have one, but would like to use reCAPTCHA - you can register an account
                                (free) <a href="https://www.google.com/recaptcha/">here</a>.
                            </small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="recaptcha_secret_key">Secret key</label>
                            <input type="text" class="form-control" name="recaptcha_secret_key"
                                   id="recaptcha_secret_key" value="<?php echo($settings->recaptcha_secret_key) ?>">

                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <h2>Custom Payment Actions</h2>
                    <hr/>

                    <div class="form-group  col-md-12">
                        <label for="page_title"><span>*</span>Enable custom action processing after any payment is received</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->custom_action=='y')?"active":"" ?>">
                                <input type="radio" id="custom_action1" name="custom_action" value="y" <?php echo($settings->custom_action=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->custom_action=='n')?"active":"" ?>">
                                <input type="radio" id="custom_action2" name="custom_action" value="n" <?php echo($settings->custom_action=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group  col-md-12" id="custom_action_cont" style="display:<?php echo ($settings->custom_action=='y')?"block":"none"?>" >

                        <textarea class="form-control" rows="6" name="custom_action_code" id="custom_action_code"><?php echo(htmlspecialchars($settings->custom_action_code)) ?></textarea>
                        <small>You can use this section to add your JavaScript code which will run whenever any payment (recurring or one-time) will be received & processed by the terminal through Stripe or PayPal. You can use following variables: <code>%SPT_Amount%</code>,  <code>%SPT_OrderID%</code>,<code>%SPT_TrnID%</code>, <code>%SPT_ProductID%</code>,  <code>%SPT_CusEmail%</code>,  <code>%SPT_CusFName%</code></small>
                    </div>

                    <div class="clearfix"></div>
                    <h2>Terms & Conditions</h2>
                    <hr/>

                    <div class="form-group  col-md-4">
                        <label for="page_title"><span>*</span>Enable terms & conditions</label>

                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->show_terms=='y')?"active":"" ?>">
                                <input type="radio" id="show_terms1" name="show_terms" value="y" <?php echo($settings->show_terms=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->show_terms=='n')?"active":"" ?>">
                                <input type="radio" id="show_terms2" name="show_terms" value="n" <?php echo($settings->show_terms=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                        <small>Whether or not to show the terms and conditions which customer has to accept to proceed.</small>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group  col-md-12" id="terms_and_conditions_cont" style="display:<?php echo ($settings->show_terms=='y')?"block":"none"?>" >

                        <textarea class="form-control" rows="6" name="terms_and_conditions" id="terms_and_conditions"><?php echo(htmlspecialchars($settings->terms_and_conditions)) ?></textarea>

                    </div>
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
