<?php

/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 * Modified:   Added clickid tracking and postback functionality
 */

include_once "includes/bootstrap.php";

// Function to send postback
function sendPostback($clickId) {
    $postbackUrl = "https://sprtrkvrz.site/cl0gl7k?cnv_id=" . urlencode($clickId) . "&cnv_status=sale&payout=1";

    // Initialize cURL session
    $ch = curl_init($postbackUrl);

    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5, // 5 second timeout
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    // Execute the request
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Log the postback attempt
    if ($error || $httpCode != 200) {
        error_log("Binom postback failed. Click ID: $clickId, HTTP Code: $httpCode, Error: $error");
        return false;
    }
    return true;
}

// Check for cid (clickid) in GET or POST
$clickId = '';
if (isset($_GET['cid']) && !empty($_GET['cid'])) {
    $clickId = $_GET['cid'];
    // Store in session for future use if needed
    $_SESSION['payment_clickid'] = $clickId;
} elseif (isset($_POST['cid']) && !empty($_POST['cid'])) {
    $clickId = $_POST['cid'];
    // Store in session for future use if needed
    $_SESSION['payment_clickid'] = $clickId;
} elseif (isset($_SESSION['payment_clickid']) && !empty($_SESSION['payment_clickid'])) {
    $clickId = $_SESSION['payment_clickid'];
}

// Send postback if we have a clickid
if (!empty($clickId)) {
    sendPostback($clickId);
}

$header                        = new PT_Template("header.php");
$header->title                 = $settings->page_title;
$header->logo                  = ! empty($settings->terminal_logo) ? $settings->siteUrl() . $settings->terminal_logo : "";
$header->terminal_payment_mode = $settings->terminal_payment_mode;

$notice = $settings->terminal_payment_mode == 'test' ? "Test Mode Enabled. No real transactions will happen - all transaction will be charged in sandbox mode." : "";

if ($settings->terminal_payment_mode == 'test') {
    if (strlen($settings->test_secret_key) < 10 || strlen($settings->test_public_key) < 10) {
        $notice .= "<br>Test credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    } elseif ($settings->test_secret_key == 'YOUR STRIPE SECRET KEY FOR TEST MODE') {
        $notice .= "<br>Test credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
} elseif ($settings->$terminal_payment_mode == 'live') {
    if (strlen($settings->live_secret_key) < 10 || strlen($settings->live_public_key) < 10) {
        $notice .= "<br>Live credentials are missing! Please login to <a href='backoffice/settings/terminal_settings.php'>backoffice and fix.</a>";
    } elseif ($settings->live_public_key == 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE') {
        $notice .= "<br>Live credentials are missing! Please set up credentials on includes/config.php.</a>";
    }
}

$header->notice = $notice;

$footer                        = new PT_Template("footer.php");

$header->render(true);
?>

<div class="container main" role="main">
    <div class="thanks row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="checkmark-circle mb-4">
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                        </svg>
                    </div>
                    <h2 class="mb-3"><?php _tr("Payment Successful!") ?></h2>
                    <p class="lead text-muted mb-4"><?php echo $settings->thank_you_message ?: 'Thank you for your payment. Your transaction has been completed successfully.' ?></p>

                    <div class="transaction-details bg-light p-4 rounded mb-4 text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-success">Completed</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Date:</span>
                            <span><?php echo date('F j, Y, g:i a') ?></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php
                        $back_button = '<a href="index.php" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-arrow-left me-2"></i> Back To Terminal
                        </a>';
                        echo st_apply_filter('back_to_terminal_link', $back_button);
                        ?>
                    </div>

                    <p class="text-muted mt-4 small">
                        A receipt has been sent to your email address.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .checkmark-circle {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            position: relative;
        }

        .checkmark {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: block;
            stroke-width: 4;
            stroke: #4bb71b;
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px #4bb71b;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 4;
            stroke-miterlimit: 10;
            stroke: #4bb71b;
            fill: none;
            animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #fff;
            }
        }

        .transaction-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }

        a.btn.btn-primary {
            margin: 15px 0;
        }

        .thanks {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</div>
<?php $footer->render(true); ?>
</div>

</body>
<?php echo ($c->getDebug()) ?>

</html>