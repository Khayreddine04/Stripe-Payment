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

$notice = $settings->terminal_payment_mode == 'test' ? "Test Mode Enabled. No real transactions will happen - all transaction will be charged in sandbox mode." : "";

if ($settings->terminal_payment_mode == 'test') {
    if (strlen($settings->test_secret_key) < 10 || strlen($settings->test_public_key) < 10) {
        $notice .= "<br>Test credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    } elseif ($settings->test_secret_key == 'YOUR STRIPE SECRET KEY FOR TEST MODE') {
        $notice .= "<br>Test credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
} elseif ($settings->$terminal_payment_mode == 'live') {
    if (strlen($settings->live_secret_key) < 10 || strlen($settings->live_public_key) < 10) {
        $notice .= "<br>Live credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    } elseif ($settings->live_public_key == 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE') {
        $notice .= "<br>Live credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
}

$header->notice = $notice;

$footer = new PT_Template("footer.php");

$custom = $c->esc('custom');
$payment_status = $c->esc('payment_status');

if ($dataArray = json_decode(stripslashes($custom), true)) {
    $pt_payment = $dataArray['id'];
    if(!empty($pt_payment) && is_numeric($pt_payment) && $payment_status=='Completed') {
        $payment = new paymentModel();
        $payment->setID($pt_payment);

        $paymentDetails = $payment->getPayment();

        if ($paymentDetails !== false && $settings->custom_action == 'y') {

            $js_map_data = array(
                '%SPT_Amount%' => $paymentDetails['amount'],
                '%SPT_OrderID%' => $paymentDetails['idPayment'],
                '%SPT_TrnID%' => $paymentDetails['idTransaction'],
                '%SPT_ProductID%' => $paymentDetails['idItem'],
                '%SPT_CusEmail%' => $paymentDetails['customerEmail'],
                '%SPT_CusFName%' => $paymentDetails['customerName']
            );

            $custom_action_code = $c->query("SELECT option_value FROM pt_settings WHERE option_name = 'custom_action_code'")->result_row('option_value');
            $js_code = strtr($custom_action_code, $js_map_data);

        }
    }
}

$header->render(true);
?>

<div class="container main" role="main">

    <h2><?php _tr("Payment successful") ?></h2>

    <p><?php echo $settings->thank_you_message ?></p>
    <p><?php echo st_apply_filter('back_to_terminal_link', '<a href="index.php">Back To Terminal</a>')?></p>
</div>
<?php $footer->render(true); ?>
</div>
<?php if(!empty($js_code)){ ?>
    <script>
        <?php echo $js_code?>
    </script>
<?php } ?>
</body>
<?php echo($c->getDebug()) ?>
</html>
