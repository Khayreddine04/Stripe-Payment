<?php
/**
 * Currency Utilities for Stripe Payment Terminal
 * 
 * Provides helper functions to access currency data stored in the session
 */

if (!function_exists('get_currency_data')) {
    /**
     * Get currency data from session
     * 
     * @param string|null $key Specific key to retrieve (e.g., 'subscription_amount', 'upfront_amount')
     * @param mixed $default Default value if key not found
     * @return mixed Array of currency data or specific value if key provided
     */
    function get_currency_data($key = null, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['api_currency_data'])) {
            return $default;
        }
        
        if ($key === null) {
            return $_SESSION['api_currency_data'];
        }
        
        // Handle nested keys with dot notation (e.g., 'subscription_amount.amount')
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $_SESSION['api_currency_data'];
            
            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            
            return $value;
        }
        
        return $_SESSION['api_currency_data'][$key] ?? $default;
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a currency amount
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @return string Formatted currency string
     */
    function format_currency($amount, $currency = null) {
        if ($currency === null) {
            $currency = get_currency_data('subscription_amount.currency', 'USD');
        }
        
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        
        return $formatter->formatCurrency($amount, $currency);
    }
}

if (!function_exists('get_formatted_subscription_amount')) {
    /**
     * Get formatted subscription amount with currency
     * 
     * @return string Formatted amount with currency (e.g., "$9.99")
     */
    function get_formatted_subscription_amount() {
        $amount = get_currency_data('subscription_amount.amount_numeric', 0);
        $currency = get_currency_data('subscription_amount.currency', 'USD');
        return format_currency($amount, $currency);
    }
}

if (!function_exists('get_formatted_upfront_amount')) {
    /**
     * Get formatted upfront fee with currency
     * 
     * @return string Formatted amount with currency (e.g., "$9.99") or empty string if no upfront fee
     */
    function get_formatted_upfront_amount() {
        $amount = get_currency_data('upfront_amount.amount_numeric', 0);
        if ($amount <= 0) {
            return '';
        }
        $currency = get_currency_data('upfront_amount.currency', 'USD');
        return format_currency($amount, $currency);
    }
}

if (!function_exists('get_formatted_total_amount')) {
    /**
     * Get formatted total amount (subscription + upfront fee) with currency
     * 
     * @return string Formatted total amount with currency (e.g., "$19.98")
     */
    function get_formatted_total_amount() {
        $subAmount = (float)get_currency_data('subscription_amount.amount_numeric', 0);
        $upfrontAmount = (float)get_currency_data('upfront_amount.amount_numeric', 0);
        $currency = get_currency_data('subscription_amount.currency', 'USD');
        
        return format_currency($subAmount + $upfrontAmount, $currency);
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * Get currency symbol for the current currency
     * 
     * @param string|null $currency Currency code (defaults to current subscription currency)
     * @return string Currency symbol
     */
    function get_currency_symbol($currency = null) {
        if ($currency === null) {
            $currency = get_currency_data('subscription_amount.currency', 'USD');
        }
        
        $formatter = new NumberFormatter('en_US@currency=' . $currency, NumberFormatter::CURRENCY);
        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }
}

if (!function_exists('get_subscription_period')) {
    /**
     * Get subscription period (e.g., "month", "year")
     * 
     * @return string Subscription period
     */
    function get_subscription_period() {
        return get_currency_data('subscription_amount.period', 'month');
    }
}

if (!function_exists('has_upfront_fee')) {
    /**
     * Check if there's an upfront fee
     * 
     * @return bool True if there's an upfront fee
     */
    function has_upfront_fee() {
        $amount = (float)get_currency_data('upfront_amount.amount_numeric', 0);
        return $amount > 0;
    }
}
