<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.com)
 * Website:    http://www.CriticalGears.com
 * Support:    http://criticalgears.com/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c) 2009 - 2015  CriticalGears.com
 *
 * Date: 15.09.15
 */


$SPTContinue = false;
$license_to_check = preg_replace('/[^a-zA-Z0-9_ -]/s', '', !empty($license) ? $license : "");
if (!empty($username) && !empty($domain)) {
    if (!empty($license_to_check)) {


        $data = array(
            "license" => $license_to_check,
            "username" => $username,
            "product" => "3710600",
            "domain" => $domain,
            "site" => get_install_site_url()
        );
        $api_url = "https://validate.criticalgears.io";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $api_call = curl_exec($ch);
        curl_close($ch);


        if ($json_data = json_decode($api_call, true)) {
            if (isset($json_data['res']) && $json_data['res']) {
                $SPTContinue = true;
            } else {
                $c->addError($json_data['msg']);
            }

        } else {
            $c->addError("Error! Wrong API response format");
        }

    } else {

        $c->addError("You either didn`t pass the license key into the url or didn`t enter your envato username/apikey into configuration");
    }
} else {
    $c->addError("License Key, Username and Domain fields are required");
}

