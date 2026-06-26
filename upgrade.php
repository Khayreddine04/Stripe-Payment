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

//define("HOME_DIR", dirname(__FILE__));

include_once "includes/bootstrap.php";



$tt = "";
$SPTContinue = true;
$success = false;

$site_url = pathinfo("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
$site_url = $site_url['dirname'];

$license = $c->esc("license");
$username = $c->esc("username");


$install = $c->esc("install");
$domain_def = $_SERVER['HTTP_HOST'];
$domain = $c->esc("domain", $domain_def);

if ($SPTContinue) {
    // LOGIN VARIABLES
    // LOGIN
    if ($install == "yes") {
        include "./includes/grid.functions.php";

        if($SPTContinue){
            require_once("includes/upgrade.php");
            if ($SPTContinue) {
                $c->addSuccess("<br><b>Upgrade was successful!</b><br>Please <b>delete this file now</b> and go to <a href='backoffice/'>your backoffice</a> to continue setting up your terminal.");
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stripe Payment Terminal</title>

    <!-- Bootstrap -->
    <link href="assets/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="assets/bootstrap/css/bootstrap-theme.css" rel="stylesheet">
    <link href="assets/css/Minimalist/style.css" rel="stylesheet">
    <link rel="stylesheet" href="backoffice/assets/css/bootstrap-custom.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>

    <![endif]-->

    <script src="assets/js/jquery-3.5.1.min.js"></script>
    <script src="assets/js/jquery.validate-1-19-3.min.js"></script>
    <style>
        .container.main {
            background-color: #fafafa
        }

        .header {
            background-color: #fff
        }
    </style>
</head>
<body role="document">

<div class="header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center text-color-black">
                <h1>Upgrade Wizard</h1>
            </div>
        </div>
    </div>
</div>

<div class="wrapper">
    <div class="container main" role="main">
        <div class="install_container">
            <?php echo($c->getMessages()) ?>
            <div class="login">
                <?php if (!empty($tt)) {
                    echo $tt;
                } ?>
                <?php if (!empty($BWMessage)) {
                    echo $BWMessage;
                }

                if ($success) {
                } else {
                    ?>

                    <br/>
                    <form method="post" action="" enctype="multipart/form-data" name="ff1" id="ff1"
                          class="form-horizontal" role="form">

                        <h3>Envato License Validation</h3>

                        <p>
                            Please enter your CodeCanyon item purchase code (located in the license text file in your purchase
                            confirmation
                            email from Envato, or login to your account and go to downloads,
                            you will see red link "License Certificate" next to our product - <a
                                href="http://support.CriticalGears.io/bb-plugins/epcv/key_instructions.jpg"
                                target="_blank">example</a>). </p>

                        <div class="clear"></div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="license">Item Purchase Code:</label>

                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="license" placeholder="Item Purchase Code"
                                       name="license" value="<?php echo($license) ?>" data-rule-required="true">
                            </div>
                            <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                                        data-toggle="tooltip"
                                                        title="Item Purchase Code"></div>
                        </div>
                        <div class="clear"></div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="license">Licensed Domain:</label>

                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="license" placeholder="Domain Name"
                                       name="domain" value="<?php echo($domain) ?>" data-rule-required="true">
                            </div>
                            <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                                        data-toggle="tooltip"
                                                        title="Each license (item purchase code) can be used on 1 domain name - you have to enter the domain which is authorized to use the license you've entered. If installing on development server - enter your final live URL (or your client's URL)"></div>
                        </div>
                        <div class="clear"></div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="username">CodeCanyon Username:</label>

                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="username" placeholder="CodeCanyon Username"
                                       name="username"
                                       value="<?php echo($username) ?>" data-rule-required="true">
                            </div>
                            <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                                        data-toggle="tooltip"
                                                        title="YOUR username which you enter when you login to envato marketplaces.">
                            </div>
                        </div>
                        <div class="clear"></div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">Upgrade</button>
                                <input type="hidden" name="install" value="yes"/>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <div class="clear"></div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>

<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script>
    $('[data-toggle="tooltip"]').tooltip();
</script>
<script>
    $().ready(function () {
        // validate the comment form when it is submitted
        $("#ff1").validate({
            errorPlacement: function (error, element) {
                element.parents(".form-group").addClass("has-error");
                element.wrap("<div class='control-wrap'>")
                error.appendTo(element.parent());

            },
            success: function (label) {
                label.parents(".form-group").removeClass("has-error").addClass("has-success");
            }
        });
    })
</script>
</body>
</html>
