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
</head>

<body>
    <div id="payment_info">
        <div class="container-info">
            <div class="checkout-section">
                <div class="payment-logos">
                    <div class="payment-logo visa-logo">Verified by VISA</div>
                    <div class="payment-logo mastercard-logo">MasterCard SecureCode</div>
                </div>

                <h2 class="section-title"><span class="section-number">1.</span> Contact Information</h2>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="section-divider"></div>

                <h2 class="section-title"><span class="section-number">2.</span> Shipping Information</h2>
                <div class="form-group">
                    <label for="address1" class="form-label">Address Line 1</label>
                    <input type="text" id="address1" name="address1" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="address2" class="form-label">Address Line 2 (Optional)</label>
                    <input type="text" id="address2" name="address2" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="city" class="form-label">City</label>
                        <input type="text" id="city" name="city" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="postalCode" class="form-label">Postal Code</label>
                        <input type="text" id="postalCode" name="zip" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                        <select class="form-control" id="country" name="country" required>
                            <option value="">Select Country</option>
                            <?php
                            // Get all countries (now flat)
                            $allCountries = $GLOBALS['countries'];

                            // Get country from URL parameter first, then from detected country
                            $selectedCountry = $_GET['country'] ?? '';
                            if (empty($selectedCountry) && isset($detectedCountry)) {
                                $selectedCountry = $detectedCountry;
                            }

                            foreach ($allCountries as $code => $name) {
                                $selected = ($selectedCountry === $code) ? 'selected' : '';
                                echo "<option value='$code' $selected>$name</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="billing-same" checked>
                    <label for="billing-same">Billing address is the same as shipping</label>
                </div>

                <div class="section-divider"></div>

                <h2 class="section-title"><span class="section-number">3.</span> Shipping Method</h2>
                <div class="shipping-option">
                    <input type="radio" id="tracked" name="shipping" checked>
                    <label for="tracked" class="shipping-label">Tracked delivery (Shipped within 48h)</label>
                    <span class="free-badge">Free</span>
                </div>

                <div class="section-divider"></div>

                <h2 class="section-title"><span class="section-number">4.</span> Payment</h2>
                <p class="payment-info">All transactions are secure and encrypted</p>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">💳</span>
                        <span style="font-weight: 600; font-size: 14px;">Credit Card</span>
                    </div>
                    <div class="card-icons">
                        <div class="card-icon visa">VISA</div>
                        <div class="card-icon mastercard">MC</div>
                        <div class="card-icon amex">AMEX</div>
                        <div class="card-icon discover">DISC</div>
                    </div>
                </div>

                <div class="payment-form">
                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <div id="card-number-element" class="form-input"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <div id="card-expiry-element" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <div id="card-cvc-element" class="form-input"></div>
                        </div>
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>

                <?php include 'templates/form/PhysicalProduct/payment_form_bottom.php'; ?>

                <div class="security-badge">
                    <span class="shield-icon">🛡️</span>
                    <span>SSL-secured transaction</span>
                </div>
            </div>

            <div class="summary-section">
                <h2 class="order-header">Order Summary</h2>

                <div class="product-item">
                    <span class="product-name">Product name</span>
                    <span class="product-price">$9.99</span>
                </div>

                <div class="promo-section">
                    <div class="promo-row">
                        <input type="text" class="promo-input" placeholder="Promo Code">
                        <button class="apply-button">Apply</button>
                    </div>
                </div>

                <div class="cost-breakdown">
                    <div class="cost-row">
                        <span class="cost-label">Subtotal</span>
                        <span class="cost-value">$9.99</span>
                    </div>
                    <div class="cost-row">
                        <span class="cost-label">Shipping</span>
                        <span class="cost-value">$0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Total</span>
                        <span>$9.99</span>
                    </div>
                </div>

                <div class="why-choose">
                    <h3 class="why-choose-title">Why Choose Us</h3>

                    <div class="trust-badge">
                        <div class="badge-icon">
                            <svg width="50" height="50" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="23" fill="#FFF5E6" stroke="#FFB84D" stroke-width="2" />
                                <path d="M25 12 L30 22 L40 23 L32 31 L34 41 L25 35 L16 41 L18 31 L10 23 L20 22 Z"
                                    fill="#FFB84D" />
                            </svg>
                        </div>
                        <div class="badge-content">
                            <div class="badge-title">15 years of experience serving your home</div>
                            <div class="badge-description">We are committed to offering you unbeatable prices every day.
                            </div>
                        </div>
                    </div>

                    <div class="trust-badge">
                        <div class="badge-icon">
                            <svg width="50" height="50" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="23" fill="#E8F5E9" stroke="#4CAF50" stroke-width="2" />
                                <text x="25" y="32" text-anchor="middle" font-size="24" fill="#4CAF50"
                                    font-weight="bold">€</text>
                            </svg>
                        </div>
                        <div class="badge-content">
                            <div class="badge-title">30-Day Money-Back Guarantee</div>
                            <div class="badge-description">If you're not satisfied with your products, we'll refund you
                                in full—no questions asked.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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