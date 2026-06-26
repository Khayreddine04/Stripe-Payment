<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 */

include_once "../includes/bootstrap.php";
include_once "settings.php";

if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$settings->set("admin_section",$pt_section);
$settings_section = "login";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("scripts.js");

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$username =  trim($a->esc("username"));
$name =  trim($a->esc("name"));
$password =  trim($a->esc("password"));
$repeat_password =  trim($a->esc("repeat_password"));
$action =  $a->esc("action");
$changeRequest = false;

if($action=='save_settings'){
    //reset old tmuser/tmpass/tmhash to avoid change of any unfinished request from previous change, if any
    $sqla = "UPDATE ".$db_pr."users SET tmhash = '', tmuser='', tmpass='' WHERE idUser = {$user->idUser}";
    $a->query($sqla);
    if($password != "") {
	    if ( $password == $repeat_password ) {
		    //passwords match, let's record it in a temporary password field and send email to current administrator email.
		    $changeRequest = true;
	    } else {
		    $a->addError( "Passwords don't match! Please re-enter new password and repeat it in repeat field." );
	    }
    }
    if($username!=$user->username && $username!=""){
        //if new username(email) doesn't equal old one - we must request validation for this action.
        $changeRequest = true;
    }

    if (!$a->error) {
        if (!empty($user->idUser)) {
            $md5password = md5($password);
            $sql = "UPDATE ".$db_pr."users SET
                            tmuser = '{$username}',
                            name = '{$name}',
                            tmpass = '{$md5password}'
                            WHERE idUser = {$user->idUser}";
            $a->query($sql);
            if($changeRequest){
                $hash = md5($user->idUser.time());
                $sqla = "UPDATE ".$db_pr."users SET tmhash = '{$hash}' WHERE idUser = {$user->idUser}";
                $a->query($sqla);
                $user->adminChangeConfirm($user->idUser,$hash,$settings->site_url);
                $a->addSuccess("New admin account information saved temporarily - you must confirm this change using email link which you will receive to current email account.");
            } else {
                $a->addSuccess("Account updated!");
            }
            st_do_action('add_user_log',"Edited system settings");
        }
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

                    <div class="clearfix"></div>
                    <h2>Your Login Information</h2>
                    <hr>
                    <div class="form-group col-md-4">
                        <label for="username"><span>*</span>Username/Email</label>
                        <input type="text" class="form-control" name="username" id="username" placeholder="" value="<?php echo(htmlspecialchars($user->username)) ?>"
                               data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email" >
                        <small>Any changes will need to be approved through current email!</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" name="name" id="name" placeholder=""  value="<?php echo(htmlspecialchars($user->name)) ?>">
                    </div>

                    <div class="clearfix"></div>

                    <div class="form-group col-md-4">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="" value=""  >
                        <small>Enter only if you want to change current password!</small>
                    </div>
                    <div class="form-group  col-md-4">
                        <label for="repeat_password">Repeat New Password</label>
                        <input type="password" class="form-control" name="repeat_password" id="repeat_password" placeholder="" value="" >
                        <small>Repeat the new password you entered</small>
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
