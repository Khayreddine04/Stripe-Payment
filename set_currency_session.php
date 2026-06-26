<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log the received data for debugging
error_log('Received currency data: ' . print_r($data, true));

if ($data) {
    // Extract currency from different possible locations in the response
    $currency = null;
    
    // Check different possible locations for the currency
    if (!empty($data['currency'])) {
        $currency = $data['currency'];
    } elseif (!empty($data['currency_code'])) {
        $currency = $data['currency_code'];
    } elseif (!empty($data['rawData']['subscription_amount']['currency_code'])) {
        $currency = $data['rawData']['subscription_amount']['currency_code'];
    } elseif (!empty($data['rawData']['upfront_amount']['currency_code'])) {
        $currency = $data['rawData']['upfront_amount']['currency_code'];
    } elseif (!empty($data['rawData']['subscription_amount']['currency'])) {
        $currency = $data['rawData']['subscription_amount']['currency'];
    } elseif (!empty($data['rawData']['upfront_amount']['currency'])) {
        $currency = $data['rawData']['upfront_amount']['currency'];
    } else {
        // Default to USD if no currency is found
        $currency = 'USD';
    }
    
    // Prepare the session data
    $sessionData = [
        'amount' => isset($data['amount']) ? (float)$data['amount'] : null,
        'currency' => $currency,
        'period' => $data['period'] ?? null,
        'upfront_amount' => isset($data['upfront_amount']) ? (float)$data['upfront_amount'] : 0,
        'rawData' => $data['rawData'] ?? null,
        'timestamp' => time()
    ];
    
    // Store the currency data in the session
    $_SESSION['api_currency_data'] = $sessionData;
    
    // Also store the currency separately for easier access
    $_SESSION['currency'] = $currency;
    
    // Log the stored data for debugging
    error_log('Stored in session: ' . print_r($_SESSION['api_currency_data'], true));
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Currency data stored in session',
        'data' => [
            'amount' => $sessionData['amount'],
            'currency' => $sessionData['currency'],
            'period' => $sessionData['period']
        ]
    ]);
} else {
    // Return error response
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid or empty data received',
        'received_data' => $json
    ]);
}
