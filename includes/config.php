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

//THIS IS TITLE ON PAGES
define("TERMINAL_TITLE", "Stripe Payment Terminal v2.0"); //site title
//THIS IS ADMIN EMAIL FOR NEW PAYMENT NOTIFICATIONS.
define("ADMIN_EMAIL", "youremailaddress@here.com"); //this email is for notifications about new payments
define("EMAIL_FROM_NAME", "Payment Terminal"); //this email is for notifications about new payments
define("EMAIL_FROM_EMAIL", "youremailaddress@here.com"); //this email is for notifications about new payments
define("TXT_KIND_REGARDS","Kind Regards");
define("DISPLAY_CURRENCY","$");
define("CURRENCY_POSITION","before");// before - before amount; after - after amount

define("SHOW_BILLING_ADDRESS",false);
define("SHOW_SHIPPING_ADDRESS",false);
define("TERMINAL_LOGO","assets/images/logo.png");
define("SSL_TEXT","SSL Verified");
define("THANK_YOU_MESSAGE","Thank you for your payment");//
define("SCRIPT_URL","");// script URL like http://domain.com/payment_terminal_folder/

// light | green
define("TERMINAL_THEME","Minimalist");

//IF YOU NEED TO ADD MORE SERVICES JUST ADD THEM THE SAME WAY THEY APPEAR BELOW.
$services = array(
    array("Service 1", "49.99"),
    array("Service 2", "149.99"),
    array("Service 3", "249.99"),
    array("Service 4", "349.99"),
);
//NOW, IF YOU WANT TO ACTIVATE THE DROPDOWN WITH SERVICES ON THE TERMINAL
//ITSELF, CHANGE BELOW VARIABLE TO TRUE;

define("PAYMENT_TYPE", 'input'); // item|input

//NOW, IF YOU WANT TO ACTIVATE THE DROPDOWN WITH SERVICES ON THE TERMINAL
//ITSELF, CHANGE BELOW VARIABLE TO TRUE;

define("SHOW_DESCRIPTION", true);

// set  to   RECUR  - for recurring payments, ONETIME - for onetime payments
define("PAYMENT_MODE", "ONETIME");

//service name   |   price  to charge   | Billing period  "Day", "Week", "Month", "Year"   |  how many periods of previous field per billing period | trial period in days | Trial amount
$recur_services = array(
    array("Service 1 monthly WITH 30 DAYS TRIAL", "49.99", "Month", "1", "30", "24.99"),
    array("Service 1 monthly", "49.99", "Month", "1", "0", "0"),
    array("Service 1 quaterly", "149.99", "Month", "3", "0", "0"),
    array("Service 1 semi-annualy", "249.99", "Month", "6", "0", "0"),
    array("Service 1 annualy", "349.99", "Year", "1", "0", "0")
);


//IF YOU'RE GOING LIVE FOLLOWING VARIABLE SHOULD BE SWITCH TO true IT WILL AUTOMATICALLY REDIRECT ALL NON-HTTTPS REQUESTS TO HTTPS - MAKE SURE SSL IS INSTALLED ALREADY.
define("REDIRECT_TO_HTTPS",false);
// IF YOU'RE GOING LIVE FOLLOWING VARIABLE SHOULD BE SWITCH TO true
define("TERMINAL_PAYMENT_MODE",'test');

/* Please note that Stripe.com will accept payments only in your account currency.
* You can set your account currency here: https://manage.stripe.com/account
* A list with Stripe Test Credit Cards Numbers can be found here: https://stripe.com/docs/testing
*
* */
if (TERMINAL_PAYMENT_MODE == 'test') {
//TEST MODE
    // Replace these with your actual Stripe test keys from the Stripe Dashboard
    // Get these from: https://dashboard.stripe.com/test/apikeys
    define('PUBLIC_KEY', 'pk_test_your_publishable_key_here');
    define('SECRET_KEY', 'sk_test_your_secret_key_here');
    define('CURRENCY', 'USD'); // Make sure this matches your Stripe account's default currency
    define('TEST_MODE', 'test');

} else {
//LIVE MODE
    define('PUBLIC_KEY', 'YOUR STRIPE PUBLISHABLE KEY FOR LIVE MODE'); //CHANGE THIS
    define('SECRET_KEY', 'YOUR STRIPE SECRET KEY FOR LIVE MODE'); // AND THIS
    define('CURRENCY', 'YOUR ACCOUNT CURRENCY'); //usd, eur, gbp, aud, cad
    define('TEST_MODE', 'live');
}
date_default_timezone_set("US/Eastern"); // !!!IMPORTANT!!! PLEASE CHANGE THIS TO YOUR TIMEZONE - according to the list from here http://php.net/manual/en/timezones.php
/*******************************************************************************************************
 * PAYPAL CONFIGURATION VARIABLES
 ********************************************************************************************************/

define("ENABLE_PAYPAL", true);//shows/hides paypal payment option from payment form.
define("PAYPAL_MERCHANT_EMAIL", "your_paypal_merchant_email@here.com");
define("PAYPAL_SUCCESS_URL", "http://yourdomain.com/stripe-payment-terminal/paypal_thankyou.php");
define("PAYPAL_CANCEL_URL", "http://yourdomain.com/stripe-payment-terminal/paypal_cancel.php");
define("PAYPAL_IPN_LISTENER", "http://yourdomain.com/stripe-payment-terminal/paypal_listener.php");
define("PAYPAL_CUSTOM_VARIABLE", "some_var");
define("PAYPAL_CURRENCY", "USD");
define("PAYPAL_PAYMENT_MODE", "test"); //if you want to test payments with your sandbox account change to test (you must have account at https://developer.paypal.com/ and YOU MUST BE LOGGED IN WHILE TESTING!)


//DO NOT CHANGE ANYTHING BELOW THIS LINE, UNLESS SURE OF COURSE
if (REDIRECT_TO_HTTPS) {
    if ($_SERVER['SERVER_PORT'] != 443) {
        $sslport = 443; //whatever your ssl port is
        $url = "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        header("Location: $url");
        exit();
    }
}

$countries = array(

    "US" => "United States",
    "CA" => "Canada",
    "UK" => "United Kingdom",
    "AU" => "Australia",
    "AF" => "Afghanistan",
    "AL" => "Albania",
    "DZ" => "Algeria",
    "AS" => "American Samoa",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AI" => "Anguilla",
    "AQ" => "Antarctica",
    "AG" => "Antigua and Barbuda",
    "AR" => "Argentina",
    "AM" => "Armenia",
    "AW" => "Aruba",
    "AT" => "Austria",
    "AZ" => "Azerbaijan",
    "BS" => "Bahamas",
    "BH" => "Bahrain",
    "BD" => "Bangladesh",
    "BB" => "Barbados",
    "BY" => "Belarus",
    "BE" => "Belgium",
    "BZ" => "Belize",
    "BJ" => "Benin",
    "BM" => "Bermuda",
    "BT" => "Bhutan",
    "BO" => "Bolivia",
    "BA" => "Bosnia and Herzegovina",
    "BW" => "Botswana",
    "BR" => "Brazil",
    "BN" => "Brunei Darussalam",
    "BG" => "Bulgaria",
    "BF" => "Burkina Faso",
    "BI" => "Burundi",
    "KH" => "Cambodia",
    "CM" => "Cameroon",
    "CV" => "Cape Verde",
    "KY" => "Cayman Islands",
    "CF" => "Central African Republic",
    "TD" => "Chad",
    "CL" => "Chile",
    "CN" => "China",
    "CX" => "Christmas Island",
    "CC" => "Cocos (Keeling) Islands",
    "CO" => "Colombia",
    "KM" => "Comoros",
    "CG" => "Congo",
    "CD" => "Congo, The Democratic Republic of the",
    "CK" => "Cook Islands",
    "CR" => "Costa Rica",
    "CI" => "Cote D`Ivoire",
    "HR" => "Croatia",
    "CY" => "Cyprus",
    "CZ" => "Czech Republic",
    "DK" => "Denmark",
    "DJ" => "Djibouti",
    "DM" => "Dominica",
    "DO" => "Dominican Republic",
    "EC" => "Ecuador",
    "EG" => "Egypt",
    "SV" => "El Salvador",
    "GQ" => "Equatorial Guinea",
    "ER" => "Eritrea",
    "EE" => "Estonia",
    "ET" => "Ethiopia",
    "FK" => "Falkland Islands (Malvinas)",
    "FO" => "Faroe Islands",
    "FJ" => "Fiji",
    "FI" => "Finland",
    "FR" => "France",
    "GF" => "French Guiana",
    "PF" => "French Polynesia",
    "GA" => "Gabon",
    "GM" => "Gambia",
    "GE" => "Georgia",
    "DE" => "Germany",
    "GH" => "Ghana",
    "GI" => "Gibraltar",
    "GR" => "Greece",
    "GL" => "Greenland",
    "GD" => "Grenada",
    "GP" => "Guadeloupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GN" => "Guinea",
    "GW" => "Guinea-Bissau",
    "GY" => "Guyana",
    "HT" => "Haiti",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungary",
    "IS" => "Iceland",
    "IN" => "India",
    "ID" => "Indonesia",
    "IR" => "Iran (Islamic Republic Of)",
    "IQ" => "Iraq",
    "IE" => "Ireland",
    "IL" => "Israel",
    "IT" => "Italy",
    "JM" => "Jamaica",
    "JP" => "Japan",
    "JO" => "Jordan",
    "KZ" => "Kazakhstan",
    "KE" => "Kenya",
    "KI" => "Kiribati",
    "KP" => "Korea North",
    "KR" => "Korea South",
    "KW" => "Kuwait",
    "KG" => "Kyrgyzstan",
    "LA" => "Laos",
    "LV" => "Latvia",
    "LB" => "Lebanon",
    "LS" => "Lesotho",
    "LR" => "Liberia",
    "LI" => "Liechtenstein",
    "LT" => "Lithuania",
    "LU" => "Luxembourg",
    "MO" => "Macau",
    "MK" => "Macedonia",
    "MG" => "Madagascar",
    "MW" => "Malawi",
    "MY" => "Malaysia",
    "MV" => "Maldives",
    "ML" => "Mali",
    "MT" => "Malta",
    "MH" => "Marshall Islands",
    "MQ" => "Martinique",
    "MR" => "Mauritania",
    "MU" => "Mauritius",
    "MX" => "Mexico",
    "FM" => "Micronesia",
    "MD" => "Moldova",
    "MC" => "Monaco",
    "MN" => "Mongolia",
    "MS" => "Montserrat",
    "MA" => "Morocco",
    "MZ" => "Mozambique",
    "NA" => "Namibia",
    "NP" => "Nepal",
    "NL" => "Netherlands",
    "AN" => "Netherlands Antilles",
    "NC" => "New Caledonia",
    "NZ" => "New Zealand",
    "NI" => "Nicaragua",
    "NE" => "Niger",
    "NG" => "Nigeria",
    "NO" => "Norway",
    "OM" => "Oman",
    "PK" => "Pakistan",
    "PW" => "Palau",
    "PS" => "Palestine Autonomous",
    "PA" => "Panama",
    "PG" => "Papua New Guinea",
    "PY" => "Paraguay",
    "PE" => "Peru",
    "PH" => "Philippines",
    "PL" => "Poland",
    "PT" => "Portugal",
    "PR" => "Puerto Rico",
    "QA" => "Qatar",
    "RE" => "Reunion",
    "RO" => "Romania",
    "RU" => "Russian Federation",
    "RW" => "Rwanda",
    "VC" => "Saint Vincent and the Grenadines",
    "MP" => "Saipan",
    "SM" => "San Marino",
    "SA" => "Saudi Arabia",
    "SN" => "Senegal",
    "SC" => "Seychelles",
    "SL" => "Sierra Leone",
    "SG" => "Singapore",
    "SK" => "Slovak Republic",
    "SI" => "Slovenia",
    "SO" => "Somalia",
    "ZA" => "South Africa",
    "ES" => "Spain",
    "LK" => "Sri Lanka",
    "KN" => "St. Kitts/Nevis",
    "LC" => "St. Lucia",
    "SD" => "Sudan",
    "SR" => "Suriname",
    "SZ" => "Swaziland",
    "SE" => "Sweden",
    "CH" => "Switzerland",
    "SY" => "Syria",
    "TW" => "Taiwan",
    "TI" => "Tajikistan",
    "TZ" => "Tanzania",
    "TH" => "Thailand",
    "TG" => "Togo",
    "TK" => "Tokelau",
    "TO" => "Tonga",
    "TT" => "Trinidad and Tobago",
    "TN" => "Tunisia",
    "TR" => "Turkey",
    "TM" => "Turkmenistan",
    "TC" => "Turks and Caicos Islands",
    "TV" => "Tuvalu",
    "UG" => "Uganda",
    "UA" => "Ukraine",
    "AE" => "United Arab Emirates",
    "UY" => "Uruguay",
    "UZ" => "Uzbekistan",
    "VU" => "Vanuatu",
    "VE" => "Venezuela",
    "VN" => "Viet Nam",
    "VG" => "Virgin Islands (British)",
    "VI" => "Virgin Islands (U.S.)",
    "WF" => "Wallis and Futuna Islands",
    "YE" => "Yemen",
    "YU" => "Yugoslavia",
    "ZM" => "Zambia",
    "ZW" => "Zimbabwe",
);

$states = array(
    "Australian Provinces" =>
        array(
            "-AU-QLD" => "Queensland",
            "-AU-SA" => "South Australia",
            "-AU-TAS" => "Tasmania",
            "-AU-VIC" => "Victoria",
            "-AU-WA" => "Western Australia",
            "-AU-ACT" => "Australian Capital Territory",
            "-AU-NT" => "Northern Territory",
        ),
    "Canadian Provinces" =>
        array(
            "AB" => "Alberta",
            "BC" => "British Columbia",
            "MB" => "Manitoba",
            "NB" => "New Brunswick",
            "NF" => "Newfoundland",
            "NT" => "Northwest Territories",
            "NS" => "Nova Scotia",
            "NVT" => "Nunavut",
            "ON" => "Ontario",
            "PE" => "Prince Edward Island",
            "QC" => "Quebec",
            "SK" => "Saskatchewan",
            "YK" => "Yukon"
        ),
    "US States" =>
        array(
            "AL" => "Alabama",
            "AK" => "Alaska",
            "AZ" => "Arizona",
            "AR" => "Arkansas",
            "BVI" => "British Virgin Islands",
            "CA" => "California",
            "CO" => "Colorado",
            "CT" => "Connecticut",
            "DE" => "Delaware",
            "FL" => "Florida",
            "GA" => "Georgia",
            "GU" => "Guam",
            "HI" => "Hawaii",
            "ID" => "Idaho",
            "IL" => "Illinois",
            "IN" => "Indiana",
            "IA" => "Iowa",
            "KS" => "Kansas",
            "KY" => "Kentucky",
            "LA" => "Louisiana",
            "ME" => "Maine",
            "MP" => "Mariana Islands",
            "MPI" => "Mariana Islands (Pacific)",
            "MD" => "Maryland",
            "MA" => "Massachusetts",
            "MI" => "Michigan",
            "MN" => "Minnesota",
            "MS" => "Mississippi",
            "MO" => "Missouri",
            "MT" => "Montana",
            "NE" => "Nebraska",
            "NV" => "Nevada",
            "NH" => "New Hampshire",
            "NJ" => "New Jersey",
            "NM" => "New Mexico",
            "NY" => "New York",
            "NC" => "North Carolina",
            "ND" => "North Dakota",
            "OH" => "Ohio",
            "OK" => "Oklahoma",
            "OR" => "Oregon",
            "PA" => "Pennsylvania",
            "PR" => "Puerto Rico",
            "RI" => "Rhode Island",
            "SC" => "South Carolina",
            "SD" => "South Dakota",
            "TN" => "Tennessee",
            "TX" => "Texas",
            "UT" => "Utah",
            "VT" => "Vermont",
            "USVI" => "VI  U.S. Virgin Islands",
            "VA" => "Virginia",
            "WA" => "Washington",
            "DC" => "Washington, D.C.",
            "WV" => "West Virginia",
            "WI" => "Wisconsin",
            "WY" => "Wyoming"
        ),
    "England" =>
        array(
            "Bedfordshire" => "Bedfordshire",
            "Berkshire" => "Berkshire",
            "Bristol" => "Bristol",
            "Buckinghamshire" => "Buckinghamshire",
            "Cambridgeshire" => "Cambridgeshire",
            "Cheshire" => "Cheshire",
            "City of London" => "City of London",
            "Cornwall" => "Cornwall",
            "Cumbria" => "Cumbria",
            "Derbyshire" => "Derbyshire",
            "Devon" => "Devon",
            "Dorset" => "Dorset",
            "Durham" => "Durham",
            "East Riding of Yorkshire" => "East Riding of Yorkshire",
            "East Sussex" => "East Sussex",
            "Essex" => "Essex",
            "Gloucestershire" => "Gloucestershire",
            "Greater London" => "Greater London",
            "Greater Manchester" => "Greater Manchester",
            "Hampshire" => "Hampshire",
            "Herefordshire" => "Herefordshire",
            "Hertfordshire" => "Hertfordshire",
            "Isle of Wight" => "Isle of Wight",
            "Kent" => "Kent",
            "Lancashire" => "Lancashire",
            "Leicestershire" => "Leicestershire",
            "Lincolnshire" => "Lincolnshire",
            "Merseyside" => "Merseyside",
            "Norfolk" => "Norfolk",
            "North Yorkshire" => "North Yorkshire",
            "Northamptonshire" => "Northamptonshire",
            "Northumberland" => "Northumberland",
            "Nottinghamshire" => "Nottinghamshire",
            "Oxfordshire" => "Oxfordshire",
            "Rutland" => "Rutland",
            "Shropshire" => "Shropshire",
            "Somerset" => "Somerset",
            "South Yorkshire" => "South Yorkshire",
            "Staffordshire" => "Staffordshire",
            "Suffolk" => "Suffolk",
            "Surrey" => "Surrey",
            "Tyne and Wear" => "Tyne and Wear",
            "Warwickshire" => "Warwickshire",
            "West Midlands" => "West Midlands",
            "West Sussex" => "West Sussex",
            "West Yorkshire" => "West Yorkshire",
            "Wiltshire" => "Wiltshire",
            "Worcestershire" => "Worcestershire",
        ),
    "Scotland" =>
        array(
            "Anglesey" => "Anglesey",
            "Brecknockshire" => "Brecknockshire",
            "Caernarfonshire" => "Caernarfonshire",
            "Carmarthenshire" => "Carmarthenshire",
            "Cardiganshire" => "Cardiganshire",
            "Denbighshire" => "Denbighshire",
            "Flintshire" => "Flintshire",
            "Glamorgan" => "Glamorgan",
            "Merioneth" => "Merioneth",
            "Monmouthshire" => "Monmouthshire",
            "Montgomeryshire" => "Montgomeryshire",
            "Pembrokeshire" => "Pembrokeshire",
            "Radnorshire" => "Radnorshire",
        ),
    "Northern Ireland" =>
        array(
            "Antrim" => "Antrim",
            "Armagh" => "Armagh",
            "Down" => "Down",
            "Fermanagh" => "Fermanagh",
            "Londonderry" => "Londonderry",
            "Tyrone" => "Tyrone",
        )
);


