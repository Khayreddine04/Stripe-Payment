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
session_unset();
session_destroy();
setcookie ("access_token","",strtotime("-1 week"));
header ("Location: index.php");
exit();
