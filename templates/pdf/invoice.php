<table width="100%" border="0">
    <tr>
        <td width="66%"><?php if(!empty($terminal_logo)){?>
                <img src="<?php echo($site_url.$terminal_logo) ?>" class="logo" /><br>
            <?php }else{
                echo($email_name);
             }?>
            </td>
        <td width="33%" valign="center" style="vertical-align: middle"><br><br><br><font size="+5"><b>INVOICE</b></font></td>
    </tr>
</table>
<div>&nbsp;</div>
<table width="100%" border="0">
    <tr>
        <td width="33%"><strong><?php echo($email_name) ?></strong><br>
<?php echo(nl2br($billing_info)) ?>
        </td>
        <td width="33%"><strong><?php echo($invoiceData['customerName']) ?></strong><br>
<?php echo(nl2br($invoiceData['invoiceBillTo'])) ?></td>
        <td width="33%"><strong>Invoice number:&nbsp;</strong><?php echo($invoiceData['invoiceNumber']) ?><br><?php if(!empty($invoiceData['orderNumber'])){?>
<strong>P.O.Number:&nbsp;</strong><?php echo($invoiceData['orderNumber']) ?><br>

<?php }?><strong>Invoice date:&nbsp;</strong><?php echo(PT_Core::_getDateFormat($invoiceData['invoiceDate'])) ?><br>
<strong>Due date:&nbsp;</strong><?php echo(PT_Core::_getDateFormat($invoiceData['invoiceDueDate'])) ?><br>
<strong>Amount due:&nbsp;</strong><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?><br>
        </td>
    </tr>
</table>
<div>&nbsp;</div>

<table cellpadding="5" width="100%">
    <tr>
        <td style="border-top: 1px solid #000000;border-bottom:1px solid #000000;width: 40%" ><b>PRODUCT/SERVICE</b></td>
        <td style="border-top: 1px solid #000000;border-bottom:1px solid #000000;width: 15%" align="center"><b>QTY</b></td>
        <td style="border-top: 1px solid #000000;border-bottom:1px solid #000000;width: 15%" align="center"><b>PRICE</b></td>
        <td style="border-top: 1px solid #000000;border-bottom:1px solid #000000;width: 15%" align="center"><b>DISCOUNT</b></td>
        <td style="border-top: 1px solid #000000;border-bottom:1px solid #000000;width: 15%" align="center"><b>AMOUNT</b></td>
    </tr>


    <?php $i=1;foreach($invoiceItems as $item){
        $itemTotal = round($item['itemRate'] * $item['itemQty'],2);
        $itemDiscount = round($itemTotal * ($item['itemDiscount'] / 100),2);
        ?>
        <tr>
            <td <?php echo($i%2==0?' style="background-color: #eaeaea"':'') ?>><?php echo($item['itemName']) ?><?php if(!empty($item['itemDescription'])){?><br><font size="-2"><?php echo $item['itemDescription']?></font><?php }?></td>
            <td <?php echo($i%2==0?' style="background-color: #eaeaea"':'') ?> align="center"><?php echo($item['itemQty']) ?></td>
            <td <?php echo($i%2==0?' style="background-color: #eaeaea"':'') ?> align="center"><?php echo(PT_Core::_getCurrencyText($item['itemRate'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
            <td <?php echo($i%2==0?' style="background-color: #eaeaea"':'') ?> align="center">-<?php echo(PT_Core::_getCurrencyText($itemDiscount,$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
            <td <?php echo($i%2==0?' style="background-color: #eaeaea"':'') ?> class="amount" align="center"><?php echo(PT_Core::_getCurrencyText($item['itemTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
        </tr>
    <?php $i++;}?>

</table>
<div>&nbsp;</div>
<table cellpadding="5" width="100%" border="0">
<?php if(!empty($invoiceData['invoiceTax'])){?>
    <tr>
        <td style="width: 55%">&nbsp;</td>
        <td style="width: 15%">&nbsp;</td>
        <td style="border-top: 1px solid #000000;width: 15%;text-align: right">Subtotal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style="border-top: 1px solid #000000;width: 15%;text-align: center"><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceSubTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td style="border-top: 1px solid #000000;;text-align: right">Tax&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style="border-top: 1px solid #000000;;text-align: center"><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceTax'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
    </tr>
    <?php }?>
    <tr>
        <td style="width: 55%"></td>
        <td style="width: 15%"></td>
        <td style="border-top: 1px solid #000000;width: 15%;;text-align: right;"><b>Total</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style="border-top: 1px solid #000000;width: 15%;;text-align: center"><?php echo(PT_Core::_getCurrencyText($invoiceData['invoiceTotal'],$invoiceData['invoiceCurrencyPosition'],$invoiceData['invoiceCurrencySymbol'])) ?></td>
    </tr>



</table>
<div>&nbsp;</div>
<div>&nbsp;</div>
<?php /*if($display_pdf_payment_options=='y' && $invoiceData['invoiceStatus']!='paid'){?>
    <table cellpadding="5" width="100%" border="0">
    <tr>
        <td >Pay online at:<br><a href="<?php echo($viewLink) ?>"><?php echo($viewLink) ?></a>
            <div>&nbsp;</div>

            <table cellpadding="3" width="50%">
                <tr>
                    <td><img src="<?php echo($site_url) ?>/assets/images/icons/visa.png"></td>
                    <td><img src="<?php echo($site_url) ?>/assets/images/icons/mastercard.png"></td>
                    <td><img src="<?php echo($site_url) ?>/assets/images/icons/american_express.png"></td>
                    <?php if($enable_paypal=='y'){?>
                        <td><img src="<?php echo($site_url) ?>/assets/images/icons/paypal.png"></td>
                    <?php }?>
                </tr>
            </table>

        </td>
    </tr>
    </table>
<?php }*/?>

<div>&nbsp;</div>
<?php if(!empty($invoiceData['invoiceNotes'])){?>
<h2>&nbsp;&nbsp;NOTES</h2>
<div style="border-top:1px solid #ccc;font-size: 5px;height: 5px ">&nbsp;</div>
<table cellpadding="10">
    <tr>

        <td ><font size="-1"><?php echo(nl2br($invoiceData['invoiceNotes'])) ?></font></td>
    </tr>

</table>
<?php }?>
<?php if(!empty($invoiceData['invoiceTerms'])){?>

<h2>&nbsp;&nbsp;TERMS & CONDITIONS</h2>
<div style="border-top:1px solid #ccc;font-size: 5px;height: 5px ">&nbsp;</div>
<table cellpadding="10">
    <tr>

        <td ><font size="-1"><?php echo(nl2br($invoiceData['invoiceTerms'])) ?></font></td>
    </tr>

</table>
<?php }?>
