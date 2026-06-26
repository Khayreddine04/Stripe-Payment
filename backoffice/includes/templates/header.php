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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base target="_parent">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo($title) ?></title>

    <!-- Bootstrap -->
    <link href="<?php echo($site_url) ?>/assets/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="<?php echo($site_url) ?>/assets/bootstrap/css/bootstrap-theme.css" rel="stylesheet">

    <?php echo($header_styles) ?>
    <link href="<?php echo($admin_url) ?>/assets/css/bootstrap-custom.css?r=3" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script src="<?php echo($site_url) ?>/assets/js/jquery-3.5.1.min.js"></script>
    <?php echo($header_scripts) ?>
</head>
<body role="document">

<div class="header">
    <div class="container">
        <div class="row">
            <div class="col-sm-7 col-xs-12">
                <div class="logo">
                    <a href="<?php echo($site_url) ?>"  class="pull-left"/><img src="<?php echo($admin_url) ?>/assets/images/logo.png"></a>

                    <p class="pull-left"><?php echo($title) ?></p>
                </div>
            </div>
            <div class="col-sm-5 col-xs-12 header-buttons">
                <?php if($user_logon){?>

                    <a type="button" class="btn btn-transparent" href="<?php echo($admin_url) ?>/logout.php">LOGOUT</a>

                    <a type="button" class="btn btn-transparent" href="<?php echo($site_url) ?>" target="_blank">VIEW TERMINAL</a>

                <?php }?>
                <?php st_do_action('admin_top_menu') ?>
            </div>
        </div>


    </div>

</div>
<?php echo($menu) ?>

