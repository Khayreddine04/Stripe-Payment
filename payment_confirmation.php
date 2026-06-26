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

include_once "includes/bootstrap.php";

$header                        = new PT_Template( "header.php" );
$header->title                 = $settings->page_title;
$header->logo                  = ! empty( $settings->terminal_logo ) ? $settings->siteUrl() . $settings->terminal_logo : "";
$header->terminal_payment_mode = $settings->terminal_payment_mode;

$notice = $settings->terminal_payment_mode == 'test' ? "Test Mode Enabled. No real transactions will happen - all transaction will be charged in sandbox mode." : "";

if ($settings->terminal_payment_mode == 'test') {
    if (strlen($settings->test_secret_key) < 10 || strlen($settings->test_public_key) < 10) {
        $notice .= "<br>Test credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    }elseif( $settings->test_secret_key == 'YOUR STRIPE SECRET KEY FOR TEST MODE'){
        $notice .= "<br>Test credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
} elseif ($settings->terminal_payment_mode == 'live') {
    if (strlen($settings->live_secret_key) < 10 || strlen($settings->live_public_key) < 10) {
        $notice .= "<br>Live credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    }elseif( $settings->live_public_key == 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE'){
        $notice .= "<br>Live credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
}

$header->notice = $notice;

$footer                        = new PT_Template( "footer.php" );

$pt_payment = $c->esc('pt_payment');
$pt_subscription = $c->esc('pt_subscription');

$payment = new paymentModel();
$payment->setID($pt_payment);

$paymentDetails = $payment->getPayment();

if ($paymentDetails !== false && $settings->custom_action == 'y') {
    //PT_Core::_dump($paymentDetails);
    $js_map_data = array(
        '%SPT_Amount%' => $paymentDetails['amount'],
        '%SPT_OrderID%' => $paymentDetails['idPayment'],
        '%SPT_TrnID%' => $paymentDetails['stripeCharge'],
        '%SPT_ProductID%' => $paymentDetails['idItem'],
        '%SPT_CusEmail%' => $paymentDetails['customerEmail'],
        '%SPT_CusFName%' => $paymentDetails['customerName']
    );

    $custom_action_code = $settings->custom_action_code;
    $js_code = strtr($custom_action_code, $js_map_data);

}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include currency utilities if not already included
if (!function_exists('get_formatted_subscription_amount') && file_exists(__DIR__ . '/includes/utils/currency_utils.php')) {
    include_once __DIR__ . '/includes/utils/currency_utils.php';
}

$header->render(true);
?>

<div class="confirmation-container">
    <div class="confirmation-card">
        <div class="confirmation-header">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h1 class="confirmation-title"><?php _tr("Payment Successful!") ?></h1>
            <p class="confirmation-subtitle">Thank you for your purchase</p>
        </div>

        <?php if ($paymentDetails !== false): ?>
            <div class="payment-details">
                <h2 class="section-title">Order Details</h2>
                <div class="order-summary">
                    <div class="customer-info">
                        <p class="greeting">Hello, <strong><?php echo htmlspecialchars($paymentDetails['customerName']); ?></strong></p>
                        <p>Your payment has been processed successfully.</p>
                    </div>

                    <div class="order-items">
                        <div class="order-item">
                            <span class="item-label">Order Number</span>
                            <span class="item-value">#<?php echo htmlspecialchars($paymentDetails['idPayment']); ?></span>
                        </div>
                        <div class="order-item">
                            <span class="item-label">Date</span>
                            <span class="item-value"><?php echo date('F j, Y, g:i a', strtotime($paymentDetails['date'])); ?></span>
                        </div>
                        <div class="order-item">
                            <span class="item-label">Transaction ID</span>
                            <span class="item-value transaction-id"><?php echo htmlspecialchars($paymentDetails['stripeCharge']); ?></span>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <?php if (function_exists('get_currency_data') && get_currency_data() !== null): ?>
                            <div class="order-item">
                                <span class="item-label">Subscription</span>
                                <span class="item-value">
                                    <?php 
                                    $subscriptionAmount = get_formatted_subscription_amount();
                                    $period = get_subscription_period();
                                    echo htmlspecialchars($subscriptionAmount);
                                    if (!empty($period)) {
                                        echo ' ' . htmlspecialchars($period);
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if (has_upfront_fee()): ?>
                                <div class="order-item">
                                    <span class="item-label">Upfront Fee</span>
                                    <span class="item-value"><?php echo htmlspecialchars(get_formatted_upfront_amount()); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="order-total">
                                <span class="total-label">Total Paid</span>
                                <span class="total-amount"><?php echo htmlspecialchars(get_formatted_total_amount()); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="order-total">
                                <span class="total-label">Amount Paid</span>
                                <span class="total-amount"><?php echo htmlspecialchars($paymentDetails['amount'] . ' ' . $paymentDetails['currency']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="error-notice">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>We couldn't retrieve your payment details. Please contact support with this reference: <strong><?php echo htmlspecialchars($pt_payment); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($settings->thank_you_message)): ?>
            <div class="thank-you-message">
                <h3>Thank You Note</h3>
                <p><?php echo nl2br(htmlspecialchars($settings->thank_you_message)); ?></p>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <?php echo st_apply_filter('back_to_terminal_link', '<a href="index.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Terminal
            </a>'); ?>
            
            <?php if (!empty($settings->dashboard_url)): ?>
                <a href="<?php echo htmlspecialchars($settings->dashboard_url); ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .confirmation-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 2rem;
            background-color: #f8fafc;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .confirmation-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29-22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }
        
        .success-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .success-icon svg {
            color: white;
            width: 40px;
            height: 40px;
        }
        
        .confirmation-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .confirmation-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .payment-details {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            color: #1f2937;
            margin: 0 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-summary {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .customer-info {
            margin-bottom: 1.5rem;
        }
        
        .greeting {
            font-size: 1.1rem;
            color: #1f2937;
            margin: 0 0 0.5rem;
        }
        
        .order-items {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: #4b5563;
        }
        
        .item-label {
            color: #6b7280;
        }
        
        .transaction-id {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
        
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 1rem 0;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .total-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4f46e5;
        }
        
        .error-notice {
            display: flex;
            align-items: flex-start;
            background: #fef2f2;
            color: #991b1b;
            padding: 1.25rem;
            border-radius: 8px;
            margin: 1.5rem;
        }
        
        .error-notice svg {
            margin-right: 0.75rem;
            flex-shrink: 0;
            margin-top: 0.2rem;
        }
        
        .thank-you-message {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 0 2rem 2rem;
            border-left: 4px solid #0ea5e9;
        }
        
        .thank-you-message h3 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            color: #0369a1;
            font-size: 1.1rem;
        }
        
        .thank-you-message p {
            margin: 0;
            color: #0c4a6e;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            padding: 0 2rem 2.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 1rem;
            border: none;
            cursor: pointer;
        }
        
        .btn svg {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
        }
        
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2), 0 4px 6px -2px rgba(79, 70, 229, 0.1);
        }
        
        .btn-secondary {
            background: white;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        @media (max-width: 640px) {
            .confirmation-container {
                padding: 1rem;
            }
            
            .confirmation-card {
                border-radius: 8px;
            }
            
            .confirmation-header {
                padding: 1.5rem 1rem;
            }
            
            .payment-details {
                padding: 1.5rem 1rem;
            }
            
            .thank-you-message {
                margin: 0 1rem 1.5rem;
            }
            
            .action-buttons {
                padding: 0 1rem 1.5rem;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
    </div>
</div>
<?php $footer->render( true ); ?>
</div>
<?php echo( $c->getDebug() ) ?>
<?php if(!empty($js_code)){ ?>
    <script>
        <?php echo $js_code?>
    </script>
<?php } ?>
</body>

</html>
