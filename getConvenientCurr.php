<?php
// Enable error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set up error logging
$logFile = __DIR__ . '/logs/currency_errors.log';
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Only set error log if directory is writable
if (is_writable($logDir)) {
    ini_set('error_log', $logFile);
}

// Function to log errors with timestamp
function logError($message, $data = null) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== null) {
        $logMessage .= ' ' . (is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT));
    }
    error_log($logMessage);
}

// Include config and bootstrap
try {
    // Set a temporary error handler to catch include warnings
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
    
    // Include bootstrap first
    $bootstrapFile = __DIR__ . '/includes/bootstrap.php';
    if (!file_exists($bootstrapFile)) {
        throw new Exception("Bootstrap file not found at: " . $bootstrapFile);
    }
    require_once $bootstrapFile;
    
    // Include config
    $configFile = __DIR__ . '/includes/_config.php';
    if (!file_exists($configFile)) {
        throw new Exception("Config file not found at: " . $configFile);
    }
    require_once $configFile;
    
    // Restore error handler
    restore_error_handler();
    
    logError('getConvenientCurr.php: Bootstrap and config loaded successfully');
    
} catch (Exception $e) {
    $errorMsg = 'Error including required files: ' . $e->getMessage() . 
               ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    logError($errorMsg);
    
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Initialization Error',
        'message' => 'Failed to initialize application',
        'details' => $errorMsg,
        'debug' => [
            'php_version' => phpversion(),
            'os' => PHP_OS,
            'cwd' => getcwd(),
            'included_files' => get_included_files()
        ]
    ], JSON_PRETTY_PRINT));
}

// Start session after all includes
if (session_status() === PHP_SESSION_NONE) {
    try {
        if (!session_start()) {
            throw new Exception('session_start() returned false');
        }
        logError('Session started successfully');
    } catch (Exception $e) {
        $errorMsg = 'Failed to start session: ' . $e->getMessage();
        logError($errorMsg);
        
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Session Error',
            'message' => 'Failed to start session',
            'details' => $errorMsg
        ], JSON_PRETTY_PRINT));
    }
}

// Get parameters from URL
// Example: https://vip.cosmopredict.com/getConvenientCurr.php?ctc=0.10
$ctc = isset($_GET['ctc']) ? trim($_GET['ctc']) : '';
$serviceId = isset($_GET['service']) ? trim($_GET['service']) : '';

logError('getConvenientCurr.php: Parameters - ctc=' . $ctc . ', service=' . $serviceId);

// Detect country automatically
$country = '';
try {
    logError('Attempting to detect country automatically...');
    
    // Include country detector
    $detectorPath = __DIR__ . '/includes/country_detector.php';
    logError('Country detector path: ' . $detectorPath);
    
    // Comprehensive file checks
    if (!file_exists($detectorPath)) {
        throw new Exception('Country detector file not found at: ' . $detectorPath);
    }
    
    if (!is_readable($detectorPath)) {
        $perms = substr(sprintf('%o', fileperms($detectorPath)), -4);
        throw new Exception('Country detector file not readable. Permissions: ' . $perms);
    }
    
    $fileSize = filesize($detectorPath);
    if ($fileSize === 0) {
        throw new Exception('Country detector file is empty');
    }
    logError('Country detector file size: ' . $fileSize . ' bytes');
    
    // Include the file
    if (!function_exists('get_user_country')) {
        logError('Including country_detector.php...');
        
        // Use output buffering to capture any potential output
        ob_start();
        $includeResult = include_once $detectorPath;
        $output = ob_get_clean();
        
        if (!empty($output)) {
            logError('Output during include: ' . $output);
        }
        
        if ($includeResult === false) {
            throw new Exception('Failed to include country_detector.php');
        }
        
        logError('country_detector.php included successfully');
    }
    
    // Verify function exists
    if (!function_exists('get_user_country')) {
        throw new Exception('get_user_country() function not found after including file');
    }
    
    // Call the function
    logError('Calling get_user_country()...');
    $country = get_user_country();
    
    if (empty($country)) {
        throw new Exception('get_user_country() returned empty value');
    }
    
    // Validate the returned country code
    $country = strtoupper(trim($country));
    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        throw new Exception('Invalid country code format from get_user_country(): ' . $country);
    }
    
    logError('Successfully detected country code: ' . $country);
    
} catch (Exception $e) {
    $errorMsg = 'ERROR detecting country: ' . $e->getMessage() . 
               ' in ' . $e->getFile() . ' on line ' . $e->getLine() . 
               '\nStack trace: ' . $e->getTraceAsString();
    logError($errorMsg);
    
    // Fall back to default country
    $country = 'US';
    logError('Using default country code: ' . $country);
}


// Validate inputs
if (empty($country) || empty($ctc)) {
    logError('getConvenientCurr.php: Missing parameters');
    header('Content-Type: application/json');
    http_response_code(400);
    error_log('getConvenientCurr.php: Missing parameters');
    die(json_encode([
        'success' => false,
        'error' => 'Missing parameters',
        'message' => 'Please provide both country and ctc parameters'
    ]));
}

try {
    error_log('getConvenientCurr.php: Inside try block');
    // Google Sheets Configuration
    $publishedKey = '2PACX-1vTTMI1QOfIJpHeUqIGt6WB-ixfEtMzXe_GVUHHkBahe6y_GDEGBrHq2oDtKE4_5MMQsbpsvTBf5QcLD';

    // Fetch data from both sheets
    $ctcData = fetchPublishedSheetData($publishedKey, 290147050); // CTC sheet
    $subscriptionData = fetchPublishedSheetData($publishedKey, 444002262); // Subscription sheet

    error_log('getConvenientCurr.php: Fetched data from Google Sheets');

    // Find values from both sheets
    $ctcResult = lookupCTCValue($ctcData, $country, $ctc);
    $subscriptionResult = lookupSubscriptionValue($subscriptionData, $country);

    // Extract amount and currency from CTC
    $ctcData = extractAmountAndCurrency($ctcResult);
    $ctcAmount = $ctcData['amount'];
    $ctcAmountNumeric = $ctcData['amount_numeric'];
    $ctcCurrencySymbol = $ctcData['currency_symbol'];
    $ctcCurrencyCode = $ctcData['currency_code'];

    // Extract amount and currency from Subscription
    $subscriptionData = extractAmountAndCurrency($subscriptionResult);
    $subscriptionAmount = $subscriptionData['amount'];
    $subscriptionAmountNumeric = $subscriptionData['amount_numeric'];
    $subscriptionCurrencySymbol = $subscriptionData['currency_symbol'];
    $subscriptionCurrencyCode = $subscriptionData['currency_code'];

    // List of RTL currency symbols
    $rtlCurrencies = ['ر.س', 'د.إ', 'ر.ق', 'د.ك', 'ع.د', 'ل.ل', 'ج.م', 'د.ج', 'د.م.', 'د.ت', 'ل.د'];
    
    // Format CTC amount with proper spacing
    $isCtcRtl = in_array($ctcCurrencySymbol, $rtlCurrencies);
    $formattedCtcAmount = $isCtcRtl 
        ? $ctcAmount . ' ' . $ctcCurrencySymbol
        : $ctcCurrencySymbol . ' ' . $ctcAmount;

    // Format Subscription amount with proper spacing
    $isSubscriptionRtl = in_array($subscriptionCurrencySymbol, $rtlCurrencies);
    $formattedSubscriptionAmount = $isSubscriptionRtl 
        ? $subscriptionAmount . ' ' . $subscriptionCurrencySymbol
        : $subscriptionCurrencySymbol . ' ' . $subscriptionAmount;

    // Default period
    $period = 'monthly';  // Fallback value

    // Fetch period from the database if service ID is provided
    if (!empty($serviceId)) {
        try {
            global $mysqli;
            $stmt = $mysqli->prepare("SELECT itemFrequency FROM vcp_pt_items WHERE idItem = ?");
            $stmt->bind_param('s', $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $row = $result->fetch_assoc()) {
                if (!empty($row['itemFrequency'])) {
                    $period = $row['itemFrequency'];
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            // Log the error but continue with the default period
            error_log("Error fetching item frequency: " . $e->getMessage());
        }
    }

    // Prepare response
    $response = [
        'success' => true,
        'country' => $country,
        'ctc' => $ctc,
        'upfront_amount' => [
            'amount' => $formattedCtcAmount,
            'amount_numeric' => $ctcAmountNumeric,
            'currency_symbol' => $ctcCurrencySymbol,
            'currency_code' => $ctcCurrencyCode
        ],
        'subscription_amount' => [
            'amount' => $formattedSubscriptionAmount,
            'amount_numeric' => $subscriptionAmountNumeric,
            'currency_symbol' => $subscriptionCurrencySymbol,
            'currency_code' => $subscriptionCurrencyCode,
            'period' => $period  // Dynamic value from the database
        ],
        'service_id' => $serviceId,
        'item_updated' => false
    ];

    // Store data in session
    $_SESSION['api_currency_data'] = $response;

    error_log('getConvenientCurr.php: Session data set: ' . print_r($_SESSION, true));

    error_log('getConvenientCurr.php: Success, returning JSON response');

    // Return the response with proper encoding
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log('getConvenientCurr.php: Exception caught: ' . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * Fetch data from published Google Sheet
 * 
 * @param string $publishedKey The published key from Google Sheets URL
 * @param int $gid The sheet ID
 * @return array Array of sheet data
 * @throws Exception If there's an error fetching or parsing the data
 */
function fetchPublishedSheetData($publishedKey, $gid)
{
    // URL for published CSV version
    $url = "https://docs.google.com/spreadsheets/d/e/{$publishedKey}/pub?output=csv&gid={$gid}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!empty($curlError)) {
        throw new Exception('Curl error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('Failed to fetch data from published Google Sheet. HTTP Code: ' . $httpCode . '. Make sure the sheet is published to the web.');
    }

    if (empty($response)) {
        throw new Exception('Empty response from Google Sheets');
    }

    // Parse CSV data
    $rows = array_map('str_getcsv', explode("\n", trim($response)));

    // Remove empty rows
    $rows = array_filter($rows, function ($row) {
        return !empty(array_filter($row, function ($cell) {
            return $cell !== '' && $cell !== null;
        }));
    });

    return array_values($rows);
}

/**
 * Extract amount and currency from a string
 * 
 * @param string $input The input string containing amount and currency
 * @return array Array with 'amount', 'amount_numeric', and 'currency' keys
 */
/**
 * Get currency code from symbol
 *
 * @param string $symbol The currency symbol
 * @return string The currency code or empty string if not found
 */
function getCurrencyCodeFromSymbol($symbol)
{
    global $CURRENCY_SYMBOLS;
    foreach ($CURRENCY_SYMBOLS as $code => $s) {
        if ($s === $symbol) {
            return $code;
        }
    }
    return '';
}

function extractAmountAndCurrency($input) {
    // Remove any non-breaking spaces and normalize spaces
    $input = str_replace(["\xc2\xa0", "  "], " ", trim($input));
    
    // Extract the amount (numbers, decimal points, and commas)
    preg_match('/([0-9]+[.,]?[0-9]*)/', $input, $matches);
    $amount = $matches[1] ?? '';
    
    // The currency ISO code is everything that's not the amount
    $currencyIso = trim(str_replace($amount, '', $input));
    
    // Clean up the amount (replace comma with dot for float conversion)
    $amountNumeric = (float)str_replace(',', '.', $amount);
    
    // Get the currency symbol from the ISO code using the $CURRENCY_SYMBOLS array
    global $CURRENCY_SYMBOLS;
    $currencyCode = strtoupper(trim($currencyIso));
    $currencySymbol = $CURRENCY_SYMBOLS[$currencyCode] ?? $currencyCode;
    
    // Ensure we're using the symbol from $CURRENCY_SYMBOLS
    if (isset($CURRENCY_SYMBOLS[$currencyCode])) {
        $currencySymbol = $CURRENCY_SYMBOLS[$currencyCode];
    }
    
    return [
        'amount' => $amount,
        'amount_numeric' => $amountNumeric,
        'currency_symbol' => $currencySymbol,
        'currency_code' => $currencyCode
    ];
}

/**
 * Lookup CTC value in the data array based on country and CTC
 * 
 * @param array $data The sheet data
 * @param string $country 2-letter country code
 * @param string $ctc Cost to Company value
 * @return string The found value with currency symbol
 * @throws Exception If country or CTC is not found
 */
function lookupCTCValue($data, $country, $ctc)
{
    if (empty($data)) {
        throw new Exception('No data found in CTC sheet');
    }

    // First row is header with CTC numbers
    $header = $data[0];

    // Find the column index for the given CTC
    $ctcColumn = -1;
    for ($i = 1; $i < count($header); $i++) {
        // Extract numeric value from header (e.g., '14.95 EURO' -> '14.95')
        if (preg_match('/([0-9]+\.?[0-9]*)/', $header[$i], $matches)) {
            $headerValue = $matches[1];
            if ($headerValue == $ctc) {
                $ctcColumn = $i;
                break;
            }
        }
    }

    if ($ctcColumn === -1) {
        throw new Exception("CTC number '{$ctc}' not found in header. Available values: " .
            implode(', ', array_map(function ($h) {
                if (preg_match('/([0-9]+\.?[0-9]*)/', $h, $m)) {
                    return $m[1];
                }
                return $h;
            }, array_slice($header, 1))));
    }

    // Find the row for the given country
    // Match against last 2 letters of sheet values (e.g., "customPriceDE" -> "DE")
    $countryRow = -1;
    for ($i = 1; $i < count($data); $i++) {
        if (isset($data[$i][0])) {
            $sheetCountry = strtoupper(substr(trim($data[$i][0]), -2));
            if ($sheetCountry === $country) {
                $countryRow = $i;
                break;
            }
        }
    }

    if ($countryRow === -1) {
        throw new Exception("Country '{$country}' not found in CTC sheet");
    }

    // Get the value at the intersection
    if (!isset($data[$countryRow][$ctcColumn])) {
        throw new Exception("No value found for country '{$country}' and CTC '{$ctc}' in CTC sheet");
    }

    return $data[$countryRow][$ctcColumn];
}

/**
 * Lookup subscription value in the data array based on country
 * 
 * @param array $data The subscription sheet data
 * @param string $country 2-letter country code
 * @return string The found value with currency symbol
 * @throws Exception If country is not found
 */
function lookupSubscriptionValue($data, $country)
{
    if (empty($data)) {
        throw new Exception('No data found in subscription sheet');
    }

    // First row is header - subscription sheet should have country and amount columns
    $header = $data[0];

    // Find the amount column (assuming it's the second column, index 1)
    $amountColumn = 1; // Adjust this if the subscription sheet has different structure

    // Find the row for the given country
    $countryRow = -1;
    for ($i = 1; $i < count($data); $i++) {
        if (isset($data[$i][0])) {
            $sheetCountry = strtoupper(substr(trim($data[$i][0]), -2));
            if ($sheetCountry === $country) {
                $countryRow = $i;
                break;
            }
        }
    }

    if ($countryRow === -1) {
        throw new Exception("Country '{$country}' not found in subscription sheet");
    }

    // Get the subscription amount value
    if (!isset($data[$countryRow][$amountColumn])) {
        throw new Exception("No subscription amount found for country '{$country}'");
    }

    return $data[$countryRow][$amountColumn];
}