<?php
// Initialize theme with error handling
try {
    // Get theme from available sources
    $theme = $__theme ?? $selected_theme ?? 'CardStyle';

    // Ensure theme is one of our supported themes
    $supported_themes = ['CardStyle', 'Minimalist', 'Colorful'];
    if (!in_array($theme, $supported_themes)) {
        error_log('Unsupported theme detected: ' . $theme . '. Defaulting to CardStyle');
        $theme = 'CardStyle';
    }

    error_log('Footer - Theme set to: ' . $theme . ' (from: ' . ($__theme ?? 'null') . ' / ' . ($selected_theme ?? 'null') . ')');
} catch (Exception $e) {
    error_log('Error setting footer theme: ' . $e->getMessage());
    $theme = 'CardStyle';
}

// Function to render footer content
function renderFooterContent($is_trial = false, $service = [])
{
    global $settings; // Access the global settings object

    // Check for endpoint data in session or global variable
    $endpoint_data = $_SESSION['api_response_data'] ?? $GLOBALS['endpoint_currency_data'] ?? null;

    if ($endpoint_data) {
        // Extract data from endpoint response
        $regular_amount = $endpoint_data['subscription_amount']['amount_numeric'] ??
            $endpoint_data['amount'] ?? 0;

        $upfront_amount = $endpoint_data['upfront_amount']['amount_numeric'] ??
            $endpoint_data['upfront'] ?? 0;

        $currency = $endpoint_data['subscription_amount']['currency_code'] ??
            $endpoint_data['currency'] ??
            ($settings->display_currency ?? 'USD');

        // Get billing cycle from service or use default
        $billing_cycle = isset($service['itemFrequency']) ? $service['itemFrequency'] : 'month';
        $billing_cycle_display = rtrim($billing_cycle, 'ly');
        $billing_cycle_display = $billing_cycle_display === 'dai' ? 'day' : $billing_cycle_display;
        $billing_cycle_display = $billing_cycle_display === 'bi-month' ? '14-day' : $billing_cycle_display;
        $billing_cycle_display = ucfirst($billing_cycle_display);

        // Get trial days from service
        $trial_days = isset($service['itemTrialDays']) ? (int)$service['itemTrialDays'] : 0;

        // Log the values being used
        error_log('Using endpoint data - Regular: ' . $regular_amount . ', Upfront: ' . $upfront_amount . ', Currency: ' . $currency);

?>

        <div class="trial-info" style="padding: 15px; border-radius: 8px; font-size: 12px; line-height: 1.5; text-align: left; margin: 20px auto; color: #333; max-width: 900px; text-align: center;">
            <p data-i18n="trial_subscription_notice" style="margin-bottom: 0px; color: #333;">
                By activating the TRIAL, you will subscribe to the <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'checkout domain'); ?>
                service for a <span id="footer-trial-days"><?php echo (int)$trial_days; ?></span>-day trial period at the promotional price indicated in the offer.
                A charge of <span id="footer-upfront-currency"></span><span id="footer-upfront-amount"></span> will be made to your card for verification purposes.
                After the <span id="footer-trial-days-2"><?php echo (int)$trial_days; ?></span>-day trial period, unless you cancel your subscription,
                your account will automatically be upgraded to a Full Access Membership,
                and you will be charged <span id="footer-regular-currency"></span><span id="footer-regular-amount"></span>
                every <span id="footer-billing-cycle"></span>.
                All details and conditions related to the subscription can be found in our <a href="tos.html" target="_blank" style="color: #555; text-decoration: none;  transition: text-decoration 0.2s ease;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'" data-i18n="terms_and_conditions">Terms and Conditions</a>.
            </p>
        </div>
        <?php
        return;
    }


    // If service is just an ID, try to load the full service data
    if (!empty($service) && !is_array($service)) {
        global $c; // Access the global $c object if needed

        try {
            // Include the item model if not already included
            if (!class_exists('itemModel')) {
                @include_once(dirname(__DIR__) . '/includes/classes/models/item.model.php');
            }

            if (class_exists('itemModel')) {
                $itemModel = new itemModel();
                $itemModel->setID($service);
                if ($itemModel->getItem()) {
                    $service = $itemModel->itemData;
                    error_log('Successfully loaded service data: ' . print_r($service, true));
                } else {
                    error_log('Failed to load service data for ID: ' . $service);
                }
            } else {
                error_log('itemModel class not found');
            }
        } catch (Exception $e) {
            error_log('Error loading service data: ' . $e->getMessage());
        }
    }

    try {
        if ($is_trial && !empty($service) && is_array($service)) {
            $trial_days = isset($service['itemTrialDays']) ? (int)$service['itemTrialDays'] : 0;
            $regular_amount = isset($service['itemAmount']) ? (float)$service['itemAmount'] : 0;
            $billing_cycle = isset($service['itemFrequency']) ? $service['itemFrequency'] : 'month';
            $billing_cycle_display = rtrim($billing_cycle, 'ly');
            $billing_cycle_display = $billing_cycle_display === 'dai' ? 'day' : $billing_cycle_display;
            $billing_cycle_display = $billing_cycle_display === 'bi-month' ? '14-day' : $billing_cycle_display;
            $billing_cycle_display = ucfirst($billing_cycle_display);
            $upfront_amount = isset($service['itemTrialUpfront']) ? (float)$service['itemTrialUpfront'] : 0;
            // Always use the main display currency from settings
            $upfront_currency = $settings->display_currency;

            error_log('Displaying trial info - Days: ' . $trial_days . ', Amount: ' . $regular_amount);
        ?>
            <div class="trial-info" style="padding: 15px; border-radius: 8px; font-size: 12px; line-height: 1.5; text-align: left; margin: 20px auto; color: #333; max-width: 900px; text-align: center;">
                <p style="margin-bottom: 0px; color: #333;">
                    <span data-i18n="trial_activation_text_1">By activating the TRIAL, you will subscribe to the</span> 
                    <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'checkout domain'); ?>
                    <span data-i18n="trial_activation_text_2">service for a</span> 
                    <span id="footer-trial-days"><?php echo (int)$trial_days; ?></span>
                    <span data-i18n="trial_activation_text_3">day trial period at the promotional price indicated in the offer. A charge of</span>
                    <span id="footer-upfront-currency"></span><span id="footer-upfront-amount"></span>
                    <span data-i18n="trial_activation_text_4">will be made to your card for verification purposes. After the</span>
                    <span id="footer-trial-days-2"><?php echo (int)$trial_days; ?></span>
                    <span data-i18n="trial_activation_text_5">day trial period, unless you cancel your subscription, your account will automatically be upgraded to a Full Access Membership, and you will be charged</span>
                    <span id="footer-regular-currency"></span><span id="footer-regular-amount"></span>
                    <span data-i18n="trial_activation_text_6">every</span>
                    <span id="footer-billing-cycle"></span>.
                    <span data-i18n="trial_activation_text_7">All details and conditions related to the subscription can be found in our</span>
                    <a href="tos.html" target="_blank" style="color: #555; text-decoration: none; transition: text-decoration 0.2s ease;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'" data-i18n="terms_and_conditions">Terms and Conditions</a>.
                </p>
            </div>
        <?php
        } else {
            $reason = !$is_trial ? 'not a trial' : (empty($service) ? 'no service data' : 'service is not an array');
            error_log('Skipping trial info - Reason: ' . $reason);
            renderDefaultFooter();
        }
    } catch (Exception $e) {
        error_log('Error in renderFooterContent: ' . $e->getMessage());
        renderDefaultFooter();
    }
}

function renderDefaultFooter()
{
    global $theme;
    if (strtolower($theme) === 'colorful') {
        ?>
        <div class="default-footer" style="padding: 15px 0; text-align: center; color: #fff; font-size: 12px; border-radius: 8px; margin: 20px 0;">
            <div class="footer-content" style="max-width: 800px; margin: 0 auto; padding: 0 15px;">
                <div class="footer-links" style="margin-bottom: 10px;">
                    <a href="tos.html" style="color: #fff; margin: 0 10px; text-decoration: none;" data-i18n="terms_conditions">Terms and Conditions</a>
                    <span style="opacity: 0.7;">|</span>
                    <a href="privacy_policy.php" style="color: #fff; margin: 0 10px; text-decoration: none;" data-i18n="privacy_policy">Privacy Policy</a>
                    <span style="opacity: 0.7;">|</span>
                    <a href="contact.php" style="color: #fff; margin: 0 10px; text-decoration: none;" data-i18n="contact_us">Contact Us</a>
                </div>
                <div class="copyright" style="opacity: 0.8;" data-i18n="all_rights_reserved">
                    &copy; <?php echo date('Y'); ?> All Rights Reserved
                </div>
            </div>
        <?php
    } else {
    ?>
        <div class="default-footer" style="padding: 10px 0; text-align: center; color: #666; font-size: 12px;" data-i18n="all_rights_reserved">
            &copy; <?php echo date('Y'); ?> All Rights Reserved
        </div>
<?php
    }
}
?>

<?php if (strtolower($theme) === 'cardstyle') { ?>
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php renderFooterContent($is_trial ?? false, $service ?? []); ?>
                </div>
            </div>
        </div>
    </footer>
<?php } elseif (strtolower($theme) === 'minimalist') { ?>
    <footer class="footer footer--simple">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php renderFooterContent($is_trial ?? false, $service ?? []); ?>
                </div>
            </div>
        </div>
    </footer>
<?php } elseif (strtolower($theme) === 'normal') { ?>
    <footer class="footer footer--normal">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php renderFooterContent($is_trial ?? false, $service ?? []); ?>
                </div>
            </div>
        </div>
    </footer>
<?php } elseif (strtolower($theme) === 'colorful') { ?>
    <footer class="footer footer--colorful" style="padding: 30px 0; margin-top: 30px;">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php renderFooterContent($is_trial ?? false, $service ?? []); ?>
                </div>
            </div>
        </div>
    </footer>
<?php } else { ?>
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php renderFooterContent($is_trial ?? false, $service ?? []); ?>
                </div>
            </div>
        </div>
    </footer>
<?php } ?>
<script>
    $(document).ready(function() {
        function updateFooterAmountsFromAPI() {
            var country = $('#pt_country').val();
            var ctc = $('#pt_ctc').val();
            var serviceId = $('#pt_service').val();

            if (!country || !ctc) return;

            // Fetch currency data
            $.get('getConvenientCurr.php', {
                country: country,
                ctc: ctc,
                service: serviceId
            }, function(data) {
                if (data.success) {
                    // Update the displayed amounts in the footer
                    if (data.subscription_amount) {
                        var currencySymbol = data.subscription_amount.currency_symbol || data.subscription_amount.currency || '';
                        $('#footer-regular-amount').text(data.subscription_amount.amount_numeric);
                        $('#footer-regular-currency').text(currencySymbol);
                        var period = (data.subscription_amount.period === 'bi-monthly') ? '14-day' : (data.subscription_amount.period || 'month');
                        $('#footer-billing-cycle').text(period);
                    }

                    if (data.upfront_amount) {
                        var upfrontCurrencySymbol = data.upfront_amount.currency_symbol || data.upfront_amount.currency || '';
                        $('#footer-upfront-amount').text(data.upfront_amount.amount_numeric);
                        $('#footer-upfront-currency').text(upfrontCurrencySymbol);
                    }
                }
            }, 'json');
        }

        // Initial update
        updateFooterAmountsFromAPI();

        // Update when country or CTC changes
        $(document).on('change', '#pt_country, #pt_ctc, #pt_service', function() {
            updateFooterAmountsFromAPI();
        });
    });
</script>