<?php
$ctc = htmlspecialchars($_GET['ctc'] ?? '2', ENT_QUOTES);
$country = htmlspecialchars($_GET['country'] ?? 'US', ENT_QUOTES);
$serviceId = htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES);
$amountValue = htmlspecialchars($pt_amount ?? ($post['pt_amount'] ?? '0.00'), ENT_QUOTES);
$currencyValue = htmlspecialchars($pt_currency ?? ($post['pt_currency'] ?? $currency_text ?? 'USD'), ENT_QUOTES);
$currencySymbolValue = htmlspecialchars($pt_currency_symbol ?? $display_currency ?? '$', ENT_QUOTES);
$currencyPositionValue = htmlspecialchars($pt_currency_position ?? $currency_position ?? 'before', ENT_QUOTES);
$basePath = 'templates/form/adaptive-lp';
?>

<input type="hidden" name="pt_ctc" id="pt_ctc" value="<?php echo $ctc; ?>">
<input type="hidden" name="pt_country" id="pt_country" value="<?php echo $country; ?>">
<input type="hidden" name="pt_service" id="pt_service" value="<?php echo $serviceId; ?>">
<input type="hidden" name="pt_amount" id="pt_amount" value="<?php echo $amountValue; ?>">
<input type="hidden" name="pt_currency" id="pt_currency" value="<?php echo $currencyValue; ?>">
<input type="hidden" name="pt_currency_symbol" value="<?php echo $currencySymbolValue; ?>">
<input type="hidden" name="pt_currency_position" value="<?php echo $currencyPositionValue; ?>">

<?php if ($show_terms == "y") { ?>
    <div class="adaptive-terms">
        <label>
            <input type="checkbox" name="pt_terms" id="pt_terms" value="1" title="<?php _tr("Please accept terms and conditions to proceed.") ?>">
            I agree with <a href="javascript:;" data-toggle="modal" data-target="#terms_and_conditions">terms and conditions</a>
        </label>
    </div>
<?php } ?>

<?php if ($use_recaptcha == 'y') { ?>
    <div class="g-recaptcha" data-sitekey="<?php echo ($recaptcha_site_key) ?>" data-callback="checkCaptcha"></div>
<?php } ?>

<button class="primary-button" type="submit" id="form_submit_button" <?php echo ($use_recaptcha == 'y') ? "disabled" : "" ?>>
    <span data-i18n="complete_payment">Complete Payment</span>
    <span class="adaptive-submit-amount">
        <span class="pt_currency_symbol"><?php echo $currencySymbolValue; ?></span><span class="total-amount"><?php echo $amountValue; ?></span>
    </span>
</button>

<div id="payment-request-button"></div>

<p class="secure-line">Secure 256 Bit Encrypted Connection</p>
<div class="trust-badges">
    <img src="<?php echo $basePath; ?>/assets/mcaffe.svg" alt="McAfee SECURE" class="badge-icon">
    <img src="<?php echo $basePath; ?>/assets/norton.svg" alt="Norton Secured" class="badge-icon">
    <img src="<?php echo $basePath; ?>/assets/truste.png" alt="TRUSTe Verified" class="badge-icon">
</div>

</aside>
</div>

<?php
$lpDataFile = HOME_DIR . '/templates/form/adaptive-lp/data/landing-pages.json';
$lpPayload = is_file($lpDataFile) ? json_decode(file_get_contents($lpDataFile), true) : [];
$lpPages = isset($lpPayload['landingPages']) && is_array($lpPayload['landingPages']) ? $lpPayload['landingPages'] : [];
$requestedLp = $_GET['lp'] ?? '';
$lpItem = $lpPages[0] ?? [];
foreach ($lpPages as $candidate) {
    if ((string)($candidate['id'] ?? '') === (string)$requestedLp) {
        $lpItem = $candidate;
        break;
    }
}
$footer = isset($lpItem['footer']) && is_array($lpItem['footer']) ? $lpItem['footer'] : [];
?>
<?php if (!empty($footer['text'])) { ?>
    <section class="detail-block">
        <h3 class="detail-title"><?php echo htmlspecialchars($footer['title'] ?? ($lpItem['title'] ?? ''), ENT_QUOTES); ?></h3>
        <p class="detail-summary"><?php echo htmlspecialchars($footer['text'], ENT_QUOTES); ?></p>
    </section>
<?php } ?>
</section>

<script>
    function updateAmountsFromAPI() {
        var country = $('#pt_country').val();
        var ctc = $('#pt_ctc').val();
        var serviceId = $('#pt_service').val();

        if (!country || !ctc || !serviceId) return;

        $.get('getConvenientCurr.php', {
            country: country,
            ctc: ctc,
            service: serviceId
        }, function (data) {
            if (!data || !data.success || !data.subscription_amount) return;

            var amount = data.subscription_amount.amount_numeric || data.subscription_amount.amount || $('#pt_amount').val();
            var currency = data.subscription_amount.currency_code || data.subscription_amount.currency || $('#pt_currency').val();
            var symbol = data.subscription_amount.currency_symbol || currency || '';

            $('#pt_amount').val(amount);
            $('#pt_currency').val(currency);
            $('input[name="pt_currency_symbol"]').val(symbol);
            $('.pt_currency_symbol').text(symbol);
            $('.total-amount').text(parseFloat(amount || 0).toFixed(2));
        }, 'json');
    }

    $(document).ready(function () {
        updateAmountsFromAPI();
        $('#country').on('change', function () {
            $('#pt_country').val($(this).val());
            updateAmountsFromAPI();
        });
        $('#pt_ctc').on('change', updateAmountsFromAPI);
    });
</script>
