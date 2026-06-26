<div id="billing_info">
<h2><?php _tr('Billing Information') ?></h2>

<div class="form-group col-md-6 col-sm-6 col-xs-12">
    <label for="pt_name"><?php _tr('Your Name') ?></label>
    <input type="text" class="form-control" id="pt_name" name="pt_name" placeholder="John Dee" value="<?php echo isset($post['pt_name']) ? htmlspecialchars($post['pt_name']) : '' ?>"
           data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>">
</div>

<div class="form-group col-md-6 col-sm-6 col-xs-12">
    <label for="pt_email"><?php _tr('Email') ?></label>
    <input type="text" class="form-control" id="pt_email" name="pt_email" placeholder="john.doe@email.com" value="<?php echo isset($post['pt_email']) ? htmlspecialchars($post['pt_email']) : '' ?>"
           data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
           data-rule-email="true"  data-msg-email="<?php _tr('Incorrect Email') ?>">
</div>
<?php if ($show_billing == 'y') { ?>
    <div class="clearfix"></div>

    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_address1"><?php _tr('Address Line 1') ?></label>
        <input type="text" class="form-control" id="pt_address1" name="pt_address1" placeholder="" value="<?php PT_Core::r($post['pt_address1']) ?>"
               data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
        >
    </div>

    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_address2"><?php _tr('Address Line 2') ?></label>
        <input type="text" class="form-control" id="pt_address2" name="pt_address2" placeholder="" value="<?php PT_Core::r($post['pt_address2']) ?>">
    </div>
    <div class="clearfix"></div>

    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_city"><?php _tr('City') ?></label>
        <input type="text" class="form-control" id="pt_city" name="pt_city" placeholder="" value="<?php PT_Core::r($post['pt_city']) ?>"
               data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
        >
    </div>

    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_country"><?php _tr('Country') ?></label>
        <select class="form-control" name="pt_country" id="pt_country"
                data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
        >
            <option value=""><?php _tr('Please Select') ?></option>
            <?php echo ($countriesList) ?>
        </select>
    </div>

    <div class="clearfix"></div>
    <div class="form-group col-md-6 col-sm-6 col-xs-12">
        <label for="pt_state"><?php _tr('State/Province') ?></label>
        <select class="form-control" name="pt_state" id="pt_state"
                data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
        >
            <option value=""><?php _tr('Please Select') ?></option>
            <?php echo ($statesList) ?>
        </select>
    </div>
    <input type="hidden" id="pt_state_or" value="<?php PT_Core::r($post['pt_state']) ?>">
    <div class="form-group col-md-3 col-sm-3 col-xs-12">
        <label for="pt_postal"><?php _tr('Postal Code / Zip') ?></label>
        <input type="text" class="form-control" id="pt_postal" name="pt_postal" placeholder="" value="<?php PT_Core::r($post['pt_postal']) ?>"
               data-rule-required="true"  data-msg-required="<?php _tr('Required info') ?>"
        >
    </div>
    <div class="clearfix"></div>
    <?php if ($show_shipping == 'y') { ?>
    <div class="form-group col-md-12 col-sm-12 col-xs-12">
        <div class="checkbox">
            <label>
                <input type="checkbox" id="pt_shipping_same" name="pt_shipping_same" value="y" <?php echo $post['pt_shipping_same'] == 'y' ? 'checked' : '' ?>>&nbsp;Shipping information is same as billing
            </label>
        </div>
    </div>
    <?php } ?>
<?php } ?>
</div>
<div class="clearfix"></div>