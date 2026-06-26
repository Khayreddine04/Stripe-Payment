<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 * Include error handler first
 */

// Include error handler (relative to the root directory)
@include_once __DIR__ . "/../includes/error_handler.php";

include_once "includes/bootstrap.php";
$a->addScripts($settings->siteUrl()."/assets/js/jquery.validate-1-19-3.min.js",false);

$username = $a->esc("username");
$password = $a->esc("password");
$remember = $a->esc("remember",0);
$action = $a->esc("action");
$rd = $a->esc("rd");

$use_captcha = $settings->use_recaptcha=='y' && isset($_SESSION['login_failure']);

if($action == 'login'){

    $captcha = true;
    if($use_captcha && !$a->checkCaptcha())
        $captcha = false;

    if($captcha) {
        if ($user->login($username, $password, $remember)) {
            $a->addSuccess("ok");
        } else {
            $a->addError($user->error);
        }
    }
}
if($user->logon){
    if(!empty($rd)) {
        header("Location: $rd");
        exit();
    }else {
        header("Location: dashboard.php");
        exit();
    }
}
$a->getHeader();
?>
<?php if($use_captcha) {?>
<script src='https://www.google.com/recaptcha/api.js'></script>
<?php } ?>
<div class="container" role="main">
    <div class="login_form_wrapper">
        <?php echo($a->getMessages()) ?>
        <div class="login_form">
            <h2>Control Panel Login</h2>
            <form class="form-horizontal validate"  role="form" method="post">
                <input type="hidden" name="action" value="login">

                <div class="form-group" >
                    <label for="login" class="col-sm-3  control-label">Email:</label>
                    <div class="col-sm-7">
                        <div class="input-group">
                            <div class="input-group-addon">@</div>
                            <input class="form-control" type="email" id="username" name="username" placeholder="Enter email" value="<?php echo $a->crl($username) ?>"
                                   data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="col-sm-3  control-label">Password:</label>
                    <div class="col-sm-7">
                        <input type="password" class="form-control"  required id="password" placeholder="Password" name="password" autocomplete="off"
                               data-rule-required="true" data-msg-required="Enter Password">
                    </div>
                </div>
                <?php if($use_captcha) {?>
                    <div class="g-recaptcha" data-sitekey="<?php echo($settings->recaptcha_site_key) ?>" data-callback="checkCaptcha"></div>
                <?php } ?>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-8">
                        <small><a href="forgot.php" class="grey">Forgot your password?</a></small>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-success" id="login_btn"
                            <?php if($use_captcha) {?>disabled<?php } ?>>LOGIN</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $().ready(function() {
        // validate the comment form when it is submitted
        $(".validate").validate({
            errorPlacement: function(error, element) {
                element.parents(".form-group").addClass("has-error");
                element.wrap("<div class='control-wrap'>")
                error.appendTo(element.parent());
            },
            success: function(label) {
                label.parents(".form-group").removeClass("has-error").addClass("has-success");
            }
        });
    })

    function checkCaptcha() {
        $("#login_btn").prop('disabled',false);
    }
</script>
<?php echo($a->getFooter()) ?>
