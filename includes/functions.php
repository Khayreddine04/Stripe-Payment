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

spl_autoload_register(function ($class_name) { //classes auto load


    if($class_name=="Throwable"){
        return;
    }
    if(strpos($class_name,"Model")){
        if(is_file(HOME_DIR.'/includes/classes/models/'.$class_name . '.class.php'))
        {
            require_once (HOME_DIR.'/includes/classes/models/'.$class_name . '.class.php');

            return true;
        }else{
            print("Class $class_name not found");
        }
    }

    if(is_file(HOME_DIR.'/includes/classes/'.strtolower($class_name) . '.class.php'))
    {
        require_once (HOME_DIR.'/includes/classes/'.strtolower($class_name) . '.class.php');


    }elseif(is_file(HOME_DIR.'/backoffice/includes/classes/'.strtolower($class_name) . '.class.php')){
        require_once (HOME_DIR.'/backoffice/includes/classes/'.strtolower($class_name) . '.class.php');

    }else{
        print("Class $class_name not found1");
	    //include_once (HOME_DIR."/vendor/autoload.php");
    }
});
function _tr($text){
    echo( $text );
}
function __tr($text){
    return ( $text );
}
/**
 * Generate a UUID v4
 * @return string
 */
function generate_uuid(): string {
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    }
    
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Check if a string is a valid UUID v4
 * @param string $uuid The string to validate
 * @return bool
 */
function is_uuid($uuid) {
    if (!is_string($uuid)) {
        return false;
    }
    return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
}

function get_install_site_url(){
    $scheme = "";
    if ( isset( $_SERVER['HTTPS'] ) ) {
        if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
            $scheme = "https://";
        }

        if ( '1' == $_SERVER['HTTPS'] ) {
            $scheme = "https://";
        }
    } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
        $scheme = "https://";
    }else{
        $scheme = "http://";
    }

    if(isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])){
        $site = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }elseif (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])){
        $site = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    }
    $site = str_replace('install.php','',$site);
    $site = str_replace('upgrade.php','',$site);
    return $scheme.$site;
}

function getConvenientCurrencyData($countryCode, $ctc, $serviceId) {
    // Construct the URL, assuming getConvenientCurr.php is in the same directory
    $url = 'getConvenientCurr.php';
    $queryParams = http_build_query([
        'country' => $countryCode,
        'ctc' => $ctc,
        'service' => $serviceId
    ]);

    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['REQUEST_URI'] ?? '/')), '/');
    if ($basePath === '' || $basePath === '.') {
        $basePath = '';
    }
    $full_url = $scheme . '://' . $host . $basePath . '/' . $url . '?' . $queryParams;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Should be true in production
    // Fail fast so checkout rendering is never blocked by this helper.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);

    if ($response === false) {
        $curlError = curl_error($ch);
        curl_close($ch);
        throw new Exception('Failed to fetch currency data from getConvenientCurr.php: ' . $curlError);
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['success']) || !$data['success']) {
        $errorMessage = isset($data['message']) ? $data['message'] : 'Invalid response from currency endpoint.';
        throw new Exception($errorMessage);
    }

    return $data;
}