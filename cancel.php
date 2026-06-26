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
$header->terminal_payment_mode = $settings->terminal_payment_mode;
$footer = new PT_Template("footer.php");
$popup = new PT_Template("popup.php");
$payment = new PT_Stripe_Payment();
$pt_action = $c->esc("pt_action");
$pt_subscription_id = $c->esc("pt_subscription_id", 0);
$pt_agree = $c->esc("pt_agree");

$show_form = true;
if ($pt_action == 'cancel_subscription') {
    if ($payment->cancelSubscription()) {
        $show_form = false;
    }
}
$header->render(true);
?>

<div class="container main" role="main">

    <?php echo($c->getMessages()) ?>
    <?php if ($show_form) { ?>
        <form class=" validate payment_form" role="form" id="payment_form" method="post">
            <input type="hidden" name="pt_action" value="cancel_subscription">
            <input type="hidden" name="pt_subscription_id" value="<?php echo($pt_subscription_id) ?>">

            <h2><?php _tr("Subscription cancellation") ?></h2>

            <div class="row">
                <div class="col-md-12">
                    <p>Subscription ID: <?php echo $pt_subscription_id ?></p>
                </div>

            </div>
            <div class="clearfix"></div>
            <div class="bg-danger box-notice">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="pt_agree" id="pt_agree" value="1"
                               data-rule-required="true"
                               data-msg-required="<?php _tr("You need to accept subscription cancellation") ?>"> <?php _tr("I understand that clicking submit will cancel above mentioned service") ?>
                    </label>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-lg btn-success"><b><?php _tr("Cancel subscription") ?></b></button> or <a href="index.php">Back To Terminal</a>
                </div>
            </div>
        </form>
    <?php } ?>
</div>
<?php $footer->render(true); ?>
</div>

<?php $popup->render(true) ?>

<script src="assets/bootstrap/js/bootstrap.min.js" type="application/javascript"></script>
<script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
<script src="assets/js/ccvalidations.js" type="application/javascript"></script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    var stripe = Stripe('<?php echo $payment->public_key; ?>');
</script>

</body>
<?php echo($c->getDebug()) ?>
</html>
