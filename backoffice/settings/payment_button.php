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
$settings_section = "payment_button";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("../../assets/js/chosen/chosen.jquery.js");
$a->addStyle("../../assets/js/chosen/chosen.css");
$a->addScripts("scripts.js?v=".rand(0,99999));

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$buttons_enable = $a->esc("buttons_enable",'n');
$buttons_country= $a->esc("buttons_country",'');
if(!in_array($buttons_enable,array('y','n'))){$buttons_enable='n';}

$action =  $a->esc("action");

if($action=='save_settings'){

    $settings->updateOption("buttons_enable",$buttons_enable);
    $settings->updateOption("buttons_country",$buttons_country);


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
                    <h2>Google Pay, Apple Pay, Microsoft Pay Integration</h2>
                    <hr>
                    <div class="form-group  col-md-4">
                        <label>Enable GPay/Apple Pay acceptance through Stripe?</label>
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->buttons_enable=='y')?"active":"" ?>">
                                <input type="radio" id="buttons_enable1" name="buttons_enable" value="y" <?php echo($settings->buttons_enable=='y')?"checked":"" ?>/> Yes
                            </label>
                            <label class="btn btn-default <?php echo($settings->buttons_enable=='n')?"active":"" ?>">
                                <input type="radio" id="buttons_enable2" name="buttons_enable" value="n" <?php echo($settings->buttons_enable=='n')?"checked":"" ?>/> No
                            </label>
                        </div>
                    </div>

                    <div class="form-group  col-md-4" id="country_cont" style="display: <?php echo $settings->buttons_enable=='y'?"block":"none"?>">
                        <label>Country</label>
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <select name="buttons_country" class="form-control">
                                <option value="">Select Country</option>
                                <option value="US" <?php echo $settings->buttons_country=='US'?"selected":""?>>United States</option>
                                <option value="CA" <?php echo $settings->buttons_country=='CA'?"selected":""?>>Canada</option>
                                <option value="AE" <?php echo $settings->buttons_country=='AE'?"selected":""?>>United Arab Emirates</option> 
                                <option value="AT" <?php echo $settings->buttons_country=='AT'?"selected":""?>>Austria</option> 
                                <option value="AU" <?php echo $settings->buttons_country=='AU'?"selected":""?>>Australia</option> 
                                <option value="BE" <?php echo $settings->buttons_country=='BE'?"selected":""?>>Belgium</option> 
                                <option value="BG" <?php echo $settings->buttons_country=='BG'?"selected":""?>>Bulgaria</option> 
                                <option value="BR" <?php echo $settings->buttons_country=='BR'?"selected":""?>>Brazil</option> 

                                <option value="CH" <?php echo $settings->buttons_country=='CH'?"selected":""?>>Switzerland</option> 
                                <option value="CI" <?php echo $settings->buttons_country=='CI'?"selected":""?>>Ivory Coast</option> 
                                <option value="CR" <?php echo $settings->buttons_country=='CR'?"selected":""?>>Costa Rica</option> 
                                <option value="CY" <?php echo $settings->buttons_country=='CY'?"selected":""?>>Cyprus</option> 
                                <option value="CZ" <?php echo $settings->buttons_country=='CZ'?"selected":""?>>Czech Republic</option> 
                                <option value="DE" <?php echo $settings->buttons_country=='DE'?"selected":""?>>Germany</option> 
                                <option value="DK" <?php echo $settings->buttons_country=='DK'?"selected":""?>>Denmark</option> 
                                <option value="DO" <?php echo $settings->buttons_country=='DO'?"selected":""?>>Dominican Republic</option> 
                                <option value="EE" <?php echo $settings->buttons_country=='EE'?"selected":""?>>Estonia</option> 
                                <option value="ES" <?php echo $settings->buttons_country=='ES'?"selected":""?>>Spain</option> 
                                <option value="FI" <?php echo $settings->buttons_country=='FI'?"selected":""?>>Finland</option> 
                                <option value="FR" <?php echo $settings->buttons_country=='FR'?"selected":""?>>France</option> 
                                <option value="GB" <?php echo $settings->buttons_country=='GB'?"selected":""?>>United Kingdom</option> 
                                <option value="GR" <?php echo $settings->buttons_country=='GR'?"selected":""?>>Greece</option> 
                                <option value="GT" <?php echo $settings->buttons_country=='GT'?"selected":""?>>Guatemala</option> 
                                <option value="HK" <?php echo $settings->buttons_country=='HK'?"selected":""?>>Hong Kong</option> 
                                <option value="HU" <?php echo $settings->buttons_country=='HU'?"selected":""?>>Hungary</option> 
                                <option value="ID" <?php echo $settings->buttons_country=='ID'?"selected":""?>>Indonesia</option> 
                                <option value="IE" <?php echo $settings->buttons_country=='IE'?"selected":""?>>Ireland</option> 
                                <option value="IN" <?php echo $settings->buttons_country=='IN'?"selected":""?>>India</option> 
                                <option value="IT" <?php echo $settings->buttons_country=='IT'?"selected":""?>>Italy</option> 
                                <option value="JP" <?php echo $settings->buttons_country=='JP'?"selected":""?>>Japan</option> 
                                <option value="LT" <?php echo $settings->buttons_country=='LT'?"selected":""?>>Lithuania</option> 
                                <option value="LU" <?php echo $settings->buttons_country=='LU'?"selected":""?>>Luxembourg</option> 
                                <option value="LV" <?php echo $settings->buttons_country=='LV'?"selected":""?>>Latvia</option> 
                                <option value="MT" <?php echo $settings->buttons_country=='MT'?"selected":""?>>Malta</option> 
                                <option value="MX" <?php echo $settings->buttons_country=='MX'?"selected":""?>>Mexico</option> 
                                <option value="MY" <?php echo $settings->buttons_country=='MY'?"selected":""?>>Malaysia</option> 
                                <option value="NL" <?php echo $settings->buttons_country=='NL'?"selected":""?>>Netherlands</option> 
                                <option value="NO" <?php echo $settings->buttons_country=='NO'?"selected":""?>>Norway</option> 
                                <option value="NZ" <?php echo $settings->buttons_country=='NZ'?"selected":""?>>New Zealand</option> 
                                <option value="PE" <?php echo $settings->buttons_country=='PE'?"selected":""?>>Peru</option> 
                                <option value="PH" <?php echo $settings->buttons_country=='PH'?"selected":""?>>Philippines</option> 
                                <option value="PL" <?php echo $settings->buttons_country=='PL'?"selected":""?>>Poland</option> 
                                <option value="PT" <?php echo $settings->buttons_country=='PT'?"selected":""?>>Portugal</option> 
                                <option value="RO" <?php echo $settings->buttons_country=='RO'?"selected":""?>>Romania</option> 
                                <option value="SE" <?php echo $settings->buttons_country=='SE'?"selected":""?>>Sweden</option> 
                                <option value="SG" <?php echo $settings->buttons_country=='SG'?"selected":""?>>Singapore</option> 
                                <option value="SI" <?php echo $settings->buttons_country=='SI'?"selected":""?>>Slovenia</option> 
                                <option value="SK" <?php echo $settings->buttons_country=='SK'?"selected":""?>>Slovakia</option>
                                <option value="SN" <?php echo $settings->buttons_country=='SN'?"selected":""?>>Senegal</option>
                                <option value="TH" <?php echo $settings->buttons_country=='TH'?"selected":""?>>Thailand</option>
                                <option value="TT" <?php echo $settings->buttons_country=='TT'?"selected":""?>>Trinidad and Tobago</option>

                                <option value="UY" <?php echo $settings->buttons_country=='UY'?"selected":""?>>Uruguay</option>
                            </select>
                        </div>
                    </div>


                    <div class="clearfix"></div>

                    <div class="form-group  col-md-12"><h4>Important Notes:</h4><p>If "YES" selected above, customers will see a “Pay now” (Google Pay / Microsoft Pay) button or an Apple Pay button, depending on what their <b>device and browser combination supports</b>, in addition to the regular "Submit Payment" button. If neither option is available, they don’t see the button. Supporting Apple Pay <a href="https://stripe.com/docs/stripe-js/elements/payment-request-button#verifying-your-domain-with-apple-pay" target="_blank">requires additional steps</a>, but compatible devices automatically support browser-saved cards, Google Pay, and Microsoft Pay (on desktop and mobile browsers)<br><br>
    Apple Pay with the Payment Request Button requires macOS 10.12.1+ or iOS 10.1+.</p></div>
<div class="form-group  col-md-12"><h4>Testing Prerequisites:</h4>
    <p>Before you start, you need to:<br><br>
</p><ul>
  <li><strong><a href="https://stripe.com/docs/stripe-js/elements/payment-request-button#html-js-testing" target="_blank">Add a payment method to your browser.</a></strong> For example, you can save a card in Chrome, or add a card to your Wallet for Safari.</li>
  <li><strong>Serve your application over HTTPS.</strong> This is a requirement both in development and in production.</li>
  <li><strong><a href="https://stripe.com/docs/stripe-js/elements/payment-request-button#verifying-your-domain-with-apple-pay" target="_blank">Verify your domain with Apple Pay</a></strong>, both in development and production.</li>
</ul><p></p>
</div>
<div class="form-group  col-md-12"><h4>Browser Requirements:</h4>
    <p>In addition, each payment method and browser has specific requirements. Please review them <a href="https://stripe.com/docs/stripe-js/elements/payment-request-button#html-js-testing" target="_blank">here</a></p>
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
    <script>
        $(function(){
            $(".chosen-select").chosen();
        })
    </script>
<?php echo($a->getFooter()) ?>
