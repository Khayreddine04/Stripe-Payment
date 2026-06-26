<?php
// Enhanced service ID validation with better debugging
$serviceId = $_GET['service'] ?? $pt_service ?? '';

// Debug logging with more details
error_log("=== PAYMENT FORM BOTTOM DEBUG ===");
error_log("Service ID from GET: " . ($_GET['service'] ?? 'Not set'));
error_log("Service ID from pt_service: " . ($pt_service ?? 'Not set'));
error_log("Final Service ID: " . ($serviceId ?: 'Not set'));
error_log("Amount: " . ($pt_amount ?? 'Not set'));

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

// Initialize variables to prevent undefined variable warnings
$show_terms = $show_terms ?? 'n';
$use_recaptcha = $use_recaptcha ?? 'n';
$fee_enable = $fee_enable ?? 'n';
$tax_enable = $tax_enable ?? 'n';
$invoice = $invoice ?? '0';
$fee_type = $fee_type ?? '0';
$fee_amount = $fee_amount ?? '0';
$fee_label = $fee_label ?? 'Fee';
$tax_rate = $tax_rate ?? '0';
$tax_abbreviation = $tax_abbreviation ?? 'Tax';
$currency_position = $currency_position ?? 'before';
$display_currency = $display_currency ?? '$';
$currency_text = $currency_text ?? '$';

// Initialize Stripe-related variables if not set
$recaptcha_site_key = $recaptcha_site_key ?? '';
$pt_service = $pt_service ?? '';
$pt_amount = $pt_amount ?? '0.00';



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
                    const amount = parseFloat(data.subscription_amount.amount_numeric || 0).toFixed(2);
                    const currency = data.subscription_amount.currency_code || data.subscription_amount.currency || 'USD';

                    $('#pt_amount').val(amount);
                    $('#pt_currency').val(currency);
                    $('#pt_currency_symbol').val(currencySymbol);

                    // Log the updates for debugging
                    console.log('Updated payment form fields:', {
                        pt_amount: amount,
                        pt_currency: currency,
                        pt_currency_symbol: currencySymbol
                    });

                    // Update any visible currency displays
                    $('.currency-symbol').text(currencySymbol);
                    $('.currency-code').text(currency);
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
            $('.amount-display').html('<span class="text-danger" data-i18n="pricing_load_failed">Failed to load pricing. Please refresh the page.</span>');
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


<div class="clearfix"></div>
<div id="terms_cont">
    <?php
    if ($show_terms == "y") {
    ?>
        <div class="bg-danger box-notice">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="pt_terms" id="pt_terms" value="1" title="<?php _tr("Please accept terms and conditions to proceed.") ?>"> <span data-i18n="i_agree_with">I agree with</span> <a href="javascript:;" data-toggle="modal" data-target="#terms_and_conditions" data-i18n="terms_and_conditions">terms and conditions</a>
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

    if ($row = $result->fetch_assoc()) {
        $itemData = $row;

        // Debug: Log the raw database result
        error_log("Database Query Result: " . print_r($itemData, true));

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
        /* margin: 20px 0; */
        padding: 0 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .amount-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
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

        <input type="hidden" name="pt_service" value="<?php echo htmlspecialchars($serviceId ?? $pt_service ?? $_GET['service'] ?? '', ENT_QUOTES); ?>">
        <input type="hidden" name="pt_amount" id="pt_amount" value="<?php echo htmlspecialchars($pt_amount ?? $amount ?? '', ENT_QUOTES); ?>">
        <input type="hidden" name="pt_currency" id="pt_currency" value="<?php echo htmlspecialchars($pt_currency ?? $currency ?? 'USD', ENT_QUOTES); ?>">
        <input type="hidden" name="pt_currency_symbol" id="pt_currency_symbol" value="<?php echo htmlspecialchars($pt_currency_symbol ?? $currencySymbol ?? '$', ENT_QUOTES); ?>">
        <input type="hidden" name="pt_currency_position" id="pt_currency_position" value="<?php echo htmlspecialchars($pt_currency_position ?? 'before', ENT_QUOTES); ?>">

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

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('payment_form');
                if (form) {
                    // Handle form submission through our Stripe handler
                    form.addEventListener('submit', function(e) {
                        // This will be handled by handleFormSubmit in payment_form.js
                        // We'll just ensure the name fields are properly set
                        const firstName = document.querySelector('[name="first_name"]')?.value || '';
                        const lastName = document.querySelector('[name="last_name"]')?.value || '';

                        // Remove any existing pt_name field to avoid duplicates
                        const existingPtName = document.querySelector('input[name="pt_name"]');
                        if (existingPtName) {
                            existingPtName.remove();
                        }

                        // Create and append the pt_name field
                        if (firstName || lastName) {
                            const ptNameInput = document.createElement('input');
                            ptNameInput.type = 'hidden';
                            ptNameInput.name = 'pt_name';
                            ptNameInput.value = `${firstName} ${lastName}`.trim();
                            this.appendChild(ptNameInput);
                        }
                    });
                }
            });
        </script>

        <div class="button-wrapper">
            <button type="submit" class="btn btn-lg btn-success" id="submit-button" <?php echo ($use_recaptcha == 'y') ? "disabled" : "" ?>>
                <span id="button-text" data-i18n="complete_payment">Complete Payment</span>
                <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
            <div id="payment-request-button">
                <!-- A Stripe Element will be inserted here. -->
            </div>

            <div class="security-badge">
                <span class="lock-icon">🔒</span>
                <span data-i18n="ssl_connection_notice">This is a 256-Bit Secured SSL Connection</span>
            </div>

        </div>
    </div>
</div>