<?php
// Determine if the service is recurring based on its type
$is_recurring = ($service['itemType'] === 'service') ? '1' : '0';
?>

<div class="form-group col-md-6 col-sm-6 col-xs-6">

    <div style="font-size: 18px"><b><?php echo htmlspecialchars($service['itemName']) ?></b></div>
    <?php if(!empty($service['itemDescription'])) {?>
    <div style="font-size: 13px"><?php echo htmlspecialchars($service['itemDescription']) ?></div>
    <?php }?>
    <div style="font-size: 20px">
        Amount:
        <b style="color: green"><?php echo PT_Core::getCurrencyText($service['itemAmount'],false)?></b>
        <?php if($service['itemType'] == 'service'){?>
            every <?php echo htmlspecialchars($service['frequencyCycle'])?> <?php echo htmlspecialchars($service['frequencyPeriod'])?>
        <?php }?>
        <input type="hidden" id="pt_service_amount" value="<?php echo htmlspecialchars($service['itemAmount'])?>">
        <input type="hidden" id="pt_service_recurring" value="<?php echo $is_recurring ?>">
        <input type="hidden" id="pt_service_name" value="<?php echo htmlspecialchars($service['itemName'])?>">
        <input type="hidden" name="pt_service" value="<?php echo htmlspecialchars($service['idItem'])?>">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($service['itemAmount'])?>">
    </div>
</div>
<input type="hidden" name="pt_currency" value="<?php echo htmlspecialchars($currency) ?>">
<input type="hidden" name="pt_currency_symbol" value="<?php echo htmlspecialchars($currency_symbol) ?>">
<input type="hidden" name="pt_currency_position" value="<?php echo htmlspecialchars($currency_position) ?>">
<?php if(isset($service['itemPlan']) && $service['itemPlan'] === 'y' && $service['itemType'] === 'product'){?>
    <div class="form-group col-md-12 col-sm-12 col-xs-12">

        <label for="pt_amount" class="clearfix"><span id="payment-info"></span></label>
        <div class="slider-cont">
            <input name="pt_payments_count" id="pt_payments_count" type="text"
                   value="<?php echo isset($service['itemBillingMin']) ? (int)$service['itemBillingMin'] : 1 ?>"  style="display: none"/>
        </div>
    </div>
    <script>
        $(function () {
            fillPayments(<?php echo isset($service['itemBillingMin']) ? (int)$service['itemBillingMin'] : 1 ?>,<?php echo isset($service['itemBillingMax']) ? (int)$service['itemBillingMax'] : 1 ?>,<?php echo htmlspecialchars($service['itemAmount']) ?>)
        })
    </script>
<?php } ?>
