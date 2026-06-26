<?php
/**
 * Enhanced Stripe Payment Intent Creation
 * Added better error handling, input validation, and detailed logging
 */

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set a custom error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Set JSON content type header
header('Content-Type: application/json');

// Initialize response
$response = [
    'res' => false,
    'msg' => 'An unknown error occurred',
    'intent' => 0,
    'debug' => [] // For development only - remove in production
];

try {
    // Include necessary files
    include_once "../../includes/bootstrap.php";
    
    // Log incoming request for debugging
    error_log("=== Stripe Payment Intent Request ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    // Enhanced service ID handling with better validation and logging
    $pt_service = '';
    $serviceSource = 'none';
    $serviceIdType = 'unknown';
    
    // Define all possible service ID sources with their priorities
    $serviceSources = [
        'pt_service_param' => $c->_esc("pt_service"),
        'pt_service_post' => $_POST['pt_service'] ?? '',
        'service_param' => $c->_esc("service"),
        'service_post' => $_POST['service'] ?? '',
        'session_pt_service' => $_SESSION['pt_service'] ?? ''
    ];
    
    // Find the first non-empty service ID from all possible sources
    foreach ($serviceSources as $source => $value) {
        $value = is_string($value) ? trim($value) : '';
        if (!empty($value)) {
            $pt_service = $value;
            $serviceSource = $source;
            break;
        }
    }
    
    // Log the service ID source and value (masked for security)
    $maskedServiceId = !empty($pt_service) ? 
        substr($pt_service, 0, 4) . '...' . substr($pt_service, -4) : 'empty';
    error_log("Service ID found in '$serviceSource': $maskedServiceId");
    
    // Validate and sanitize all inputs
    $amount = filter_var($c->_esc("amount"), FILTER_VALIDATE_FLOAT, [
        'options' => ['min_range' => 0.01]
    ]);
    $pt_amount = filter_var($c->_esc("pt_amount", $amount), FILTER_VALIDATE_FLOAT, [
        'options' => ['min_range' => 0.01]
    ]);
    
    // Currency validation with case-insensitive comparison
    $currency = strtoupper(trim($c->_esc("currency")));
    $pt_currency = strtoupper(trim($c->_esc("pt_currency", $currency)));
    
    // Other fields
    $pt_type = $c->_esc("pt_type", "card");
    $pt_payment_type = $c->_esc("pt_payment_type", 'once');
    $pt_name = trim($c->_esc("pt_name"));
    $pt_email = filter_var(trim($c->_esc("pt_email")), FILTER_VALIDATE_EMAIL);
    $pt_description = trim($c->_esc("pt_description"));
    $invoice = filter_var($c->_esc("invoice", 0), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
    ]);
    $idInvoice = filter_var($c->_esc("idInvoice", 0), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
    ]);
    $stripeButton = $c->_esc("stripeButton", 'n') === 'y';
    
    // Input validation
    $errors = [];
    
    // Validate amount
    if ($pt_amount === false || $pt_amount <= 0) {
        $errors[] = "Invalid payment amount: " . $c->_esc("pt_amount");
    }
    
    // Validate currency
    $validCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    if (!in_array($pt_currency, $validCurrencies, true)) {
        $errors[] = "Unsupported currency: {$pt_currency}. Must be one of: " . implode(', ', $validCurrencies);
    }
    
    // Enhanced service ID validation
    if (empty($pt_service)) {
        $errors[] = "Service ID is required";
        error_log("Service ID validation failed - no service ID found in any source");
    } else {
        // Check service ID format
        $isUuid = (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pt_service);
        $isNumeric = is_numeric($pt_service);
        $isLegacyId = (bool)preg_match('/^ID-\d+$/i', $pt_service);
        
        // Determine service ID type for logging
        if ($isUuid) {
            $serviceIdType = 'UUID';
        } elseif ($isNumeric) {
            $serviceIdType = 'numeric';
        } elseif ($isLegacyId) {
            $serviceIdType = 'legacy';
        }
        
        // Log the service ID format for debugging
        error_log(sprintf(
            "Service ID validation - Type: %s, Length: %d, Source: %s",
            $serviceIdType,
            strlen($pt_service),
            $serviceSource
        ));
        
        // Validate format
        if (!$isUuid && !$isNumeric && !$isLegacyId) {
            $errors[] = "Invalid service ID format. Expected UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx), numeric ID, or legacy ID format (ID-123)";
        } elseif ($isUuid && strlen($pt_service) !== 36) {
            $errors[] = "Invalid UUID format. Must be 36 characters long";
        } elseif ($isLegacyId && !preg_match('/^ID-\d{1,10}$/i', $pt_service)) {
            $errors[] = "Invalid legacy ID format. Must be in format ID-123 (up to 10 digits)";
        }
        
        // If we have a valid service ID, store it in the session for future use
        if (empty($errors)) {
            $_SESSION['pt_service'] = $pt_service;
            error_log("Service ID stored in session: " . $maskedServiceId);
        }
    }
    
    // If there are validation errors, return them with debug info
    if (!empty($errors)) {
        $response['msg'] = implode("; ", $errors);
        $response['debug']['validation_errors'] = $errors;
        $response['debug']['received_data'] = [
            'pt_service' => $pt_service,
            'pt_amount' => $pt_amount,
            'pt_currency' => $pt_currency
        ];
        error_log("Validation errors: " . $response['msg']);
        echo json_encode($response);
        exit;
    }
    
    // Initialize payment processor
    $payment = new PT_Stripe_Payment();
    
    // Set payment data
    $payment->amount = $pt_amount;
    $payment->currency = strtolower($pt_currency);
    $payment->service_id = $pt_service;
    $payment->payment_type = $pt_payment_type;
    
    // Store customer information in the post array which is used by the payment class
    $_POST['pt_name'] = $pt_name;
    $_POST['pt_email'] = $pt_email;
    $_POST['pt_description'] = $pt_description;
    
    // Set invoice information if available
    if ($invoice > 0) {
        $payment->invoice_id = $invoice;
        // Initialize invoice_data if needed by getPaymentDescription
        $payment->invoice_data = [
            'invoiceNumber' => $invoice,
            // Add other invoice data if needed
        ];
    }
    
    // Enhanced payment data logging with masking of sensitive info
    $logData = [
        'amount' => number_format($payment->amount, 2),
        'currency' => $payment->currency,
        'service_id' => !empty($payment->service_id) ? 
            substr($payment->service_id, 0, 4) . '...' . substr($payment->service_id, -4) : 'none',
        'payment_type' => $payment->payment_type,
        'customer_name' => !empty($pt_name) ? substr($pt_name, 0, 1) . '...' : 'not provided',
        'customer_email' => !empty($pt_email) ? 
            (strpos($pt_email, '@') !== false ? 
                substr($pt_email, 0, 3) . '...@...' . substr(strrchr($pt_email, "."), 0) : 
                'invalid-email') : 'not provided',
        'invoice_id' => $payment->invoice_id ?? 'none',
        'service_id_type' => $serviceIdType,
        'service_id_source' => $serviceSource
    ];
    
    error_log("Payment processing started with data: " . json_encode($logData, JSON_PRETTY_PRINT));
    
    // Log additional debug info if in development mode
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        $debugInfo = [
            'post_data' => array_map(function($key, $value) {
                // Mask sensitive fields in POST data
                if (in_array($key, ['card_number', 'cvv', 'expiry'], true)) {
                    return '***REDACTED***';
                }
                return is_string($value) ? substr($value, 0, 100) : $value;
            }, array_keys($_POST), $_POST),
            'server' => [
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        ];
        error_log("Debug info: " . json_encode($debugInfo, JSON_PRETTY_PRINT));
    }
    
    // Create payment intent
    if ($payment->getPaymentIntent()) {
        $response = [
            'res' => true,
            'intent' => $payment->intent,
            'setupIntent' => $payment->setup_intent ?? null,
            'processing' => $payment->payment_mode ?? 'payment',
            'msg' => 'Payment intent created successfully'
        ];
        error_log("Payment intent created successfully: " . ($payment->intent ?? 'No intent ID'));
    } else {
        $errorMsg = $payment->error ?? 'Unknown error creating payment intent';
        $response['msg'] = $errorMsg;
        $response['debug']['payment_error'] = $errorMsg;
        error_log("Failed to create payment intent: " . $errorMsg);
    }
    
} catch (Exception $e) {
    // Log the full error with stack trace
    $errorMsg = "Server error: " . $e->getMessage();
    error_log($errorMsg);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return a generic error message to the client
    $response['msg'] = $errorMsg;
    // For debugging only - remove in production
    $response['debug']['error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
} catch (Error $e) {
    // Handle PHP 7+ Error exceptions
    $errorMsg = "Fatal error: " . $e->getMessage();
    error_log($errorMsg);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $response['msg'] = $errorMsg;
    // For debugging only - remove in production
    $response['debug']['error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// Log the final response for debugging
error_log("Final response: " . json_encode($response, JSON_PRETTY_PRINT));

// Output the JSON response
echo json_encode($response);