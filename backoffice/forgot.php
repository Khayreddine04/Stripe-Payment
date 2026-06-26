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

include_once "includes/bootstrap.php";

$a->addScripts($settings->siteUrl()."/assets/js/jquery.validate-1-19-3.min.js",false);
$username = $a->esc("username");
$action = $a->esc("action");
if($action == 'login'){
    if($user->passwordRetrieval($username)===true){
        $a->addSuccess("New Password has been successfully sent to your email address");
    }else{
        $a->addError($user->error);
    }
}
if($user->logon){
    header("Location: dashboard.php");
    exit();
}
$a->getHeader();
?>
<div class="container" role="main">
    <div class="login_form_wrapper">
        <?php echo($a->getMessages()) ?>
        <div class="login_form">
            <h2>Password Retrieval</h2>
            <form class="form-horizontal validate"  role="form" method="post">
                <input type="hidden" name="action" value="login">
                <div class="form-group" >
                    <label for="login" class="col-sm-3  control-label">Email:</label>
                    <div class="col-sm-7">
                        <div class="input-group">
                            <div class="input-group-addon">@</div>
                            <input class="form-control" type="email" id="username" name="username" placeholder="Enter email" value="<?php echo($username) ?>"
                                   data-rule-required="true" data-rule-email="true" data-msg-email="Incorrect Email">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-8">
                        <small><a href="index.php" class="grey">Back to login</a></small>
                    </div>
                </div>
                <div class="form-group">
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-success">Submit</button>
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
</script>
<?php echo($a->getFooter()) ?>
