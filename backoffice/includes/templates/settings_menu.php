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

<ul class="settings_menu">
    <li class="<?php echo($section=="general"?"active":"") ?>"><a href="index.php">General Settings</a></li>
    <li class="<?php echo($section=="invoice_settings"?"active":"") ?>"><a href="invoice_settings.php">Invoice Settings</a></li>
    <li class="<?php echo($section=="tax_settings"?"active":"") ?>"><a href="tax_settings.php">Tax Settings</a></li>
    <li class="<?php echo($section=="fee_settings"?"active":"") ?>"><a href="fee_settings.php">Service Fee Settings</a></li>
    <li class="<?php echo($section=="currency_settings"?"active":"") ?>"><a href="currency_settings.php">Currency Settings</a></li>
    <li class="<?php echo($section=="terminal_settings"?"active":"") ?>"><a href="terminal_settings.php">Stripe Settings</a></li>
    <li class="<?php echo($section=="payment_gateways"?"active":"") ?>"><a href="payment_gateways.php">Payment Gateways</a></li>
    <li class="<?php echo($section=="domains"?"active":"") ?>"><a href="domains.php">Domains</a></li>

    <li class="<?php echo($section=="payment_button"?"active":"") ?>"><a href="payment_button.php">GPay & Apple Pay</a></li>
    <?php if($enable_paypal=='y'){?>
    <li class="<?php echo($section=="paypal_settings"?"active":"") ?>"><a href="paypal_settings.php">PayPal Settings</a></li>
    <?php }?>
    <?php if(st_apply_filter('show_login_in_settings',true)){?>
    <li class="<?php echo($section=="login"?"active":"") ?>"><a href="login.php">Login Settings</a></li>
    <?php } ?>
    <li class="<?php echo($section=="customize"?"active":"") ?>"><a href="customize.php">Design Settings</a></li>
    <li class="<?php echo($section=="addons"?"active":"") ?>"><a href="addons.php">Plugins</a></li>
</ul>
