<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if (!empty($title)) {
                echo (strip_tags($title));
            } else {
                echo "Stripe - Payment Terminal";
            } ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme CSS -->
    <link href="assets/css/<?php echo ($selected_theme) ?>/style.css?v=<?php echo rand(1, 44) ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src="assets/js/jquery-3.5.1.min.js"></script>
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <?php 
    // Initialize variables
    $use_recaptcha = isset($use_recaptcha) ? $use_recaptcha : 'n';
    $custom_theme = isset($custom_theme) ? $custom_theme : false;
    ?>
    <?php if ($use_recaptcha == 'y') { ?>
        <script src='https://www.google.com/recaptcha/api.js'></script>
    <?php } ?>
    <style>
        <?php if ($custom_theme)
            include(HOME_DIR . "/assets/css/custom.css.php") ?>
    </style>
</head>

<body role="document">
    <?php if (!empty($notice)) { ?>
        <div class="top_notice"><?php echo ($notice) ?></div>
    <?php } ?>
    <?php if ($selected_theme === 'CardStyle') {
    ?>

        <div class="header">
            <div class="container">
                <div class="row">
                    <?php if (!empty($logo)) { ?>
                        <div class="col-sm-2 col-xs-12 vcenter">
                            <a href="<?php echo ($site_url) ?>">
                                <img src="<?php echo ($logo) ?>" class="pull-left payment-form-logo" />
                            </a>
                        </div>
                        <div class="col-sm-10 col-xs-12 text-right text-xs-center vcenter">
                            <div class="header_title_cont">
                                <h2><?php echo ($title) ?></h2>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="col-md-12">
                            <h2 class="text-center"><?php echo ($title) ?></h2>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } elseif ($selected_theme === 'green') {
    ?>
        <div class="header header--green">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8 col-xs-12">
                        <h2 class="m-0"><?php echo ($title) ?></h2>
                        <?php if (!empty($logo)) { ?>
                            <div class="mt-10">
                                <a href="<?php echo ($site_url) ?>">
                                    <img src="<?php echo ($logo) ?>" class="payment-form-logo" />
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-sm-4 col-xs-12 text-right">
                        <span class="label label-success">Secure</span>
                    </div>
                </div>
            </div>
        </div>
    <?php } elseif ($selected_theme === 'Minimalist') {
    ?>
        <div class="header header--simple">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <?php if (!empty($logo)) { ?>
                            <img src="<?php echo ($logo) ?>" class="payment-form-logo mb-10" />
                        <?php } ?>
                        <h2 class="m-0"><?php echo ($title) ?></h2>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="header">
            <div class="container">
                <div class="row">
                    <?php if (!empty($logo)) { ?>
                        <div class="col-sm-2 col-xs-12 vcenter">
                            <a href="<?php echo ($site_url) ?>">
                                <img src="<?php echo ($logo) ?>" class="pull-left payment-form-logo" />
                            </a>
                        </div>
                        <div class="col-sm-10 col-xs-12 text-right text-xs-center vcenter">
                            <div class="header_title_cont">
                                <h2><?php echo ($title) ?></h2>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="col-md-12">
                            <h2 class="text-center"><?php echo ($title) ?></h2>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>