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

$can_view = st_apply_filter('have_permissions',true,'can_manage_customers');

if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$customer = new customerModel();

$delete = $a->esc("delete");
$del_id = $a->esc("del_id");

if (!empty($delete)) {
    $deletedRecords = 0;
    foreach ($del_id as $del) {

        $customer->setID($del);
        $customer->setData();
        $result = $customer->delCustomer();
        if ($result){
            $deletedRecords++;;
            st_do_action('add_user_log',"Deleted customer: {$customer->customerName} {$customer->customerEmail}");
        }else{

            $a->addError("Customer <b>{$customer->customerName}</b> can not be deleted. You need to delete all associated invoices");
        }

    }
    $a->addWarning("{$deletedRecords} has been successfully deleted");
}

$table = new PT_Data_Table($pt_table_data);
$table->table = $pt_table;
$table->id = $pt_id;
$table->section = $pt_section;



$a->getHeader();
 ?>
    <script>
        function getPrintColumns() {
            return [0,1,2];
        }
    </script>
    <div class="container" role="main">
        <?php if($can_view){ ?>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-2 col-md-push-10 right-sidebar">
                <a type="button" class="btn  btn-success" href="edit.php">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Customer
                </a>
            </div>
            <div class="clearfix visible-xs-block visible-sm-block"></div>
            <div class="col-xs-12 col-sm-12 col-md-10 col-md-pull-2 left-sidebar">
                <?php echo($a->getMessages()) ?>
                <?php $table->getAjaxTable(); ?>
            </div>
        </div>
        <?php }else{ ?>
            You have no permissions to view this section
        <?php } ?>
    </div>

<?php echo($a->getFooter()) ?>
