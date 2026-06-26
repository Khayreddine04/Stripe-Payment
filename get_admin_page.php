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

include_once "backoffice/includes/bootstrap.php";

$page = $a->esc("p");

$a->getHeader();
?>

<div class="container" role="main">
    <?php if(st_get_admin_page($page)){

    }else{?>
        Error
    <?php } ?>
</div>

<?php echo($a->getFooter()) ?>
