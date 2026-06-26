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
$header = new PT_Template("header.php");
$header->title = $settings->page_title;
$header->logo = !empty($settings->terminal_logo) ? $settings->siteUrl() . $settings->terminal_logo : "";
$header->theme = $settings->selected_theme;
$header->custom_theme = $settings->theme_type == 'custom' ? true : false;
$header->terminal_payment_mode = $settings->paypal_payment_mode;
$footer = new PT_Template("footer.php");
$popup = new PT_Template("popup.php");
$header->render(true);
?>

<div class="container main" role="main">
    <form class=" validate payment_form" role="form" id="payment_form" method="post">
        <input type="hidden" name="pt_action" value="do_payment">
        <input type="hidden" name="stripeToken" value="" id="stripeToken">
        <h2><?php _tr("Payment Cancelled!") ?></h2>
        <p>You have cancelled PayPal payment.<br/><br/><a href="index.php">Back To Terminal</a></p>
    </form>
</div>
<?php $footer->render(true); ?>
</div>


</body>
<?php echo($c->getDebug()) ?>
</html>
