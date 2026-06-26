<?php

/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.0
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */


// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Respect the @ error suppression operator
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $error_type = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ][$errno] ?? 'Unknown Error';
    
    $error = "<div class='alert alert-danger'>";
    $error .= "<strong>$error_type:</strong> $errstr in <strong>$errfile</strong> on line <strong>$errline</strong>";
    $error .= "</div>";
    
    echo $error;
    
    // Don't execute PHP internal error handler
    return true;
});

// Handle uncaught exceptions
set_exception_handler(function($e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Uncaught Exception:</strong> " . $e->getMessage();
    echo "<br><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine();
    echo "<br><strong>Stack Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
});

// Shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Fatal Error:</strong> " . $error['message'];
        echo "<br><strong>File:</strong> " . $error['file'] . " on line " . $error['line'];
        echo "</div>";
    }
});


define("HOME_DIR", dirname(__FILE__));

include_once "includes/config.php";
include_once "includes/_config.php";
include_once "includes/functions.php";

$c = new PT_Core(false);
$settings = PT_Settings::instance();


$tt = "";
$SPTContinue = true;
$success = false;

//1. check that includes/ is writable
//2. if not - throw error, else show form.
//3. form will have 4 fields for database and 1 field for license key and 1 key for user to enter future username name for this license key.
//4. after form submitted we need to show success message and further instructions.
if (!is_writable("includes/")) {
    @chmod("includes/", 0777);
    if (!is_writable("includes/")) {
        @chmod("includes/", 777);
        if (!is_writable("includes/")) {
            //chmoding didn't help. throw error
            $SPTContinue = false;
            $c->addError("Please set chmod 755 or 777 for directory \"includes\"");
        }
    }
}

if (!is_writable("uploads/")) {
    @chmod("uploads/", 0777);
    if (!is_writable("uploads/")) {
        @chmod("uploads/", 777);
        if (!is_writable("uploads/")) {
            //chmoding didn't help. throw error
            $SPTContinue = false;
            $c->addError("Please set chmod 755 or 777 for directory \"uploads\"");
        }
    }
}

$site_url = pathinfo("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
$site_url = $site_url['dirname'];

$admin_username = $c->esc("admin_username");
$admin_password = $c->esc("admin_password");
$admin_email = $c->esc("admin_email");
$admin_confirm_password = $c->esc("admin_confirm_password");

$email_from_name = $c->esc("email_from_name", "Stripe Payment Terminal");
$server_name = str_replace("www", "", $_SERVER['SERVER_NAME']);
$email_from_email = $c->esc("email_from_email", "noreply@{$server_name}");

$dbn = $c->esc("dbn");
$dbp = $c->esc("dbp");
$dbu = $c->esc("dbu");
$dbh = $c->esc("dbh", "localhost");

$dbpr = $c->esc("dbpr", "pt_");
if ($dbpr != "pt_") {
    $dbpr .= "pt_";
}

$license = $c->esc("license");
$username = $c->esc("username");

$install = $c->esc("install");
$domain_def = $_SERVER['HTTP_HOST'];
$domain = $c->esc("domain", $domain_def);

if ($SPTContinue) {
    // LOGIN VARIABLES
    // LOGIN
    if ($install == "yes") {
        if ($dbn == "" || $dbu == "" || $dbh == "" || $license == "" || $domain == "" || $admin_email == '' || $email_from_email == '' || $email_from_name == '' || $admin_password == "" || $admin_confirm_password == "") {
            $c->addError("Some fields were left empty. All fields are mandatory. Try again");
        } elseif ($admin_password != $admin_confirm_password) {
            $c->addError("Password did not match");
        } else {
            // Check DB connection with detailed error handling
            try {
                $mysqli = new mysqli($dbh, $dbu, $dbp, $dbn);
                
                if ($mysqli->connect_error) {
                    throw new Exception("Database connection failed: " . $mysqli->connect_error);
                }
                
                // Test if we can execute a simple query
                if (!$mysqli->query("SELECT 1")) {
                    throw new Exception("Database test query failed: " . $mysqli->error);
                }
                
                $SPTContinue = true;
                $c->link = $mysqli; // Make sure the connection is available to other parts of the script
                
            } catch (Exception $e) {
                $SPTContinue = false;
                $c->addError("<strong>Database Error:</strong> " . $e->getMessage() . 
                           "<br>Please verify your database settings and try again.");
            }
            $l = $license;
            if (!is_writable("includes/dbconnect.php")) {
                @chmod("includes/dbconnect.php", 0777);
                if (!is_writable("includes/dbconnect.php")) {
                    //chmoding didn't help. throw error
                    $SPTContinue = false;
                    $c->addError("Please set chmod 755 or 777 for file \"includes/dbconnect.php\"");
                }
            }
            include "./includes/grid.functions.php";
            if ($SPTContinue) {
                $salt = md5(time());
                //create mysql.php file
                if (@mysqli_connect($dbh, $dbu, $dbp, $dbn) !== false) {
                    $ourFileName = "includes/dbconnect.php";

                    $fh = fopen($ourFileName, 'w+');
                    $stringData = '<?php

                        //EDIT ONLY FOLLOWING 5 LINES
                        define("DB_HOST", \'' . $dbh . '\'); //hostname
                        define("DB_USER", \'' . $dbu . '\'); // username
                        define("DB_PASS", \'' . $dbp . '\'); // password
                        define("DB_NAME", \'' . $dbn . '\'); //database name
                        define("DB_CHARSET", \'utf8mb4\');
                        define("DB_PREFIX", \'' . $dbpr . '\');

                        $db_pr=DB_PREFIX;

                        global $mysqli,$db_pr;
                        if (DB_HOST != "" && DB_USER != "" && DB_PASS != "" && DB_NAME != "") {
                            @$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                            if ($mysqli) {

                                mysqli_query($mysqli, \'SET NAMES \' . DB_CHARSET);
                            }else{
                                die("<html><head><link rel=\'stylesheet\' type=\'text/css\' href=\'assets/bootstrap/css/bootstrap.css\'></head><body><div class=\'row col-lg-6 col-lg-push-3\'><p class=\' alert-danger alert\'><i class=\'glyphicon glyphicon-remove-circle\'></i> Can\'t connect to database.</p></div><div class=\'clearfix\'></div><div class=\'row col-lg-6 col-lg-push-3 text-center\'><h3>Is this new installation?</h3></div><div class=\'clearfix\'></div><div class=\'row col-lg-6 col-lg-push-3\'><p class=\' alert alert-warning\'>Please navigate to <a href=\'install.php\'>install.php</a> to install this application. <br/></p></div></body></html>");
                            } 
                        } else { 
                            die("<html><head><link rel=\'stylesheet\' type=\'text/css\' href=\'assets/bootstrap/css/bootstrap.css\'></head><body><div class=\'row col-lg-6 col-lg-push-3\'><p class=\' alert-danger alert\'><i class=\'glyphicon glyphicon-remove-circle\'></i> Can\'t connect to database.</p></div><div class=\'clearfix\'></div><div class=\'row col-lg-6 col-lg-push-3 text-center\'><h3>Is this new installation?</h3></div><div class=\'clearfix\'></div><div class=\'row col-lg-6 col-lg-push-3\'><p class=\' alert alert-warning\'>Please navigate to <a href=\'install.php\'>install.php</a> to install this application. <br/></p></div></body></html>"); 
                        }

                        define("SALT","' . $salt . '");';
                    fwrite($fh, $stringData);
                    fclose($fh);
                    require_once("includes/dbconnect.php");
                    $c->connect();
                    if ($c->is_connected) {
                        require_once("includes/sql.php");
                        if ($SPTContinue) {
                            $c->addSuccess("<br><b>Installation was successful!</b><br>Please <b>delete this file now</b> and go to <a href='backoffice/'>your backoffice</a> to continue setting up your terminal.");
                            $success = true;
                            @chmod("includes/dbconnect.php", 0644);
                        }
                    } else {
                        $c->addError("Can't Connect to database");
                    }
                } else {
                    $c->addError("Cannot connect to database. Check your input");
                }
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
                    <h1>Installation Wizard</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper">
        <div class="container main" role="main">
            <div class="install_container">
                <?php echo ($c->getMessages()) ?>
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

                        <br />
                        <form method="post" action="" enctype="multipart/form-data" name="ff1" id="ff1"
                            class="form-horizontal" role="form">
                            <p>Please follow on screen instructions to install "Stripe Payment Terminal" for the first
                                time.</p>

                            <h3>Administrator Account</h3>

                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_username">Administrator Email/Login:</label>

                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">@</div>
                                        <input type="text" class="form-control" id="admin_username"
                                            placeholder="Email" name="admin_username"
                                            value="<?php if (isset($admin_username)) {
                                                        echo $admin_username;
                                                    } ?>" data-rule-required="true" data-rule-email="true"
                                            data-msg-email="Incorrect Email">
                                    </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_password">Administrator Password:</label>
                                <div class="col-sm-4">
                                    <input type="password" class="form-control" id="admin_password" placeholder="Password" name="admin_password">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_confirm_password">Confirm Password:</label>
                                <div class="col-sm-4">
                                    <input type="password" class="form-control" id="admin_confirm_password" placeholder="Confirm Password" name="admin_confirm_password">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_email">Notifications Email:</label>

                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">@</div>
                                        <input type="email" class="form-control" id="admin_email" placeholder="Email" name="admin_email" value="<?php echo ($admin_email) ?>" data-rule-required="true"
                                            data-rule-email="true" data-msg-email="Incorrect Email">
                                    </div>
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="Email for notifications of new payments"></div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_email">Notifications Sender Name:</label>

                                <div class="col-sm-4">

                                    <input type="text" class="form-control" id="email_from_name"
                                        placeholder="Name" name="email_from_name"
                                        value="<?php echo ($email_from_name) ?>" data-rule-required="true">

                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="Enter your name or your business name. This will be used for all emails that get sent out as sender name.">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="admin_email">Notifications Sender Email:</label>

                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">@</div>
                                        <input type="email" class="form-control" id="email_from_email"
                                            placeholder="Email" name="email_from_email"
                                            value="<?php echo ($email_from_email) ?>" data-rule-required="true"
                                            data-rule-email="true" data-msg-email="Incorrect Email">
                                    </div>
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="Your customers will see this EMAIL as 'FROM:' in all associated notifications.
                                                        For better email delivery we HIGHLY RECOMMEND to set this email to the same domain this application will be running from (like noreply@<?php echo $server_name; ?>)">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <h3>Database Information</h3>

                            <p>Please enter your <strong class="alert-danger"> EXISTING </strong> database login information. "Stripe payment
                                Terminal" does not create a database, it only uses the one which you specify below to
                                populate with tables, so it has to exist before proceeding!
                                All fields are <strong class="alert-danger"> mandatory </strong>.</p>

                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="dbh">Database host:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="dbh" placeholder="Database host" name="dbh"
                                        value="<?php echo ($dbh) ?>" data-rule-required="true">
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="In most cases this is 'localhost' "></div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="dbh">Database tables prefix:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="dbpr" placeholder="Database tables prefix"
                                        name="dbpr"
                                        value="<?php echo ($dbpr) ?>">
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="All 'Stripe payment Terminal' database tables will begin with this prefix. It has to be unique,so there wouldn't be any conflicts with your existing data in db.">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="dbn">Database name:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="dbn" placeholder="Database name" name="dbn"
                                        value="<?php echo ($dbn) ?>" data-rule-required="true">
                                </div>

                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="dbu">Database username:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="dbu" placeholder="Database username"
                                        name="dbu"
                                        value="<?php echo ($dbu) ?>" data-rule-required="true">
                                </div>

                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="dbp">Database password:</label>

                                <div class="col-sm-4">
                                    <input type="password" class="form-control" id="dbp" placeholder="Database password"
                                        name="dbp" value="<?php echo ($dbp) ?>" data-rule-required="true">
                                </div>

                            </div>

                            <div class="clear"></div>
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
                                        name="license" value="<?php echo ($license) ?>" data-rule-required="true">
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="Item Purchase Code"></div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="license">Licensed Domain:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="domain" placeholder="Domain Name"
                                        name="domain" value="<?php echo ($domain) ?>" data-rule-required="true">
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="Each license (item purchase code) can be used on 1 instance (installation) - you have to enter the domain which is authorized to use the license you've entered. If installing on development server - enter your final live URL (or your client's URL)"></div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" for="username">CodeCanyon Username:</label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="username" placeholder="YOUR CodeCanyon Username"
                                        name="username"
                                        value="<?php echo ($username) ?>" data-rule-required="true">
                                </div>
                                <div class="icon-info"><img src="assets/images/info.png" data-placement="bottom"
                                        data-toggle="tooltip"
                                        title="YOUR username which you enter when you login to envato marketplaces.">
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-4">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">Install</button>
                                    <input type="hidden" name="install" value="yes" />
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
        $().ready(function() {
            // validate the comment form when it is submitted
            $("#ff1").validate({
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
</body>

</html>