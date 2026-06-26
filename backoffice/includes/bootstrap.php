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

session_start();
ob_start();
define("HOME_DIR",dirname(dirname(dirname(__FILE__))));
define("ADMIN_DIR",HOME_DIR."/backoffice");


$dbconnectPath = HOME_DIR . "/includes/dbconnect.php";
if (file_exists($dbconnectPath)) {
    include_once $dbconnectPath;
} else {
    // Fallback for environments where dbconnect.php is mounted separately.
    if (!defined("DB_HOST")) {
        define("DB_HOST", (string) (getenv("DB_HOST") ?: getenv("MYSQL_HOST") ?: ""));
    }
    if (!defined("DB_USER")) {
        define("DB_USER", (string) (getenv("DB_USER") ?: getenv("MYSQL_USER") ?: ""));
    }
    if (!defined("DB_PASS")) {
        define("DB_PASS", (string) (getenv("DB_PASS") ?: getenv("MYSQL_PASSWORD") ?: ""));
    }
    if (!defined("DB_NAME")) {
        define("DB_NAME", (string) (getenv("DB_NAME") ?: getenv("MYSQL_DATABASE") ?: ""));
    }
    if (!defined("DB_CHARSET")) {
        define("DB_CHARSET", (string) (getenv("DB_CHARSET") ?: "utf8mb4"));
    }
    if (!defined("DB_PREFIX")) {
        define("DB_PREFIX", (string) (getenv("DB_PREFIX") ?: "pt_"));
    }
    if (!defined("SALT")) {
        define("SALT", (string) (getenv("SALT") ?: getenv("APP_SALT") ?: ""));
    }

    $db_pr = DB_PREFIX;
    error_log("[PT bootstrap] Missing includes/dbconnect.php, using environment DB_* values.");
}
include_once HOME_DIR."/includes/config.php";
include_once HOME_DIR."/includes/_config.php";
include_once ADMIN_DIR."/includes/config.php";
include_once HOME_DIR."/includes/functions.php";


$user = PT_User::instance();
$settings = PT_Settings::instance();
$a = PT_Admin_Core::instance();

if($settings->timezone === false) {
    date_default_timezone_set(date_default_timezone_get());
}else{
    date_default_timezone_set($settings->timezone);
}

define("NOW_DATE_TIME",date("Y-m-d H:i:s"));
define("NOW_DATE",date("Y-m-d"));

include_once HOME_DIR."/includes/plugin.functions.php";

load_plugins();

st_do_action("st_admin_init");


