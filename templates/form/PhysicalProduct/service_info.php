<?php if ( ! empty( $services_list ) ) { ?>
    <div class="form-group
    <?php echo $show_currency_selector?"col-md-6 col-sm-6 col-xs-8":"col-md-6 col-sm-6 col-xs-12"?>
    ">
        <?php
        switch ( $payment_type ) {
            case "product":
                $label_item = "Products";
                break;
            case "service":
                $label_item = "Services";
                break;
            case "donation":
                $label_item = "Donation Options";
                break;
            default:
                $label_item = "Products & Services";
                break;
        }
        ?>
        <label for="pt_service"><?php _tr( $label_item ) ?></label>
        <select class="form-control" name="pt_service" id="pt_service"
                data-rule-required="true" data-msg-required="<?php _tr( "Required info" ) ?>">
            <option value=""><?php _tr( "Please select" ) ?></option>
            <?php echo( $services_list ) ?>
            <?php if ( $payment_type == 'donation' ) { ?>
                <option value="pt_donation" data-recurring="false" <?php echo( $post['pt_service'] == 'pt_donation' ? "selected" : "" ) ?>>
                    One-Time Donation
                </option>
                <option value="pt_donation_weekly" data-recurring="true" <?php echo( $post['pt_service'] == 'pt_donation_weekly' ? "selected" : "" ) ?>>
                    Weekly Donation
                </option>
                <option value="pt_donation_monthly" data-recurring="true" <?php echo( $post['pt_service'] == 'pt_donation_monthly' ? "selected" : "" ) ?>>
                    Monthly Donation
                </option>
                <option value="pt_donation_bi-monthly" data-recurring="true" <?php echo( $post['pt_service'] == 'pt_donation_bi-monthly' ? "selected" : "" ) ?>>
                    Bi-Monthly Donation
                </option>
            <?php } ?>

        </select>
        <div id="pt_service_description"></div>
        <?php if ( $payment_type != 'donation' ) { ?>
            <input type="hidden" name="pt_currency" value="<?php echo( $currency ) ?>">
            <input type="hidden" name="pt_currency_symbol" value="<?php echo( $currency_symbol ) ?>">
            <input type="hidden" name="pt_currency_position" value="<?php echo( $currency_position ) ?>">
        <?php } ?>
    </div>
<?php } ?>

<div class="form-group
<?php echo $show_currency_selector?"col-md-4 col-sm-4 col-xs-8":"col-md-4 col-sm-4 col-xs-12"?>
" id="pt_amount"
     style="display: <?php echo( $post['pt_service'] == 'pt_donation' || ( $payment_type == 'donation' && empty( $services_list ) ) ? "block" : "none" ) ?>">
    <label for="pt_amount"><span id="donation_period"></span><?php _tr( "Donation Amount" ) ?></label>

    <?php if ( ! $show_currency_selector ){ ?>
    <div class="input-group">
        <?php } ?>
        <?php if ( $currency_position == 'before' && ! $show_currency_selector ) { ?>

            <div class="input-group-addon"><span class="pt_currency_symbol"><?php echo( $currency_symbol ) ?></span>
            </div>
        <?php } ?>
        <input type="text" class="form-control" id="pt_amount" name="pt_amount" placeholder=""
               value="<?php echo( $post['pt_amount'] ) ?>"
               data-rule-required="true" data-msg-required="<?php _tr( "Required info" ) ?>"
               data-rule-number="true" data-msg-number="<?php _tr( "Only numbers" ) ?>">
        <?php if ( $currency_position == 'after' && ! $show_currency_selector ) { ?>
            <div class="input-group-addon"><span class="pt_currency_symbol"><?php echo( $currency_symbol ) ?></span>
            </div>

        <?php } ?>
        <?php if ( ! $show_currency_selector ){ ?>
    </div>
<?php } ?>

</div>
<?php if ( $show_currency_selector ) { ?>
    <div class="form-group col-md-2 col-sm-2 col-xs-4" id="pt_amount_currency">
        <label for="pt_amount">&nbsp;</label>
        <select class="form-control" name="pt_currency" id="pt_currency"
                data-rule-required="true" data-msg-required="<?php _tr( "Required info" ) ?>">
            <?php echo( $currency_selector_html ) ?>
        </select>
        <input type="hidden" name="pt_currency_symbol" value="<?php echo( $currency_symbol ) ?>">
        <input type="hidden" name="pt_currency_position" value="<?php echo( $currency_position ) ?>">
    </div>
<?php } elseif ( $show_currency_selector === false && $payment_type == 'donation' ) { ?>
    <input type="hidden" name="pt_currency_symbol" value="<?php echo( $currency_symbol ) ?>">
    <input type="hidden" name="pt_currency_position" value="<?php echo( $currency_position ) ?>">
    <input type="hidden" name="pt_currency" value="<?php echo( $currency ) ?>">
<?php } ?>

<div class="form-group col-md-12 col-sm-12 col-xs-12" id="pt_payments_cont" style="display: none">
    <label for="pt_amount" class="clearfix"><span id="payment-info"></span></label>
    <div class="slider-cont">
        <input name="pt_payments_count" id="pt_payments_count" type="text" value="0" />
    </div>
</div>

