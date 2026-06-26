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

include_once "includes/bootstrap.php";

$payment = new PT_Stripe_Payment();
$header = new PT_Template("header.php");
$header->title = $settings->page_title;
$header->logo = !empty($settings->terminal_logo) ? $settings->siteUrl() . $settings->terminal_logo : "";
$header->terminal_payment_mode = $settings->terminal_payment_mode;
$header->theme = $settings->selected_theme;
$header->custom_theme = $settings->theme_type == 'custom' ? true : false;
$footer = new PT_Template("footer.php");
$h = $c->esc("h");

if(!empty($h)){
    //check if valid md5 hash
    if($c->isValidMd5($h)){
        $sql = "SELECT * FROM {$user->db->db_pr}users WHERE tmhash = '{$h}'";
        $res = $user->db->query($sql);
        if(!$res->count){
            $c->addError("Invalid entry! (e01)");
        } else {
            $userData = $res->result_row();
            if(!empty($userData["tmpass"])){
                $sql = "UPDATE {$user->db->db_pr}users SET password='".$userData["tmpass"]."', tmpass='' WHERE tmhash = '{$h}'";
                $user->db->query($sql);
            }
            if(!empty($userData["tmuser"])){
                $sql = "UPDATE {$user->db->db_pr}users SET username='".$userData["tmuser"]."', tmuser='' WHERE tmhash = '{$h}'";
                $user->db->query($sql);
            }
            $sql = "UPDATE {$user->db->db_pr}users SET tmhash='' WHERE tmhash = '{$h}'";
            $user->db->query($sql);
            $c->addSuccess("Account change confirmed! You can now login with the new info you approved.");
        }

    }
} else {
    $c->addError("Invalid entry!");
}


$header->render(true);
?>
<div class="container main" role="main">
    <?php echo($c->getMessages()) ?>
</div>
<?php $footer->render(true); ?>
</div>
<script src="assets/bootstrap/js/bootstrap.min.js" type="application/javascript"></script>
</body>
<?php echo($c->getDebug()) ?>
</html>
