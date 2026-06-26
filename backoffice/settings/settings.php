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

$pt_section = "settings";
$pt_table = $db_pr . "settings";
$pt_id = "id";
$pt_title = "Payments";

$can_view = st_apply_filter('have_permissions',true,'can_manage_settings');
