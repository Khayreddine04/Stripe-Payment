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
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");

$action = $a->esc("action");
$$pt_id = (int)$a->esc($pt_id );
$customerName = $a->esc("customerName");
$customerEmail = $a->esc("customerEmail");
$customerTerm = $a->esc("customerTerm");
$customerBill = $a->esc("customerBill");

$customer = new customerModel();


if ($action == 'update') {

    $currentCustomerEmail = '';
    if(!empty($$pt_id)){
        $currentCustomer = new customerModel();
        $currentCustomer->setID($$pt_id);
        $currentCustomer->setData();
        $currentCustomerEmail = $currentCustomer->customerEmail;
    }
    if (empty($customerName)) {
        $a->addError("Customer Name are required");
    }
    if (empty($customerBill)) {
        $a->addError("Billing information are required");
    }
    if (empty($customerEmail)) {
        $a->addError("Billing information are required");
    }elseif($customer->getCustomerByEmail($customerEmail)!==false && $currentCustomerEmail !== $customerEmail){
        $a->addError("Email '{$customerEmail}' already exist");
    }

    if (!$a->error) {
        if (empty($$pt_id)) {
            $sql = "INSERT INTO $pt_table SET
                customerName = '{$customerName}',
                customerEmail = '{$customerEmail}',
                customerTerm = '{$customerTerm}',
                customerBill = '{$customerBill}',
                dateCreated = '".NOW_DATE_TIME."'";
            $res = $a->query($sql);
            $$pt_id = $res->insert_id;
            $a->addSuccess("Customer '".$a->crl($customerName)."' has been successfully added");
        } else {
            $customer->setID($$pt_id);
            $customer->setData();
            $sql = "UPDATE $pt_table SET
                customerName = '{$customerName}',
                customerEmail = '{$customerEmail}',
                customerTerm = '{$customerTerm}',
                customerBill = '{$customerBill}'
                WHERE {$pt_id} = {$$pt_id}";
            $a->query($sql);
            $a->addSuccess("Customer '".$a->crl($customerName)."' has been successfully updated");
        }
    }
}

if (!empty($$pt_id)) {
    $sql = "SELECT * FROM $pt_table WHERE {$pt_id} = {$$pt_id}";
    foreach($a->query($sql)->result_row() as $k=>$v)
        $$k = $v;
}

$a->getHeader();
?>
<div class="container" role="main">
    <?php if($can_view){ ?>
    <form class=" validate" role="form" action="edit.php" method="post">
        <div class="row">
            <div class="col-md-9 col-lg-9 col-sm-9 col-xs-6 vcenter"><h2><?php echo(!empty($$pt_id)?"Edit":"Add") ?> customer</h2></div><div class="col-md-3 col-lg-3 col-sm-3 col-xs-6 vcenter text-right"><span class="back_to_list">&larr;<a href="index.php">Back to list</a></span> </div>
        </div>
        <input type="hidden" name="action" value="update" />
        <?php echo($a->getMessages()) ?>
        <div class="col-md-9 col-lg-8 col-sm-12 col-xs-12 form_section">
            <div class="rowItem">
                <input type="hidden" name="<?php echo($pt_id) ?>" value="<?php echo($$pt_id) ?>" id="<?php echo($pt_id) ?>">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="form-group col-md-6 col-sm-6">
                        <label for="customerName"><span>*</span>Customer Name</label>
                        <input type="text" class="form-control" name="customerName" id="customerName" placeholder=""
                               value="<?php echo $a->crl($customerName) ?>"
                               data-rule-required="true">
                    </div>
                    <div class="form-group  col-md-6 col-sm-6">
                        <label for="customerEmail"><span>*</span>Customer Email</label>

                            <input type="email" class="form-control" name="customerEmail" id="customerEmail" placeholder=""
                                   value="<?php echo $a->crl($customerEmail) ?>"
                                   data-rule-required="true"
                                   data-rule-email="true">


                    </div>
                    <div class="clearfix"></div>
                    <div class="form-group col-md-6 col-sm-6">
                        <label for="customerBill"><span>*</span>Billing Info</label>
                        <textarea class="form-control" name="customerBill" id="customerBill" rows="4"
                                  data-rule-required="true"><?php echo $a->crl($customerBill)?></textarea>
                    </div>
                    <div class="form-group col-md-6 col-sm-6">
                        <label for="customerBill">Default Terms</label>
                        <select class="form-control" name="customerTerm" id="customerTerm">
                            <option value="0">Due upon receipt</option>
                            <option value="due15" <?php echo($customerTerm=="due15"?"selected":"") ?> data-days="15">Due withing 15 days</option>
                            <option value="due30" <?php echo($customerTerm=="due30"?"selected":"") ?> data-days="30">Due withing 30 days</option>
                            <option value="due45" <?php echo($customerTerm=="due45"?"selected":"") ?> data-days="45">Due withing 45 days</option>
                            <option value="custom" <?php echo($customerTerm=="custom"?"selected":"") ?>>Custom</option>
                        </select>
                    </div>

                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="clearfix"></div>
        <p>&nbsp;</p>
        <div class="col-md-12 ">
            <button type="submit" class="btn btn-success btn-lg">Save</button>
            <?php if(!empty($$pt_id)){?>
                <a class="btn btn-success btn-lg" href="edit.php">Add New</a>
            <?php }?>
        </div>
    </form>
    <?php }else{ ?>
        You have no permissions to view this section
    <?php } ?>
</div>
<script>
    $().ready(function() {
        // validate the comment form when it is submitted
        $(".validate").validate({
            errorPlacement: function(error, element) {
                element.parents(".form-group").addClass("has-error");
                element.wrap("<div class='control-wrap'>")
                error.appendTo(element.parent());
            },
            success: function(label) {
                label.parents(".form-group").removeClass("has-error").addClass("has-success");
            }
        });
    });

    function updateFrequency(el){
        var $el = $(el);
        if($el.val()=='product'){
            $el.parents(".rowItem").find("select").attr("disabled","disabled").prev("label").addClass("disabled");
            $("#trialCont").hide();
        }else{
            $el.parents(".rowItem").find("select").removeAttr("disabled").prev("label").removeClass("disabled");
            $("#trialCont").show();
        }
    }

    function updateTrial(el){
        var $el = $(el);
        if($el.val()=='y'){
            $("#itemTrialDaysCont").show();
        }else{
            $("#itemTrialDaysCont").hide();
        }
    }
</script>
<?php echo($a->getFooter()) ?>
