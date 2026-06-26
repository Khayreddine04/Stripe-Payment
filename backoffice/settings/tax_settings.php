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
$settings_section = "tax_settings";
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

$tax_enable = $a->esc("tax_enable",'n');
if(!in_array($tax_enable,array('y','n'))){$tax_enable='n';}
$tax_abbreviation =  $a->esc("tax_abbreviation");
$tax_rate = (double)$a->esc("tax_rate");

$action =  $a->esc("action");

if($action=='save_settings'){

    if($tax_enable === 'n'){
        $tax_abbreviation = "";
    }

    $settings->updateOption("tax_enable",$tax_enable);
    $settings->updateOption("tax_rate",$tax_rate);
    $settings->updateOption("tax_abbreviation",$tax_abbreviation);

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
                <h2>Tax Settings</h2>
                <hr>
                <div class="form-group  col-md-4">
                    <label>Enable taxes?</label>
                    <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->tax_enable=='y')?"active":"" ?>">
                            <input type="radio" id="tax_enable1" name="tax_enable" value="y" <?php echo($settings->tax_enable=='y')?"checked":"" ?>/> Yes
                        </label>
                        <label class="btn btn-default <?php echo($settings->tax_enable=='n')?"active":"" ?>">
                            <input type="radio" id="tax_enable2" name="tax_enable" value="n" <?php echo($settings->tax_enable=='n')?"checked":"" ?>/> No
                        </label>
                    </div>
                </div>
                <div class="clearfix"></div>

                <div id="tax_options" style="display: <?php echo($settings->tax_enable=='y')?"block":"none" ?>">

                    <div class="form-group  col-md-3">
                        <label for="tax_abbreviation">Tax Abbreviation</label>
                        <input type="text" name="tax_abbreviation" id="tax_abbreviation" placeholder="e.g. VAT" value="<?php echo $settings->tax_abbreviation?>" class="form-control">
                    </div>

                    <div class="form-group  col-md-3">
                        <label for="tax_rate">Tax Percentage</label>
                        <input type="text" name="tax_rate" id="tax_rate" placeholder="e.g. 20" value="<?php echo $settings->tax_rate?>" class="form-control">
                    </div>
                    <div class="clearfix"></div>
                </div>


                <hr/>
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-success btn-lg">Save</button>
                </div>
            </form>
            <?php }else{?>
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
