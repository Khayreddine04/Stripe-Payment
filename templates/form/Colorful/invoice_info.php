<div class="form-group col-md-3 col-sm-3 col-xs-6">
    <label for="pt_amount"><?php _tr("Amount")?></label>
    <div class="checkbox">
        <?php echo($amount) ?>
    </div>
</div>
<div class="form-group col-md-3 col-sm-3 col-xs-6">
    <label for="pt_amount"><?php _tr("Tax included")?></label>
    <div class="checkbox">
        <?php echo($tax) ?>
    </div>
</div>
<div class="form-group col-md-3 col-sm-3 col-xs-6">
    <label for="pt_amount"><?php _tr("Due Date")?></label>
    <div class="checkbox">
        <?php echo($due_date) ?>
    </div>
</div>
<div class="form-group col-md-3 col-sm-3 col-xs-6">
    <label for="pt_amount"><?php _tr("Order #")?></label>
    <div class="checkbox">
        <?php echo($number) ?>
    </div>
</div>
<div class="clearfix"></div>

<input type="hidden" name="idInvoice" value="<?php echo($idInvoice) ?>" id="idInvoice">
<input type="hidden" name="pt_invoice_amount" value="<?php echo($_amount) ?>" id="pt_invoice_amount">
<input type="hidden" name="pt_invoice_recurring" value="<?php echo($is_recurring) ?>" id="pt_invoice_recurring">
<input type="hidden" name="pt_invoice_number" value="<?php echo($number) ?>" id="pt_invoice_number">
<input type="hidden" name="pt_currency" value="<?php echo($currency) ?>" id="pt_currency">
<input type="hidden" name="pt_currency_symbol" value="<?php echo($display_currency)?>">
<input type="hidden" name="pt_currency_position" value="<?php echo($currency_position)?>">
