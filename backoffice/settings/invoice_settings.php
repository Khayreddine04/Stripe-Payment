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
$settings_section = "invoice_settings";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap3-wysihtml5.js",false);
$a->addStyle("../../assets/bootstrap/css/bootstrap3-wysihtml5.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("scripts.js?v=".rand(0,99999));

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$attach_pdf_invoice =  $a->esc("attach_pdf_invoice","y");
$display_pdf_payment_options =  $a->esc("display_pdf_payment_options","y");
$invoice_terms_preset =  $a->esc("invoice_terms_preset");
$track_invoice =  $a->esc("track_invoice",'n');
$action =  $a->esc("action");

if($action=='save_settings'){

    $settings->updateOption("attach_pdf_invoice",$attach_pdf_invoice);
    $settings->updateOption("display_pdf_payment_options",$display_pdf_payment_options);
    $settings->updateOption("invoice_terms_preset",$invoice_terms_preset);
    $settings->updateOption("track_invoice",$track_invoice);
    $a->addSuccess("Invoice settings have been successfully updated");

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
                <h2>Invoice Settings</h2>
                <hr>
                <div class="form-group col-md-4">
                    <label for="attach_pdf_invoice">Attach PDF invoice to invoice emails?</label>
                    <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->attach_pdf_invoice=='y')?"active":"" ?>">
                            <input type="radio" id="attach_pdf_invoice1" name="attach_pdf_invoice" value="y" <?php echo($settings->attach_pdf_invoice=='y')?"checked":"" ?>/> Yes
                        </label>
                        <label class="btn btn-default <?php echo($settings->attach_pdf_invoice=='n')?"active":"" ?>">
                            <input type="radio" id="attach_pdf_invoice2" name="attach_pdf_invoice" value="n" <?php echo($settings->attach_pdf_invoice=='n')?"checked":"" ?>/> No
                        </label>
                    </div>
                </div>


                <div class="form-group col-md-4" id="display_pdf_payment_options_cont"
                     style="display:<?php echo($settings->attach_pdf_invoice=='y')?"block":"none" ?>;">
                    <label for="display_pdf_payment_options">Display payment options on PDF</label>
                    <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->display_pdf_payment_options=='y')?"active":"" ?>">
                            <input type="radio" id="display_pdf_payment_options1" name="display_pdf_payment_options" value="y" <?php echo($settings->display_pdf_payment_options=='y')?"checked":"" ?>/> Yes
                        </label>
                        <label class="btn btn-default <?php echo($settings->display_pdf_payment_options=='n')?"active":"" ?>">
                            <input type="radio" id="display_pdf_payment_options2" name="display_pdf_payment_options" value="n" <?php echo($settings->display_pdf_payment_options=='n')?"checked":"" ?>/> No
                        </label>
                    </div>
                </div>

                <div class="form-group col-md-4">
                    <label for="display_pdf_payment_options">Track Invoice Email</label>
                    <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->track_invoice=='y')?"active":"" ?>">
                            <input type="radio" id="track_invoice1" name="track_invoice" value="y" <?php echo($settings->track_invoice=='y')?"checked":"" ?>/> Yes
                        </label>
                        <label class="btn btn-default <?php echo($settings->track_invoice=='n')?"active":"" ?>">
                            <input type="radio" id="track_invoice2" name="track_invoice" value="n" <?php echo($settings->track_invoice=='n')?"checked":"" ?>/> No
                        </label>
                    </div>
                    <div class="clearfix"></div>

                    <small>if enabled, we will place tiny transparent image on all invoice emails - if customer opens that email, we will record "open" activity for that invoice.</small>
                </div>
                <div class="clearfix"></div>
                <hr/>
                <div class="form-group  col-md-12">
                    <label for="page_title">Invoice Terms</label>
                    <div class="clearfix"></div>
                    <textarea class="form-control" rows="6" name="invoice_terms_preset" id="invoice_terms_preset"><?php echo(htmlspecialchars($settings->invoice_terms_preset)) ?></textarea>
                    <small>You can pre-set invoice terms & conditions in this field.</small>
                </div>


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
