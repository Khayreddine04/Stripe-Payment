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
$settings_section = "fee_settings";
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

$fee_enable = $a->esc("fee_enable",'n');
if(!in_array($fee_enable,array('y','n'))){$fee_enable='n';}
$fee_type =  (double)$a->esc("fee_type");
$fee_label =  $a->esc("fee_label");
$fee_amount = (double)$a->esc("fee_amount");
$action =  $a->esc("action");

if($action=='save_settings'){

    $settings->updateOption("fee_enable",$fee_enable);
    $settings->updateOption("fee_type",$fee_type);
    $settings->updateOption("fee_amount",$fee_amount);
    $settings->updateOption("fee_label",$fee_label);

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
                <h2>Service Fee Settings</h2>
                <hr>
                <div class="form-group  col-md-4">
                    <label>Enable Service Fee?</label>
                    <div class="clearfix"></div>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->fee_enable=='y')?"active":"" ?>">
                            <input type="radio" id="fee_enable1" name="fee_enable" value="y" <?php echo($settings->fee_enable=='y')?"checked":"" ?>/> Yes
                        </label>
                        <label class="btn btn-default <?php echo($settings->fee_enable=='n')?"active":"" ?>">
                            <input type="radio" id="fee_enable2" name="fee_enable" value="n" <?php echo($settings->fee_enable=='n')?"checked":"" ?>/> No
                        </label>
                    </div>
                </div>


                <div id="fee_options" style="display: <?php echo($settings->fee_enable=='y')?"block":"none" ?>">

                    <div class="form-group  col-md-3">
                        <label for="tax_abbreviation">Service Charge Type</label>
                        <div class="clearfix"></div>
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default <?php echo($settings->fee_type==1)?"active":"" ?>">
                                <input type="radio" id="fee_type1" name="fee_type" value="1" <?php echo($settings->fee_type==1)?"checked":"" ?>/> %
                            </label>
                            <label class="btn btn-default <?php echo($settings->fee_type==2)?"active":"" ?>">
                                <input type="radio" id="fee_type2" name="fee_type" value="2" <?php echo($settings->fee_type==2)?"checked":"" ?>/> $
                            </label>
                        </div>
                    </div>

                    <div class="form-group  col-md-3">
                        <label for="tax_rate">Service Fee</label>
                        <input type="text" name="fee_amount" id="fee_amount" placeholder="e.g. 20" value="<?php echo $settings->fee_amount?>" class="form-control">
                    </div>
                    <div class="clearfix"></div>


                    <div class="form-group  col-md-3">
                        <label for="fee_label">Service Label</label>
                        <input type="text" name="fee_label" id="fee_label" placeholder="Service Fee" value="<?php echo $settings->fee_label?>" class="form-control">
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
