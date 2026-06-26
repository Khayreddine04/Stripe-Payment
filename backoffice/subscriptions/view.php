<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../log/subscription_errors.log');

// Log the start of the script
error_log('===== START subscription view.php =====');
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('GET data: ' . print_r($_GET, true));
error_log('POST data: ' . print_r($_POST, true));

// Function to log errors with backtrace
function logError($message)
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1] ?? [];
    $location = ($caller['file'] ?? 'unknown') . ':' . ($caller['line'] ?? '0');
    error_log("[ERROR] $message in $location");
}

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

include_once "../includes/bootstrap.php";
include_once "settings.php";

$settings->set("admin_section", $pt_section);

$can_view = st_apply_filter('have_permissions', true, 'can_view_subscriptions');
$can_cancel = st_apply_filter('have_permissions', true, 'can_cancel_subscriptions');

if (!$user->logon) {
    header("Location: ../index.php");
    exit();
}

// Log the ID being used
$subscriptionId = $a->esc($pt_id);
$$pt_id = $subscriptionId;
error_log("Subscription ID: $subscriptionId");

// Verify the ID is not empty
if (empty($subscriptionId)) {
    $errorMsg = 'No subscription ID provided';
    error_log($errorMsg);
    die($errorMsg);
}

error_log('Creating subscription model with ID: ' . $subscriptionId);
try {
    $subscription = new subscriptionModel();
    if (!method_exists($subscription, 'setID')) {
        throw new Exception('subscriptionModel does not have setID method');
    }
    $subscription->setID($subscriptionId);
    error_log('Subscription model created successfully');
} catch (Exception $e) {
    $errorMsg = 'Failed to create subscription model: ' . $e->getMessage();
    error_log($errorMsg);
    die($errorMsg);
}
$itemData = $billingAddress = '';

try {
    error_log('Calling getSubscription()');
    $paymentDetails = $subscription->getSubscription();
    error_log('getSubscription() returned: ' . print_r($paymentDetails, true));

    if ($paymentDetails === false) {
        $errorMsg = "Sorry. Payment not found for ID: " . ($$pt_id ?? 'null');
        error_log($errorMsg);
        $a->addError($errorMsg);
    }
} catch (Exception $e) {
    $errorMsg = 'Error in getSubscription(): ' . $e->getMessage();
    error_log($errorMsg);
    error_log('Stack trace: ' . $e->getTraceAsString());
    $a->addError('An error occurred while loading subscription details.');
}

if (!empty($paymentDetails['idItem'])) {
    $item = new itemModel();
    $item->setID($paymentDetails['idItem']);
    $item->getItem();
    $itemData = $item->itemData;
}

if (!empty($paymentDetails['idInvoice'])) {
    $invoice = new invoiceModel();
    $invoice->setID($paymentDetails['idInvoice']);
    $invoice->setInvoiceData();
    $invoiceData = $invoice->invoiceData;
    $invoiceNumber = $invoiceData['invoiceNumber'];
    $invoiceID = $invoiceData['idInvoice'];
}

$billingAddress = $subscription->getFormattedAddress();
$shippingAddress = $subscription->getFormattedShippingAddress();

$cancelLink = $subscription->getCancelSubscriptionUrl();
$start_date = $subscription->getSubscriptionStartDateText();

$payments = $subscription->getPayments();

// Get upfront fee payment if it exists
$upfrontFeePayment = $subscription->getUpfrontFeePayment();

// Extract source and clickid directly from payment details
$clickid = !empty($paymentDetails['clickid']) ? $paymentDetails['clickid'] : '';
$source = !empty($paymentDetails['source']) ? $paymentDetails['source'] : '';

$is_overriden = false;
if ($paymentDetails['paymentsCount'] > 0) {
    $initial_amount = round($paymentDetails['or_amount'] / $paymentDetails['paymentsCount'], 2);
    $is_overriden = $initial_amount != $paymentDetails['amount'];
}

$a->addStyle($a->getSiteUrl() . "/assets/js/data_table/css/dataTable.bootstrap.css");
$a->addStyle("//cdn.datatables.net/responsive/1.0.7/css/responsive.dataTables.min.css");

$title = strpos($paymentDetails['idItem'], 'pt_donation') !== false ? "Donation Details" : "Subscription Details";

$a->getHeader();
?>
<div class="container" role="main">
    <?php if ($can_view) { ?>
        <div class="row">
            <div class="col-md-9 col-lg-9 col-sm-9 col-xs-6 vcenter">
                <h2><?php echo $title ?></h2>
            </div><!--
            -->
            <div class="col-md-3 col-lg-3 col-sm-3 col-xs-6 vcenter text-right"><span class="back_to_list">&larr;<a href="index.php">Back to list</a></span> </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <?php echo ($a->getMessages()) ?>
                <div class="details">
                    <table>
                        <tbody>
                            <tr>
                                <td>Customer Name:</td>
                                <td><?php echo (stripslashes($paymentDetails['customerName'])) ?></td>
                            </tr>
                            <tr>
                                <td>Customer Email:</td>
                                <td><?php echo (stripslashes($paymentDetails['customerEmail'])) ?></td>
                            </tr>
                            <?php if (!empty($itemData)) { ?>
                                <tr>
                                    <td>Product/Service:</td>
                                    <td><?php echo (stripslashes($itemData['itemName'])) ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (!empty($paymentDetails['source'])) { ?>
                                <tr>
                                    <td>Source:</td>
                                    <td><?php echo htmlspecialchars($paymentDetails['source']) ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (!empty($paymentDetails['clickid'])) { ?>
                                <tr>
                                    <td>Click ID:</td>
                                    <td><?php echo htmlspecialchars($paymentDetails['clickid']) ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (!empty($billingAddress)) { ?>
                                <tr>
                                    <td>Billing Address:</td>
                                    <td><?php echo (stripslashes($billingAddress)) ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (!empty($shippingAddress)) { ?>
                                <tr>
                                    <td>Shipping Address:</td>
                                    <td><?php echo (stripslashes($shippingAddress)) ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Date Processed:</td>
                                <td><?php echo ($a->getDateFormat($paymentDetails['dateCreated'])) ?></td>
                            </tr>

                            <tr>
                                <td>Start Date:</td>
                                <td><?php echo ($start_date) ?></td>
                            </tr>
                            <tr>
                                <td>Transaction Type:</td>
                                <td><?php echo ($subscription->getFrequencyText()) ?> </td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td>
                                    <?php echo (getStatus($paymentDetails['status'], $paymentDetails)) ?>

                                    </span>
                                    <?php if ($can_cancel) { ?>
                                        <?php if ($paymentDetails['status'] == 'active' && $cancelLink !== false) { ?>
                                            ( <a href="<?php echo ($cancelLink) ?>" target="_blank">cancel</a> )
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Amount:</td>
                                <td>
                                    <?php if (!empty($paymentDetails['paymentsCount'])) { ?>
                                        <?php echo (stripslashes($paymentDetails['paymentsCount']) . " payments by ") ?>
                                        <b><?php echo (PT_Core::_getCurrencyText($paymentDetails['amount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?></b>,
                                        total <b><?php echo (PT_Core::_getCurrencyText($paymentDetails['amount'] * $paymentDetails['paymentsCount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?></b>
                                    <?php } else { ?>
                                        <?php echo (PT_Core::_getCurrencyText($paymentDetails['amount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?>
                                    <?php } ?>

                                </td>
                            </tr>
                            <?php if (!empty($paymentDetails['upfront_fee']) && $paymentDetails['upfront_fee'] > 0) { ?>
                                <tr>
                                    <td>Upfront Fee:</td>
                                    <td>
                                        <?php
                                        echo PT_Core::_getCurrencyText(
                                            $paymentDetails['upfront_fee'],
                                            $paymentDetails['currency_position'],
                                            $paymentDetails['currency_symbol']
                                        );
                                        if (isset($paymentDetails['upfront_fee_paid']) && $paymentDetails['upfront_fee_paid']) {
                                            echo ' <span class="label label-success">Paid on ' . date('M j, Y', strtotime($paymentDetails['upfront_fee_paid_date'])) . '</span>';
                                        } else {
                                            echo ' <span class="label label-warning">Pending</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if ($is_overriden) { ?>
                                <tr>
                                    <td>Original Total:</td>
                                    <td><?php echo (PT_Core::_getCurrencyText($initial_amount * $paymentDetails['paymentsCount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Processor:</td>
                                <td><img src="../assets/images/icons/<?php echo ($paymentDetails['processor']) ?>.png" /></td>
                            </tr>
                            <tr>
                                <td>Transaction ID:</td>
                                <td><?php echo ($paymentDetails['idTransaction']) ?></td>
                            </tr>
                            <?php if (!empty($paymentDetails['payment_method'])) { ?>
                            <tr>
                                <td>Payment Method ID:</td>
                                <td><?php echo htmlspecialchars($paymentDetails['payment_method']) ?></td>
                            </tr>
                            <?php } ?>
                            
                            <?php if (!empty($invoiceNumber)) { ?>
                                <tr>
                                    <td>Invoice:</td>
                                    <td><a href="<?php echo $settings->admin_url ?>/invoices/edit_recurring.php?idInvoice=<?php echo ($invoiceID) ?>"><?php echo (stripslashes($invoiceNumber)) ?></a></td>
                                </tr>
                            <?php } ?>
                            <?php if (!empty($paymentDetails['notes'])) { ?>
                                <tr>
                                    <td>Notes:</td>
                                    <td><?php echo (nl2br(stripslashes($paymentDetails['notes']))) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
        <?php if (count($payments)) { ?>
            <div class="row">
                <div class="col-xs-12">
                    <h2>Payments</h2>
                    <table class="dataTable" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date Created</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            foreach ($payments as $payment) { ?>
                                <tr>
                                    <td><?php echo $i ?></td>
                                    <td><?php echo $a->getDateFormat($payment['dateCreated']) . " " . PT_Core::_getTimeFormat($payment['dateCreated']) ?></td>
                                    <td>
                                        <?php echo $payment['paypalStatus'] == 'refunded' ? "<del>" : "" ?>
                                        <?php echo PT_Core::_getCurrencyText($payment['amount'], $payment['currency_position'], $payment['currency_symbol']) ?>
                                        <?php echo $payment['paypalStatus'] == 'refunded' ? "</del>" : "" ?>
                                    </td>
                                    <td><a href="<?php echo $settings->admin_url ?>/payments/view.php?idPayment=<?php echo $payment['idPayment'] ?>"
                                            style="text-decoration: underline"><?php echo $payment['idTransaction'] ?></a></td>
                                    <td>
                                        <?php switch ($payment['paypalStatus']) {
                                            case "paid":
                                                echo "<span class='active'><i></i>Paid</span>";
                                                break;
                                            case "refunded":
                                                echo "<span class='warning'><i></i>Refunded</span>";
                                                break;
                                            case "pending":
                                                echo "<span class='canceled'><i></i>Pending</span>";
                                                break;
                                        } ?>

                                    </td>
                                </tr>
                            <?php $i++;
                            } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Date Created</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="row">
            <div class="col-md-12">
                You have no permission to view this section
            </div>
        </div>
    <?php } ?>

</div>
<script type="text/javascript">
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
<?php echo ($a->getFooter()) ?>