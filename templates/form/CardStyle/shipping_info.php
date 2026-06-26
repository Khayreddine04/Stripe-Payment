<?php if($show_shipping=='y'){?>
    <div id="shipping_info" style="display: <?php echo $post['pt_shipping_same'] == 'y' ? "none" : "block" ?>;" >
        <h2><?php _tr("Shipping Information") ?></h2>
        <div class="clearfix"></div>

        <div class="form-group col-md-6 col-sm-6 col-xs-12">
            <label for="pt_address1_s"><?php _tr("Address Line 1") ?></label>
            <input type="text" class="form-control" id="pt_address1_s" name="pt_address1_s" placeholder="" value="<?php PT_Core::r($post['pt_address1_s']) ?>">
        </div>

        <div class="form-group col-md-6 col-sm-6 col-xs-12">
            <label for="pt_address2_s"><?php _tr("Address Line 2") ?></label>
            <input type="text" class="form-control" id="pt_address2_s" name="pt_address2_s" placeholder="" value="<?php PT_Core::r($post['pt_address2_s']) ?>">
        </div>
        <div class="clearfix"></div>

        <div class="form-group col-md-6 col-sm-6 col-xs-12">
            <label for="pt_city_s"><?php _tr("City") ?></label>
            <input type="text" class="form-control" id="pt_city_s" name="pt_city_s" placeholder="" value="<?php PT_Core::r($post['pt_city_s']) ?>">
        </div>

        <div class="form-group col-md-6 col-sm-6 col-xs-12">
            <label for="pt_country_s"><?php _tr("Country") ?></label>
            <select class="form-control" name="pt_country_s" id="pt_country_s">
                <option value=""><?php _tr("Please Select") ?></option>
                <?php echo($countriesList) ?>
            </select>
        </div>

        <div class="clearfix"></div>
        <div class="form-group col-md-6 col-sm-6 col-xs-12">
            <label for="pt_state_s"><?php _tr("State/Province") ?></label>
            <select class="form-control" name="pt_state_s" id="pt_state_s">
                <option value=""><?php _tr("Please Select") ?></option>
                <?php echo($statesList) ?>
            </select>
        </div>
        <div class="form-group col-md-3 col-sm-3 col-xs-12">
            <label for="pt_postal_s"><?php _tr("Postal Code / Zip") ?></label>
            <input type="text" class="form-control" id="pt_postal_s" name="pt_postal_s" placeholder="" value="<?php PT_Core::r($post['pt_postal_s']) ?>">
        </div>

        <div class="clearfix"></div>
</div>
<?php } ?>
