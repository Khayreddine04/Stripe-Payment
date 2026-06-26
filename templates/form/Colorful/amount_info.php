<div class="form-group
<?php echo $show_currency_selector?"col-md-4 col-sm-4 col-xs-8":"col-md-6 col-sm-6 col-xs-12"?>

">
    <label for="pt_amount"><?php _tr("Amount")?></label>
	<?php if ( ! $show_currency_selector ){ ?>
    <div class="input-group">
		<?php } ?>
        <?php if($currency_position=='before' && !$show_currency_selector){?>

        <div class="input-group-addon"><span class="pt_currency_symbol"><?php echo($currency_symbol) ?></span></div>
        <?php }?>
        <input type="text" class="form-control" id="pt_amount" name="pt_amount" placeholder="0.00" value="<?php PT_Core::r($post['pt_amount']) ?>"
               data-rule-required="true"  data-msg-required="<?php _tr("Required info")?>"
               data-rule-number="true"  data-msg-number="<?php _tr("Only numbers")?>">
        <?php if($currency_position=='after' && !$show_currency_selector){?>
            <div class="input-group-addon"><span class="pt_currency_symbol"><?php echo($currency_symbol) ?></span></div>

        <?php }?>
	    <?php if ( ! $show_currency_selector ){ ?>
        </div>
		    <?php } ?>

</div>
<?php if($show_currency_selector){?>
    <div class="form-group col-md-2 col-sm-2 col-xs-4">
        <label for="pt_amount">&nbsp;</label>
        <select class="form-control" name="pt_currency" id="pt_currency"
                data-rule-required="true" data-msg-required="<?php _tr("Required info") ?>">
            <?php echo($currency_selector_html) ?>
        </select>
        <input type="hidden" name="pt_currency_symbol" value="">
        <input type="hidden" name="pt_currency_position" value="after">
    </div>
<?php }else{?>
    <input type="hidden" name="pt_currency" value="<?php echo($currency)?>">
    <input type="hidden" name="pt_currency_symbol" value="<?php echo($currency_symbol)?>">
    <input type="hidden" name="pt_currency_position" value="<?php echo($currency_position)?>">
<?php }?>

<?php if($show_description=='y'){?>
    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_description"><?php _tr("Description")?></label>
        <textarea name="pt_description" id="pt_description" class="form-control"><?php PT_Core::r($post['pt_description']) ?></textarea>

    </div>
    <div class="clearfix"></div>
<?php }?>

