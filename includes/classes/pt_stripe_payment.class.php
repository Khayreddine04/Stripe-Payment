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

class PT_Stripe_Payment extends PT_Payment
{
    /**
     * Transaction ID
     * @var string
     */
    public $trn_id = '';

    /**
     * Stripe intentToken
     * @var string
     */
    public $intent = '';

    /**
     * Recurring subscription ID
     * @var string
     */
    public $subscription_id = '';

    /**
     * Click ID for tracking
     * @var string
     */
    public $clickid = '';

    /**
     * Source of the transaction
     * @var string
     */
    public $source = '';

    public $payment_mode;

    public $setup_intent;

    public $subscription_obj;

    public $convenient_currency_data = null;

    public $gateway_profile = null;

    private $checkout_trace_id = '';

    function __construct()
    {
        parent::__construct();

        if (PT_Settings::type() == 'static') {
            $this->setStaticSettings();
        } else {
            $this->setVariableSettings();
        }
        $this->applyGatewayFromRequest();
        $this->setStripeAppInfo();
        $this->checkout_trace_id = $_REQUEST['checkout_trace'] ?? $_REQUEST['checkout_trace_id'] ?? '';
        if ($this->checkout_trace_id === '') {
            $this->checkout_trace_id = substr(hash('sha256', session_id() . '|' . microtime(true)), 0, 12);
        }
    }

    private function timingStart()
    {
        return microtime(true);
    }

    private function timingLog($step, $startedAt, $extra = array())
    {
        $ms = round((microtime(true) - $startedAt) * 1000, 2);
        $parts = array(
            'step=' . $step,
            'ms=' . $ms,
            'trace=' . $this->checkout_trace_id
        );

        foreach ($extra as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $parts[] = $key . '=' . (string)$value;
            }
        }

        PT_Core::_error_log('CHECKOUT_TIMING ' . implode(' ', $parts));
    }

    public function setGatewayProfile($gateway)
    {
        if (empty($gateway) || !is_array($gateway)) {
            return false;
        }

        $this->gateway_profile = $gateway;
        $this->settings['gateway_profile_id'] = (int)$gateway['id'];
        $this->settings['gateway_code'] = $gateway['gateway_code'];
        $this->settings['gateway_type'] = $gateway['gateway_type'];
        $this->settings['gateway_label'] = $gateway['label'];

        if (!empty($gateway['public_key'])) {
            $this->settings['public_key'] = $gateway['public_key'];
        }
        if (!empty($gateway['secret_key'])) {
            $this->settings['secret_key'] = $gateway['secret_key'];
        }

        return true;
    }

    public function applyGatewayFromRequest()
    {
        if (!class_exists('PT_Payment_Gateway')) {
            return false;
        }

        $post = $this->core->post;
        $itemId = $post['pt_service'] ?? $_REQUEST['pt_service'] ?? $_REQUEST['service'] ?? $_REQUEST['item_id'] ?? '';
        $paymentType = $post['pt_type'] ?? $_REQUEST['pt_type'] ?? 'card';
        $gatewayCode = $post['gateway_code'] ?? $_REQUEST['gateway_code'] ?? '';

        $gateway = PT_Payment_Gateway::resolve($itemId, $paymentType, $gatewayCode);
        if ($gateway) {
            return $this->setGatewayProfile($gateway);
        }

        return false;
    }

    private function getGatewayRecordFields()
    {
        if (class_exists('PT_Payment_Gateway')) {
            if (empty($this->gateway_profile)) {
                $this->applyGatewayFromRequest();
            }
            return PT_Payment_Gateway::fieldsFromGateway($this->gateway_profile);
        }

        return array();
    }

    private function loadGatewayForPayment($paymentDetails)
    {
        if (!class_exists('PT_Payment_Gateway') || empty($paymentDetails)) {
            return false;
        }

        if (!empty($paymentDetails['gateway_code'])) {
            $gateway = PT_Payment_Gateway::getByCode($paymentDetails['gateway_code']);
        } elseif (!empty($paymentDetails['gateway_profile_id'])) {
            $gateway = PT_Payment_Gateway::getById($paymentDetails['gateway_profile_id']);
        } else {
            $gateway = PT_Payment_Gateway::getDefault('stripe');
        }

        return $gateway ? $this->setGatewayProfile($gateway) : false;
    }

    public function setStripeAppInfo()
    {
        include_once HOME_DIR . "/includes/processor/stripe-php-10.4.0/init.php";
        \Stripe\Stripe::setAppInfo(
            "Stripe Payment Terminal",
            "2.3.3",
            "http://www.CriticalGears.io"
        );
        \Stripe\Stripe::setApiVersion("2020-08-27");
    }

    public function setStaticSettings()
    {

        $this->settings = array_merge_recursive($this->settings, array(
            "admin_email" => ADMIN_EMAIL,
            "payment_type" => PAYMENT_TYPE,
            "payment_mode" => PAYMENT_MODE, // RECUR | ONETIME
            "display_currency" => DISPLAY_CURRENCY,
            "currency_position" => CURRENCY_POSITION,

            "paypal_merchant_email" => PAYPAL_MERCHANT_EMAIL,
            "paypal_currency" => PAYPAL_CURRENCY,
            "paypal_payment_mode" => PAYPAL_PAYMENT_MODE,

            "paypal_success_url" => PAYPAL_SUCCESS_URL,
            "paypal_cancel_url" => PAYPAL_CANCEL_URL,
            "paypal_ipn_listener" => PAYPAL_IPN_LISTENER,

            "public_key" => PUBLIC_KEY,
            "secret_key" => SECRET_KEY,
            "currency" => CURRENCY,

            "terminal_payment_mode" => TEST_MODE // live | test
        ));
    }

    public function setVariableSettings()
    {


        $settings = PT_Settings::instance();
        if ($settings->terminal_payment_mode == 'live') {
            $this->settings = array_merge_recursive($this->settings, array(
                "public_key" => $settings->live_public_key,
                "secret_key" => $settings->live_secret_key
            ));
        } else {
            $this->settings = array_merge_recursive($this->settings, array(
                "public_key" => $settings->test_public_key,
                "secret_key" => $settings->test_secret_key
            ));
        }

        $this->settings = array_merge_recursive($this->settings, array(
            "admin_email" => $settings->email,

            "payment_type" => $settings->payment_type,

            "paypal_merchant_email" => $settings->paypal_merchant,
            "paypal_payment_mode" => $settings->paypal_payment_mode,

            "paypal_success_url" => $settings->site_url . "/paypal_thankyou.php",
            "paypal_cancel_url" => $settings->site_url . "/paypal_cancel.php",
            "paypal_ipn_listener" => $settings->site_url . "/paypal_listener.php",
            "paypal_currency_converter" => $settings->paypal_currency_converter,
            "paypal_currency_converter_to" => $settings->paypal_currency_converter_to

        ));
    }


    public function addServiceFee($return = false)
    {
        /* Let's try adding the service fee, if it's enabled. */
        $settings = PT_Settings::instance();
        if ($settings->fee_enable == 'y') {
            if ($settings->fee_type == 1) {
                $new_amount = $this->amount + ($settings->fee_amount * $this->amount) / 100;
            } else {
                $new_amount = $this->amount + $settings->fee_amount;
            }
            $this->amount = $new_amount;
            if ($return) {
                return $new_amount;
            }
        } else {
            if ($return) {
                return 0;
            }
        }
    }

    public function doPayment()
    {
        $post = $this->core->post;
        $settings = PT_Settings::instance();
        $validator = PT_Settings::type() == 'static' ? "validateForm" : "validateVarForm";
        if ($this->$validator()) {
            $this->addServiceFee();
            if ($post['pt_type'] == 'card' || $post['pt_type'] == 'cash' || $post['pt_type'] == 'gpay') {
                if ($this->payment_mode == 'RECUR') {
                    return $this->recurringPayment($post['pt_type']);
                } elseif ($this->payment_mode == 'ONETIME') {
                    return $this->singlePayment($post['pt_type']);
                }
            } elseif ($post['pt_type'] == 'paypal') {
                if ($settings->enable_payapl == 'n')
                    throw new PT_Exception("PayPal payment Disabled");
                return $this->payPalPayment();
            } else {
                throw new PT_Exception("Unknown payment type '{$post['pt_type']}'. ");
            }
        }
    }

    public function getPaymentIntent()
    {
        $post = $this->core->post;
        $settings = PT_Settings::instance();
        $validator = PT_Settings::type() == 'static' ? "validateForm" : "validateVarForm";
        if ($this->$validator()) {
            if ($post['pt_type'] == 'card' || $post['pt_type'] == 'gpay') {
                if ($this->payment_mode == 'RECUR') {
                    return $this->getStripeSetupIntentToken();
                    /*return $this->getStripeIntentToken();*/
                } elseif ($this->payment_mode == 'ONETIME') {
                    return $this->getStripeIntentToken();
                }
            }
        }
    }

    public function setupSubscription()
    {
        $post = $this->core->post;
        $settings = PT_Settings::instance();
        $validator = PT_Settings::type() == 'static' ? "validateForm" : "validateVarForm";
        if ($this->$validator()) {
            if ($post['pt_type'] == 'card' || $post['pt_type'] == 'gpay') {
                if ($this->payment_mode == 'RECUR') {
                    return $this->getStripeSubscription();
                } elseif ($this->payment_mode == 'ONETIME') {
                    return false;
                }
            }
        }
    }

    private function getStripeSetupIntentToken()
    {
        //include_once HOME_DIR . "/includes/processor/stripe-php-7.75.0/init.php";
        global $settings;
        $user = PT_User::instance();
        $post = $this->core->post;
        \Stripe\Stripe::setApiKey($this->secret_key);
        $this->addServiceFee();
        try {
            $timer = $this->timingStart();
            $intent = \Stripe\SetupIntent::create([
                'payment_method_types' => ['card'],
            ]);
            $this->timingLog('stripe_setup_intent_create', $timer);

            $this->setup_intent = $intent;
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function getStripeSubscription($type = 'card')
    {
        global $settings;
        $post = $this->core->post;
        \Stripe\Stripe::setApiKey($this->secret_key);
        $stripeCustomer = $stripeCharge = $idTransaction = '';

        if (empty($post['payment_method'])) {
            $this->core->addError('Payment Method not found');
            return false;
        }

        if (!empty($this->recurring_plan_name)) {
            $planName = "{$this->recurring_plan_name}-";
        } else {
            $planName = "{$this->service_name}-";
        }

        // CRITICAL: Get API currency data from session first
        if (!isset($this->convenient_currency_data) || empty($this->convenient_currency_data)) {
            if (isset($_SESSION['api_currency_data'])) {
                $sessionData = $_SESSION['api_currency_data'];
                // Convert session data to the expected format
                $this->convenient_currency_data = [
                    'success' => true,
                    'subscription_amount' => $sessionData['subscription_amount'],
                    'upfront_amount' => $sessionData['upfront_amount']
                ];

                PT_Core::_error_log("Converted session data: " . print_r($this->convenient_currency_data, true));
            }
        }

        if ($this->convenient_currency_data) {
            PT_Core::_error_log("Using API currency data: " . print_r($this->convenient_currency_data, true));
            $calculated_amount = $this->convenient_currency_data['subscription_amount']['amount_numeric'];
            $this->currency = $this->convenient_currency_data['subscription_amount']['currency_code'];
            $upfrontFee = (float)$this->convenient_currency_data['upfront_amount']['amount_numeric'];

            // Use already set billing_period and periods_count
            $period = $this->billing_period;
            $interval = $this->billing_period;
            $interval_count = $this->periods_count;
            $tax_amount = 0;
            $this->trial_period = 0;
            $arb_interval = [$interval, $interval_count];
        } else {
            throw new Exception("Convenient currency data not provided and not found in session.");
        }


        $planName .= number_format(($calculated_amount), 2, '_', '');
        $planName .= "-{$this->currency}-{$this->periods_count}-{$this->billing_period}";
        $planName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $planName);

        $hasTrial = ($this->trial_period > 0) ? true : false;

        PT_Core::_error_log("=== SUBSCRIPTION CREATION DEBUG ===");
        PT_Core::_error_log("Upfront fee: " . (float)$upfrontFee);
        PT_Core::_error_log("Has trial: " . ($hasTrial ? 'yes' : 'no'));
        PT_Core::_error_log("Trial period: " . $this->trial_period);

        if ($this->trial_period > 0) {
            $trial_s = $this->trial_period;
            $trial_end = time() + ($trial_s * 24 * 60 * 60);
            $planName .= '-t' . $trial_s;
        } else {
            $trial_s = null;
            $trial_end = null;
        }

        if ($type == 'card') {
            \Stripe\Stripe::setApiKey($this->secret_key);

            /* Create Plan if does not exists */
            PT_Core::_error_log("Creating Stripe Plan with currency: " . $this->currency);
            try {
                $timer = $this->timingStart();
                \Stripe\Plan::create(
                    array(
                        "amount" => $calculated_amount * 100,
                        "interval" => $arb_interval[0],
                        "interval_count" => $arb_interval[1],
                        "trial_period_days" => $trial_s,
                        "product" => array(
                            "name" => $planName
                        ),
                        "currency" => $this->currency,
                        "id" => $planName
                    )
                );
                $this->timingLog('stripe_plan_create', $timer, array('currency' => $this->currency));
            } catch (Exception $p) {
                if ($p->getError()->code != 'resource_already_exists') {
                    $this->error = $p->getMessage();
                    return false;
                }
                $this->timingLog('stripe_plan_exists', $timer ?? $this->timingStart(), array('currency' => $this->currency));
            }

            $stripeCustomer = st_apply_filter('set_stripe_customer', '');

            // Get clickid and source from POST data
            $clickid = !empty($post['clickid']) ? $post['clickid'] : (!empty($this->clickid) ? $this->clickid : '');
            $source = !empty($post['source']) ? $post['source'] : (!empty($this->source) ? $this->source : '');

            $meta_data = array(
                'Customer Name' => $post['pt_name'],
                'Customer Email' => $post['pt_email'],
                'Comments' => $this->getPaymentDescription(),
                'Service' => $this->service_name,
                'clickid' => $clickid,
                'source' => $source
            );

            if ($settings->show_billing === 'y') {
                $meta_data = array_merge($meta_data, array(
                    'Billing Address1' => $post['pt_address1'],
                    'Billing Address2' => $post['pt_address2'],
                    'Billing City' => $post['pt_city'],
                    'Billing Country' => !empty($post['pt_country']) ? $this->getCountriesListJSON($post['pt_country']) : '',
                    'Billing State' => $post['pt_state'],
                    'Billing Zip' => $post['pt_postal']
                ));
            }

            if ($settings->show_shipping === 'y') {
                $meta_data = array_merge($meta_data, array(
                    'Shipping Address1' => $post['pt_address1_s'],
                    'Shipping Address2' => $post['pt_address2_s'],
                    'Shipping City' => $post['pt_city_s'],
                    'Shipping Country' => !empty($post['pt_country_s']) ? $this->getCountriesListJSON($post['pt_country_s']) : '',
                    'Shipping State' => $post['pt_state_s'],
                    'Shipping Zip' => $post['pt_postal_s']
                ));
            }

            $payment_method = $post['payment_method'];

            if (empty($stripeCustomer)) {
                try {
                    if (empty($payment_method))
                        throw new Exception("The Stripe Payment Method was not generated correctly");

                    $timer = $this->timingStart();
                    $customer = \Stripe\Customer::create(
                        array(
                            "payment_method" => $payment_method,
                            "email" => $post['pt_email'],
                            "name" => $post['pt_name'],
                            "metadata" => $meta_data
                        )
                    );
                    $this->timingLog('stripe_customer_create', $timer);

                    $stripeCustomer = $customer->id;
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    return false;
                }
            } else {
                try {
                    if (empty($payment_method))
                        throw new Exception("The Stripe Payment method was not generated correctly");

                    $stripe = new \Stripe\StripeClient($this->secret_key);
                    $timer = $this->timingStart();
                    $stripe->paymentMethods->attach(
                        $payment_method,
                        ['customer' => $stripeCustomer]
                    );
                    $this->timingLog('stripe_payment_method_attach', $timer);

                    $timer = $this->timingStart();
                    $customer = \Stripe\Customer::update(
                        $stripeCustomer,
                        array(
                            "email" => $post['pt_email'],
                            "name" => $post['pt_name'],
                            "metadata" => $meta_data
                        )
                    );
                    $this->timingLog('stripe_customer_update', $timer);
                    $stripeCustomer = $customer->id;
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    return false;
                }
            }

            if (!empty($stripeCustomer)) {
                try {
                    // Store in class properties for later use
                    $this->clickid = $clickid;
                    $this->source = $source;

                    // Create upfront fee invoice item if applicable
                    $upfrontPaymentIntent = null;
                    if ($upfrontFee > 0) {
                        PT_Core::_error_log("Creating upfront fee invoice item: $" . (float)$upfrontFee);

                        $timer = $this->timingStart();
                        $invoiceItem = \Stripe\InvoiceItem::create([
                            'customer' => $stripeCustomer,
                            'amount' => (float)$upfrontFee * 100,
                            'currency' => strtolower($this->currency),
                            'description' => 'Upfront fee for ' . $this->service_name,
                        ]);
                        $this->timingLog('stripe_invoice_item_create', $timer, array('currency' => $this->currency));

                        $timer = $this->timingStart();
                        $invoice = \Stripe\Invoice::create([
                            'customer' => $stripeCustomer,
                            'auto_advance' => true, // Automatically finalize and attempt payment
                            'collection_method' => 'charge_automatically',
                            'default_payment_method' => $payment_method,
                        ]);
                        $this->timingLog('stripe_invoice_create', $timer);


                        PT_Core::_error_log("Created invoice item: " . $invoiceItem->id);
                    }

                    // Create subscription
                    $subscriptionData = [
                        'customer' => $stripeCustomer,
                        'items' => [['plan' => $planName]],
                        'payment_behavior' => 'default_incomplete',
                        'metadata' => $meta_data,
                        'default_payment_method' => $payment_method
                    ];

                    if ($hasTrial) {
                        $subscriptionData['trial_from_plan'] = true;
                    }

                    if ($this->plan_payments) {
                        $subscriptionData['cancel_at'] = $this->cancel_date($this->plan_payments, $this->trial_period);
                    }

                    // Create the subscription
                    $timer = $this->timingStart();
                    $subscription = \Stripe\Subscription::create($subscriptionData);
                    $this->timingLog('stripe_subscription_create', $timer, array('currency' => $this->currency));
                    PT_Core::_error_log("Subscription created: " . $subscription->id);

                    // If there's an upfront fee, we need to check for immediate payment
                    if ($upfrontFee > 0) {
                        PT_Core::_error_log("Checking for upfront fee payment for subscription: " . $subscription->id);

                        try {
                            // Retrieve the latest invoice which should contain the upfront fee
                            $timer = $this->timingStart();
                            $latestInvoice = \Stripe\Invoice::retrieve($subscription->latest_invoice);
                            $this->timingLog('stripe_latest_invoice_retrieve', $timer);

                            if ($latestInvoice && $latestInvoice->status === 'paid') {
                                PT_Core::_error_log("Upfront fee invoice found and paid: " . $latestInvoice->id);
                                PT_Core::_error_log("Payment intent: " . $latestInvoice->payment_intent);
                                PT_Core::_error_log("Charge: " . $latestInvoice->charge);

                                // Store the upfront payment info
                                $upfrontPaymentIntent = $latestInvoice->payment_intent;
                                $upfrontCharge = $latestInvoice->charge;
                            } else {
                                PT_Core::_error_log("Upfront fee invoice status: " . ($latestInvoice->status ?? 'not found'));
                            }
                        } catch (Exception $e) {
                            PT_Core::_error_log("Error checking upfront fee payment: " . $e->getMessage());
                        }
                    }

                    // Retrieve the subscription with expanded details
                    $timer = $this->timingStart();
                    $new_subscription = \Stripe\Subscription::retrieve([
                        'id' => $subscription->id,
                        'expand' => ['latest_invoice', 'latest_invoice.payment_intent', 'pending_setup_intent']
                    ]);
                    $this->timingLog('stripe_subscription_retrieve', $timer);

                    $this->subscription_id = $subscription->id;
                    $this->subscription_obj = $new_subscription;

                    // Store upfront payment info if it exists
                    if (isset($upfrontPaymentIntent)) {
                        $this->subscription_obj->upfront_payment_intent = $upfrontPaymentIntent;
                        $this->subscription_obj->upfront_charge = $upfrontCharge;
                        $this->subscription_obj->upfront_fee = (float)$upfrontFee;
                    }

                    PT_Core::_error_log("=== END SUBSCRIPTION CREATION DEBUG ===");
                    return true;
                } catch (Exception $e) {
                    PT_Core::_error_log("Subscription creation error: " . $e->getMessage());
                    $this->error = $e->getMessage();
                    return false;
                }
            } else {
                $this->error = 'Customer not created';
                return false;
            }

            $this->error = 'Card declined';
            return false;
        } else {
            $this->subscription_id = "";
            $this->subscription_obj = "";
        }

        return false;
    }

    private function getStripeIntentToken()
    {
        //include_once HOME_DIR . "/includes/processor/stripe-php-7.75.0/init.php";
        global $settings;
        $user = PT_User::instance();
        $post = $this->core->post;
        \Stripe\Stripe::setApiKey($this->secret_key);
        $this->addServiceFee();
        try {
            /* PAYMENTINTENT CHARGE PROCESSING */
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
                    $calculated_amount = round($this->amount, 2) * 100;
                    $tax_amount = 0;
                } else {
                    $calculated_amount = round($this->amount + $this->amount * ($settings->tax_rate / 100), 2) * 100;
                    $tax_amount = round($this->amount * ($settings->tax_rate / 100), 2) * 100;
                }
            } else {
                $calculated_amount = round($this->amount, 2) * 100;
                $tax_amount = 0;
            }

            $timer = $this->timingStart();
            $intent = \Stripe\PaymentIntent::create(array(
                "amount" => $calculated_amount,
                "currency" => $this->currency,
                "description" => $this->getPaymentDescription(false)
            ));
            $this->timingLog('stripe_payment_intent_create', $timer, array('currency' => $this->currency));

            $this->intent = $intent;
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * Make a single payment
     * @return bool
     */
    public function singlePayment($type = "card")
    {
        //include_once HOME_DIR . "/includes/processor/stripe-php-7.75.0/init.php";
        global $settings;
        $user = PT_User::instance();
        $post = $this->core->post;
        $charge_id = '';

        if ($type == 'card' || $type == 'gpay') {

            \Stripe\Stripe::setApiKey($this->secret_key);
            try {
                if (!empty($post['stripeToken']) || !empty($post['stripeIntent'])) {
                    if (isset($post['stripeIntent'])) {
                        $timer = $this->timingStart();
                        $responsePI = \Stripe\PaymentIntent::retrieve(
                            $post['stripeIntent']
                        );
                        $this->timingLog('stripe_payment_intent_retrieve', $timer);
                        if ($responsePI->status === 'succeeded') {
                            $this->trn_id = $responsePI->id;

                            $charge_id = !empty($responsePI['charges']['data'][0]['id']) ? $responsePI['charges']['data'][0]['id'] : '';

                            // getting data from payment intent charge
                            if (isset($post['stripeButton']) && $post['stripeButton'] == 'y' && !empty($charge_id)) {
                                $charge_data = $responsePI['charges']['data'][0];
                                $post['pt_name'] = $this->core->post['pt_name'] = $charge_data['billing_details']['name'];
                                $post['pt_email'] = $this->core->post['pt_email'] = $charge_data['billing_details']['email'];

                                $post['pt_address1'] = $this->core->post['pt_address1'] = $charge_data['billing_details']['address']['line1'];
                                $post['pt_address2'] = $this->core->post['pt_address2'] = $charge_data['billing_details']['address']['line2'];
                                $post['pt_city'] = $this->core->post['pt_city'] = $charge_data['billing_details']['address']['city'];
                                $post['pt_country'] = $this->core->post['pt_country'] = $charge_data['billing_details']['address']['country'];
                                $post['pt_state'] = $this->core->post['pt_state'] = $charge_data['billing_details']['address']['state'];
                                $post['pt_postal'] = $this->core->post['pt_postal'] = $charge_data['billing_details']['address']['postal_code'];
                            }

                            $meta_data = array(
                                'Customer Name' => $post['pt_name'],
                                'Customer Email' => $post['pt_email'],
                                'Comments' => $this->getPaymentDescription(),
                                'clickid' => !empty($this->clickid) ? $this->clickid : '',
                                'source' => !empty($this->source) ? $this->source : ''
                            );
                            if ($this->invoice_id != 0) {
                                $meta_data['Invoice ID'] = $this->invoice_id;
                                $meta_data['Invoice #'] = $this->invoice_number;
                            } else {
                                $meta_data['Service'] = $this->service_name;
                            }
                            if ($settings->show_billing === 'y') {
                                $meta_data = array_merge($meta_data, array(
                                    'Billing Address1' => $post['pt_address1'],
                                    'Billing Address2' => $post['pt_address2'],
                                    'Billing City' => $post['pt_city'],
                                    'Billing Country' => !empty($post['pt_country']) ? $this->getCountriesListJSON($post['pt_country']) : '',
                                    'Billing State' => $post['pt_state'],
                                    'Billing Zip' => $post['pt_postal']
                                ));
                            }
                            if ($settings->show_shipping === 'y') {
                                $meta_data = array_merge($meta_data, array(
                                    'Shipping Address1' => $post['pt_address1_s'],
                                    'Shipping Address2' => $post['pt_address2_s'],
                                    'Shipping City' => $post['pt_city_s'],
                                    'Shipping Country' => !empty($post['pt_country_s']) ? $this->getCountriesListJSON($post['pt_country_s']) : '',
                                    'Shipping State' => $post['pt_state_s'],
                                    'Shipping Zip' => $post['pt_postal_s']
                                ));
                                $customerShipping = [

                                    "address" => [
                                        "city" => $post['pt_city_s'],
                                        "country" => !empty($post['pt_country_s']) ? $this->getCountriesListJSON($post['pt_country_s']) : '',
                                        "line1" => $post['pt_address1_s'],
                                        "line2" => $post['pt_address2_s'],
                                        "postal_code" => $post['pt_state_s'],
                                        "state" => $post['pt_postal_s'],
                                    ],
                                    "name" => $post['pt_name']


                                ];
                            }



                            $customerData = [
                                "email" => $post['pt_email'],
                                "name" => $post['pt_name'],
                                "address" => [
                                    "city" => $post['pt_city'],
                                    "country" => !empty($post['pt_country']) ? $this->getCountriesListJSON($post['pt_country']) : '',
                                    "line1" => $post['pt_address1'],
                                    "line2" => $post['pt_address2'],
                                    "postal_code" => $post['pt_state'],
                                    "state" => $post['pt_postal'],

                                ]
                            ];
                            if (!empty($customerShipping)) {
                                $customerData["shipping"] = $customerShipping;
                            }

                            $customer = \Stripe\Customer::create($customerData);

                            \Stripe\PaymentIntent::update($post['stripeIntent'], array(
                                'metadata' => $meta_data,
                                'customer' => $customer
                            ));
                        } else {
                            throw new Exception($responsePI->last_payment_error->message);
                        }
                    } elseif (isset($post['stripeToken'])) {
                        /* REGULAR CHARGE, NOT A PAYMENTINTENT */
                        /* check if taxes are enabled and if we're paying NOT for invoice
                        * This is initial release of the tax settings. The following code
                        * TODO must be converted to a function in future updates (used in several places/files) */
                        if ($settings->tax_enable === 'y' && $this->invoice_id == 0) {
                            if (is_uuid($post['pt_service'])) {
                                $item = new itemModel();
                                $item->setID($post['pt_service']);
                                $exempt = $item::getTaxExemption($post['pt_service']) === "y" ? true : false;
                            } else {
                                $exempt = false;
                            }
                            if ($exempt) {
                                /* product is exempt from taxes */
                                $calculated_amount = round($this->amount, 2) * 100;
                            } else {
                                $calculated_amount = round($this->amount + $this->amount * ($settings->tax_rate / 100), 2) * 100;
                            }
                        } else {
                            $calculated_amount = round($this->amount, 2) * 100;
                        }

                        $charge_array = array(
                            /* Add here more options or the charge */
                            "amount" => $calculated_amount,
                            "currency" => $this->currency,
                            "card" => $post['stripeToken'],
                            "description" => $this->getPaymentDescription(false)
                        );
                        $response = \Stripe\Charge::create($charge_array);

                        $this->trn_id = $response->id;
                        $charge_id = $response->id;
                    }
                } else {
                    throw new Exception("The Stripe Token was not generated correctly");
                }
            } catch (Exception $e) {
                $this->core->addError($e->getMessage());
                return false;
            }
        } else {
            $this->trn_id = "";
        }
        if ($type == 'cash' && !$user->logon) {
            $this->core->addError("To apply cash payment you must login as administrator");
            return false;
        }

        $this->core->addSuccess($settings->thank_you_message);

        if (PT_Settings::type() == 'var') {

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
                    $calculated_amount = round($this->amount + $this->amount * ($settings->tax_rate / 100), 2);
                    $tax_amount = round($this->amount * ($settings->tax_rate / 100), 2);
                }
            } else {
                $calculated_amount = round($this->amount, 2);
                $tax_amount = 0;
            }

            $payment = new paymentModel();
            $this->payment_id = $payment->addPayment(array(
                'paypalStatus' => 'paid',
                'customerName' => $post['pt_name'],
                'customerEmail' => $post['pt_email'],
                'amount' => $calculated_amount,
                'tax_amount' => $tax_amount,
                'tax_rate' => empty($settings->tax_rate) ? 0 : $settings->tax_rate,
                'tax_abbreviation' => $settings->tax_abbreviation,
                'currency' => $this->currency,
                'currency_symbol' => $this->display_currency,
                'currency_position' => $this->currency_position,
                'idItem' => $post['pt_service'],
                'processor' => 'stripe',
                'comments' => addslashes($this->getPaymentDescription()),
                'idInvoice' => $this->invoice_id,
                'idTransaction' => $this->trn_id,
                'clickid' => !empty($post['clickid']) ? $post['clickid'] : '',
                'source' => !empty($post['source']) ? $post['source'] : '',
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
                'stripeCharge' => $charge_id,
                'stripeCustomer' => '',
                'stripeSubscription' => ''
            ) + $this->getGatewayRecordFields());

            if ($this->invoice_id != 0) {
                $invoice = new invoiceModel();
                $invoice->setID($this->invoice_id);
                $invoice->setUsPaid();
                $invoice->addHistory("paid", "Invoice paid by {$type}, Trn ID #{$this->trn_id}");
            }
        }
        $this->sendEmails();
        return true;
    }

    /**
     * Make a recurring payment
     * @param string $type
     * @return bool
     */

    /**
     * Make a recurring payment
     * @param string $type
     * @return bool
     */
    function recurringPayment($type = "card")
    {
        global $settings;
        $post = $this->core->post;

        error_log("=== RECURRING PAYMENT DEBUG ===");
        error_log("POST clickid: " . (isset($post['clickid']) ? $post['clickid'] : 'NOT SET'));
        error_log("POST source: " . (isset($post['source']) ? $post['source'] : 'NOT SET'));

        // CRITICAL: Load API currency data from session if not already set
        if (!isset($this->convenient_currency_data) || empty($this->convenient_currency_data)) {
            if (isset($_SESSION['api_currency_data'])) {
                error_log("Loading API currency data from session in recurringPayment");
                $sessionData = $_SESSION['api_currency_data'];

                // Convert session data to the expected format
                $this->convenient_currency_data = [
                    'success' => true,
                    'subscription_amount' => $sessionData['subscription_amount'],
                    'upfront_amount' => $sessionData['upfront_amount']
                ];

                error_log("Loaded API data into convenient_currency_data: " . print_r($this->convenient_currency_data, true));
            } else {
                error_log("WARNING: No API currency data found in session");
            }
        }

        \Stripe\Stripe::setApiKey($this->secret_key);

        if (!empty($post['subscription_id'])) {
            $this->subscription_id = $post['subscription_id'];
            try {
                $subscription = \Stripe\Subscription::retrieve([
                    'id' => $this->subscription_id,
                    'expand' => ['latest_invoice', 'latest_invoice.payment_intent', 'pending_setup_intent']
                ]);

                if (isset($subscription['latest_invoice']['payment_intent']['charges']['data'][0]['id'])) {
                    $idTransaction = $subscription['latest_invoice']['payment_intent']['id'];
                    $stripeCharge = $subscription['latest_invoice']['payment_intent']['charges']['data'][0]['id'];
                }
                $stripeCustomer = $subscription['customer'];
            } catch (Exception $e) {
                $this->core->addError($e->getMessage());
                return false;
            }
        }

        if ($subscription['status'] == 'trialing') {
            if (
                isset($subscription['pending_setup_intent']['status']) &&
                $subscription['pending_setup_intent']['status'] == 'succeeded'
            ) {
                $subscription['status'] = 'active';
            } elseif (empty($subscription['pending_setup_intent'])) {
                $subscription['status'] = 'active';
            } else {
                $this->core->addError('Subscription not created. 3D secure error');
                return false;
            }
        }

        if ($subscription['status'] == 'active' || $subscription['status'] == 'trialing') {
            /* check if taxes are enabled */
            if ($settings->tax_enable === 'y' && $this->invoice_id == 0) {
                if (is_uuid($post['pt_service'])) {
                    $item = new itemModel();
                    $item->setID($post['pt_service']);
                    $exempt = $item::getTaxExemption($post['pt_service']) === "y" ? true : false;
                } else {
                    $exempt = false;
                }

                if ($exempt) {
                    $calculated_amount = round($this->amount, 2);
                    $tax_amount = 0;
                } else {
                    $tmp_amount = number_format($this->amount, 2, ".", "");
                    $calculated_amount = round($tmp_amount * 1 + $tmp_amount * ($settings->tax_rate / 100), 2);
                    $tax_amount = round($tmp_amount * ($settings->tax_rate / 100), 2);
                }
            } else {
                $calculated_amount = round($this->amount, 2);
                $tax_amount = 0;
            }

            $this->core->addSuccess($settings->thank_you_message);

            if (PT_Settings::type() == 'var') {
                $subscriptionModel = new subscriptionModel();

                // Extract clickid and source from POST data
                $clickid = !empty($post['clickid']) ? $post['clickid'] : '';
                $source = !empty($post['source']) ? $post['source'] : '';

                $subscription_data = array(
                    'customerName' => $post['pt_name'],
                    'customerEmail' => $post['pt_email'],
                    'tax_abbreviation' => $settings->tax_abbreviation,
                    'tax_rate' => empty($settings->tax_rate) ? 0 : $settings->tax_rate,
                    'idItem' => $post['pt_service'],
                    'processor' => 'stripe',
                    'comments' => addslashes($this->getPaymentDescription()),
                    'idInvoice' => $this->invoice_id,
                    'idTransaction' => $this->subscription_id,
                    'paymentsCount' => $this->plan_payments,
                    'clickid' => $clickid,
                    'source' => $source,
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
                    'status' => "active",
                    'trial_days' => $this->trial_period,
                    'stripeCustomer' => $stripeCustomer,
                    'payment_method' => $this->core->post['payment_method'] ?? ($post['payment_method'] ?? '')
                );
                $subscription_data = array_merge($subscription_data, $this->getGatewayRecordFields());

                if ($this->convenient_currency_data) {
                    $subscription_data['amount'] = $this->convenient_currency_data['subscription_amount']['amount_numeric'];
                    $subscription_data['currency'] = $this->convenient_currency_data['subscription_amount']['currency_code'];
                    $subscription_data['currency_symbol'] = $this->convenient_currency_data['subscription_amount']['currency_symbol'];
                    $subscription_data['currency_position'] = 'before';
                    $subscription_data['period'] = $this->billing_period;
                    $subscription_data['period_count'] = $this->periods_count;
                } else {
                    $subscription_data['amount'] = $calculated_amount;
                    $subscription_data['currency'] = $this->currency;
                    $subscription_data['currency_symbol'] = $this->display_currency;
                    $subscription_data['currency_position'] = $this->currency_position;
                    $subscription_data['period'] = strtolower($this->billing_period);
                    $subscription_data['period_count'] = $this->periods_count;
                }

                $timer = $this->timingStart();
                $subscription_id = $subscriptionModel->addSubscription($subscription_data);
                $this->timingLog('local_subscription_add', $timer, array('idSubscription' => $subscription_id));

                PT_Core::_error_log("Subscription created with ID: " . $subscription_id);

                // Skip creating payment records for both subscription and upfront fees
                PT_Core::_error_log("Skipping payment record creation for subscription (including upfront fee if any)");
                PT_Core::_error_log("Subscription ID: " . $subscription_id);

                // Get upfront fee from API response if available, otherwise default to 0
                $upfrontFee = 0;
                if (!empty($this->convenient_currency_data['upfront_amount']['amount_numeric'])) {
                    $upfrontFee = floatval($this->convenient_currency_data['upfront_amount']['amount_numeric']);
                } elseif (!empty($this->subscription_obj->upfront_fee)) {
                    $upfrontFee = floatval($this->subscription_obj->upfront_fee);
                }

                // If there's an upfront fee, update the subscription record directly
                if ($subscription_id && $upfrontFee > 0) {
                    PT_Core::_error_log("NOTE: Upfront fee of " . $upfrontFee . " detected - updating subscription record directly");

                    // Get the database connection
                    $db = new PT_Db();

                    // Build the table name with the correct prefix
                    $table = $db->db_pr . "subscriptions";

                    // Update the subscription record directly to mark upfront fee as paid
                    $sql = "UPDATE `" . $table . "` 
                            SET upfront_fee_paid = 1, 
                                upfront_fee_paid_date = NOW(),
                                upfront_fee = " . $upfrontFee . "
                            WHERE idSubscription = " . intval($subscription_id);

                    $updateResult = $db->query($sql);

                    if ($updateResult) {
                        PT_Core::_error_log("SUCCESS: Upfront fee marked as paid in subscription record");
                    } else {
                        PT_Core::_error_log("ERROR: Failed to update upfront fee status in subscription record");
                        PT_Core::_error_log("SQL Error: " . $subscriptionModel->db->get_error());
                    }
                }

                PT_Core::_error_log("Subscription created successfully. No payment records were created.");

                // Handle invoice if applicable
                if ($this->invoice_id != 0) {
                    $invoice = new invoiceModel();
                    $invoice->setID($this->invoice_id);
                    $invoice->setUsPaid();
                    $invoice->addHistory("paid", "Invoice paid by subscription {$this->subscription_id}, Trn ID #{$idTransaction}");
                }
            }

            $this->sendEmails();
            return true;
        }

        return false;
    }



    /**
     * cancel payment subscription
     */
    public function cancelSubscription()
    {
        //include_once HOME_DIR . "/includes/processor/stripe-php-7.75.0/init.php";
        $post = $this->core->post;

        if (empty($post['pt_agree'])) {
            $this->core->addError("Please confirm subscription cancellation by clicking the checkbox.");
            return false;
        } elseif (empty($post['pt_subscription_id'])) {
            $this->core->addError("Subscription ID was not specified");
            return false;
        } else {
            $this->subscription_id = $post['pt_subscription_id'];
            try {
                if (class_exists('PT_Payment_Gateway')) {
                    $subscriptionModel = new subscriptionModel();
                    $subscriptionData = $subscriptionModel->getSubscriptionByTrn($post['pt_subscription_id']);
                    if ($subscriptionData) {
                        $this->loadGatewayForPayment($subscriptionData);
                    }
                }
                \Stripe\Stripe::setApiKey($this->secret_key);
                $cu = \Stripe\Subscription::retrieve($post['pt_subscription_id']);
                $cu->delete();
                if (PT_Settings::type() == 'var') {
                    $subscription = new subscriptionModel();
                    $subscription->cancelSubscription($this->subscription_id);
                }
                $this->sendCancelationEmails();
            } catch (Exception $e) {
                $this->core->addError("Cancellation Un-successful!<br>There was an error with your subscription cancellation.<br/>Please contact us directly to cancel your subscription<br>
                 " . $e->getMessage());
                return false;
            }
        }
        $this->core->addSuccess("You have been successfully unsubscribed !");
        return true;
    }

    public function cancel_date($periods, $trial = 0)
    {
        return strtotime("now +$periods month") + 86400 + ($trial * 24 * 60 * 60);
    }


    public function importTransactions()
    {
        global $CURRENCY_SYMBOLS;
        $import_settings = ['expand' => ['data.customer', 'data.invoice']];
        $paymentModel = new paymentModel();
        $settings = PT_Settings::instance();

        $last_import = $settings->last_import;
        /*PT_Core::_dump($last_import);*/
        if (empty($last_import)) {
            $last_import = $paymentModel->getFirstTransaction();
        }
        if (!empty($last_import)) {
            $import_settings['ending_before'] = $last_import;
            $import_settings['limit'] = 100;
        } else {
            $import_settings['limit'] = 50;
        }

        /*PT_Core::_dump($import_settings);*/


        \Stripe\Stripe::setApiKey($this->secret_key);
        $payments = \Stripe\Charge::all($import_settings);


        $imported = 0;
        $i = 0;
        foreach ($payments as $payment) {
            if ($payment['status'] !== 'succeeded')
                continue;

            $paymentIntent = $payment['payment_intent'];
            $paymentCharge = $payment['id'];
            $created = date("Y-m-d H:i", $payment['created']);

            $payment_data = array(
                'paypalStatus' => 'paid',
                'idItem' => '',
                'idInvoice' => 0,
                'billingAddress1' => '',
                'tax_rate' => '',
                'tax_abbreviation' => '',
                'tax_amount' => '0.00',
                'billingAddress2' => '',
                'billingCity' => '',
                'billingCountry' => '',
                'billingState' => '',
                'billingZip' => '',
                'shippingAddress1' => '',
                'shippingAddress2' => '',
                'shippingCity' => '',
                'shippingCountry' => '',
                'shippingState' => '',
                'shippingZip' => '',
                'stripeCharge' => $paymentCharge,
                'stripeCustomer' => '',
                'stripeSubscription' => '',
                'customerName' => '',
                'customerEmail' => '',
                'amount' => $payment['amount'] / 100,
                'currency' => $payment['currency'],
                'currency_symbol' => isset($CURRENCY_SYMBOLS[strtoupper($payment['currency'])]) ? $CURRENCY_SYMBOLS[strtoupper($payment['currency'])] : '',
                'currency_position' => 'before',
                'comments' => 'Stripe ' . $payment['description'],
                'idTransaction' => $paymentIntent,
                'created' => $created
            );
            if (!empty($payment['customer'])) {
                $payment_data['stripeCustomer'] = $payment['customer']['id'];
                $payment_data['customerName'] = $payment['customer']['name'];
                $payment_data['customerEmail'] = $payment['customer']['email'];
                $payment_data['processor'] = 'stripe';
            } else {
                $payment_data['stripeCustomer'] = '';
                $payment_data['customerName'] = $payment['billing_details']['name'];
                $payment_data['customerEmail'] = $payment['billing_details']['email'];
                $payment_data['processor'] = 'stripe_direct';
            }

            if (!empty($payment['invoice']['subscription'])) {
                $payment_data['stripeSubscription'] = $payment['invoice']['subscription'];
            }
            $payment_data = array_merge($payment_data, $this->getGatewayRecordFields());
            /*PT_Core::_dump($payment['customer']['id']);
            PT_Core::_dump($payment['invoice']['subscription']);
            PT_Core::_dump($payment_data);
            PT_Core::_dump($payment);*/

            if ($paymentModel->importPayment($payment_data) !== false) {
                $imported++;
            }
            if ($i == 0)
                $settings->updateOption('last_import', $paymentCharge);
            $i++;
        }
        return $imported;
    }

    public function refund($transaction_id, $amount, $reason, $refund_message)
    {
        $settings = PT_Settings::instance();
        $payment = new paymentModel();
        $payment->setID($transaction_id);
        $paymentDetails = $payment->getPayment();
        $this->loadGatewayForPayment($paymentDetails);
        if ($amount > $paymentDetails['amount']) {
            return "Refund amount must be less or equal payment amount";
        }

        \Stripe\Stripe::setApiKey($this->secret_key);
        try {
            $re = \Stripe\Refund::create([
                'payment_intent' => $paymentDetails['idTransaction'],
                'amount' => $amount * 100
            ]);

            $payment->refundPayment($paymentDetails['idPayment'], $re->id, $amount, $reason);

            if (!empty($paymentDetails['idInvoice'])) {
                $invoice = new invoiceModel();
                $invoice->setID($paymentDetails['idInvoice']);
                $invoice->setUsRefunded();
            }

            if (!empty($settings->terminal_logo)) {
                $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
            } else {
                $logoBlock = '';
            }
            $mailData = array(
                "{%logo_block%}" => $logoBlock,
                "{%site_url%}" => $settings->site_url,
                "{%name%}" => $paymentDetails['customerName'],
                "{%email%}" => $paymentDetails['customerEmail'],
                "{%amount%}" => PT_Core::_getCurrencyText($amount, $paymentDetails['currency_position'], $paymentDetails['currency_symbol']),
                "{%transaction_id%}" => $re->id,
                "{%reason%}" => ($refund_message != '' ? "<label>Refund Reason: </label><br />" . nl2br($refund_message) . "<br /><br />" : '')
            );
            $this->core->sendMail($paymentDetails['customerEmail'], "Payment Refunded", "refund.html", $mailData, false);
            $logInfo = "{$paymentDetails['currency_symbol']}{$paymentDetails['amount']}{$paymentDetails['currency']} transaction {$paymentDetails['idTransaction']} 
                for customer {$paymentDetails['customerName']}";
            if (!empty(!empty($paymentDetails['stripeSubscription'])))
                $logInfo .= " for subscription {$paymentDetails['stripeSubscription']}";
            st_do_action('add_user_log', "Issued refund for $logInfo");
        } catch (Exception $e) {

            return $e->getMessage();
        }
        return true;
    }

    public function processWebhook()
    {
        $settings = PT_Settings::instance();
        $endpoint_secret = $settings->webhook_secret_key;

        if (class_exists('PT_Payment_Gateway')) {
            $gatewayCode = isset($_GET['gateway']) ? trim((string)$_GET['gateway']) : '';
            $gateway = $gatewayCode !== '' ? PT_Payment_Gateway::getByCode($gatewayCode) : PT_Payment_Gateway::getDefault('stripe');
            if (!$gateway || $gateway['gateway_type'] !== 'stripe') {
                http_response_code(400);
                echo 'Unknown Stripe gateway';
                exit();
            }
            $this->setGatewayProfile($gateway);
            $endpoint_secret = $gateway['webhook_secret'];
        }

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? "";
        $event = null;

        try {
            \Stripe\Stripe::setApiKey($this->secret_key);
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            PT_Core::_error_log(print_r($e, 1));
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            PT_Core::_error_log(print_r($e, 1));
            http_response_code(400);
            exit();
        }

        PT_Core::_error_log("ACTION: " . $event->type);
        // Handle the event
        switch ($event->type) {
            case 'charge.refund.updated':
                $refund = $event->data->object;
                /*
				 * TODO handle refund
				 */
                break;
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionDeleted($subscription);
                break;
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                /*
				 * TODO handle subscription update
				 */
                break;
            case 'invoice.created':
                $invoice = $event->data->object;
                /**
                 * TODO process invoice created
                 */
                break;
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                // Convert the Stripe object to an array and add the subscription ID if it exists
                $invoiceData = $invoice->toArray();
                if (!empty($invoice->subscription)) {
                    $invoiceData['subscription'] = $invoice->subscription;
                }
                $this->handleInvoiceSucceeded($invoiceData);
                break;
            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $this->handlePaymentFailed($invoice);
                break;
            // ... handle other event types
            default:
                echo 'Received unknown event type ' . $event->type;
        }

        http_response_code(200);
    }

    protected function handleInvoiceSucceeded($payment)
    {
        global $CURRENCY_SYMBOLS, $settings;
        PT_Core::_error_log("=== handleInvoiceSucceeded START ===");
        PT_Core::_error_log(print_r($payment, 1));

        $created = date("Y-m-d H:i", $payment['created']);
        $currencySymbol = isset($CURRENCY_SYMBOLS[strtoupper($payment['currency'])]) ?
            $CURRENCY_SYMBOLS[strtoupper($payment['currency'])] : '';
        $amount = $payment['amount_paid'] / 100;

        // Get subscription data if this is related to a subscription
        $subscriptionData = null;
        if (!empty($payment['subscription'])) {
            $subscription = new subscriptionModel();
            $subscriptionData = $subscription->getSubscriptionByTrn($payment['subscription']);

            // Activate subscription
            $subscription->activateSubscription($payment['subscription']);
            PT_Core::_error_log("Activated subscription: " . $payment['subscription']);
        }

        // Only create payment records for NON-SUBSCRIPTION payments
        // Subscriptions are tracked separately in the subscriptions table
        if (empty($payment['subscription'])) {
            PT_Core::_error_log("=== PROCESSING ONE-TIME PAYMENT ===");

            $paymentModel = new paymentModel();
            $paymentIntent = $payment['payment_intent'];
            $paymentCharge = $payment['charge'];

            $payment_data = array(
                'paypalStatus' => 'paid',
                'idItem' => '',
                'idInvoice' => 0,
                'customerName' => $payment['customer_name'] ?? '',
                'customerEmail' => $payment['customer_email'] ?? '',
                'amount' => $amount,
                'currency' => $payment['currency'],
                'currency_symbol' => $currencySymbol,
                'currency_position' => 'before',
                'processor' => 'stripe',
                'comments' => 'One-time payment',
                'idTransaction' => $paymentIntent,
                'billingAddress1' => '',
                'tax_rate' => '',
                'tax_abbreviation' => '',
                'tax_amount' => '0.00',
                'billingAddress2' => '',
                'billingCity' => '',
                'billingCountry' => '',
                'billingState' => '',
                'billingZip' => '',
                'shippingAddress1' => '',
                'shippingAddress2' => '',
                'shippingCity' => '',
                'shippingCountry' => '',
                'shippingState' => '',
                'shippingZip' => '',
                'stripeCharge' => $paymentCharge,
                'stripeCustomer' => $payment['customer'] ?? '',
                'stripeSubscription' => '',
                'created' => $created,
                'clickid' => $_POST['clickid'] ?? '',
                'source' => $_POST['source'] ?? ''
            );
            $payment_data = array_merge($payment_data, $this->getGatewayRecordFields());

            $paymentId = $paymentModel->importPayment($payment_data);

            if ($paymentId === false) {
                PT_Core::_error_log("ERROR: Failed to import one-time payment");
            } else {
                PT_Core::_error_log("SUCCESS: Imported one-time payment with ID: " . $paymentId);
            }

            PT_Core::_error_log("=== END ONE-TIME PAYMENT PROCESSING ===");
        } else {
            PT_Core::_error_log("Skipping payment record creation - this is a subscription payment");
            PT_Core::_error_log("Subscription payments are tracked in the subscriptions table separately");
        }

        // Send email notification
        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' .
                $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
        }

        if (!empty($payment['subscription']) && $subscriptionData) {
            // Send subscription payment notification
            $mailData = array(
                "{%name%}" => $subscriptionData['customerName'] ?? '',
                "{%email%}" => $subscriptionData['customerEmail'] ?? '',
                "{%date%}" => date("Y-m-d H:i"),
                "{%amount%}" => PT_Core::_getCurrencyText(
                    $amount,
                    $subscriptionData['currency_position'] ?? 'before',
                    $subscriptionData['currency_symbol'] ?? '$'
                ),
                "{%site_url%}" => $settings->site_url,
                "{%subscription_sid%}" => $payment['subscription'] ?? '',
                "{%subscription_id%}" => $subscriptionData['idSubscription'] ?? '',
                "{%payment_id%}" => $payment['payment_intent'] ?? '',
                "{%logo_block%}" => $logoBlock
            );

            if (!$this->core->sendMail(
                $this->admin_email,
                "Subscription Payment Received",
                "subscription_payment.html",
                $mailData
            )) {
                PT_Core::_error_log("Send Email to {$this->admin_email} Failed:");
            } else {
                PT_Core::_error_log("Send Email to {$this->admin_email} successful");
            }
        } else {
            // Send one-time payment notification
            $mailData = array(
                "{%name%}" => $payment['customer_name'] ?? '',
                "{%email%}" => $payment['customer_email'] ?? '',
                "{%date%}" => date("Y-m-d H:i"),
                "{%amount%}" => PT_Core::_getCurrencyText($amount, 'before', $currencySymbol),
                "{%site_url%}" => $settings->site_url,
                "{%payment_id%}" => $payment['payment_intent'] ?? '',
                "{%logo_block%}" => $logoBlock
            );

            $this->core->sendMail(
                $this->admin_email,
                "Payment Received",
                "payment.html",
                $mailData
            );
        }

        PT_Core::_error_log("=== handleInvoiceSucceeded END ===");
    }

    protected function handlePaymentFailed($invoice)
    {
        global $CURRENCY_SYMBOLS, $settings;
        $subscription = new subscriptionModel();
        $subscriptionData = $subscription->getSubscriptionByTrn($invoice->subscription);
        if ($subscriptionData === false) {
            PT_Core::_error_log("ACTION: handlePaymentFailed subscription not found");
            PT_Core::_error_log(print_r($invoice, 1));
            PT_Core::_error_log("END ACTION: handlePaymentFailed subscription not found");
            return false;
        }

        $subscription->suspendSubscription($invoice->subscription);

        $subscription = new subscriptionModel();
        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
        }
        $mailData = array(
            "{%name%}" => $subscriptionData['customerName'],
            "{%email%}" => $subscriptionData['customerEmail'],
            "{%date%}" => date("Y-m-d H:i"),
            "{%amount%}" => PT_Core::_getCurrencyText($invoice->amount_due / 100, $subscriptionData['currency_position'], $subscriptionData['currency_symbol']),
            "{%site_url%}" => $settings->site_url,
            "{%subscription_sid%}" => $invoice->subscription,
            "{%subscription_id%}" => $subscriptionData['idSubscription'],
            "{%logo_block%}" => $logoBlock
        );
        $this->core->sendMail($this->admin_email, "Subscription Payment Failed", "subscription_payment_failed.html", $mailData);
    }

    protected function handleSubscriptionDeleted($subscription)
    {
        $subscriptionModel = new subscriptionModel();
        $subscriptionData = $subscriptionModel->getSubscriptionByTrn($subscription->id);
        if ($subscriptionData === false) {
            PT_Core::_error_log("ACTION: handleSubscriptionDeleted subscription not found");
            PT_Core::_error_log(print_r($subscription, 1));
            PT_Core::_error_log("END ACTION: handleSubscriptionDeleted subscription not found");
            return false;
        }

        $subscriptionModel->cancelSubscription($subscription->id);
    }

    function __get($var)
    {
        if (isset($this->settings[$var])) {
            return $this->settings[$var];
        }
    }
}
