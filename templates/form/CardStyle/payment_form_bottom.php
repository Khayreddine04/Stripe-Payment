<div class="clearfix"></div>
<div id="terms_cont">
    <?php
    if ($show_terms == "y") {
    ?>
        <div class="bg-danger box-notice">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="pt_terms" id="pt_terms" value="1" title="<?php _tr("Please accept terms and conditions to proceed.") ?>"> I agree with <a href="javascript:;" data-toggle="modal" data-target="#terms_and_conditions">terms and conditions</a>
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
    <?php
    }
    ?>

    <?php if ($use_recaptcha == 'y') { ?>
        <div class="col-md-12">
            <div class="g-recaptcha" data-sitekey="<?php echo ($recaptcha_site_key) ?>" data-callback="checkCaptcha"></div>
            <div class="clearfix"></div>
        </div>
    <?php } ?>

</div>

<?php
// Debug: Log the service ID and amount
error_log("Payment Form - Service ID: " . ($pt_service ?? $_GET['service'] ?? 'Not set'));
error_log("Payment Form - Amount: " . ($pt_amount ?? 'Not set'));

// Initialize variables
$upfrontFee = 0.00;  // Default to 0.00
$hasUpfrontFee = false;
$itemData = [];

// Get the service ID from either pt_service or service parameter
$serviceId = $pt_service ?? $_GET['service'] ?? '';

// Get currency symbol from settings or use default
if (isset($pt_currency_symbol) && !empty($pt_currency_symbol)) {
    $currencySymbol = $pt_currency_symbol;
} elseif (isset($settings->display_currency) && !empty($settings->display_currency)) {
    $currencySymbol = $settings->display_currency;
} else {
    $currencySymbol = '$';  // Fallback to dollar sign
}

error_log("Currency Symbol: " . $currencySymbol);

// Check if we have a service ID
if (!empty($serviceId)) {
    // First try to get the item data directly from the database
    global $mysqli;
    $query = "SELECT * FROM vcp_pt_items WHERE idItem = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $itemData = $result->fetch_assoc();

    // Debug: Log the raw database result
    error_log("Database Query Result: " . print_r($itemData, true));

    if (!empty($itemData)) {
        $upfrontFee = isset($itemData['itemTrialUpfront']) ? (float)$itemData['itemTrialUpfront'] : 0.00;
        $hasUpfrontFee = ($upfrontFee > 0 && isset($itemData['itemTrial']) && $itemData['itemTrial'] == 'y');

        // Debug: Log the upfront fee information
        error_log("Upfront Fee: " . $upfrontFee);
        error_log("Has Upfront Fee: " . ($hasUpfrontFee ? 'Yes' : 'No'));
    } else {
        // Fallback to the model if direct query fails
        $item = new itemModel();
        $item->setID($serviceId);
        $itemData = $item->getItem();

        if (!empty($itemData) && is_array($itemData)) {
            $upfrontFee = isset($itemData['itemTrialUpfront']) ? (float)$itemData['itemTrialUpfront'] : 0.00;
            $hasUpfrontFee = ($upfrontFee > 0 && isset($itemData['itemTrial']) && $itemData['itemTrial'] == 'y');
        }
    }
}
?>

<?php // Upfront fee message has been removed as per request 
?>

<?php
// Add hidden fields for currency data
$ctc = isset($_GET['ctc']) ? htmlspecialchars($_GET['ctc']) : '2';
$country = isset($_GET['country']) ? htmlspecialchars($_GET['country']) : 'US';
$service_id = isset($_GET['service']) ? htmlspecialchars($_GET['service']) : '';
?>
<input type="hidden" name="pt_ctc" id="pt_ctc" value="<?php echo $ctc; ?>">
<input type="hidden" name="pt_country" id="pt_country" value="<?php echo $country; ?>">
<input type="hidden" name="pt_service" id="pt_service" value="<?php echo $service_id; ?>">

<script>
    // Function to update amounts from the API
    function updateAmountsFromAPI() {
        var country = $('#pt_country').val();
        var ctc = $('#pt_ctc').val();
        var serviceId = $('#pt_service').val();

        if (!country || !ctc) return;

        // Show loading state
        $('.amount-display').html('<i class="fa fa-spinner fa-spin"></i> Loading...');

        // Fetch currency data
        $.get('getConvenientCurr.php', {
            country: country,
            ctc: ctc,
            service: serviceId
        }, function(data) {
            if (data.success) {
                // Debug log the received data
                console.log("API Response Data:", data);

                // Update the displayed amounts
                if (data.subscription_amount) {
                    // Use currency_symbol if available, otherwise fall back to currency code
                    var currencySymbol = data.subscription_amount.currency_symbol || data.subscription_amount.currency || '';

                    // Update displayed amounts with the correct symbol
                    $('.subscription-amount').text(data.subscription_amount.amount);
                    var period = (data.subscription_amount.period === 'bi-monthly') ? '14-day' : (data.subscription_amount.period || 'month');
                    $('.subscription-period').text('every ' + period);
                    $('.subscription-currency').text(currencySymbol);

                    // Update hidden amount and currency fields
                    $('#pt_amount').val(data.subscription_amount.amount_numeric);
                    console.log("Updated pt_amount: ", $('#pt_amount').val());
                    $('#pt_currency').val(data.subscription_amount.currency_code || data.subscription_amount.currency);
                    console.log("Updated pt_currency: ", $('#pt_currency').val());
                    $('input[name="pt_currency_symbol"]').val(currencySymbol);
                    console.log("Updated pt_currency_symbol: ", $('input[name="pt_currency_symbol"]').val());
                }

                // Update upfront fee if exists
                if (data.upfront_amount) {
                    var upfrontCurrencySymbol = data.upfront_amount.currency_symbol || data.upfront_amount.currency || '';
                    $('.upfront-fee-amount').text(data.upfront_amount.amount);
                    $('.upfront-fee-currency').text(upfrontCurrencySymbol);
                    $('.upfront-fee-container').show();
                } else {
                    $('.upfront-fee-container').hide();
                }

                // Update total amount
                var total = parseFloat(data.subscription_amount.amount_numeric || 0);
                if (data.upfront_amount) {
                    total += parseFloat(data.upfront_amount.amount_numeric || 0);
                }

                // Ensure we use the correct currency symbol for the total
                var totalCurrencySymbol = data.subscription_amount.currency_symbol || data.subscription_amount.currency || '';
                $('.total-amount').text(total.toFixed(2));
                $('.total-currency').text(totalCurrencySymbol);

                // Debug log the final values
                console.log("Final currency symbol: ", totalCurrencySymbol);
            }
        }, 'json').fail(function() {
            $('.amount-display').html('<span class="text-danger">Failed to load pricing. Please refresh the page.</span>');
        });
    }

    // Update amounts when the page loads
    $(document).ready(function() {
        // Initial update
        updateAmountsFromAPI();

        // Update when country or CTC changes
        $('#pt_country, #pt_ctc').on('change', updateAmountsFromAPI);
    });
</script>

<div class="amount-summary">
    <div class="amount-row" style="display:none;">
        <span class="amount-label">Subscription:</span>
        <span class="amount-value">
            <span class="subscription-amount">0</span>
            <!-- <span class="subscription-currency"></span> -->
            <span class="subscription-period"></span>
        </span>
    </div>

    <div class="upfront-fee-container">
        <div class="amount-row">
            <span class="amount-label" data-i18n="fees">Fees:</span>
            <span class="amount-value">
                <span class="upfront-fee-amount">0</span>
                <!-- <span class="upfront-fee-currency"></span> -->
            </span>
        </div>
    </div>

    <div class="amount-row total-amount-row" style="display: none;">
        <span class="amount-label"><strong>Total:</strong></span>
        <span class="amount-value">
            <strong>
                <span class="total-amount">0</span>
                <span class="total-currency"></span>
            </strong>
        </span>
    </div>
</div>

<style>
    .amount-summary {
        margin: 20px 0;
        padding: 0 15px;
        /*  background: #f8f9fa;*/
        border-radius: 5px;
    }

    .amount-row {
        display: flex;
        justify-content: space-between;
        /* margin-bottom: 8px; */
    }

    .amount-row.total-amount-row {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #dee2e6;
    }

    .amount-label {
        font-weight: 500;
    }

    .amount-value {
        text-align: right;
    }
</style>

<div class="row">
    <div class="col-md-12">

        <?php if ($fee_enable == "y" && $invoice == "0") {
            $fee_type_display = "";
            if ($fee_type == 1) {
                $fee_type_display = number_format($fee_amount, 2) . "%";
            } else {
                $fee_type_display = number_format($fee_amount, 2);
            }
        ?>
            <div class="fee-wrapper">
                <label><?php echo $fee_label ?>:
                    <span id="pt_fee_amount"><?php echo $fee_type_display ?></span>
                    <?php if ($currency_position == 'after') { ?>
                        <span class="pt_currency_text"><?php echo ($display_currency) ?></span>
                    <?php } else { ?>
                        &nbsp;<span class="pt_currency_text"><?php echo ($currency_text) ?></span>
                    <?php } ?>
                </label>
            </div>
            <div class="clearfix"></div>
        <?php } ?>

        <?php if ($tax_enable == "y" && $invoice == "0") { ?>
            <div class="tax-wrapper">
                <label><?php echo $tax_rate ?>% <?php echo $tax_abbreviation ?>:
                    <span id="pt_subtotal_amount">0.00</span>
                    <?php if ($currency_position == 'after') { ?>
                        <span class="pt_currency_text"><?php echo ($display_currency) ?></span>
                    <?php } else { ?>
                        &nbsp;<span class="pt_currency_text"><?php echo ($currency_text) ?></span>
                    <?php } ?>
                </label>
            </div>
            <div class="clearfix"></div>
        <?php } ?>

        <div class="button-wrapper">
            <button type="submit" class="btn btn-lg btn-success"
                <?php echo ($use_recaptcha == 'y') ? "disabled" : "" ?> id="form_submit_button">
                <div id="card_payment_button_cont" data-i18n="complete_payment">Complete Payment</div>
            </button>
            <div id="payment-request-button">
                <!-- A Stripe Element will be inserted here. -->
            </div>

            <div class="security-badge">
                <span class="lock-icon">🔒</span>
                <span data-i18n="ssl_connection">This is a 256-Bit secure SSL connection</span>
            </div>
            <!-- Accepted payment methods / trust logos -->
            <div class="payments-logos" style="margin:14px auto; display:flex; align-items:center; justify-content:center; gap:16px; flex-wrap:wrap; opacity:.95;">
                <img src="assets/images/icons/visa.png" alt="Visa" height="40" />
                <img src="assets/images/icons/mastercard.svg" alt="Mastercard" height="40" />
                <img src="assets/images/icons/mcafee.svg" alt="Mcafee" height="40" width="100" style="height:40px !important; width: 100px !important" />
                <img src="assets/images/icons/norton.jpeg" alt="Norton" height="50" width="100" style="height:50px !important; width: 100px !important" />
            </div>

        </div>
    </div>
</div>