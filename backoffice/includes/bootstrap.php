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


include_once HOME_DIR."/includes/dbconnect.php";
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


