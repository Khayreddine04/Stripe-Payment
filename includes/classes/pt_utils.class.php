<?php
/**
 * Payment Terminal Utilities
 * Common validation and helper functions
 */

class PT_Utils {
    /**
     * Validate UUID format
     * 
     * @param string $uuid The UUID to validate
     * @return bool True if valid UUID, false otherwise
     */
    public static function isValidUuid($uuid) {
        if (!is_string($uuid) || empty($uuid)) {
            return false;
        }
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get service ID from request with fallback to session
     * 
     * @param array $request The request data (usually $_POST or $_GET)
     * @param array $session The session data (usually $_SESSION)
     * @return string|null The service ID or null if not found
     */
    public static function getServiceId($request, $session = []) {
        // Check request parameters first
        if (!empty($request['pt_service']) && self::isValidUuid($request['pt_service'])) {
            return $request['pt_service'];
        }
        
        // Fallback to service parameter
        if (!empty($request['service']) && self::isValidUuid($request['service'])) {
            return $request['service'];
        }
        
        // Check session if provided
        if (!empty($session['pt_service']) && self::isValidUuid($session['pt_service'])) {
            return $session['pt_service'];
        }
        
        return null;
    }

    /**
     * Format amount for payment processing
     * Converts to smallest currency unit (e.g., cents for USD)
     * 
     * @param float $amount The amount to format
     * @param string $currency The currency code (e.g., 'USD')
     * @return int The amount in smallest currency unit
     */
    public static function formatAmount($amount, $currency) {
        $amount = (float)$amount;
        
        // Handle zero-decimal currencies
        $zeroDecimalCurrencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW',
            'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        ];
        
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (int)round($amount);
        }
        
        return (int)round($amount * 100);
    }

    /**
     * Standard error response format
     * 
     * @param string $message Error message
     * @param array $data Additional error data
     * @param int $statusCode HTTP status code
     * @return array Standardized error response
     */
    public static function errorResponse($message, $data = [], $statusCode = 400) {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode,
                'details' => $data
            ]
        ];
    }

    /**
     * Standard success response format
     * 
     * @param array $data Response data
     * @return array Standardized success response
     */
    public static function successResponse($data = []) {
        return array_merge(['success' => true], $data);
    }
}
