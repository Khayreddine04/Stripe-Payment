<?php if(($enable_paypal=='y' && !empty($paypal_merchant)) || $buttons_enable=='y'){ ?>
    <div class="form-group col-md-12 col-sm-12 col-xs-12">
        <label for="exampleInputPassword1"><?php _tr("I'll pay by")?></label>

        <div class="radio">
            <label>
                <input type="radio" name="pt_type" id="pt_type" value="card" <?php echo($post['pt_type']=='card'?"checked":"") ?>>
                <img src="assets/images/icons/visa.png">
                <img src="assets/images/icons/mastercard.png">
                <img src="assets/images/icons/american_express.png">
            </label>
            <?php if($enable_paypal=='y' && !empty($paypal_merchant)){?>
            <label>
                <input type="radio" name="pt_type" id="pt_type1" value="paypal" <?php echo($post['pt_type']=='paypal'?"checked":"") ?>>
                <img src="assets/images/icons/paypal.png" title="Payment by PayPal">
            </label>
            <?php }?>
            <?php if($buttons_enable=='y'){ ?>
                <label id="payment_buttons_selector" style="display: none">
                    <input type="radio" name="pt_type" id="pt_type2" value="gpay" <?php echo($post['pt_type']=='gpay'?"checked":"") ?>>
                    <img src="assets/images/icons/payment_buttons.svg" title="Payment Buttons">
                </label>
            <?php }?>
            <?php if(isset($userLogon) && $userLogon){ ?>
                <label>
                    <input type="radio" name="pt_type" id="pt_type3" value="cash" <?php echo($post['pt_type']=='cash'?"checked":"") ?>>
                    <img src="assets/images/icons/cash.png" title="Cash payment">
                </label>
            <?php }?>
        </div>
    </div>

<?php }?>
