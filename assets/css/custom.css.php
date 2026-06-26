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

$path = dirname(dirname(dirname(__FILE__)));
include_once $path ."/includes/bootstrap.php";
$settings = PT_Settings::instance();
//$url = $settings->site_url;
?>

.header {
    <?php if(is_file(HOME_DIR.$settings->header_background_image)) {?>
        background: url("<?php echo(trim($settings->header_background_image,'/')) ?>") repeat scroll 0 0 <?php echo(empty($settings->header_background_color)?$settings->header_background_color:"transparent") ?>;
    <?php }?>
    <?php if(empty($settings->header_background_color)){?>
        background-color: <?php echo($settings->header_background_color) ?>;
    <?php }?>
}
<?php echo(PHP_EOL) ?>
.header h2 {
    <?php if(!empty($settings->header_text_color)) {?>
    color:<?php echo($settings->header_text_color) ?>;
    <?php }?>
    font-size: <?php echo($settings->header_text_size) ?>px
}

html {
<?php if(is_file(HOME_DIR.$settings->container_background_image)) {?>
    background-image: url("<?php echo(trim($settings->container_background_image,'/')) ?>");
    background-size: inherit;
    background-position: center top;
<?php }else{?>
    <?php if(!empty($settings->container_background_color)){?>
        background-color: <?php echo($settings->container_background_color) ?>;
    <?php }?>
    background-image:none;
<?php }?>

}


.container.main {
<?php if(is_file(HOME_DIR.$settings->form_background_image)) {?>
    background-image: url("<?php echo(trim($settings->form_background_image,'/')) ?>");
    background-size: inherit;
    background-position: center top;
<?php }else{?>
    <?php if(!empty($settings->form_background_color)){?>
        background-color: <?php echo($settings->form_background_color) ?>;
    <?php }?>
    background-image:none;
<?php }?>

}

.payment_form h2 {
<?php if(!empty($settings->form_headers_background_color)){?>
    background-color: <?php echo($settings->form_headers_background_color) ?>;
<?php }?>
<?php if(!empty($settings->form_header_text_color)){?>
    color: <?php echo($settings->form_header_text_color) ?>;
<?php }?>
<?php if(!empty($settings->form_header_text_size)){?>
    font-size: <?php echo($settings->form_header_text_size) ?>px;
<?php }?>
}

.payment_form label{
<?php if(!empty($settings->form_label_text_color)){?>
    color: <?php echo($settings->form_label_text_color) ?>;
<?php }?>
<?php if(!empty($settings->form_label_text_size)){?>
    font-size: <?php echo($settings->form_label_text_size) ?>px;
<?php }?>
}

.payment_form a {
<?php if(!empty($settings->form_label_text_size)){?>
    font-size: <?php echo($settings->form_label_text_size) ?>px;
<?php }?>
}

.button-wrapper button{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>

<?php if(!empty($settings->form_button_text_color)){?>
    color: <?php echo($settings->form_button_text_color) ?> !important;
<?php }?>
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
    line-height: 50px;
    min-height: 50px;
    padding-bottom: 0;
    padding-top: 0;
}
.button-wrapper button:hover{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>
}
.payment_form .button-wrapper label{
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
}

.tax-wrapper button{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>

<?php if(!empty($settings->form_button_text_color)){?>
    color: <?php echo($settings->form_button_text_color) ?> !important;
<?php }?>
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
    line-height: 50px;
    min-height: 50px;
    padding-bottom: 0;
    padding-top: 0;
}
.tax-wrapper button:hover{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>
}
.payment_form .tax-wrapper label{
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
}

.fee-wrapper button{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>

<?php if(!empty($settings->form_button_text_color)){?>
    color: <?php echo($settings->form_button_text_color) ?> !important;
<?php }?>
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
    line-height: 50px;
    min-height: 50px;
    padding-bottom: 0;
    padding-top: 0;
}
.fee-wrapper button:hover{
<?php if(!empty($settings->form_button_background_color)){?>
    background-color: <?php echo($settings->form_button_background_color) ?> !important;
    border-color: <?php echo($settings->form_button_background_color) ?> !important;
<?php }?>
}
.payment_form .fee-wrapper label{
<?php if(!empty($settings->form_button_text_size)){?>
    font-size: <?php echo($settings->form_button_text_size) ?>px !important;
<?php }?>
}