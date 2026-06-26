<?php
// Include the item model class
require_once __DIR__ . '/../../../includes/classes/models/itemModel.class.php';


?>

<div id="payment_info">



    <div class="payment-section cardstyle">

        <?php
        // Get the selected service details using the item model
        // First check URL parameters, then POST data
        $selected_service = $_GET['service'] ?? $c->get['service'] ?? $c->post['pt_service'] ?? '';
        $service = [];

        // Set default currency symbol
        $currency_symbol = '$'; // Default to dollar sign

        // Initialize variables with default values
        $regular_amount = 0;
        $trial_amount = 0;
        $trial_days = 0;
        $billing_cycle = 'month';
        $billing_cycle_display = 'Month';
        $trial_end_date = '';
        $is_trial = false;

        if (!empty($selected_service)) {
            $itemModel = new itemModel();
            $itemModel->setID($selected_service);
            $itemLoaded = $itemModel->getItem();

            if ($itemLoaded) {
                $service = $itemModel->itemData;

                // Always use the amount from the database
                $regular_amount = isset($service['itemAmount']) ? (float)$service['itemAmount'] : 0;

                // Check if this is a trial item
                $is_trial = isset($service['itemTrial']) && $service['itemTrial'] === 'y' && !empty($service['itemTrialDays']);

                if ($is_trial) {
                    // For trial items, get trial amount and days
                    $trial_amount = isset($service['itemTrialUpfront']) ? (float)$service['itemTrialUpfront'] : 0;
                    $trial_days = (int)$service['itemTrialDays'];

                    // Calculate trial end date
                    $trial_end_date = date('F j, Y', strtotime("+{$trial_days} days"));
                }

                // Set billing cycle
                $billing_cycle = $service['itemFrequency'] ?? 'month';

                // Format the billing cycle for display
                $billing_cycle_display = rtrim($billing_cycle, 'ly'); // Remove 'ly' from 'monthly'/'yearly'
                $billing_cycle_display = $billing_cycle_display === 'dai' ? 'day' : $billing_cycle_display; // Fix for 'daily'
                $billing_cycle_display = ucfirst($billing_cycle_display); // Capitalize first letter
            }
        }
        ?>




        <h3 class="payment-title" data-i18n="secure_payment">
            🔒 Secure Payment
        </h3>

        <div class="form-group">
            <label class="form-label" data-i18n="card_number">Card Number</label>
            <div id="card-number-element" class="form-input" data-i18n-placeholder=""></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" data-i18n="expiry_date">Expiry Date</label>
                <div id="card-expiry-element" class="form-input" data-i18n-placeholder=""></div>
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="cvv">CVV</label>
                <div id="card-cvc-element" class="form-input" data-i18n-placeholder=""></div>
            </div>
        </div>
        <div id="card-errors" role="alert"></div>

    </div>
</div>

<!-- Load Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<!-- Load the Cardstyle template's JavaScript -->
<script>
    // Set the Stripe public key
    window.stripePublicKey = '<?php echo $payment->public_key; ?>';
</script>