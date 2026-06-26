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
function logError($message) {
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
$paymentId = $a->esc($pt_id);
$$pt_id = $paymentId;
error_log("Payment ID: $paymentId");

// Verify the ID is not empty
if (empty($paymentId)) {
    $errorMsg = 'No payment ID provided';
    error_log($errorMsg);
    die($errorMsg);
}

error_log('Creating payment model with ID: ' . $paymentId);
try {
    $payment = new paymentModel();
    if (!method_exists($payment, 'setID')) {
        throw new Exception('paymentModel does not have setID method');
    }
    $payment->setID($paymentId);
    error_log('Payment model created successfully');
} catch (Exception $e) {
    $errorMsg = 'Failed to create payment model: ' . $e->getMessage();
    error_log($errorMsg);
    die($errorMsg);
}
$itemData = $billingAddress = '';

try {
    error_log('Calling getPayment()');
    $paymentDetails = $payment->getPayment();
    error_log('getPayment() returned: ' . print_r($paymentDetails, true));
    
    if ($paymentDetails === false) {
        $errorMsg = "Sorry. Payment not found for ID: " . ($$pt_id ?? 'null');
        error_log($errorMsg);
        $a->addError($errorMsg);
    }
} catch (Exception $e) {
    $errorMsg = 'Error in getPayment(): ' . $e->getMessage();
    error_log($errorMsg);
    error_log('Stack trace: ' . $e->getTraceAsString());
    $a->addError('An error occurred while loading payment details.');
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

$billingAddress = $payment->getFormattedAddress();
$shippingAddress = $payment->getFormattedShippingAddress();

// For one-time payments, these don't apply
$cancelLink = false;
$start_date = '';
$payments = array();
$upfrontFeePayment = false;

// Extract source and clickid directly from payment details
$clickid = !empty($paymentDetails['clickid']) ? $paymentDetails['clickid'] : '';
$source = !empty($paymentDetails['source']) ? $paymentDetails['source'] : '';

$a->addStyle($a->getSiteUrl() . "/assets/js/data_table/css/dataTable.bootstrap.css");
$a->addStyle("//cdn.datatables.net/responsive/1.0.7/css/responsive.dataTables.min.css");

$title = ($paymentDetails && strpos($paymentDetails['idItem'], 'pt_donation') !== false) ? "Donation Details" : "Payment Details";

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
                            <?php if ($upfrontFeePayment) { ?>
                                <tr>
                                    <td colspan="2" style="background: #f5f5f5; font-weight: bold; padding: 10px;">
                                        Upfront Fee Payment Details
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Amount:</td>
                                    <td>
                                        <?php 
                                        echo PT_Core::_getCurrencyText(
                                            $upfrontFeePayment['amount'], 
                                            $paymentDetails['currency_position'], 
                                            $paymentDetails['currency_symbol']
                                        );
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Payment Date:</td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($upfrontFeePayment['dateCreated'])); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Transaction ID:</td>
                                    <td>
                                        <a href="<?php echo $settings->admin_url ?>/payments/view.php?idPayment=<?php echo $upfrontFeePayment['idPayment'] ?>" 
                                           style="text-decoration: underline;">
                                            <?php echo htmlspecialchars($upfrontFeePayment['idTransaction']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Status:</td>
                                    <td>
                                        <?php 
                                        switch ($upfrontFeePayment['paypalStatus']) {
                                            case "paid":
                                                echo "<span class='active'><i></i>Paid</span>";
                                                break;
                                            case "refunded":
                                                echo "<span class='warning'><i></i>Refunded</span>";
                                                break;
                                            case "pending":
                                                echo "<span class='canceled'><i></i>Pending</span>";
                                                break;
                                            case "partial_refund":
                                                echo "<span class='warning'><i></i>Partially Refunded</span>";
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php if (!empty($upfrontFeePayment['source'])) { ?>
                                <tr>
                                    <td style="padding-left: 20px;">Source:</td>
                                    <td><?php echo htmlspecialchars($upfrontFeePayment['source']); ?></td>
                                </tr>
                                <?php } ?>
                                <?php if (!empty($upfrontFeePayment['clickid'])) { ?>
                                <tr>
                                    <td style="padding-left: 20px;">Click ID:</td>
                                    <td><?php echo htmlspecialchars($upfrontFeePayment['clickid']); ?></td>
                                </tr>
                                <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Date Processed:</td>
                                <td><?php echo ($a->getDateFormat($paymentDetails['dateCreated'])) ?></td>
                            </tr>
                            <tr>
                                <td>Transaction Type:</td>
                                <td>One-time Payment</td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td>
                                    <?php 
                                    switch ($paymentDetails['paypalStatus']) {
                                        case "paid":
                                            echo "<span class='active'><i></i>Paid</span>";
                                            break;
                                        case "refunded":
                                            echo "<span class='warning'><i></i>Refunded</span>";
                                            break;
                                        case "pending":
                                            echo "<span class='canceled'><i></i>Pending</span>";
                                            break;
                                        case "partial_refund":
                                            echo "<span class='warning'><i></i>Partially Refunded</span>";
                                            break;
                                        default:
                                            echo "<span>" . htmlspecialchars($paymentDetails['paypalStatus']) . "</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Amount:</td>
                                <td>
                                    <?php echo (PT_Core::_getCurrencyText($paymentDetails['amount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?>
                                </td>
                            </tr>
                            <?php if (!empty($paymentDetails['or_amount']) && $paymentDetails['or_amount'] != $paymentDetails['amount']) { ?>
                            <tr>
                                <td>Original Amount:</td>
                                <td><?php echo (PT_Core::_getCurrencyText($paymentDetails['or_amount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($paymentDetails['tax_amount']) && $paymentDetails['tax_amount'] > 0) { ?>
                            <tr>
                                <td>Tax Amount:</td>
                                <td><?php echo (PT_Core::_getCurrencyText($paymentDetails['tax_amount'], $paymentDetails['currency_position'], $paymentDetails['currency_symbol'])) ?> (<?php echo $paymentDetails['tax_rate'] ?>%)</td>
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
