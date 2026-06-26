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

include_once "../includes/bootstrap.php";
include_once "settings.php";

$settings->set("admin_section",$pt_section);

$can_view = st_apply_filter('have_permissions',true,'can_view_subscriptions');


if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$can_delete = $user->is_main_admin();
$delete = $a->esc("delete");
$del_id = $a->esc("del_id");

if (!empty($delete)) {
    $deletedRecords = 0;
    foreach ($del_id as $del) {
        $subscription = new subscriptionModel();
        $subscription->setID($del);
        $subscriptionData = $subscription->getSubscription();
        $sql = "DELETE FROM `{$pt_table}` WHERE `$pt_id`='{$del}'";
        $res = $a->query($sql);
        if ($res->count) {
            $deletedRecords++;;
            st_do_action('add_user_log',"Deleted subscription {$subscriptionData['idTransaction']} in the amount of {$subscriptionData['currency_symbol']}{$subscriptionData['amount']}{$subscriptionData['currency']} for customer {$subscriptionData['customerName']}");
        }
    }
    $a->addWarning("{$deletedRecords} record has been successfully deleted");
}

$table = new PT_Data_Table($pt_table_data);
$table->table = $pt_table;
$table->id = $pt_id;
$table->section = $pt_section;


$a->getHeader();
?>
<script>
    function getPrintColumns() {
        return [0,1,2,3,4,5,6];
    }
</script>

<div class="container" role="main">
    <div class="row">

        <div class="col-xs-12 col-sm-12 col-md-12">
            <?php if($can_view){ ?>
                <?php echo($a->getMessages()) ?>
                <?php $table->getAjaxTable($can_delete); ?>
            <?php }else{ ?>
                You have no permissions to view this section
            <?php } ?>
        </div>


    </div>
</div>

<?php echo($a->getFooter()) ?>
