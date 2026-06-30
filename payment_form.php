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

$__ptDomainItemId = trim((string)($_REQUEST['pt_service'] ?? $_GET['service'] ?? $_GET['item_id'] ?? ''));
$__ptDomainInvoiceId = isset($_REQUEST['idInvoice']) ? (int)$_REQUEST['idInvoice'] : 0;
$__ptDomainToken = trim((string)($_REQUEST['drt'] ?? ''));
pt_enforce_domain_access_or_exit($__ptDomainItemId, $__ptDomainInvoiceId, $__ptDomainToken, true);

function getConvenientCurrencyData($countryCode, $ctc, $serviceId)
{
    // Construct the URL, assuming getConvenientCurr.php is in the same directory
    $url = 'getConvenientCurr.php';
    $queryParams = http_build_query([
        'country' => $countryCode,
        'ctc' => $ctc,
        'service' => $serviceId
    ]);

    $full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER[REQUEST_URI]) . '/' . $url . '?' . $queryParams;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Should be true in production
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Failed to fetch currency data from getConvenientCurr.php');
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['success']) || !$data['success']) {
        $errorMessage = isset($data['message']) ? $data['message'] : 'Invalid response from currency endpoint.';
        throw new Exception($errorMessage);
    }

    return $data;
}

$payment = new PT_Stripe_Payment();
$payment->setVariableSettings();
$payment->applyGatewayFromRequest();

$header = new PT_Template("header.php");
$header->title = $settings->page_title;
$header->logo = '' !== $settings->terminal_logo ? $settings->siteUrl() . $settings->terminal_logo : "";

$footer = new PT_Template("footer.php");
$cvv_info = new PT_Template("cvv_info.php");
$popup = new PT_Template("popup.php");

$pt_action = $c->esc("pt_action");

$ctc = $c->esc("ctc");

$idInvoice = $c->esc("idInvoice", 0);

$pt_amount = $c->esc("pt_amount", 0);
$pt_service = $c->esc("pt_service");
$pt_payment_type = $c->esc("pt_payment_type", 'once');
$pt_name = $c->esc("pt_name");
$pt_email = $c->esc("pt_email");
$pt_description = $c->esc("pt_description");

$pt_address1 = $c->esc("pt_address1");
$pt_address2 = $c->esc("pt_address2");
$pt_city = $c->esc("pt_city");
$pt_country = $c->esc("pt_country");

$pt_state = $c->esc("pt_state");
$pt_postal = $c->esc("pt_postal");
$pt_country = $c->esc("pt_country");

$pt_type = $c->esc("pt_type", "card");
$pt_card_name = $c->esc("pt_card_name");
$pt_card_number = $c->esc("pt_card_number");
$pt_mm = $c->esc("pt_mm");
$pt_yy = $c->esc("pt_yy");
$pt_cvv = $c->esc("pt_cvv");

$stripeToken = $c->esc("stripeToken");

$pt_terms = $c->esc("pt_terms", 0);

// Capture clickid and source from URL or POST data
$clickid = '';
$source = '';

// Check POST first (from hidden form fields), then GET (from URL)
if (isset($_POST['clickid']) && !empty($_POST['clickid'])) {
    $clickid = $c->esc($_POST['clickid']);
} elseif (isset($_GET['clickid']) && !empty($_GET['clickid'])) {
    $clickid = $c->esc($_GET['clickid']);
}

if (isset($_POST['source']) && !empty($_POST['source'])) {
    $source = $c->esc($_POST['source']);
} elseif (isset($_GET['source']) && !empty($_GET['source'])) {
    $source = $c->esc($_GET['source']);
}

// billing information template
$billing_info = new PT_Template("form/billing_info.php");
$billing_info->countriesList = $payment->getHTMLCountriesList();
$billing_info->statesList = $payment->getHTMLStatesList();
$billing_info->post = $c->post;

// payment information template
$payment_info = new PT_Template("form/payment_info.php");
$payment_info->actualYear = $payment->getActualYears();
$payment_info->cvv_info = $cvv_info->render();
$payment_info->post = $c->post;
$payment_info->userLogon = $user->logon;

// form bottom section template
$bottom_info = new PT_Template("form/payment_form_bottom.php");
$bottom_info->amount = '0';

// invoice
$isInvoice = false;
if (!empty($idInvoice)) {
    $invoice = new invoiceModel();
    $invoice->setID($idInvoice);
    if ($invoice->setInvoiceData())
        $isInvoice = true;
    if ($invoice->invoiceData['invoiceStatus'] == 'paid') {
        $c->addWarning("Invoice was already paid!");
        $show_form = false;
        if (!empty($settings->thank_you_redirect)) {
            $redirectUrl = $settings->thank_you_redirect;
            // Add click ID if present
            if (!empty($clickid)) {
                $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == null) ? '?' : '&';
                $redirectUrl .= $separator . 'clickid=' . urlencode($clickid);
            }
            if (!empty($source)) {
                $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == null) ? '?' : '&';
                $redirectUrl .= $separator . 'source=' . urlencode($source);
            }
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
}

$show_form = true;
if ($pt_action == 'do_payment') {
    $selected_gateway = class_exists('PT_Payment_Gateway') ? PT_Payment_Gateway::resolve($pt_service, $pt_type, $c->esc('gateway_code')) : false;
    if ($selected_gateway && !empty($selected_gateway['secret_key'])) {
        \Stripe\Stripe::setApiKey($selected_gateway['secret_key']);
    } else {
        \Stripe\Stripe::setApiKey($settings->terminal_payment_mode == 'live' ? $settings->live_secret_key : $settings->test_secret_key);
    }

    // Get the payment method ID from the form
    $payment_method_id = $c->esc('payment_method');
    $subscription_id = $c->esc('subscription_id', '');


    // Get customer details
    $pt_first_name = $c->esc('pt_name');
    $pt_country = $c->esc('pt_country'); // Country from form

    try {
        // NEW: Get data from getConvenientCurr.php
        $currencyData = getConvenientCurrencyData($pt_country, $ctc, $pt_service);
        $_SESSION['api_currency_data'] = $currencyData;

        $pt_amount = $currencyData['subscription_amount']['amount_numeric'];
        $upfront_fee = $currencyData['upfront_amount']['amount_numeric'];
        $currency = $currencyData['subscription_amount']['currency_code']; // Use currency_code
        $currencySymbol = $currencyData['subscription_amount']['currency_symbol']; // Get currency symbol
        $itemName = 'Subscription'; // Or some other name

        // NEW itemData structure
        $itemData = [
            'itemName' => $itemName,
            // other fields can be populated if needed by downstream code
        ];

        // Prepare metadata for customer
        $customer_metadata = [];
        if (!empty($clickid)) {
            $customer_metadata['clickid'] = $clickid;
        }
        if (!empty($source)) {
            $customer_metadata['source'] = $source;
        }

        $customer = \Stripe\Customer::create([
            'payment_method' => $payment_method_id,
            'email' => $pt_email,
            'name' => $pt_name,
            'metadata' => $customer_metadata,
            'invoice_settings' => [
                'default_payment_method' => $payment_method_id
            ]
        ]);

        $upfront_payment_succeeded = true;

        // If there's an upfront fee, process it first
        if ($upfront_fee > 0) {
            try {
                // Create a payment intent for the upfront fee
                $payment_intent = \Stripe\PaymentIntent::create([
                    'amount' => $upfront_fee * 100, // Convert to cents
                    'currency' => strtolower($currency),
                    'customer' => $customer->id,
                    'payment_method' => $payment_method_id,
                    'off_session' => true,
                    'confirm' => true,
                    'description' => 'Upfront fee for ' . $itemData['itemName'],
                    'metadata' => [
                        'item_id' => $pt_service,
                        'item_name' => $itemData['itemName'],
                        'customer_email' => $pt_email,
                        'upfront_fee' => 'yes',
                        'clickid' => $clickid,
                        'source' => $source
                    ]
                ]);

                // Record the upfront fee payment in your database
                if (class_exists('paymentModel')) {
                    $payment_record = new paymentModel();
                    $payment_record->insert([
                        'idPayment' => $payment_intent->id,
                        'idItem' => $pt_service,
                        'idSubscription' => $subscription_id,
                        'amount' => $upfront_fee,
                        'tax' => 0,
                        'fee' => 0,
                        'currency' => $settings->currency,
                        'paymentMethod' => 'stripe',
                        'paymentStatus' => $payment_intent->status,
                        'customerEmail' => $pt_email,
                        'customerName' => $pt_name,
                        'description' => 'Upfront fee for ' . $itemData['itemName'],
                        'created' => date('Y-m-d H:i:s'),
                        'metadata' => json_encode([
                            'stripe_payment_intent' => $payment_intent->id,
                            'stripe_customer' => $customer->id,
                            'upfront_fee' => 'yes',
                            'clickid' => $clickid,
                            'source' => $source
                        ])
                    ]);
                }
            } catch (\Stripe\Exception\CardException $e) {
                error_log("Upfront fee payment failed: " . $e->getMessage());
                $c->setMessage('error', 'Payment failed: ' . $e->getError()->message);
                $upfront_payment_succeeded = false;
            } catch (Exception $e) {
                error_log("Upfront fee payment failed: " . $e->getMessage());
                $c->setMessage('error', 'An error occurred while processing your payment. Please try again.');
                $upfront_payment_succeeded = false;
            }
        }

        // Process the subscription only if upfront payment succeeded (or there was no upfront fee)
        if ($upfront_payment_succeeded) {
            // Process the subscription
            global $CURRENCY_SYMBOLS; // Make sure we have access to currency symbols

            $stripe = new PT_Stripe_Payment();
            if (!empty($selected_gateway)) {
                $stripe->setGatewayProfile($selected_gateway);
            }
            $stripe->convenient_currency_data = $currencyData;
            $stripe->setAmount($pt_amount);

            // Get the currency code from the form data or use EUR as default
            // OVERRIDE with currency from endpoint
            $currencyCode = strtolower(trim($currency)); // Use the currency from the endpoint

            // Ensure the currency code is valid for Stripe
            $validCurrencies = ['usd', 'aed', 'afn', 'all', 'amd', 'ang', 'aoa', 'ars', 'aud', 'awg', 'azn', 'bam', 'bbd', 'bdt', 'bgn', 'bhd', 'bif', 'bmd', 'bnd', 'bob', 'brl', 'bsd', 'bwp', 'byn', 'bzd', 'cad', 'cdf', 'chf', 'clp', 'cny', 'cop', 'crc', 'cve', 'czk', 'djf', 'dkk', 'dop', 'dzd', 'egp', 'etb', 'eur', 'fjd', 'fkp', 'gbp', 'gel', 'gip', 'gmd', 'gnf', 'gtq', 'gyd', 'hkd', 'hnl', 'hrk', 'htg', 'huf', 'idr', 'ils', 'inr', 'isk', 'jmd', 'jod', 'jpy', 'kes', 'kgs', 'khr', 'kmf', 'krw', 'kwd', 'kyd', 'kzt', 'lak', 'lbp', 'lkr', 'lrd', 'lsl', 'mad', 'mdl', 'mga', 'mkd', 'mmk', 'mnt', 'mop', 'mur', 'mvr', 'mwk', 'mxn', 'myr', 'mzn', 'nad', 'ngn', 'nio', 'nok', 'npr', 'nzd', 'omr', 'pab', 'pen', 'pgk', 'php', 'pkr', 'pln', 'pyg', 'qar', 'ron', 'rsd', 'rub', 'rwf', 'sar', 'sbd', 'scr', 'sek', 'sgd', 'shp', 'sle', 'sos', 'srd', 'std', 'szl', 'thb', 'tjs', 'tnd', 'top', 'try', 'ttd', 'twd', 'tzs', 'uah', 'ugx', 'usd', 'uyu', 'uzs', 'vnd', 'vuv', 'wst', 'xaf', 'xcd', 'xcg', 'xof', 'xpf', 'yer', 'zar', 'zmw', 'usdc', 'btn', 'ghs', 'eek', 'lvl', 'svc', 'vef', 'ltl', 'sll', 'mro'];

            // Convert to lowercase and check if it's a valid currency code
            $currencyCode = strtolower(trim($currencyCode));
            if (!in_array($currencyCode, $validCurrencies)) {
                // If not a valid currency code, use EUR as fallback
                $currencyCode = 'eur';
            }

            // Set the currency for Stripe
            $stripe->setCurrency($currencyCode);
            // Also set the currency symbol for consistency if needed later
            $stripe->currency_symbol = $currencySymbol;

            // Set clickid and source from URL parameters
            if (!empty($clickid)) {
                $stripe->clickid = $clickid;
            }
            if (!empty($source)) {
                $stripe->source = $source;
            }
            $stripe->setItem($pt_service);
            $stripe->setCustomer($pt_email, $pt_name, '', '', '', '', '', '', '', '');
            $stripe->setDescription($pt_description);
            $stripe->setPaymentMethod($payment_method_id);
            $stripe->setCustomerId($customer->id);
            // Store clickid and source in the payment object for later use
            $stripe->clickid = $clickid;
            $stripe->source = $source;

            // Set the subscription ID if this is an existing subscription
            if (!empty($subscription_id)) {
                $stripe->setSubscriptionId($subscription_id);
            }

            // Process the payment
            $result = $stripe->doPayment();

            if ($result) {
                // CRITICAL: Update the subscription with clickid and source after creation
                $db = PT_Core::getDB();

                // Get the subscription ID from the stripe object
                $created_subscription_id = null;
                if (property_exists($stripe, 'subscription_id') && !empty($stripe->subscription_id)) {
                    $created_subscription_id = $stripe->subscription_id;
                } elseif (property_exists($stripe, 'idTransaction') && !empty($stripe->idTransaction)) {
                    $created_subscription_id = $stripe->idTransaction;
                }

                if (!empty($created_subscription_id)) {
                    // Prepare update data
                    $update_data = [];
                    if (!empty($clickid)) {
                        $update_data['clickid'] = $clickid;
                    }
                    if (!empty($source)) {
                        $update_data['source'] = $source;
                    }

                    if (!empty($update_data)) {
                        try {
                            $db->where('idTransaction', $created_subscription_id);
                            $update_result = $db->update('subscriptions', $update_data);

                            // Log for debugging
                            error_log("Updated subscription $created_subscription_id with clickid: $clickid and source: $source. Result: " . ($update_result ? 'success' : 'failed'));

                            // Alternative: Try updating by idSubscription if idTransaction didn't work
                            if (!$update_result) {
                                // Get the actual subscription ID from database
                                $db->where('idTransaction', $created_subscription_id);
                                $subscription_record = $db->getOne('subscriptions', 'idSubscription');

                                if ($subscription_record && isset($subscription_record['idSubscription'])) {
                                    $db->where('idSubscription', $subscription_record['idSubscription']);
                                    $update_result = $db->update('subscriptions', $update_data);
                                    error_log("Updated subscription by idSubscription: " . ($update_result ? 'success' : 'failed'));
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error updating subscription with tracking params: " . $e->getMessage());
                        }
                    }
                } else {
                    error_log("Warning: Could not find subscription ID to update with tracking parameters");
                }

                $show_form = false;
                if (!empty($settings->thank_you_redirect)) {
                    $redirectUrl = $settings->thank_you_redirect;
                    if (strpos($redirectUrl, '?') !== false) {
                        $redirectUrl .= '&payment=success';
                    } else {
                        $redirectUrl .= '?payment=success';
                    }
                    // Use payment method ID instead of clickid for tracking
                    if (!empty($payment_method_id) && strpos($payment_method_id, 'pm_') === 0) {
                        $redirectUrl .= '&clickid=' . urlencode($payment_method_id);
                    } elseif (!empty($clickid)) {
                        // Fallback to clickid if payment method ID is not available
                        $redirectUrl .= '&clickid=' . urlencode($clickid);
                    }
                    if (!empty($source)) {
                        $redirectUrl .= '&source=' . urlencode($source);
                    }
                    header('Location: ' . $redirectUrl);
                    exit();
                }
            } else {
                $c->setMessage('error', 'Payment failed: ' . $stripe->error);
                $show_form = true;
            }
        } else {
            // Upfront payment failed, show form again
            $show_form = true;
        }
    } catch (Exception $e) {
        error_log("Payment processing error: " . $e->getMessage());
        $c->setMessage('error', 'An error occurred while processing your payment. Please try again.');
        $show_form = true;
    }
}

$header->render(true);
?>
<div class="container main" role="main">
    <?php echo ($c->getMessages()) ?>

    <?php if ($show_form) { ?>
        <form class=" validate payment_form" role="form" id="payment_form" method="post">
            <input type="hidden" name="pt_action" value="do_payment">
            <input type="hidden" name="stripeToken" value="" id="stripeToken">
            <?php
            $clickid_val = isset($_GET['clickid']) ? htmlspecialchars($_GET['clickid'], ENT_QUOTES, 'UTF-8') : '';
            $source_val = isset($_GET['source']) ? htmlspecialchars($_GET['source'], ENT_QUOTES, 'UTF-8') : '';
            ?>
            <input type="hidden" name="clickid" value="<?php echo $clickid_val; ?>">
            <input type="hidden" name="source" value="<?php echo $source_val; ?>">
            <?php
            $clickid_val = isset($_GET['clickid']) ? htmlspecialchars($_GET['clickid'], ENT_QUOTES, 'UTF-8') : '';
            $source_val = isset($_GET['source']) ? htmlspecialchars($_GET['source'], ENT_QUOTES, 'UTF-8') : '';
            ?>
            <input type="hidden" name="clickid" value="<?php echo $clickid_val; ?>">
            <input type="hidden" name="source" value="<?php echo $source_val; ?>">

            <h2><?php _tr("Order Information") ?></h2>
            <?php if ($settings->payment_type == 'item') { ?>
                <div class="form-group col-md-6 col-sm-6 col-xs-6">
                    <label for="pt_service"><?php _tr("Service") ?></label>
                    <select class="form-control" name="pt_service" id="pt_service"
                        data-rule-required="true" data-msg-required="<?php _tr("Required info") ?>">
                        <option value=""><?php _tr("Please select") ?></option>
                        <?php echo ($payment->getHTMLServicesList()) ?>
                    </select>
                </div>
            <?php } else { ?>
                <div class="form-group col-md-3 col-sm-3 col-xs-6">
                    <label for="pt_amount"><?php _tr("Amount") ?></label>
                    <div class="input-group">
                        <div class="input-group-addon">$</div>
                        <input type="text" class="form-control" id="pt_amount" name="pt_amount" placeholder=""
                            value="<?php echo ($pt_amount) ?>"
                            data-rule-required="true" data-msg-required="<?php _tr("Required info") ?>"
                            data-rule-number="true" data-msg-number="<?php _tr("Only numbers") ?>">
                    </div>
                </div>
                <div class="form-group col-md-3 col-sm-3 hidden-xs">&nbsp;</div>
                <?php if ($settings->show_description == 'y') { ?>
                    <div class="form-group col-md-6 col-sm-6 col-xs-6">
                        <label for="pt_description"><?php _tr("Description") ?></label>
                        <textarea name="pt_description" id="pt_description"
                            class="form-control"><?php echo ($pt_description) ?></textarea>
                    </div>
                    <div class="clearfix"></div>
                <?php } ?>
            <?php } ?>
            <div class="clearfix"></div>
            <?php $billing_info->render(true); ?>
            <?php $payment_info->render(true); ?>
            <?php $bottom_info->render(true); ?>
        </form>
    <?php } ?>
</div>
<?php $footer->render(true); ?>
</div>
<?php $popup->render(true) ?>

<script src="assets/bootstrap/js/bootstrap.min.js" type="application/javascript"></script>
<script src="assets/js/jquery.validate-1-19-3.min.js" type="application/javascript"></script>
<script src="assets/js/ccvalidations.js" type="application/javascript"></script>
<script src="assets/js/payment_form.js" type="application/javascript"></script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    var stripe = Stripe('<?php echo $payment->public_key; ?>');
</script>

</body>
<?php echo ($c->getDebug()) ?>

</html>
