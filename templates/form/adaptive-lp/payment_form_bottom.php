<?php
$ctc = htmlspecialchars($_GET['ctc'] ?? '2', ENT_QUOTES);
$country = htmlspecialchars($adaptive_detected_country ?? ($post['pt_country'] ?? ''), ENT_QUOTES);
$serviceId = htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES);
$amountValue = htmlspecialchars($pt_amount ?? ($post['pt_amount'] ?? '0.00'), ENT_QUOTES);
$currencyValue = htmlspecialchars($pt_currency ?? ($post['pt_currency'] ?? $currency_text ?? ''), ENT_QUOTES);
$currencySymbolValue = htmlspecialchars($pt_currency_symbol ?? $display_currency ?? '', ENT_QUOTES);
$currencyPositionValue = htmlspecialchars($pt_currency_position ?? $currency_position ?? 'before', ENT_QUOTES);
$upfrontAmountValue = isset($adaptive_upfront_amount) ? htmlspecialchars(number_format((float)$adaptive_upfront_amount, 2, '.', ''), ENT_QUOTES) : '';
$upfrontCurrencySymbolValue = htmlspecialchars($adaptive_upfront_currency_symbol ?? '', ENT_QUOTES);
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
            <span data-i18n="i_agree_with">I agree with</span> <a href="javascript:;" data-toggle="modal" data-target="#terms_and_conditions" data-i18n="terms_and_conditions">terms and conditions</a>
        </label>
    </div>
<?php } ?>

<?php if ($use_recaptcha == 'y') { ?>
    <div class="g-recaptcha" data-sitekey="<?php echo ($recaptcha_site_key) ?>" data-callback="checkCaptcha"></div>
<?php } ?>

<button class="primary-button" type="submit" id="form_submit_button" <?php echo ($use_recaptcha == 'y') ? "disabled" : "" ?>>
    <span class="adaptive-button-spinner" aria-hidden="true"></span>
    <span class="adaptive-button-label" data-i18n="complete_payment">Complete Payment</span>
    <span class="adaptive-submit-amount">
        <span class="adaptive-upfront-currency-symbol"><?php echo $upfrontCurrencySymbolValue; ?></span><span class="adaptive-upfront-amount"><?php echo $upfrontAmountValue; ?></span>
    </span>
</button>

<div id="payment-request-button"></div>

<p class="secure-line" data-i18n="secure_connection">Secure 256 Bit Encrypted Connection</p>
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
        var ctc = $('#pt_ctc').val();
        var serviceId = $('#pt_service').val();

        if (!ctc || !serviceId) return;

        $.get('getConvenientCurr.php', {
            country: $('#pt_country').val(),
            ctc: ctc,
            service: serviceId
        }, function (data) {
            if (!data || !data.success || !data.subscription_amount) return;

            var resolvedCountry = data.country || $('#pt_country').val();
            var amount = data.subscription_amount.amount_numeric;
            var currency = data.subscription_amount.currency_code || data.subscription_amount.currency || '';
            var symbol = data.subscription_amount.currency_symbol || '';

            if (amount === undefined || amount === null || amount === '' || !currency) return;

            $('#pt_country').val(resolvedCountry);
            $('#country').val(resolvedCountry);
            $('#pt_amount').val(amount);
            $('#pt_currency').val(currency);
            $('input[name="amount"]').val(amount);
            $('input[name="currency"]').val(currency);
            $('input[name="pt_currency_symbol"]').val(symbol);
            $('input[name="pt_currency_position"]').val('before');
            $('.pt_currency_symbol').text(symbol);
            $('.total-amount').text(parseFloat(amount || 0).toFixed(2));

            if (data.upfront_amount) {
                var upfrontAmount = data.upfront_amount.amount_numeric;
                var upfrontSymbol = data.upfront_amount.currency_symbol || '';
                if (upfrontAmount === undefined || upfrontAmount === null || upfrontAmount === '') return;
                $('.adaptive-upfront-currency-symbol').text(upfrontSymbol);
                $('.adaptive-upfront-amount').text(parseFloat(upfrontAmount || 0).toFixed(2));
            }
        }, 'json');
    }

    $(document).ready(function () {
        updateAmountsFromAPI();
        $('#country').on('change', function () {
            updateAmountsFromAPI();
        });
        $('#pt_ctc').on('change', updateAmountsFromAPI);
    });
</script>
