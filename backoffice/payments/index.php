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

$a->addStyle("../assets/vendors/sweetalert2/sweetalert2.min.css");
$a->addScripts("../assets/vendors/sweetalert2/sweetalert2.min.js",false);

$can_view = st_apply_filter('have_permissions',true,'can_view_payments');
$can_refund = st_apply_filter('have_permissions',true,'can_refund');


$settings->set("admin_section",$pt_section);

if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$can_delete = $user->is_main_admin();
$delete = $a->esc("delete");
$del_id = $a->esc("del_id");
$action = $a->esc("action");

if($action =='import'){

    $payment = new PT_Stripe_Payment();
    $importedRecords = $payment->importTransactions();
    $a->addSuccess("{$importedRecords} payments has been successfully imported");
}
if (!empty($delete)) {
    $deletedRecords = 0;
    foreach ($del_id as $del) {
        $payment = new paymentModel();
        $payment->setID($del);
        $paymentDetails = $payment->getPayment();
        $sql = "DELETE FROM `{$pt_table}` WHERE `$pt_id`='{$del}'";
        $res = $a->query($sql);
        if ($res->count) {
            $deletedRecords++;;
            st_do_action('add_user_log',"Deleted transaction {$paymentDetails['idTransaction']} in the amount of {$paymentDetails['currency_symbol']}{$paymentDetails['amount']}{$paymentDetails['currency']} for customer {$paymentDetails['customerName']}");
        }
    }
    $a->addWarning("{$deletedRecords} has been successfully deleted");
}

$table = new PT_Data_Table($pt_table_data);
$table->table = $pt_table;
$table->id = $pt_id;
$table->section = $pt_section;

$table->pt_order_col=$pt_order_col;
$table->pt_order_type=$pt_order_type;


$a->getHeader();
?>
<script>
    function getPrintColumns() {
        return [1,2,3,4,5,6,7];
    }
</script>
<div class="container" role="main">
    <?php if($can_view){ ?>
    <div class="row">

        <div class="col-xs-12 col-sm-12 col-md-12">
            <form style="padding-bottom: 20px" action="" class="clearfix" id="sync_form" method="post">
                <input type="hidden" name="action" value="import">
                <button class="btn btn-success pull-right" type="button">Sync with Stripe</button>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <?php echo($a->getMessages()) ?>

            <?php $table->getAjaxTable($can_delete); ?>

        </div>
    </div>
    <?php }else{ ?>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            You have no permissions to view this section
        </div>
    </div>
    <?php } ?>
</div>
<script type="text/javascript">

    $('#grid').on( 'draw.dt', function () {
        $('[data-toggle="tooltip"]').tooltip()
    } );

    $(function () {
        $('#sync_form button').click(function () {
            swal({
                title: 'Warning',
                text: 'Are you sure to import payments?',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#DD6B55',
                confirmButtonText: 'Proceed',
                cancelButtonText: "Cancel"

            }).then(function (result) {
                $('#sync_form').submit()
            })
        })

    })
</script>
<?php echo($a->getFooter()) ?>
