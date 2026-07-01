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
$subscription_data = false;

if (!empty($pt_subscription_id) && class_exists('subscriptionModel')) {
    $subscriptionModel = new subscriptionModel();
    $subscription_data = $subscriptionModel->getSubscriptionByTrn($pt_subscription_id);
}

$show_form = true;
if ($pt_action == 'cancel_subscription') {
    if ($payment->cancelSubscription()) {
        $show_form = false;
    }
}
$header->render(true);
?>

<style>
    .cancel-page {
        max-width: 760px;
        margin: 42px auto 64px;
        color: #263238;
    }

    .cancel-panel {
        background: #fff;
        border: 1px solid #e7ebef;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(31, 45, 61, 0.08);
        overflow: hidden;
    }

    .cancel-hero {
        padding: 30px 34px 24px;
        border-bottom: 1px solid #eef1f4;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .cancel-eyebrow {
        display: inline-block;
        margin-bottom: 10px;
        color: #687789;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cancel-hero h1 {
        margin: 0 0 10px;
        font-size: 30px;
        line-height: 1.2;
        font-weight: 700;
        color: #1f2d3d;
    }

    .cancel-hero p {
        margin: 0;
        color: #536273;
        font-size: 16px;
        line-height: 1.6;
    }

    .cancel-body {
        padding: 28px 34px 34px;
    }

    .cancel-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 24px;
    }

    .cancel-summary-item {
        padding: 14px 16px;
        border: 1px solid #edf0f3;
        border-radius: 6px;
        background: #fbfcfd;
    }

    .cancel-summary-label {
        display: block;
        margin-bottom: 6px;
        color: #778391;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .cancel-summary-value {
        color: #1f2d3d;
        font-weight: 600;
        word-break: break-word;
    }

    .cancel-note {
        margin: 0 0 22px;
        padding: 16px 18px;
        border-left: 4px solid #f0ad4e;
        border-radius: 6px;
        background: #fff8ec;
        color: #6f552b;
        line-height: 1.55;
    }

    .cancel-confirm {
        margin-bottom: 24px;
        padding: 18px;
        border: 1px solid #f1c7c7;
        border-radius: 6px;
        background: #fff5f5;
    }

    .cancel-confirm label {
        margin: 0;
        color: #5d2f2f;
        font-weight: 600;
        line-height: 1.5;
    }

    .cancel-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }

    .cancel-actions .btn {
        min-width: 190px;
    }

    .cancel-success {
        padding: 38px 34px;
        text-align: center;
    }

    .cancel-success h1 {
        margin: 0 0 12px;
        color: #1f2d3d;
        font-size: 30px;
        font-weight: 700;
    }

    .cancel-success p {
        max-width: 560px;
        margin: 0 auto 22px;
        color: #536273;
        font-size: 16px;
        line-height: 1.6;
    }

    @media (max-width: 640px) {
        .cancel-page {
            margin: 20px auto 40px;
        }

        .cancel-hero,
        .cancel-body,
        .cancel-success {
            padding-left: 22px;
            padding-right: 22px;
        }

        .cancel-summary {
            grid-template-columns: 1fr;
        }

        .cancel-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="container main cancel-page" role="main">

    <?php echo($c->getMessages()) ?>
    <?php if ($show_form) { ?>
        <form class="validate payment_form cancel-panel" role="form" id="payment_form" method="post">
            <input type="hidden" name="pt_action" value="cancel_subscription">
            <input type="hidden" name="pt_subscription_id" value="<?php echo htmlspecialchars($pt_subscription_id, ENT_QUOTES, 'UTF-8') ?>">

            <div class="cancel-hero">
                <span class="cancel-eyebrow"><?php _tr("Subscription cancellation") ?></span>
                <h1><?php _tr("Confirm subscription cancellation") ?></h1>
                <p><?php _tr("We are sorry to see you leave. Your access will stop after cancellation. If something did not work as expected, you can keep your subscription active and contact support instead.") ?></p>
            </div>

            <div class="cancel-body">
                <div class="cancel-summary">
                    <div class="cancel-summary-item">
                        <span class="cancel-summary-label"><?php _tr("Subscription ID") ?></span>
                        <span class="cancel-summary-value"><?php echo htmlspecialchars($pt_subscription_id, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="cancel-summary-item">
                        <span class="cancel-summary-label"><?php _tr("Customer") ?></span>
                        <span class="cancel-summary-value">
                            <?php
                            echo htmlspecialchars(
                                $subscription_data['customerEmail'] ?? $subscription_data['customerName'] ?? 'Current subscription',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <p class="cancel-note">
                    <?php _tr("Before you cancel, please note that active subscription benefits and future access will be removed. You can restart later, but your current subscription terms may no longer be available.") ?>
                </p>

                <div class="cancel-confirm">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="pt_agree" id="pt_agree" value="1"
                                   data-rule-required="true"
                                   data-msg-required="<?php _tr("Please confirm that you want to cancel this subscription") ?>">
                            <?php _tr("I understand that submitting this form will cancel my subscription.") ?>
                        </label>
                    </div>
                </div>

                <div class="cancel-actions">
                    <button type="submit" class="btn btn-lg btn-danger"><b><?php _tr("Cancel my subscription") ?></b></button>
                    <a class="btn btn-lg btn-default" href="index.php"><?php _tr("Keep my access") ?></a>
                </div>
            </div>
        </form>
    <?php } else { ?>
        <div class="cancel-panel cancel-success">
            <h1><?php _tr("Your subscription has been cancelled") ?></h1>
            <p><?php _tr("We are sorry to see you go. Your cancellation has been processed, and you can return at any time if your needs change.") ?></p>
            <a class="btn btn-lg btn-primary" href="index.php"><?php _tr("Back to terminal") ?></a>
        </div>
    <?php } ?>
</div>
<?php $footer->render(true); ?>
</div>

<?php $popup->render(true) ?>

<script src="assets/bootstrap/js/bootstrap.min.js" type="application/javascript"></script>
<script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
<script type="text/javascript">
    $(function () {
        $("#payment_form").on("submit", function () {
            if (!$("#pt_agree").is(":checked")) {
                return true;
            }
            $(this).find("button[type='submit']").prop("disabled", true);
        });
    });
</script>

</body>
<?php echo($c->getDebug()) ?>
</html>
