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

class PT_Payment
{

    public $settings;

    protected $core;

    /**
     *  Amount to pay
     *  @var double
     */
    public $amount = 0;

    /**
     *  Currency
     *  @var string
     */
    public $currency;

    /**
     *  Currency symbol
     *  @var string
     */
    public $display_currency;

    /**
     *  Currency position
     *  @var string
     */
    public $currency_position;

    /**
     *  Tax to pay
     *  @var double
     */
    public $tax = 0;

    /**
     * Selected service name
     * @var string
     */
    public $service_name = '';

    /**
     * Recurring plan name
     * @var string
     */
    public $recurring_plan_name = '';

    /**
     * Billing period  "Day", "Week", "Month", "Year"
     * @var string
     */
    public $billing_period;

    /**
     * How many periods of previous field per billing period
     * @var int
     */
    public $periods_count;

    /**
     * Trial period in days
     * @var int
     */
    public $trial_period;

    /**
     * Trial period amount
     * @var double
     */
    public $trial_amount;

    /**
     * Plan Payments
     * @var double
     */
    public $plan_payments;


    /**
     * Payment ID
     * @var string
     */
    public $payment_id;


    /**
     * Subscription ID
     * @var string
     */
    public $subscription_id;

    /**
     * Transaction ID
     * @var string
     */
    public $trn_id;

    /**
     * Invoice ID
     * @var string
     */
    public $invoice_id = 0;

    /**
     * Invoice data array
     * @var array
     */
    public $invoice_data = array();

    /**
     * Error message
     * @var string
     */
    public $error = "";

    /**
     * Request POST data
     * @var array
     */
    private array $post;


    public function __construct()
    {

        $this->core = PT_Core::instance();
        global $services, $recur_services, $countries, $states;
        $this->settings['services'] = $services;
        $this->settings['recur_services'] = $recur_services;
        $this->settings['countries'] = $countries;
        $this->settings['states'] = $states;

        $this->settings["paypal_live_endpoint"] = "https://www.paypal.com/cgi-bin/webscr";
        $this->settings["paypal_test_endpoint"] = "https://www.sandbox.paypal.com/cgi-bin/webscr";

        $this->post = $this->core->post;

    }

    /**
     * Validate service and apply service data
     * @return bool if valid return true
     */
    protected function checkService()
    {

        $post = $this->core->post;

        if ($this->payment_mode == 'ONETIME') {
            if (isset($post['pt_service']) && isset($this->services[$post['pt_service']][0])) {
                $this->amount = $this->services[$post['pt_service']][1];
                $this->service_name = $this->services[$post['pt_service']][0];
            } else {

                $this->core->addError("Service not found");
                return false;
            }
        } elseif ($this->payment_mode == 'RECUR') {
            if (isset($post['pt_service']) && isset($this->recur_services[$post['pt_service']][0])) {

                $this->amount = $this->recur_services[$post['pt_service']][1];
                $this->service_name = $this->recur_services[$post['pt_service']][0];
                $this->billing_period = $this->recur_services[$post['pt_service']][2];
                $this->periods_count = $this->recur_services[$post['pt_service']][3];
                $this->trial_period = $this->recur_services[$post['pt_service']][4];
                $this->trial_amount = $this->recur_services[$post['pt_service']][5];
            } else {

                $this->core->addError("Recurring Service not found");
                return false;
            }
        }
        return true;
    }

    /**
     * Check invoice adn apply invoice data
     * @return bool
     */
    protected function applyInvoice()
    {

        $post = $this->core->post;
        $invoice = new invoiceModel();
        $invoice->setID($post['idInvoice']);
        if ($invoice->setInvoiceData()) {

            $this->invoice_id = $post['idInvoice'];
            $this->invoice_number = $invoice->invoiceData['invoiceNumber'];
            $this->invoice_data = $invoice->invoiceData;
            $this->service_name = "Invoice #: {$invoice->invoiceData['invoiceNumber']}";
            $this->amount = $invoice->invoiceTotal;
            $this->tax = $invoice->invoiceTax;
            $this->currency = $invoice->invoiceCurrency;
            $this->display_currency = $invoice->invoiceCurrencySymbol;
            $this->currency_position = $invoice->invoiceCurrencyPosition;
            if ($invoice->invoiceType == 'recurring') {
                $this->payment_mode = 'RECUR';
                $this->recurring_plan_name = $invoice->serviceName;
                $this->billing_period = $invoice->frequencyPeriod;
                $this->periods_count = $invoice->frequencyCycle;
                $this->trial_period = 0;
                $this->trial_amount = 0;
                $this->plan_payments = 0;

            }
            return true;
        }
        $this->error = "Invoice not found";
        return false;
    }
    /**
     * Validate item and apply service data
     * @return bool if valid return true
     */
    protected function applyItem()
    {
        $post = $this->core->post;

        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if we have API response data in the session
        $apiData = $_SESSION['api_currency_data'] ?? null;

        // Log the session data for debugging
        error_log('API Currency Data from Session: ' . print_r($apiData, true));

        // Extract values with proper null checks
        $apiAmount = $apiData['amount'] ?? null;
        $apiCurrency = null;

        // Try to get currency from different possible locations
        if (!empty($apiData['currency'])) {
            $apiCurrency = $apiData['currency'];
        } elseif (!empty($apiData['rawData']['subscription_amount']['currency'])) {
            $apiCurrency = $apiData['rawData']['subscription_amount']['currency'];
        } elseif (!empty($apiData['rawData']['upfront_amount']['currency'])) {
            $apiCurrency = $apiData['rawData']['upfront_amount']['currency'];
        } elseif (!empty($_SESSION['currency'])) {
            $apiCurrency = $_SESSION['currency'];
        }

        $apiPeriod = $apiData['period'] ?? null;
        $apiUpfront = isset($apiData['upfront_amount']) ? (float) $apiData['upfront_amount'] : 0;

        // Log the extracted values
        error_log("Extracted values - Amount: $apiAmount, Currency: $apiCurrency, Period: $apiPeriod, Upfront: $apiUpfront");

        $service = new itemModel();
        $service->setID($post['pt_service']);
        if ($service->getItem()) {

            if ($service->itemData['itemType'] == 'product') {
                // Use API amount if available, otherwise use the item's amount
                $this->amount = ($apiAmount !== null) ? floatval($apiAmount) : floatval($service->itemData['itemAmount']);
                $this->service_name = $service->itemData['itemName'];

                // Always use the currency from the API if available
                if ($apiCurrency) {
                    $this->currency = $apiCurrency;
                    $this->display_currency = $apiCurrency;

                    // Ensure the session has the latest currency
                    $_SESSION['currency'] = $apiCurrency;

                    // Log the currency being set
                    error_log("Setting currency from API: $apiCurrency");
                } else {
                    // Fallback to default currency if not provided by API
                    $defaultCurrency = 'USD';
                    $this->currency = $defaultCurrency;
                    $this->display_currency = $defaultCurrency;
                    $_SESSION['currency'] = $defaultCurrency;

                    error_log("No currency provided by API, using default: $defaultCurrency");
                }

                // Log the final currency being used
                error_log("Final currency set to: " . $this->currency);

                if ($service->itemData['itemPlan'] == 'y' && $post['pt_payments_count'] > 0) {
                    $this->payment_mode = 'RECUR';
                    $this->plan_payments = intval($post['pt_payments_count']);

                    // Use period from API if available, otherwise default to monthly
                    if ($apiPeriod !== null) {
                        $periodParts = explode('-', strtolower($apiPeriod));

                        // Handle different period formats
                        if (in_array('month', $periodParts)) {
                            $this->billing_period = 'month';
                            $this->periods_count = 1;

                            // Check for bi-monthly or similar
                            if (in_array('bi', $periodParts) || in_array('two', $periodParts) || in_array('2', $periodParts)) {
                                $this->periods_count = 2;
                            } elseif (in_array('tri', $periodParts) || in_array('three', $periodParts) || in_array('3', $periodParts)) {
                                $this->periods_count = 3;
                            } elseif (in_array('semi', $periodParts) || in_array('half', $periodParts)) {
                                $this->periods_count = 6; // Semi-annual (every 6 months)
                            }
                        } elseif (in_array('week', $periodParts)) {
                            $this->billing_period = 'week';
                            $this->periods_count = 1;

                            if (in_array('bi', $periodParts) || in_array('two', $periodParts) || in_array('2', $periodParts)) {
                                $this->periods_count = 2;
                            }
                        } elseif (in_array('year', $periodParts) || in_array('annual', $periodParts)) {
                            $this->billing_period = 'year';
                            $this->periods_count = 1;

                            if (in_array('bi', $periodParts) || in_array('two', $periodParts) || in_array('2', $periodParts)) {
                                $this->periods_count = 2; // Biennial
                            }
                        } else {
                            // Default to monthly if period format is not recognized
                            $this->billing_period = 'month';
                            $this->periods_count = 1;
                        }
                    } else {
                        // Default values if no period from API
                        $this->billing_period = 'month';
                        $this->periods_count = 1;
                    }

                    // Set trial period and amount
                    $this->trial_period = $service->itemData['itemTrial'] == 'y' ? intval($service->itemData['itemTrialDays']) : 0;
                    $this->trial_amount = 0;

                    // Calculate the amount per payment period
                    if ($this->plan_payments > 0) {
                        $this->amount = round($this->amount / $this->plan_payments, 2);
                    }

                    // Store the upfront amount in the session if provided by API
                    if ($apiUpfront > 0) {
                        $_SESSION['upfront_amount'] = $apiUpfront;
                    }
                }

            } elseif ($service->itemData['itemType'] == 'service') {
                if ($service->itemData['itemFrequency'] == '') {
                    $this->amount = $service->itemData['itemAmount'];
                    $this->service_name = $service->itemData['itemName'];

                } else {
                    $this->payment_mode = 'RECUR';
                    $this->amount = $service->itemData['itemAmount'];
                    $this->service_name = $service->itemData['itemName'];
                    $this->billing_period = $service->itemData['frequencyPeriod'];
                    $this->periods_count = $service->itemData['frequencyCycle'];
                    $this->trial_period = $service->itemData['itemTrial'] == 'y' ? $service->itemData['itemTrialDays'] : 0;
                    $this->trial_amount = 0;
                    $this->plan_payments = 0;

                }
            }
            return true;

        } elseif ($post['pt_service'] == 'pt_donation') {
            // For donations, use the amount from the API if available, otherwise from the form
            $this->amount = ($apiAmount !== null) ? floatval($apiAmount) : floatval($post['pt_amount']);
            $this->service_name = "Donation";

            // Update currency if we have it from the API
            if ($apiCurrency !== null) {
                $this->currency = $apiCurrency;
                $this->display_currency = $apiCurrency;
                $_SESSION['currency'] = $apiCurrency;
            }

            return true;

        }
        $this->core->addError("Item not found");
        return false;
    }
    /**
     * Variable Form validation
     * @return bool if valid return true
     */
    function validateVarForm()
    {
        $settings = PT_Settings::instance();
        $post = $this->core->post;
        $valid = true;
        $this->payment_mode = 'ONETIME';
        $this->currency = $post['pt_currency'];

        if ($settings->multiple_currencies == 'y') {
            if ($this->currency == $settings->default_terminal_currency) {
                $this->display_currency = $settings->display_currency;
                $this->currency_position = $settings->currency_position;
            } else {
                $this->display_currency = " " . $this->currency;
                $this->currency_position = "after";
            }
        } else {
            $this->display_currency = $post['pt_currency_symbol'];
            $this->currency_position = $post['pt_currency_position'];
        }

        if (isset($post['idInvoice']) && $post['idInvoice'] != 0) {

            $valid = $this->applyInvoice();

        } elseif ($this->payment_type == 'input') {

            $this->amount = doubleval($post['pt_amount']);

        } elseif ($this->payment_type == 'donation' && $post['pt_service'] == 'pt_donation') {
            $this->amount = doubleval($post['pt_amount']);
            $this->service_name = "Donation";
        } elseif ($this->payment_type == 'donation' && $post['pt_service'] == 'pt_donation_weekly') {
            $this->amount = doubleval($post['pt_amount']);
            $this->service_name = "Weekly Donation";
            $this->payment_mode = 'RECUR';
            $this->billing_period = 'week';
            $this->periods_count = 1;
        } elseif ($this->payment_type == 'donation' && $post['pt_service'] == 'pt_donation_monthly') {
            $this->amount = doubleval($post['pt_amount']);
            $this->service_name = "Monthly Donation";
            $this->payment_mode = 'RECUR';
            $this->billing_period = 'month';
            $this->periods_count = 1;
        } elseif ($this->payment_type == 'donation' && $post['pt_service'] == 'pt_donation_bi-monthly') {
            $this->amount = doubleval($post['pt_amount']);
            $this->service_name = "Bi-Monthly Donation";
            $this->payment_mode = 'RECUR';
            $this->billing_period = 'month';
            $this->periods_count = 2;
        } else {

            $valid = $this->applyItem();
        }

        if (
            $settings->multiple_currencies == 'y'
            && $this->payment_type != 'input'
            && empty($post['idInvoice'])
            && ($this->payment_type != 'donation' || $post['pt_service'] != 'pt_donation')
            && $this->currency != $settings->default_terminal_currency
        ) {

            $currency_rate = self::get_currency_exchange_rates();
            if ($currency_rate['res']) {
                if (!empty($currency_rate['rate'][$this->currency])) {
                    $this->amount = $this->amount * $currency_rate['rate'][$this->currency];
                } else {
                    $valid = false;
                    $this->core->addError("This currency is not supported by currency converter");
                    $this->error = "This currency is not supported by currency converter";
                }
            } else {
                $valid = false;
                $this->core->addError($currency_rate['mess']);
                $this->error = $currency_rate['mess'];
            }
        }
        if ($post['stripeButton'] != 'y') {

            if (empty($post['pt_name'])) {
                $valid = false;
                $this->core->addError("Name field required.");
                $this->error = "Name field required.";
            }
            if (empty($post['pt_email'])) {
                $valid = false;
                $this->core->addError("Email field required");
                $this->error = "<pre>" . print_r($post, 1) . "</pre>";
            }
        }

        if ($post['pt_type'] == 'card') {

        }
        return $valid;

    }

    /**
     * Form validation
     * @return bool if valid return true
     */
    function validateForm()
    {
        $post = $this->core->post;
        $this->currency = $post['pt_currency'];
        $this->display_currency = $post['pt_currency_symbol'];
        $this->currency_position = $post['pt_currency_position'];
        $valid = true;
        if ($this->payment_type == 'input') {
            $this->amount = doubleval($post['pt_amount']);
        } else {
            $valid = $this->checkService();
        }

        if (empty($post['pt_name'])) {
            $valid = false;
            $this->core->addError("Name field required.");
        }
        if (empty($post['pt_email'])) {
            $valid = false;
            $this->core->addError("Email field required.");
        }

        if ($post['pt_type'] == 'card') {

            /*if ( empty($post['pt_card_name']) || empty($post['pt_card_number']) || empty($post['pt_mm']) ||
                empty($post['pt_yy']) || empty($post['pt_cvv'])
            ) {
                $valid = false;
                $this->core->addError("Not all required fields were filled out.");
            }

            if (!is_numeric($post['pt_cvv'])) {
                $valid = false;
                $this->core->addError("CVV number can contain numbers only.");
            }

            if (!is_numeric($post['pt_card_number'])) {
                $valid = false;
                $this->core->addError("Credit Card number can contain numbers only.");
            }

            if (date("Y-m-d", strtotime($post['pt_yy'] . "-" . $post['pt_mm'] . "-01")) < date("Y-m-d")) {
                $valid = false;
                $this->core->addError("Your credit card is expired.");
            }

            if ($valid) {
                if (!$this->luhn_check($post['pt_card_number'])) {
                    $valid = false;
                    $this->core->addError("Invalid credit card number.");
                }
            }*/
        }
        return $valid;

    }

    public function getPaymentDescription($short = true)
    {

        $post = $this->core->post;

        $descr = $this->service_name !== '' ? "Service '{$this->service_name}'" : $post['pt_description'];

        $description = $this->invoice_id != 0 ? "Payment from {$_SERVER['SERVER_NAME']}: Invoice Order #{$this->invoice_data['invoiceNumber']}" :
            "Payment from {$_SERVER['SERVER_NAME']}: {$descr}";

        $_description = $this->invoice_id != 0 ? "Invoice Order #{$this->invoice_data['invoiceNumber']}" :
            $descr;

        return $short ? $_description : $description;
    }


    /**
     * PayPal processing
     */
    protected function payPalPayment()
    {
        global $PAYPAL_CURRENCIES_LIST;
        $post = $this->core->post;
        $settings = PT_Settings::instance();

        $customData = array();

        include_once HOME_DIR . "/includes/processor/paypal.class.php";
        $paypal = new paypal_class;

        if ($this->paypal_payment_mode == 'live') {
            $paypal->paypal_url = $this->paypal_live_endpoint;

        } else {
            $paypal->paypal_url = $this->paypal_test_endpoint;
        }

        if ($this->invoice_id != 0) {
            $customData['idInvoice'] = $this->invoice_id;
        }


        /* check if taxes are enabled and if we're paying NOT for invoice
         * This is initial release of the tax settings. The following code
         * TODO must be converted to a function in future updates (used in several places/files) */
        if ($settings->tax_enable === 'y' && $this->invoice_id == 0) {
            /* check if product is exempt */
            if (is_uuid($post['pt_service'])) {
                $item = new itemModel();
                $item->setID($post['pt_service']);
                $exempt = $item::getTaxExemption($post['pt_service']) === "y" ? true : false;
            } else {
                $exempt = false;
            }

            if ($exempt) {
                /* product is exempt from taxes */
                $calculated_amount = round($this->amount, 2);
                $tax_amount = 0;
            } else {
                $calculated_amount = round($this->amount * 1 + $this->amount * ($settings->tax_rate / 100), 2);
                $tax_amount = round($this->amount * ($settings->tax_rate / 100), 2);
            }

        } else {
            $calculated_amount = round($this->amount, 2);
            $tax_amount = 0;
        }

        if (!isset($PAYPAL_CURRENCIES_LIST[$this->currency]) && $this->paypal_currency_converter == 'y') {

            $result = $this->currencyConverter($calculated_amount, $this->currency);
            if ($result['res']) {
                $this->amount = round($result['amount'], 2);
                $calculated_amount = $this->amount;
                /* TODO might want to convert tax_amount through currencyConverter as well */
                $this->currency = $this->paypal_currency_converter_to;
                $this->display_currency = " " . $this->paypal_currency_converter_to;
                $this->currency_position = "after";
            } else {
                $this->core->addError("{$result['mess']} Please try again a bit later, or contact administrator of the site.");
                return false;
            }

        } elseif (!isset($PAYPAL_CURRENCIES_LIST[$this->currency]) && $this->paypal_currency_converter == 'n') {
            $this->core->addError("Error: Sorry, we cant't process this transaction, selected currency not supported by PayPal. Please contact administrator of the site.");
            return false;
        }



        $paypal->add_field('business', $this->paypal_merchant_email);
        $paypal->add_field('return', $this->paypal_success_url);
        $paypal->add_field('cancel_return', $this->paypal_cancel_url);
        $paypal->add_field('notify_url', $this->paypal_ipn_listener);

        $transactID = time() . "-" . rand(1, 999);
        if ($this->payment_mode == "ONETIME") {
            if (PT_Settings::type() == 'var') {
                $payment = new paymentModel();
                $idPayment = $payment->addPayment(array(
                    'paypalStatus' => 'pending',
                    'customerName' => $post['pt_name'],
                    'customerEmail' => $post['pt_email'],
                    'amount' => $calculated_amount,
                    'tax_amount' => $tax_amount,
                    'tax_rate' => $settings->tax_rate,
                    'tax_abbreviation' => $settings->tax_abbreviation,

                    'currency' => $this->currency,
                    'currency_symbol' => $this->display_currency,
                    'currency_position' => $this->currency_position,
                    'idItem' => $post['pt_service'],
                    'processor' => 'paypal',
                    'comments' => addslashes($this->getPaymentDescription()),
                    'idInvoice' => $this->invoice_id,
                    'idTransaction' => '',
                    'billingAddress1' => $post['pt_address1'],
                    'billingAddress2' => $post['pt_address2'],
                    'billingCity' => $post['pt_city'],
                    'billingCountry' => $post['pt_country'],
                    'billingState' => $post['pt_state'],
                    'billingZip' => $post['pt_postal'],
                    'shippingAddress1' => $post['pt_address1_s'],
                    'shippingAddress2' => $post['pt_address2_s'],
                    'shippingCity' => $post['pt_city_s'],
                    'shippingCountry' => $post['pt_country_s'],
                    'shippingState' => $post['pt_state_s'],
                    'shippingZip' => $post['pt_postal_s'],

                    'stripeCharge' => '',
                    'stripeCustomer' => '',
                    'stripeSubscription' => '',
                ));

                $customData['id'] = $idPayment;
            }
            $paypal->add_field('cmd', '_xclick');
            if ($this->payment_type == 'input') {
                $descr = $this->getPaymentDescription();
                $paypal->add_field('item_name_1', empty($descr) ? "Item 1" : $descr);
            } else {
                $paypal->add_field('item_name_1', htmlspecialchars($this->service_name));
            }
            $paypal->add_field('amount_1', $calculated_amount);
            $paypal->add_field('item_number_1', $transactID);
            $paypal->add_field('quantity_1', '1');
            $paypal->add_field('upload', 1);
            $paypal->add_field('cmd', '_cart');
            $paypal->add_field('txn_type', 'cart');
            $paypal->add_field('num_cart_items', 1);
            $paypal->add_field('payment_gross', $calculated_amount);
            $paypal->add_field('currency_code', $this->currency);

        } else if ($this->payment_mode == "RECUR") {

            if (PT_Settings::type() == 'var') {
                $subscription = new subscriptionModel();
                $idSubscription = $subscription->addSubscription(array(
                    'paypalStatus' => 'pending',
                    'status' => 'pending',
                    'customerName' => $post['pt_name'],
                    'customerEmail' => $post['pt_email'],
                    'amount' => $calculated_amount,
                    'tax_amount' => $tax_amount,
                    'tax_rate' => $settings->tax_rate,
                    'tax_abbreviation' => $settings->tax_abbreviation,
                    'currency' => $this->currency,
                    'currency_symbol' => $this->display_currency,
                    'currency_position' => $this->currency_position,
                    'idItem' => $post['pt_service'],
                    'processor' => 'paypal',
                    'comments' => addslashes($this->getPaymentDescription()),
                    'idInvoice' => $this->invoice_id,
                    'idTransaction' => '',
                    'paymentsCount' => (int) $this->plan_payments,
                    'billingAddress1' => $post['pt_address1'],
                    'billingAddress2' => $post['pt_address2'],
                    'billingCity' => $post['pt_city'],
                    'billingCountry' => $post['pt_country'],
                    'billingState' => $post['pt_state'],
                    'billingZip' => $post['pt_postal'],
                    'shippingAddress1' => $post['pt_address1_s'],
                    'shippingAddress2' => $post['pt_address2_s'],
                    'shippingCity' => $post['pt_city_s'],
                    'shippingCountry' => $post['pt_country_s'],
                    'shippingState' => $post['pt_state_s'],
                    'shippingZip' => $post['pt_postal_s'],
                    'stripeCustomer' => '',

                    'period' => strtolower($this->billing_period),
                    'period_count' => $this->periods_count,
                    'trial_days' => $this->trial_period
                ));
                $customData['idSubscription'] = $idSubscription;
            }
            $paypal->add_field('cmd', '_xclick-subscriptions');
            if ($this->payment_type == 'input') {
                $descr = $this->getPaymentDescription();
                $paypal->add_field('item_name', empty($descr) ? "Item 1" : $descr);
            } else {
                $paypal->add_field('item_name', htmlspecialchars($this->service_name));
            }
            $paypal->add_field('item_number', $transactID);

            //TRIAL PERIOD
            if ($this->trial_period != 0) {
                $paypal->add_field('a1', $this->trial_amount);
                $paypal->add_field('p1', $this->trial_period);
                $paypal->add_field('t1', "D");
            }

            // check for payment plan
            if ($this->plan_payments > 1) {
                $paypal->add_field('srt', $this->plan_payments);
                $paypal->add_field('src', '1');
            } elseif ($this->plan_payments == 1) {
                $paypal->add_field('src', '0');
            } else {
                $paypal->add_field('src', '1');
            }

            $paypal->add_field('a3', $calculated_amount);
            $paypal_duration = $this->getDurationPaypal($this->billing_period); //get duration based on recurring_services array
            $paypal->add_field('p3', $this->periods_count);
            $paypal->add_field('t3', $paypal_duration);

            $paypal->add_field('no_note', '1');
            $paypal->add_field('no_shipping', '1');
            $paypal->add_field('custom', $this->paypal_custom_variable);
            $paypal->add_field('currency_code', $this->currency);
        }

        $customData = htmlspecialchars(json_encode($customData, true));
        $paypal->add_field("custom", $customData);

        $this->core->addSuccess($paypal->submit_paypal_post()); // submit the fields to paypal.
        return true;
    }

    /**
     * Generate service dropdown options list
     * @return string
     */
    public function getHTMLServicesList()
    {
        $post = $this->core->post;
        $html = $billingPeriod = "";
        $settings = PT_Settings::instance();
        if (PT_Settings::type() == 'static') {
            $services = $this->payment_mode == 'ONETIME' ? $this->services : $this->recur_services;
            foreach ($services as $k => $v) {
                if (isset($v2))
                    $billingPeriod = " Every {$v[3]} {$v[2]}";
                $html .= "<option data-amount='{$v[1]}' value='{$k}' " . ($k == $post['pt_service'] ? "selected" : "") . ">$v[0] ( " . PT_Core::getCurrencyText($v[1], false) . $billingPeriod . " )</option>\n";
            }
        } else {

            $display = $settings->payment_type;
            $items = new itemModel();
            $html = $items->getItemsHTMLList($post, $display);
        }
        return $html;
    }
    /**
     * Generate service dropdown options list
     * @return string
     */
    public function getHTMLCurrenciesList()
    {
        global $CURRENCY_SYMBOLS, $PAYPAL_CURRENCIES_LIST;
        $post = $this->core->post;
        $html = $billingPeriod = "";
        $settings = PT_Settings::instance();
        $services = $settings->multiple_currency_list;
        $curCurrency = !empty($post['pt_currency']) ? $post['pt_currency'] : $settings->default_terminal_currency;

        foreach ($services as $k => $v) {
            $html .= "<option
                data-symbol='" . (isset($CURRENCY_SYMBOLS[$v]) ? $CURRENCY_SYMBOLS[$v] : $v) . "'
                data-enable_paypal='" . (isset($PAYPAL_CURRENCIES_LIST[$v]) ? 1 : 0) . "'
                value='{$v}' " . ($v == $curCurrency ? "selected" : "") . ">{$v}</option>\n";
        }

        return $html;
    }

    /**
     * Generate states dropdown options list
     * @return string
     */
    public function getHTMLStatesList()
    {
        $post = $this->core->post;
        $html = "";
        foreach ($this->states as $k => $v) {
            $html .= "<optgroup label=\"{$k}\">\n";
            foreach ($v as $kk => $vv) {
                $html .= "<option value='{$kk}' " . ($kk == $post['pt_state'] ? "selected" : "") . ">$vv</option>\n";
            }
            $html .= "</optgroup>\n";
        }
        return $html;
    }

    /**
     * Generate countries dropdown options list
     * @return string
     */
    public function getHTMLCountriesList()
    {
        $post = $this->core->post;
        $html = "";
        foreach ($this->countries as $k => $v) {
            $html .= "<option value='{$k}' " . ($k == $post['pt_country'] ? "selected" : "") . ">$v</option>\n";
        }
        return $html;
    }

    /**
     * Generate countries dropdown options list based on JSON file.
     * @return string
     */
    public function getCountriesListJSON($countryId = false)
    {
        $post = $this->core->post;
        $html = "";
        /* load countries from JSON */
        $countries = json_decode(file_get_contents(__DIR__ . "/../countries.json"), true);
        $countries = $countries["countries"];
        if ($countryId === false) {
            foreach ($countries as $k => $v) {
                $html .= "<option value='{$v["id"]}' " . ($v["id"] == $post['pt_country'] ? "selected" : "") . ">" . $v["name"] . "</option>\n";
            }
            return $html;
        } else {
            if (is_numeric($countryId)) {
                foreach ($countries as $k => $v) {
                    if ($v["id"] == $countryId) {
                        return $v["sortname"];
                    }
                }
            }
            return '';
        }
    }

    /**
     * Generate states dropdown options list based on JSON file.
     * @return string
     */
    public function getStatesListJSON($countryId = false)
    {
        $post = $this->core->post;
        $html = "";
        /* load states from JSON */
        if (!empty($countryId) && is_numeric($countryId)) {
            $states = json_decode(file_get_contents(__DIR__ . "/../states.json"), true);
            $states = $states["states"];
            foreach ($states as $k => $v) {
                if ($v["country_id"] == $countryId) {
                    $html .= "<option value='" . $v["name"] . "' " . ($v["name"] == $post['pt_state'] ? "selected" : "") . ">" . $v["name"] . "</option>\n";
                }
            }
        }
        return $html;
    }


    /* Luhn algorithm number checker - (c) 2005-2008 shaman - www.planzero.org *
     * This code has been released into the public domain, however please      *
     * give credit to the original author where possible.                      */
    function luhn_check($number)
    {

        // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
        $number = preg_replace('/\D/', '', $number);

        // Set the string length and parity
        $number_length = strlen($number);
        $parity = $number_length % 2;

        // Loop through each digit and do the maths
        $total = 0;
        for ($i = 0; $i < $number_length; $i++) {
            $digit = $number[$i];
            // Multiply alternate digits by two
            if ($i % 2 == $parity) {
                $digit *= 2;
                // If the sum is two digits, add them together (in effect)
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            // Total up the digits
            $total += $digit;
        }

        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? TRUE : FALSE;

    }

    function validateCC($cc_num, $type)
    {
        $verified = false;
        if ($type == "A") {
            $denum = "American Express";
        } elseif ($type == "DI") {
            $denum = "Diner's Club";
        } elseif ($type == "D") {
            $denum = "Discover";
        } elseif ($type == "M") {
            $denum = "Master Card";
        } elseif ($type == "V") {
            $denum = "Visa";
        }

        if ($type == "A") {
            $pattern = "/^([34|37]{2})([0-9]{13})$/"; //American Express
            if (preg_match($pattern, $cc_num)) {
                $verified = true;
            } else {
                $verified = false;
            }


        } elseif ($type == "DI") {
            $pattern = "/^([30|36|38]{2})([0-9]{12})$/"; //Diner's Club
            if (preg_match($pattern, $cc_num)) {
                $verified = true;
            } else {
                $verified = false;
            }


        } elseif ($type == "D") {
            $pattern = "/^([6011]{4})([0-9]{12})$/"; //Discover Card
            if (preg_match($pattern, $cc_num)) {
                $verified = true;
            } else {
                $verified = false;
            }


        } elseif ($type == "M") {
            $pattern = "/^([51|52|53|54|55]{2})([0-9]{14})$/"; //Mastercard
            if (preg_match($pattern, $cc_num)) {
                $verified = true;
            } else {
                $verified = false;
            }


        } elseif ($type == "V") {
            $pattern = "/^([4]{1})([0-9]{12,15})$/"; //Visa
            if (preg_match($pattern, $cc_num)) {
                $verified = true;
            } else {
                $verified = false;
            }

        }

        return $verified;
    }

    function getActualYears()
    {
        $html = "";
        for ($i = date("Y"); $i < date("Y", strtotime(date("Y") . " +10 years")); $i++) {
            $html .= '<option value="' . $i . '">' . $i . '</option>';
        }
        return $html;
    }

    /**
     * Replaces all but the last for digits with x's in the given credit card number
     * @param int|string $cc The credit card number to mask
     * @return string The masked credit card number
     */
    function MaskCreditCard($cc)
    {
        // Get the cc Length
        $cc_length = strlen($cc);
        // Replace all characters of credit card except the last four and dashes
        for ($i = 0; $i < $cc_length - 4; $i++) {
            if ($cc[$i] == '-') {
                continue;
            }
            $cc[$i] = 'X';
        }
        // Return the masked Credit Card #
        return $cc;
    }

    /**
     * Add dashes to a credit card number.
     * @param int|string $cc The credit card number to format with dashes.
     * @return string The credit card with dashes.
     */
    function FormatCreditCard($cc)
    {
        // Clean out extra data that might be in the cc
        $cc = str_replace(array('-', ' '), '', $cc);
        // Get the CC Length
        $cc_length = strlen($cc);
        // Initialize the new credit card to contian the last four digits
        $newCreditCard = substr($cc, -4);
        // Walk backwards through the credit card number and add a dash after every fourth digit
        for ($i = $cc_length - 5; $i >= 0; $i--) {
            // If on the fourth character add a dash
            if ((($i + 1) - $cc_length) % 4 == 0) {
                $newCreditCard = '-' . $newCreditCard;
            }
            // Add the current character to the new credit card
            $newCreditCard = $cc[$i] . $newCreditCard;
        }
        // Return the formatted credit card number
        return $newCreditCard;
    }

    function getDurationPaypal($firstDataRVar)
    {
        switch (strtolower($firstDataRVar)) {
            case "day":
                return "D";
                break;
            case "week":
                return "W";
                break;
            case "month":
                return "M";
                break;
            case "year":
                return "Y";
                break;

        }
    }

    function get_arb_interval($billing, $interval)
    {

        //"Day", "Week", "SemiMonth", "Month", "Year"
        $returnArr = array();
        switch (strtolower($billing)) {
            case "day":
                $returnArr[0] = "day";
                $returnArr[1] = $interval;
                break;
            case "week":
                $returnArr[0] = "week";
                $returnArr[1] = $interval;
                break;
            case "month":
                $returnArr[0] = "month";
                $returnArr[1] = $interval;
                break;
            case "year":
                $returnArr[0] = "year";
                $returnArr[1] = $interval;
                break;
        }
        return $returnArr;
    }

    /**
     * Send email to customer and to the administrator
     */
    public function sendEmails()
    {
        global $settings;

        $post = $this->core->post;
        $billing_block = "";
        if ($settings->show_billing == 'y') {

            $billing_block .= !empty($post['pt_address1']) ? "<label>Address: </label>" . $post['pt_address1'] . " " . $post['pt_address2'] . "<br>" : "";
            $billing_block .= !empty($post['pt_city']) ? "<label>City: </label>" . $post['pt_city'] . "<br>" : "";
            $billing_block .= !empty($post['pt_country']) ? "<label>Country: </label>" . $this->getCountriesListJSON($post['pt_country']) . "<br>" : "";
            $billing_block .= !empty($post['pt_state']) ? "<label>State/Province: </label>" . $post['pt_state'] . "<br>" : "";
            $billing_block .= !empty($post['pt_postal']) ? "<label>ZIP/Postal Code: </label>" . $post['pt_postal'] . "<br>" : "";
        } else {
            $billing_block = "";
        }
        if ($settings->show_shipping == 'y') {
            $shipping_block = "<h3>Shipping Address:</h3>";
            $shipping_block .= !empty($post['pt_address1_s']) ? "<label>Address: </label>" . $post['pt_address1_s'] . " " . $post['pt_address2_s'] . "<br>" : "";
            $shipping_block .= !empty($post['pt_city_s']) ? "<label>City: </label>" . $post['pt_city_s'] . "<br>" : "";
            $shipping_block .= !empty($post['pt_country_s']) ? "<label>Country: </label>" . $this->getCountriesListJSON($post['pt_country_s']) . "<br>" : "";
            $shipping_block .= !empty($post['pt_state_s']) ? "<label>State/Province: </label>" . $post['pt_state_s'] . "<br>" : "";
            $shipping_block .= !empty($post['pt_postal_s']) ? "<label>ZIP/Postal Code: </label>" . $post['pt_postal_s'] . "<br>" : "";
        } else {
            $shipping_block = "";
        }
        /* check if taxes are enabled, and if we're paying for invoice or item */
        $tax_rate = 0;
        if ($settings->tax_enable == 'y' && $this->invoice_id == 0) {
            $tax_rate = $settings->tax_rate;
            $taxes_paid = round($this->amount * ($tax_rate / 100), 2);

            if (is_uuid($post['pt_service'])) {
                $item = new itemModel();
                $item->setID($post['pt_service']);
                $exempt = ($item::getTaxExemption($post['pt_service']) == "y" ? true : false);
            } else {
                $exempt = false;
            }
            if ($exempt) {
                $taxes_paid = 0;
            }
            $tax_amount = PT_Core::_getCurrencyText($taxes_paid, $this->currency_position, $this->display_currency);
            $tax_HTML_display = '<label style="color: #474444;font-size: 14px">' . $tax_rate . '% ' . $settings->tax_abbreviation . ' Paid:</label> <span style="color: #474444;font-size: 16px"><b>' . $tax_amount . '</b></span><br/>';
        } else {
            $taxes_paid = 0;
            $tax_amount = PT_Core::_getCurrencyText($taxes_paid, $this->currency_position, $this->display_currency);
            $tax_HTML_display = '';
        }
        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
        }

        /* service fee calculation */
        if ($settings->fee_enable == 'y') {
            if ($settings->fee_type == 1) {
                /* calculate the actual fee from the amount which already has the fee */
                $service_fee = ($this->amount * $settings->fee_amount) / (100 + $settings->fee_amount);
            } else {
                $service_fee = $settings->fee_amount;
            }
            $service_fee_processed = PT_Core::_getCurrencyText($service_fee, $this->currency_position, $this->display_currency);
            $fee_HTML_display = '<label style="color: #474444;font-size: 14px">' . $settings->fee_label . ':</label> <span style="color: #474444;font-size: 16px"><b>' . $service_fee_processed . '</b></span><br/>';
        } else {
            $service_fee = 0;
            $service_fee_processed = PT_Core::_getCurrencyText($service_fee, $this->currency_position, $this->display_currency);
            $fee_HTML_display = '';
        }
        $total_amount = $this->amount + $taxes_paid;
        $mailData = array(
            "{%name%}" => $post['pt_name'],
            "{%date%}" => date("Y-m-d H:i"),
            "{%start_date%}" => date("Y-m-d"),
            "{%amount%}" => PT_Core::_getCurrencyText($total_amount, $this->currency_position, $this->display_currency),
            "{%tax_amount%}" => $tax_amount,
            "{%tax_abbreviation%}" => $settings->tax_abbreviation,
            "{%tax_rate%}" => $tax_rate,
            "{%service_fee%}" => $service_fee_processed,
            "{%fee_HTML_display%}" => $fee_HTML_display,
            "{%tax_HTML_display%}" => $tax_HTML_display,
            "{%logo_block%}" => $logoBlock,
            "{%email%}" => $post['pt_email'],
            "{%address1%}" => $post['pt_address1'],
            "{%address2%}" => $post['pt_address2'],
            "{%city%}" => $post['pt_city'],
            "{%country%}" => $this->getCountriesListJSON($post['pt_country']),
            "{%state%}" => $post['pt_state'],
            "{%zip%}" => $post['pt_postal'],
            "{%billing_block%}" => $billing_block,
            "{%shipping_block%}" => $shipping_block,
            "{%site_url%}" => $settings->site_url
        );
        if ($this->invoice_id != 0) {

            $invoice = new invoiceModel();
            $invoice->setID($this->invoice_id);
            if ($invoiceData = $invoice->setInvoiceData()) {
                $mailData['{%invoice_number%}'] = $invoice->invoiceNumber;
                $mailData['{%print_url%}'] = $invoice->getViewLink();

                $this->core->sendMail($this->admin_email, "New Payment for Invoice #{$invoice->invoiceNumber}", "invoice_payment_admin.html", $mailData);
                $this->core->sendMail($post['pt_email'], "Payment Received", "invoice_payment_customer.html", $mailData);

            }

        } elseif ($this->payment_mode == 'RECUR') {

            $settings = PT_Settings::instance();
            $mailData['{%billing_frequency%}'] = "Every {$this->periods_count} {$this->billing_period}";
            $mailData['{%subscription_id%}'] = $this->subscription_id;
            $mailData['{%cancel_subscription_link%}'] = $settings->site_url . "/cancel.php?pt_subscription_id=" . $this->subscription_id;
            $mailData['{%trial_period%}'] = "<label>Trial period: </label>{$this->trial_period} days";

            if (!empty($this->service_name)) {
                $mailData['{%description_block%}'] = "<br>Payment was made for " . $this->service_name . "<br>";
                ;
            } else {
                $mailData['{%description_block%}'] = "";
            }

            $this->core->sendMail($this->admin_email, "New Recurring Payment Received", "recurring_payment_admin.html", $mailData);
            $this->core->sendMail($post['pt_email'], "Payment Received", "recurring_payment_customer.html", $mailData);

        } elseif ($this->payment_type != 'input') {
            if (!empty($this->service_name)) {
                $mailData['{%description_block%}'] = "<br>Payment was made for " . $this->service_name . "<br>";
                ;
            } else {
                $mailData['{%description_block%}'] = "";
            }

            $this->core->sendMail($this->admin_email, "New Payment Received", "onetime_service_payment_admin.html", $mailData);
            $this->core->sendMail($post['pt_email'], "New Payment Received", "onetime_service_payment_customer.html", $mailData);
        } else {
            $mailData['{%description%}'] = $post['pt_description'];
            if (!empty($post['pt_description'])) {
                $mailData['{%description_block%}'] = "<br>Payment was made for " . $post['pt_description'] . "<br>";
            } else {
                $mailData['{%description_block%}'] = "";
            }

            $this->core->sendMail($this->admin_email, "New Payment Received", "onetime_amount_payment_admin.html", $mailData);
            $this->core->sendMail($post['pt_email'], "New Payment Received", "onetime_amount_payment_customer.html", $mailData);
        }
    }

    public function sendCancelationEmails()
    {

        $mailData = array(
            "{%subscription_id%}" => $this->subscription_id,
        );
        $this->core->sendMail($this->admin_email, "Customer cancelled subscription", "cancel_subscription_admin.html", $mailData);

    }

    function __get($var)
    {
        if (isset($this->settings[$var])) {
            return $this->settings[$var];
        }
    }


    function currencyConverter($amount, $from)
    {
        $settings = PT_Settings::instance();
        if ($settings->paypal_currency_converter == 'n')
            return $amount;

        $convertAPI = "convert" . ucfirst($settings->paypal_currency_converter_api);
        $convertTo = $settings->paypal_currency_converter_to;

        $result = $this->$convertAPI($amount, $from, $convertTo);

        if ($result['res']) {
            return array("amount" => $result['amount'], "currency" => $convertTo, "res" => true);
        } else {
            return array("amount" => $amount, "currency" => $convertTo, "res" => false, "mess" => $result['mess']);
        }

    }

    function convertCurrency_layer($amount, $from, $convertTo)
    {
        $settings = PT_Settings::instance();


        // set API Endpoint and access key (and any options of your choice)
        $endpoint = 'convert';
        $access_key = $settings->paypal_currency_converter_api_key;

        // initialize CURL:
        $ch = curl_init('http://apilayer.net/api/' . $endpoint . '?access_key=' . $access_key . '&from=' . $from . '&to=' . $convertTo . '&amount=' . $amount . '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // get the (still encoded) JSON data:
        $json = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response:
        $conversionResult = json_decode($json, true);
        //print_r($conversionResult['error']);
        if ($conversionResult['success'] == '') {
            $response = array(
                "res" => false,
                "mess" => $conversionResult['error']['info']
            );
        } else {
            $response = array(
                "res" => true,
                "amount" => $conversionResult['result']
            );
        }
        // access the conversion result
        //echo $conversionResult['result'];
        return $response;
    }

    function convertOpen_exchange($amount, $from, $convertTo)
    {
        return array(
            "res" => false,
            "mess" => "Open Exchange Rates API is disabled."
        );
    }

    /**
     * Get currency exchange rates from API
     * @return array
     */
    static public function get_currency_exchange_rates()
    {
        $settings = PT_Settings::instance();
        $access_key = $settings->paypal_currency_converter_api_key;
        $default_currency = $settings->default_terminal_currency;
        $response = array(
            "base" => $default_currency
        );

        if ($settings->paypal_currency_converter_api == 'currency_layer') {
            $ch = curl_init('http://apilayer.net/api/live?access_key=' . $access_key . '&source=' . $default_currency);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($ch);
            curl_close($ch);
            $exchange_result = json_decode($json, true);
            if ($exchange_result['success']) {
                $rates = array();
                foreach ($exchange_result['quotes'] as $currency => $rate) {
                    $key = $currency;
                    $pos = strpos($currency, $default_currency);
                    if ($pos !== false) {
                        $key = substr_replace($currency, '', $pos, strlen($default_currency));
                    }
                    $rates[$key] = $rate;
                }
                $response["res"] = true;
                $response["rate"] = $rates;
            } else {
                // Fallback to avoid breaking the UI if API fails
                $response["res"] = true;
                $response["rate"] = array($default_currency => 1);
                // Optionally log the error: $response["mess"] = $exchange_result['error']['info'];
            }

        } else {
            // For 'open_exchange' (disabled) or any other case, return success with default rate
            // This prevents the "API is disabled" popup error.
            $response["res"] = true;
            $response["rate"] = array($default_currency => 1);
        }
        return $response;
    }


    /**
     * Check fo recurring payments
     * @return string
     */
    public static function hasRecurring()
    {

        $settings = PT_Settings::instance();

        if ($settings->payment_type == "item" || $settings->payment_type == "service" || $settings->payment_type == "donation") {
            $items = new itemModel();

            return count($items->getItems('service'));
        }
        return false;
    }

    /**
     * Check fo single payments
     * @return string
     */
    public static function hasSingle()
    {

        $settings = PT_Settings::instance();

        if ($settings->payment_type == "item" || $settings->payment_type == "donation" || $settings->payment_type == "product") {
            $items = new itemModel();

            return count($items->getItems('product'));
        }
        return false;
    }
}