<?php
// Include the item model class
require_once __DIR__ . '/../../../includes/classes/models/itemModel.class.php';

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
        $regular_amount = isset($service['itemAmount']) ? (float) $service['itemAmount'] : 0;

        // Check if this is a trial item
        $is_trial = isset($service['itemTrial']) && $service['itemTrial'] === 'y' && !empty($service['itemTrialDays']);

        if ($is_trial) {
            // For trial items, get trial amount and days
            $trial_amount = isset($service['itemTrialUpfront']) ? (float) $service['itemTrialUpfront'] : 0;
            $trial_days = (int) $service['itemTrialDays'];

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Stripe.js library will be loaded in the footer to avoid duplicates -->
    <!-- <link rel="stylesheet" href="assets/css/Normal/style.css"> -->
</head>

<body>
    <div id="payment_info normal">
        <div class="row">

            <div class="header">
                <div class="logo" data-i18n="premium">Premium</div>
                <div class="domain">
                    <svg class="lock-icon" viewBox="0 0 24 24" fill="currentColor" style="color: #4CAF50">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
                    </svg>
                    <span data-i18n="secure_site">securesite.com</span>
                </div>
                <div class="info-icon">i</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h1 class="card-title" data-i18n="confirm_account">Confirm Your Account</h1>
                    <div class="rating">
                        <div class="stars">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star empty">★</span>
                        </div>
                        <div class="users-count" data-i18n="user_count">425,830 users</div>
                    </div>
                </div>

                <p class="description" data-i18n="instant_access_desc">
                    Get instant access to over 5,000 premium files, audiobooks, and courses. Enjoy unrestricted access
                    to 800+ master classes and webinars.
                </p>

                <form>
                    <div class="form-group card-number-group">
                        <div class="card-number-header">
                            <label class="form-label" data-i18n="card_number">Card Number</label>
                            <div class="card-logos">
                                <div class="card-logo visa">VISA</div>
                                <div class="card-logo mastercard">MC</div>
                            </div>
                        </div>
                        <div id="card-number-element" class="form-input"
                            data-i18n-placeholder="placeholder_card_number"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" data-i18n="expiry_date">Expiry Date</label>
                            <div id="card-expiry-element" class="form-input"
                                data-i18n-placeholder="placeholder_expiry_date"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" data-i18n="cvv">CVV</label>
                            <div id="card-cvc-element" class="form-input" data-i18n-placeholder="placeholder_cvv"></div>
                        </div>
                    </div>
                    <div id="card-errors" role="alert"></div>

                    <div class="form-row">
                        <div class="form-group ">
                            <label for="first_name" class="form-label" data-i18n="first_name">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                data-i18n-placeholder="first_name" placeholder="First name" required
                                value="<?php echo htmlspecialchars(urldecode($_GET['first'] ?? ''), ENT_QUOTES); ?>">
                        </div>
                        <div class="form-group ">
                            <label for="last_name" class="form-label" data-i18n="last_name">Last Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                data-i18n-placeholder="last_name" placeholder="Last name" required
                                value="<?php echo htmlspecialchars(urldecode($_GET['last'] ?? ''), ENT_QUOTES); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="country" class="form-label" data-i18n="country">Country <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="country" name="country" required>
                                <option value="" data-i18n="select_country" style="font-weight: 800; font-size: 17px;">
                                    Select Country</option>
                                <?php
                                // Ensure we have the language (check passed var, then global, then default)
                                $currentLang = $lang ?? $GLOBALS['current_lang'] ?? 'en';

                                // Ensure function is available
                                include_once "includes/language_utils.php";

                                // Get translations for the current language
                                $translatedCountries = getAllCountriesInLanguage($currentLang);

                                // Use the structure from includes/countries.php which is now flat
                                $countries = $GLOBALS['countries'];

                                // Use detectedCountry if available (check passed var, then global, then default)
                                $selectedCode = $detectedCountry ?? $GLOBALS['detectedCountry'] ?? 'US';

                                foreach ($countries as $code => $englishName) {
                                    // Use translation if available, otherwise fallback to English name
                                    $displayName = $translatedCountries[$code] ?? $englishName;

                                    $selected = ($selectedCode === $code) ? 'selected' : '';
                                    echo "<option value='$code' $selected>$displayName</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="">
                            <div class="form-group">
                                <label for="zip" class="form-label" data-i18n="zip_postal_code">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" placeholder="23424"
                                    data-i18n-placeholder="zip_postal_code"
                                    value="<?php echo htmlspecialchars(urldecode($_GET['zip'] ?? ''), ENT_QUOTES); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label" data-i18n="email_address">Email Address</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="John.doe@gmail.com"
                            data-i18n-placeholder="email_address"
                            value="<?php echo htmlspecialchars(urldecode($_GET['email'] ?? ''), ENT_QUOTES); ?>">
                    </div>

                    <?php include 'templates/form/Normal/payment_form_bottom.php'; ?>

                    <div class="security-badges">
                        <div class="badge">
                            <div class="badge-icon secure-icon">✓</div>
                            <span data-i18n="secure_badge">SECURE</span>
                        </div>
                        <div class="badge">
                            <div class="badge-icon instant-icon">⚡</div>
                            <span data-i18n="instant_badge">INSTANT</span>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
    <style>
        /* Basic styling for the payment form */
        .form-label {
            font-weight: normal;
        }

        .spinner-border {
            display: none;
        }

        .spinner-border.d-none {
            display: none !important;
        }

        .spinner-border:not(.d-none) {
            display: inline-block !important;
        }

        /* Stripe Element styles */
        .StripeElement {
            box-sizing: border-box;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;
        }

        .payment-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .payment-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .payment-icon {
            margin-right: 8px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
        }

        .card-logos {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
        }

        .order-summary {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .product-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .image-placeholder {
            color: #6c757d;
            font-size: 24px;
        }

        .product-title {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-price {
            margin-bottom: 10px;
        }

        .price-amount {
            font-weight: bold;
        }

        .trial-info {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }

        .promo-section {
            margin: 20px 0;
        }

        .promo-input-group {
            display: flex;
            gap: 10px;
        }

        .order-totals {
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }

        .why-choose-us {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .benefit {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .benefit:last-child {
            margin-bottom: 0;
        }

        .benefit-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            flex-shrink: 0;
        }

        .benefit-content h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .benefit-content p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .footer-links {
            text-align: center;
            padding: 15px 0;
        }

        .footer-link {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .divider {
            color: #6c757d;
            margin: 0 10px;
        }

        @media (max-width: 768px) {
            .product-section {
                flex-direction: column;
            }

            .product-image {
                align-self: center;
            }

            .product-image img {
                max-width: 100%;
                max-height: 100%;
                object-fit: cover;
            }

            .image-placeholder {
                color: #6c757d;
                font-size: 24px;
            }

            .product-title {
                font-size: 1.1rem;
                margin-bottom: 5px;
            }

            .product-description {
                color: #6c757d;
                font-size: 0.9rem;
                margin-bottom: 10px;
            }

            .product-price {
                margin-bottom: 10px;
            }

            .price-amount {
                font-weight: bold;
            }

            .trial-info {
                display: block;
                font-size: 0.85rem;
                color: #6c757d;
                margin-top: 5px;
            }

            .quantity-selector {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .quantity-btn {
                width: 30px;
                height: 30px;
                border: 1px solid #ddd;
                background: #f8f9fa;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .quantity-input {
                width: 50px;
                text-align: center;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 5px;
            }

            .promo-section {
                margin: 20px 0;
            }

            .promo-input-group {
                display: flex;
                gap: 10px;
            }

            .order-totals {
                border-top: 1px solid #eee;
                padding-top: 15px;
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .summary-row.total {
                font-weight: bold;
                font-size: 1.1rem;
                border-top: 1px solid #eee;
                padding-top: 10px;
                margin-top: 10px;
            }

            .why-choose-us {
                background: #fff;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }

            .benefit {
                display: flex;
                gap: 15px;
                margin-bottom: 20px;
            }

            .benefit:last-child {
                margin-bottom: 0;
            }

            .benefit-icon {
                width: 40px;
                height: 40px;
                background: #f8f9fa;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #007bff;
                flex-shrink: 0;
            }

            .benefit-content h4 {
                font-size: 1rem;
                margin-bottom: 5px;
            }

            .benefit-content p {
                color: #6c757d;
                font-size: 0.9rem;
                margin: 0;
            }

            .footer-links {
                text-align: center;
                padding: 15px 0;
            }

            .footer-link {
                color: #6c757d;
                text-decoration: none;
                font-size: 0.85rem;
            }

            .divider {
                color: #6c757d;
                margin: 0 10px;
            }

            @media (max-width: 768px) {
                .product-section {
                    flex-direction: column;
                }

                .product-image {
                    align-self: center;
                }
            }

        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #fff 100%) !important;
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            background: #c3c3c314 !important;
            box-shadow: none !important;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            background-color: #fdfdfd00 !important;
        }
    </style>
    <script>
        $(document).ready(function () {
            function updateTrialPrice() {
                var country = $('#pt_country').val();
                var ctc = $('#pt_ctc').val();
                var serviceId = $('#pt_service').val();

                if (!country || !ctc) {
                    console.log('Country or CTC not available yet');
                    return;
                }

                console.log('Fetching currency data for:', {
                    country: country,
                    ctc: ctc,
                    service: serviceId
                });

                // Fetch currency data
                $.get('getConvenientCurr.php', {
                    country: country,
                    ctc: ctc,
                    service: serviceId
                }, function (data) {
                    console.log('Received currency data:', data);

                    if (data && data.success) {
                        if (data.upfront_amount) {
                            var amount = data.upfront_amount.amount_numeric || data.upfront_amount.amount || '0.99';
                            var currencySymbol = data.upfront_amount.currency_symbol || data.upfront_amount.currency || '$';

                            // Format the price with proper decimal places
                            var formattedPrice = parseFloat(amount).toFixed(2);

                            // Update the trial price display
                            $('#trial-price').text(currencySymbol + ' ' + formattedPrice);
                            console.log('Updated trial price to:', currencySymbol + ' ' + formattedPrice);
                        } else {
                            console.log('No upfront amount found in response');
                        }
                    } else {
                        console.log('Invalid or missing data in API response');
                    }
                }, 'json')
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        console.error('Error fetching currency data:', textStatus, errorThrown);
                        // Fallback to default price if API call fails
                        $('#trial-price').text('$0.99');
                    });
            }

            // Update price when document is ready
            $(document).ready(function () {
                updateTrialPrice();

                // Also update when country or CTC changes
                $('#pt_country, #pt_ctc').on('change', function () {
                    updateTrialPrice();
                });
            });

        });
    </script>
    <script type="text/javascript">
        // Handle form submission
        function updatePaymentForm() {
            // Update hidden fields in payment form with personal info
            const fullName = $('#first_name').val() + ' ' + $('#last_name').val();
            const email = $('#email').val();
            const phone = $('#phone').val();
            const address = $('#address1').val();
            const city = $('#city').val();
            const zip = $('#postalCode').val() || $('#zip').val();
            const country = $('#country').val();

            // Update hidden fields by ID
            $('#form_full_name').val(fullName.trim());
            $('#form_email').val(email);
            $('#form_phone').val(phone);
            $('#form_address').val(address);
            $('#form_city').val(city);
            $('#form_zip').val(zip);
            $('#form_country').val(country);

            // Ensure currency is set if empty
            if (!$('input[name="currency"]').val()) {
                $('input[name="currency"]').val('USD');
            }

            console.log('Updated payment form fields:', {
                name: fullName.trim(),
                email: email,
                phone: phone,
                address: address,
                city: city,
                zip: zip,
                country: country
            });

            return true;
        }

        // Simple validation without jQuery validate plugin
        function validatePersonalInfo() {
            let isValid = true;

            // Validate first name
            const firstName = $('#first_name').val();
            if (!firstName || firstName.length < 2) {
                $('#first_name').addClass('is-invalid');
                isValid = false;
            } else {
                $('#first_name').removeClass('is-invalid');
            }

            // Validate last name
            const lastName = $('#last_name').val();
            if (!lastName || lastName.length < 2) {
                $('#last_name').addClass('is-invalid');
                isValid = false;
            } else {
                $('#last_name').removeClass('is-invalid');
            }

            // Validate email
            const email = $('#email').val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                $('#email').addClass('is-invalid');
                isValid = false;
            } else {
                $('#email').removeClass('is-invalid');
            }

            return isValid;
        }

        // Form functionality
        $(document).ready(function () {
            function updateFullName() {
                const firstName = $('#first_name').val() || '';
                const lastName = $('#last_name').val() || '';
                $('#form_full_name').val((firstName + ' ' + lastName).trim());
            }

            $('#first_name, #last_name').on('input', updateFullName);

            // Update payment form on any field change
            $('#first_name, #last_name, #email, #phone, #address1, #address2, #city, #zip, #country')
                .on('change blur', function () {
                    updatePaymentForm();
                    $(this).removeClass('is-invalid');
                });
        });

        // Next button click handler
        $('#next-btn').on('click', function (e) {
            e.preventDefault();

            // Validate personal info
            if (!validatePersonalInfo()) {
                // Scroll to first invalid field
                const firstInvalid = $('.is-invalid').first();
                if (firstInvalid.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalid.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // Update all hidden fields in payment form with personal info
            updatePaymentForm();

            // Also ensure these values are passed as URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const additionalParams = {};

            // Add URL parameters that should be preserved
            ['cid', 'service', 'language', 'productname', 'price'].forEach(param => {
                if (urlParams.has(param)) {
                    additionalParams[param] = urlParams.get(param);
                }
            });

            // Update hidden inputs for URL parameters
            Object.entries(additionalParams).forEach(([key, value]) => {
                $(`input[name="${key}"]`).val(value);
            });

            // Progress Bar State Management
            function updateProgressBar(step) {
                const progressBar = document.querySelector('.progressbar');
                const step1Indicator = document.getElementById('step1-indicator');
                const step2Indicator = document.getElementById('step2-indicator');

                if (step === 1) {
                    // Reset to step 1
                    progressBar.classList.remove('step2');
                    step1Indicator.classList.add('active');
                    step1Indicator.classList.remove('completed');
                    step2Indicator.classList.remove('active', 'completed');

                    // Reset progress line
                    progressBar.style.setProperty('--progress-width', '0%');
                    progressBar.style.setProperty('--progress-color', '#4f46e5');
                } else if (step === 2) {
                    // Move to step 2
                    progressBar.classList.add('step2');
                    step1Indicator.classList.remove('active');
                    step1Indicator.classList.add('completed');
                    step2Indicator.classList.add('active');

                    // Animate progress line
                    progressBar.style.setProperty('--progress-width', '100%');
                    progressBar.style.setProperty('--progress-color', '#4f46e5');
                }
            }

            updateProgressBar(2);

            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.product-info').offset().top - 20
            }, 300);
        });

        // Back button click handler
        $('#back-btn').on('click', function (e) {
            e.preventDefault();

            // Reset progress bar to step 1
            updateProgressBar(1);



            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.product-info').offset().top - 20
            }, 300);
        });
    </script>



</body>

</html>