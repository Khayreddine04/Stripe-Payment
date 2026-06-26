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


$settings_section = "addons";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");

$a->addStyle("../assets/vendors/sweetalert2/sweetalert2.min.css");
$a->addScripts("../assets/vendors/sweetalert2/sweetalert2.min.js",false);

$a->addScripts("scripts.js?v=".rand(0,99999));

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$fee_enable = $a->esc("fee_enable",'n');


$do = $a->esc('do');
$n = $a->esc('n');

$license = $a->esc('license');
$username = $a->esc('username');


if(!empty ($do) && $do == "activate"){

    $data = array(
        "license" => $license,
        "username" => $username,
        "product" => $n,
        "action" => "validate_stripe_plugin"
    );
    $api_url = "https://validate.criticalgears.io";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $api_call = curl_exec($ch);
    curl_close($ch);
    if ($json_data = json_decode($api_call, true)) {
        if (isset($json_data['res']) && $json_data['res']) {
            if(st_activate_plugin($n))
                $a->addSuccess("Plugin $n was successfully activated", "success");
        } else {
            $a->addError($json_data['msg']);
        }
    } else {
        $a->addError("Error! Wrong API response format");
    }


}
if(!empty ($do) && $do == "deactivate"){
    if( st_deactivate_plugin($n))
        $a->addSuccess("Plugin $n was successfully deactivated", "success");
}


$pluginsList = get_plugins_list();
$activePlugins = is_array(getOption('active_plugins'))?getOption('active_plugins'):array();

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
            <h2>Plugins</h2>
            <hr>
            <div class="plugin_items">
                <?php foreach ($pluginsList as $plugin){?>
                            <div class="plugin_item">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo $plugin['plugin_name'] ?>
                                            <?php if(isset($plugin['present'])){?>
                                            <span class="pull-right">v<?php echo $plugin['plugin_version'] ?></span>
                                            <?php } ?>
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <?php echo $plugin['plugin_description'] ?>

                                    </div>
                                    <div class="panel-footer">
                                        <?php if (isset($plugin['present'])) { ?>
                                            <?php if (in_array($plugin['name'], $activePlugins)) { ?>
                                                <a href='?do=deactivate&n=<?php echo urlencode($plugin['name']) ?>' class='btn btn-sm btn-warning deactivate' id="deactivate_button" alt='Deactivate'>Deactivate</a>
                                            <?php } else { ?>
                                                <a href='javascript:;' onclick="check_license('<?php echo urlencode($plugin['name']) ?>')" class='btn btn-sm btn-success activate' alt='Activate'>Activate</a>
                                            <?php } ?>
                                            <a href="<?php echo $plugin['plugin_link'] ?>" class="pull-right plugin_page" alt="Plugin Page">Plugin Page</a>
                                        <?php } else { ?>
                                            <a href='<?php echo $plugin['buy_link'] ?>' target="_blank" class=' btn btn-sm btn-success ' alt='Buy Now'>Buy Now</a>
                                            <a href="<?php echo $plugin['plugin_link'] ?>" target="_blank" class="pull-right plugin_page" alt="Plugin Page">Plugin Page</a>
                                        <?php } ?>

                                    </div>
                                </div>
                            </div>
                <?php } ?>

            </div>
            <?php }else{ ?>
                You have no permissions to view this section
            <?php } ?>
        </div>


    </div>
</div>
<!-- Modal License check -->
<div class="modal fade" id="checkLicense" tabindex="-1" role="dialog" aria-labelledby="confirmEdit">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="get" action="">
                <input type="hidden" name="do" value="activate">
                <input type="hidden" name="n" value="" id="form_plugin">
                <div class="modal-header">
                    <h4 class="modal-title" id="confirmEditLabel">License Validation</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group  col-md-12">
                            <p>License validation required in order to use this premium plugin.</p>
                            <p>Please enter <strong>Item Purchase Code</strong> for <strong>Stripe Payment Terminal</strong>. You can get it from CodeCanyon, from downloads section, by clicking on green download button next to Stripe Payment Terminal and selecting license certificate file.</p>
                        </div>
                        <div class="form-group  col-md-12">
                            <label for="tax_rate">Item Purchase Code:</label>
                            <input type="text" name="license" id="license"  value="" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success pull-left">Submit</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                </div>
            </form>
        </div>
    </div>
</div>

<?php echo($a->getFooter()) ?>
