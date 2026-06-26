<table class="invoiceInfo">
    <tr>
        <td>
            <?php if(!empty($terminal_logo)){?>
                <img src="<?php echo($site_url.$terminal_logo) ?>" class="logo"/><br>
            <?php }?>
            <strong><?php echo($email_name) ?></strong><br>
            <?php echo(nl2br($billing_info)) ?>
        </td>
        <td style="padding-top: 66px;">

            <strong>Bill to:</strong><br>
            <strong><?php echo($invoiceData['customerName']) ?></strong><br>
            <?php echo(nl2br($invoiceData['invoiceBillTo'])) ?>

        </td>
        <td>

            <h2>Invoice</h2>
            <strong>Invoice number:&nbsp;</strong> <?php echo($invoiceData['invoiceNumber']) ?><br>
            <?php if(!empty($invoiceData['orderNumber'])){?>
                <strong>P.O.Number:&nbsp;</strong><?php echo($invoiceData['orderNumber']) ?><br>
            <?php }?>
            <strong>Invoice date:&nbsp;</strong><?php echo(PT_Core::_getDateFormat($invoiceData['invoiceDate'])) ?><br>
            <strong>Due date:&nbsp;</strong><?php echo(PT_Core::_getDateFormat($invoiceData['invoiceDueDate'])) ?><br>
            <strong>Amount due:&nbsp;</strong><?php echo($invoiceAmount) ?><br>

        </td>
    </tr>
</table>

<div class="invoiceDetailsCont">
    <table class="invoiceDetails">
        <tr>
            <th>PRODUCT/SERVICE</th>
            <th>QTY</th>
            <th>PRICE</th>
            <th>DISCOUNT</th>
            <th>AMOUNT</th>
        </tr>
        <?php foreach($invoiceItems as $item){
            $itemTotal = round($item['itemRate'] * $item['itemQty'],2);
            $itemDiscount = round($itemTotal * ($item['itemDiscount'] / 100),2);?>
        <tr>
            <td><?php echo($item['itemName']) ?>
            <?php if(!empty($item['itemDescription'])){?>
                <p style="font-size: 11px"><?php echo $item['itemDescription']?></p>
            <?php }?>
            </td>
            <td><?php echo($item['itemQty']) ?></td>

            <td><?php echo(PT_Core::_getCurrencyText($item['itemRate'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
            <td>-<?php echo(PT_Core::_getCurrencyText($itemDiscount,$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
            <td class="amount"><?php echo(PT_Core::_getCurrencyText($item['itemTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
        </tr>
        <?php }?>
    </table>
</div>
<div class="invoiceTotalsCont">
    <table class="invoiceTotals">
        <?php if(!empty($invoiceData['invoiceTax'])){?>
        <tr>

            <td><strong>Subtotal</strong></td>
            <td><strong><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceSubTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></strong></td>

        </tr>
            <tr>

                <td>Tax</td>
                <td><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceTax'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>

            </tr>
        <?php }?>
        <tr>

            <td><strong>Total</strong></td>
            <td><strong><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></strong></td>

        </tr>
    </table>
    <?php if(!$paid && $display_pdf_payment_options=='y'){ ?>
    <div class="invoicePayOnline">
        <hr>
        <p>Pay online at:</p>
        <a href="<?php echo($viewLink) ?>"><?php echo($viewLink) ?></a>

        <ul>
            <li><img src="assets/images/icons/visa.png"></li>
            <li><img src="assets/images/icons/mastercard.png"></li>
            <li><img src="assets/images/icons/american_express.png"></li>
            <?php if($enable_paypal=='y'){?>
                <li><img src="assets/images/icons/paypal.png"></li>
            <?php }?>
        </ul>

    </div>
    <?php } ?>
</div>
<?php if(!empty($invoiceData['invoiceNotes'])){?>
<div class="invoiceNotes">
    <h4>Notes</h4>
    <p>
        <?php echo(nl2br($invoiceData['invoiceNotes'])) ?>
    </p>
</div>
<?php }?>

<?php if(!empty($invoiceData['invoiceTerms'])){?>
    <div class="invoiceNotes">
        <h4>Terms & Conditions</h4>
        <p>
            <?php echo(nl2br($invoiceData['invoiceTerms'])) ?>
        </p>
    </div>
<?php }?>
