<?php
// DETECT LANGUAGE AND COUNTRY
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Language Detection
$lang = 'en'; // Default
if (isset($_GET['lang'])) {
    $lang = substr($_GET['lang'], 0, 2);
} elseif (isset($_COOKIE['site_lang'])) {
    $lang = $_COOKIE['site_lang'];
} elseif (isset($_SESSION['site_lang'])) {
    $lang = $_SESSION['site_lang'];
} else {
    $lang = detectUserLanguage();
}
// Sanitize
$lang = preg_replace('/[^a-z]/', '', strtolower($lang));
// Store
$_SESSION['site_lang'] = $lang;
setcookie('site_lang', $lang, time() + (86400 * 30), "/");
$GLOBALS['current_lang'] = $lang;

// 2. Country Detection
if (!isset($detectedCountry)) {
    $detectedCountry = getCountryFromIP();
}

/**
 * Fallback function to get country code from IP address
 * This is used only if the country detector is not available
 */
function getCountryFromIP()
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

    // Log the IP being used for lookup
    error_log("IP Lookup - Client IP: " . $ip);

    if (empty($ip) || $ip === '::1' || $ip === '127.0.0.1') {
        error_log("IP Lookup - Using default country (US) for localhost");
        return 'US'; // Default to US for localhost
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ],
        'http' => [
            'timeout' => 3, // Reduced timeout to 3 seconds
            'user_agent' => 'Mozilla/5.0 (compatible; IP lookup)'
        ]
    ]);

    // Try ipapi.co first
    $ipApiUrl = "https://ipapi.co/{$ip}/json/";
    error_log("IP Lookup - Trying ipapi.co: " . $ipApiUrl);

    $response = @file_get_contents($ipApiUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        error_log("IP Lookup - ipapi.co request failed: " . ($error['message'] ?? 'Unknown error'));
    } else {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("IP Lookup - ipapi.co response: " . print_r($data, true));
            if (isset($data['country_code']) && !empty($data['country_code'])) {
                $country = strtoupper($data['country_code']);
                error_log("IP Lookup - Using country from ipapi.co: " . $country);
                return $country;
            }
        } else {
            error_log("IP Lookup - Invalid JSON from ipapi.co: " . $response);
        }
    }

    // Fallback to ip-api.com
    $ipApiUrl = "http://ip-api.com/json/{$ip}?fields=status,message,countryCode";
    error_log("IP Lookup - Trying ip-api.com: " . $ipApiUrl);

    $response = @file_get_contents($ipApiUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        error_log("IP Lookup - ip-api.com request failed: " . ($error['message'] ?? 'Unknown error'));
    } else {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("IP Lookup - ip-api.com response: " . print_r($data, true));
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['countryCode'])) {
                $country = strtoupper($data['countryCode']);
                error_log("IP Lookup - Using country from ip-api.com: " . $country);
                return $country;
            } else {
                error_log("IP Lookup - ip-api.com returned error: " . ($data['message'] ?? 'Unknown error'));
            }
        } else {
            error_log("IP Lookup - Invalid JSON from ip-api.com: " . $response);
        }
    }

    // Final fallback - try to get country from CloudFlare headers if available
    if (isset($_SERVER['HTTP_CF_IPCOUNTRY']) && !empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $country = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
        if (strlen($country) === 2) {
            error_log("IP Lookup - Using country from CloudFlare header: " . $country);
            return $country;
        }
    }

    error_log("IP Lookup - All methods failed, using default country (US)");
    return 'US'; // Default to US if all else fails
}

/**
 * Detect user's preferred language
 * 
 * @param string|null $preferredLang User-specified preferred language code
 * @return string Selected language code
 */
function detectUserLanguage($preferredLang = null)
{
    // 1. Check if we already detected it globally
    if (isset($GLOBALS['current_lang']) && empty($preferredLang)) {
        return $GLOBALS['current_lang'];
    }

    // 2. If user specified a language, use it
    if (!empty($preferredLang)) {
        return strtolower(substr($preferredLang, 0, 2));
    }

    // 3. Otherwise, detect from browser Accept-Language header
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($languages as $language) {
            $langCode = strtolower(substr(trim($language), 0, 2));
            // Check if it's a supported language
            if (in_array($langCode, array_keys(getSupportedLanguages()))) {
                return $langCode;
            }
        }
    }

    // Default to English
    return 'en';
}

/**
 * Get list of supported languages
 * 
 * @return array Language codes and names
 */
function getSupportedLanguages()
{
    return [
        'af' => 'Afrikaans',
        'ar' => 'Arabic',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bs' => 'Bosnian',
        'ca' => 'Catalan',
        'cs' => 'Czech',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'en' => 'English',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'fi' => 'Finnish',
        'fo' => 'Faroese',
        'fr' => 'French',
        'fy' => 'Frisian',
        'ga' => 'Irish',
        'gl' => 'Galician',
        'he' => 'Hebrew',
        'hr' => 'Croatian',
        'hsb' => 'Upper Sorbian',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kl' => 'Greenlandic',
        'lb' => 'Luxembourgish',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'me' => 'Montenegrin (Latin script)',
        'mk' => 'Macedonian',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'oc' => 'Occitan',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'si' => 'Sinhala',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sq' => 'Albanian',
        'sr' => 'Serbian (Cyrillic script)',
        'sv' => 'Swedish',
        'ta' => 'Tamil',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'uz' => 'Uzbek',
        'yi' => 'Yiddish',
        'zh' => 'Chinese Simplified',
        'ko' => 'Korean',
        'vi' => 'Vietnamese',
        'hi' => 'Hindi',
        'th' => 'Thai',
        'fa' => 'Persian',
        'id' => 'Indonesian',
        'ms' => 'Malay',
        'sw' => 'Swahili',
        'am' => 'Amharic',
        'ne' => 'Nepali',
        'ur' => 'Urdu',
        'bn' => 'Bengali',
        'pa' => 'Punjabi',
        'gu' => 'Gujarati',
        'or' => 'Odia',
        'te' => 'Telugu',
        'kn' => 'Kannada',
        'ml' => 'Malayalam',
        'sd' => 'Sindhi',
        'my' => 'Burmese',
        'km' => 'Khmer',
        'lo' => 'Lao',
        'ka' => 'Georgian',
        'az' => 'Azerbaijani',
        'eu' => 'Basque',
        'br' => 'Breton',
        'cy' => 'Welsh',
        'kw' => 'Cornish',
        'gv' => 'Manx',
        'gd' => 'Scottish Gaelic',
        'mt' => 'Maltese',
        'mi' => 'Maori',
        'sm' => 'Samoan',
        'to' => 'Tongan',
        'fj' => 'Fijian',
        'haw' => 'Hawaiian',
        'ty' => 'Tahitian',
        'mg' => 'Malagasy',
        'ny' => 'Chichewa',
        'st' => 'Southern Sotho',
        'tn' => 'Tswana',
        'ts' => 'Tsonga',
        've' => 'Venda',
        'xh' => 'Xhosa',
        'zu' => 'Zulu',
        'rw' => 'Kinyarwanda',
        'rn' => 'Rundi',
        'sn' => 'Shona',
        'so' => 'Somali',
        'ti' => 'Tigrinya',
        'om' => 'Oromo',
        'aa' => 'Afar',
        'ss' => 'Swati',
        'nr' => 'Southern Ndebele',
        'ff' => 'Fulah',
        'wo' => 'Wolof',
        'dy' => 'Dyula',
        'bm' => 'Bambara',
        'ee' => 'Ewe',
        'ak' => 'Akan',
        'lg' => 'Ganda',
        'ln' => 'Lingala',
        'kg' => 'Kongo',
        'lu' => 'Luba-Katanga',
    ];
}

/**
 * Get localized country name
 * 
 * @param string $countryCode Two-letter country code (e.g., US, FR, DE)
 * @param string|null $lang Optional language code (e.g., 'en', 'fr', 'de'). 
 *                         If not provided, uses browser Accept-Language header.
 * @return string Localized country name
 */
function getLocalizedCountryName($countryCode, $lang = null)
{
    // Detect user language
    $selectedLang = detectUserLanguage($lang);

    // Get localized country name
    $countryName = getCountryNameInLanguage($countryCode, $selectedLang);

    // If translation not found in selected language, fallback to English
    if ($countryName === null && $selectedLang !== 'en') {
        $countryName = getCountryNameInLanguage($countryCode, 'en');
    }

    // If English translation also not found, return country code
    if ($countryName === null) {
        return $countryCode;
    }

    return $countryName;
}

/**
 * Get country name in specific language
 * 
 * @param string $countryCode Two-letter country code
 * @param string $lang Two-letter language code
 * @return string|null Country name or null if not found
 */
function getCountryNameInLanguage($countryCode, $lang)
{
    $countryCode = strtoupper($countryCode);
    $lang = strtolower($lang);

    // 1. Get all country translations (Manual/Hardcoded list)
    $allTranslations = getAllCountryTranslations();

    // Check if we have a manual translation that differs from the English name
    // This prioritizes user-defined translations over Locale and default English placeholders
    $manualTranslation = $allTranslations[$lang][$countryCode] ?? null;
    $englishName = $allTranslations['en'][$countryCode] ?? null;

    if ($manualTranslation && $manualTranslation !== $englishName) {
        return $manualTranslation;
    }

    // 2. Try to use PHP's intl extension (best quality, full coverage)
    if (class_exists('Locale')) {
        $displayRegion = Locale::getDisplayRegion('-' . $countryCode, $lang);
        // If it returns a valid name (not just the code), return it
        if ($displayRegion && $displayRegion !== $countryCode && $displayRegion !== '-' . $countryCode) {
            return $displayRegion;
        }
    }

    // 3. Fallback to hardcoded translation (even if it matches English)
    // This catches cases where the manual translation IS the same as English, 
    // or if Locale failed and we have a placeholder.
    if ($manualTranslation) {
        return $manualTranslation;
    }

    // 4. Fallback to English from the hardcoded list
    if ($englishName) {
        return $englishName;
    }

    return null;
}

// Due to the extremely large size (249 countries × 53 languages = 13,197 translations),
// I'll create a separate function to generate the translations
function getAllCountryTranslations()
{
    // First, let's define all country codes and their English names
    $countries = [
        'AF' => 'Afghanistan',
        'AX' => 'Åland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BQ' => 'Bonaire, Sint Eustatius and Saba',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CV' => 'Cabo Verde',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, Democratic Republic of the',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Côte d\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'Curaçao',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'SZ' => 'Eswatini',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island and McDonald Islands',
        'VA' => 'Holy See',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran, Islamic Republic of',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => 'Korea, Democratic People\'s Republic of',
        'KR' => 'Korea, Republic of',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia, Federated States of',
        'MD' => 'Moldova, Republic of',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MK' => 'North Macedonia',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestine, State of',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Réunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthélemy',
        'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin (French part)',
        'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SX' => 'Sint Maarten (Dutch part)',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia and the South Sandwich Islands',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan, Province of China',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania, United Republic of',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom of Great Britain and Northern Ireland',
        'US' => 'United States of America',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela, Bolivarian Republic of',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis and Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    ];

    // Initialize translations array with English as base
    $translations = ['en' => $countries];

    // For other languages, we'll use English as fallback or generate translations
    // In a real application, you would use actual translation services or databases
    $languages = array_keys(getSupportedLanguages());

    foreach ($languages as $lang) {
        if ($lang === 'en')
            continue;

        // For demonstration, we'll copy English names
        // In production, you should use actual translations
        $translations[$lang] = [];
        foreach ($countries as $code => $name) {
            // Add language-specific translations here
            // For now, using English as placeholder
            $translations[$lang][$code] = $name;
        }
    }
    include "includes/translations.php";
    // Fill remaining English names for all languages
    foreach ($languages as $lang) {
        if (!isset($translations[$lang])) {
            $translations[$lang] = $countries;
        } else {
            // Merge with English for missing translations
            foreach ($countries as $code => $name) {
                if (!isset($translations[$lang][$code])) {
                    $translations[$lang][$code] = $name;
                }
            }
        }
    }

    return $translations;
}

/**
 * Alternative: Load translations from JSON file for better performance
 * This is recommended for production use
 */
function loadTranslationsFromJSON($filePath = 'country_translations.json')
{
    if (file_exists($filePath)) {
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }

    // Fallback to generated translations
    return getAllCountryTranslations();
}

/**
 * Save translations to JSON file
 */
function saveTranslationsToJSON($filePath = 'country_translations.json')
{
    $translations = getAllCountryTranslations();
    $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($filePath, $json);
    return true;
}

/**
 * Simplified usage method for common use cases
 */
function getCountryInfoForUser($preferredLanguage = null)
{
    // Get country code
    $countryCode = getCountryFromIP();

    // Get localized country name
    $countryName = getLocalizedCountryName($countryCode, $preferredLanguage);

    // Get detected language
    $detectedLanguage = detectUserLanguage($preferredLanguage);
    $languageName = getSupportedLanguages()[$detectedLanguage] ?? 'English';

    return [
        'country_code' => $countryCode,
        'country_name' => $countryName,
        'language_code' => $detectedLanguage,
        'language_name' => $languageName
    ];
}

/**
 * Helper function to get all countries in a specific language
 */
function getAllCountriesInLanguage($lang = 'en')
{
    // Check if the getCountryNameInLanguage function exists in this context before calling it
    // to avoid potential re-declaration conflicts if this file is included alongside index.php
    if (!function_exists('getAllCountryTranslations')) {
        return [];
    }

    $translations = getAllCountryTranslations();
    return $translations[$lang] ?? $translations['en'];
}

/**
 * Search countries by name in a specific language
 */
function searchCountries($query, $lang = 'en')
{
    $countries = getAllCountriesInLanguage($lang);
    $results = [];

    foreach ($countries as $code => $name) {
        if (stripos($name, $query) !== false) {
            $results[$code] = $name;
        }
    }

    return $results;
}

/**
 * Get country information by code
 */
function getCountryInfo($countryCode, $lang = null)
{
    $countryCode = strtoupper($countryCode);
    $countryName = getLocalizedCountryName($countryCode, $lang);

    return [
        'code' => $countryCode,
        'name' => $countryName,
        'flag_emoji' => getCountryFlagEmoji($countryCode)
    ];
}

/**
 * Get country flag emoji from country code
 */
function getCountryFlagEmoji($countryCode)
{
    $countryCode = strtoupper($countryCode);

    // Convert country code to regional indicator symbols
    $flag = '';
    for ($i = 0; $i < strlen($countryCode); $i++) {
        $flag .= mb_chr(ord($countryCode[$i]) + 127397);
    }

    return $flag;
}
