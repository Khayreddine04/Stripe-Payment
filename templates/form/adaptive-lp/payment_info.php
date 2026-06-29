<?php
$basePath = 'templates/form/adaptive-lp';
$dataFile = HOME_DIR . '/templates/form/adaptive-lp/data/landing-pages.json';
$payload = is_file($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$site = isset($payload['site']) && is_array($payload['site']) ? $payload['site'] : [];
$landingPages = isset($payload['landingPages']) && is_array($payload['landingPages']) ? $payload['landingPages'] : [];
$requestedLp = $_GET['lp'] ?? '';
$item = $landingPages[0] ?? [];

foreach ($landingPages as $candidate) {
    if ((string)($candidate['id'] ?? '') === (string)$requestedLp) {
        $item = $candidate;
        break;
    }
}

$title = $item['title'] ?? urldecode($_GET['productname'] ?? 'Special Offer');
$price = $item['price'] ?? urldecode($_GET['price'] ?? ($pt_amount ?? '0.00'));
$upfrontPrice = $adaptive_upfront_amount ?? urldecode($_GET['price'] ?? ($item['price'] ?? '0.00'));
$upfrontCurrencySymbol = $adaptive_upfront_currency_symbol ?? ($pt_currency_symbol ?: '$');
$inventory = $item['inventory'] ?? 3;
$features = isset($item['features']) && is_array($item['features']) ? $item['features'] : [];
$footer = isset($item['footer']) && is_array($item['footer']) ? $item['footer'] : [];
$image = $item['image'] ?? '';
$image = preg_replace('#^\./#', '', $image);
$imageUrl = $image !== '' ? $basePath . '/' . $image : '';
$stockColor = $item['stockColor'] ?? '#e11c24';
$buttonColor = $item['buttonColor'] ?? '#2c63e6';
$formTitle = $item['formTitle'] ?? ($site['formTitle'] ?? 'Fill Out Your Details');
$secureText = $site['secureText'] ?? 'Secure 256 Bit Encrypted Connection';
$badges = isset($site['badges']) && is_array($site['badges']) ? $site['badges'] : [];
$countryValue = $_GET['country'] ?? 'US';
$firstName = htmlspecialchars(urldecode($_GET['first'] ?? ''), ENT_QUOTES);
$lastName = htmlspecialchars(urldecode($_GET['last'] ?? ''), ENT_QUOTES);
$email = htmlspecialchars(urldecode($_GET['email'] ?? ''), ENT_QUOTES);
$phone = htmlspecialchars(urldecode($_GET['phone'] ?? ''), ENT_QUOTES);
$address = htmlspecialchars(urldecode($_GET['address'] ?? ''), ENT_QUOTES);
$address2 = htmlspecialchars(urldecode($_GET['address2'] ?? ''), ENT_QUOTES);
$city = htmlspecialchars(urldecode($_GET['city'] ?? ''), ENT_QUOTES);
$state = htmlspecialchars(urldecode($_GET['state'] ?? ''), ENT_QUOTES);
$zip = htmlspecialchars(urldecode($_GET['zip'] ?? ''), ENT_QUOTES);
?>

<link rel="stylesheet" href="<?php echo $basePath; ?>/assets/styles.css?v=<?php echo rand(1, 9999); ?>">

<section class="offer-page adaptive-offer-page" style="--stock-color: <?php echo htmlspecialchars($stockColor, ENT_QUOTES); ?>; --button-color: <?php echo htmlspecialchars($buttonColor, ENT_QUOTES); ?>;">
    <div class="offer-page__bar"><?php echo htmlspecialchars($site['offerLabel'] ?? 'Special Offer', ENT_QUOTES); ?></div>
    <div class="offer-page__trail"><?php echo htmlspecialchars($site['offerTrailPrefix'] ?? "Today's Offer", ENT_QUOTES); ?> &gt; <?php echo htmlspecialchars($title, ENT_QUOTES); ?></div>
    <div class="offer-page__timer" id="adaptive-countdown"><?php echo htmlspecialchars($site['deadlineText'] ?? 'Attention, this offer expires in:', ENT_QUOTES); ?> 04:55</div>

    <div class="offer-grid">
        <section class="product-card">
            <div class="product-shot-wrap">
                <div class="price-badge">
                    <small>Pay Only</small>
                    <span><span class="adaptive-upfront-currency-symbol"><?php echo htmlspecialchars($upfrontCurrencySymbol, ENT_QUOTES); ?></span><span class="adaptive-upfront-amount"><?php echo htmlspecialchars(number_format((float)$upfrontPrice, 2, '.', ''), ENT_QUOTES); ?></span></span>
                </div>
                <div class="product-shot" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES); ?> preview" style="background-image: url('<?php echo htmlspecialchars($imageUrl, ENT_QUOTES); ?>');"></div>
            </div>

            <div class="product-copy">
                <h2 class="product-title"><?php echo htmlspecialchars($title, ENT_QUOTES); ?></h2>
                <p class="stock-line"><span class="stock-dot"></span><?php echo htmlspecialchars((string)$inventory, ENT_QUOTES); ?> In stock</p>
                <ul class="feature-list">
                    <?php foreach ($features as $feature) { ?>
                        <li><?php echo $feature; ?></li>
                    <?php } ?>
                </ul>
            </div>
        </section>

        <aside class="lead-card">
            <h3 class="lead-card__title"><?php echo htmlspecialchars($formTitle, ENT_QUOTES); ?></h3>

            <div class="lead-form">
                <input type="hidden" name="pt_name" id="pt_name" value="">
                <input type="hidden" name="pt_email" id="pt_email" value="">
                <input type="hidden" name="pt_phone" id="pt_phone" value="">
                <input type="hidden" name="pt_address1" id="pt_address1" value="">
                <input type="hidden" name="pt_address" id="pt_address" value="">
                <input type="hidden" name="pt_address2" id="pt_address2" value="">
                <input type="hidden" name="pt_city" id="pt_city" value="">
                <input type="hidden" name="pt_postal" id="pt_postal" value="">
                <input type="hidden" name="pt_zip" id="pt_zip" value="">
                <input type="hidden" name="pt_state" id="pt_state" value="">

                <div class="form-grid">
                    <input class="adaptive-customer-field" type="text" id="first_name" name="first_name" placeholder="First name" value="<?php echo $firstName; ?>" required>
                    <input class="adaptive-customer-field" type="text" id="last_name" name="last_name" placeholder="Last name" value="<?php echo $lastName; ?>" required>
                    <input class="adaptive-customer-field" type="email" id="email" name="email" placeholder="Email address" value="<?php echo $email; ?>" required>
                    <input class="adaptive-customer-field" type="tel" id="phone" name="phone" placeholder="Phone" value="<?php echo $phone; ?>">
                    <select class="adaptive-customer-field" id="country" name="country" required>
                        <?php foreach (($GLOBALS['countries'] ?? ['US' => 'United States']) as $code => $name) { ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES); ?>" <?php echo $countryValue === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES); ?></option>
                        <?php } ?>
                    </select>
                    <input class="adaptive-customer-field" type="text" id="address" name="address" placeholder="Billing address" value="<?php echo $address; ?>">
                    <input class="adaptive-customer-field" type="text" id="address2" name="address2" placeholder="Apartment, suite, etc. (optional)" value="<?php echo $address2; ?>">
                    <input class="adaptive-customer-field" type="text" id="city" name="city" placeholder="City" value="<?php echo $city; ?>">
                    <input class="adaptive-customer-field" type="text" id="state" name="state" placeholder="State" value="<?php echo $state; ?>">
                    <input class="adaptive-customer-field" type="text" id="zip" name="zip" placeholder="ZIP code" value="<?php echo $zip; ?>">
                </div>

                <div class="adaptive-card-fields">
                    <div class="adaptive-card-field adaptive-card-field--full">
                        <label for="card-number-element">Card Number</label>
                        <div id="card-number-element" class="form-input"></div>
                    </div>
                    <div class="adaptive-card-field">
                        <label for="card-expiry-element">Expiry Date</label>
                        <div id="card-expiry-element" class="form-input"></div>
                    </div>
                    <div class="adaptive-card-field">
                        <label for="card-cvc-element">CVV</label>
                        <div id="card-cvc-element" class="form-input"></div>
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>
            </div>

            <script>
                (function () {
                    var seconds = 295;
                    var node = document.getElementById('adaptive-countdown');
                    var prefix = <?php echo json_encode($site['deadlineText'] ?? 'Attention, this offer expires in:'); ?>;
                    function tick() {
                        var minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
                        var rest = String(seconds % 60).padStart(2, '0');
                        if (node) node.textContent = prefix + ' ' + minutes + ':' + rest;
                        if (seconds > 0) {
                            seconds--;
                            window.setTimeout(tick, 1000);
                        }
                    }
                    tick();
                })();
            </script>
