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

class paymentModel {

    /**
     * @var Payment ID
     */
    public $idPayment;

    private $table;

	private $refund_table;

	private $subscripttion_table;

	private $settings_table;

	private $subscription;

	private PT_Db $db;

    /**
     * @var array payment array data
     */
    public $paymentData = array();

    function __construct()
    {
        global $db_pr;
        $this->table = $db_pr . "payments";
		$this->refund_table = $db_pr . "refunds";
        $this->subscripttion_table = $db_pr . "subscriptions";
        $this->settings_table = $db_pr . "settings";
        $this->db = new PT_Db();
    }

    /**
     * @return array|bool|null
     */
    public function getPayment(){
        $sql = "SELECT * FROM `{$this->table}` WHERE idPayment = '{$this->idPayment}'";
        $res = $this->db->query($sql);
        if($res->count)
            return $this->paymentData = $res->result_row();
        return false;
    }
    /**
     * @param $id set ID for item
     */
    public function setID($id)
    {
        $this->idPayment = $id;
    }

    /**
     * @param $data
     * @return bool
     */
    public function addPayment($data){

        $sql = "INSERT INTO `{$this->table}` SET
            `paypalStatus` =  '{$data['paypalStatus']}',
            `dateCreated` = '".NOW_DATE_TIME."',
            `customerName` = '{$data['customerName']}',
            `customerEmail` = '{$data['customerEmail']}',
            `amount` = '{$data['amount']}',
            `tax_amount` = '{$data['tax_amount']}',
            `tax_rate` = '{$data['tax_rate']}',
            `tax_abbreviation` = '{$data['tax_abbreviation']}',
            `idTransaction` = '{$data['idTransaction']}',
            `idItem` = '{$data['idItem']}',
            `processor` = '{$data['processor']}',
            `comments` = '{$data['comments']}',
            `idInvoice` = '{$data['idInvoice']}',
            `clickid` = '" . (isset($data['clickid']) ? mysqli_real_escape_string($this->db->link, $data['clickid']) : '') . "',
            `source` = '" . (isset($data['source']) ? mysqli_real_escape_string($this->db->link, $data['source']) : '') . "',
            `billingAddress1` = '{$data['billingAddress1']}',
            `billingAddress2` = '{$data['billingAddress2']}',
            `billingCity` = '{$data['billingCity']}',
            `billingCountry` = '{$data['billingCountry']}',
            `billingState` = '{$data['billingState']}',
            `billingZip` = '{$data['billingZip']}',
            `paypalSubscription` = '',

            `shippingAddress1` = '{$data['shippingAddress1']}',
            `shippingAddress2` = '{$data['shippingAddress2']}',
            `shippingCity` = '{$data['shippingCity']}',
            `shippingCountry` = '{$data['shippingCountry']}',
            `shippingState` = '{$data['shippingState']}',
            `shippingZip` = '{$data['shippingZip']}',

            `currency` = '{$data['currency']}',
            `currency_symbol` = '{$data['currency_symbol']}',
            `currency_position` = '{$data['currency_position']}',
            `stripeCharge` = '{$data['stripeCharge']}',
            `stripeCustomer` = '{$data['stripeCustomer']}',
            `stripeSubscription` = '{$data['stripeSubscription']}'";

        if (class_exists('PT_Payment_Gateway')) {
            $sql .= PT_Payment_Gateway::sqlAssignments($data, $this->db);
        }

        $sql .= ",";


            /* check if item's exempt or not and add tax to or_amount in that case */
            $service_fee = 0;
            if(!empty($data['idItem'])){
                /* paying for item */
                $sql_temp = "SELECT * FROM `{$this->settings_table}` WHERE option_name = 'tax_enable' LIMIT 1;";
                $tax_enable = $this->db->query($sql_temp)->result_row("option_value");
                $item_amount = itemModel::getItemAmount($data['idItem']);

                /* service fee adjustments */
                $settings = PT_Settings::instance();
                if($settings->fee_enable == 'y'){
                    $service_fee = ($settings->fee_type==1)?(($settings->fee_amount * $item_amount) / 100):($settings->fee_amount);
                }
                $item_amount = $item_amount + $service_fee;

                if($tax_enable === 'y'){
                    if(is_numeric($data['idItem'])) {
                        /* only if paying for item/product/service, not donation */
                        $exempt = itemModel::getTaxExemption($data['idItem']) === "y";
                    } else { $exempt = false; }

                    if(!$exempt){
                        /* get tax rate, since tax is enabled and payment is for non exempt item */
                        $sql_temp = "SELECT * FROM `{$this->settings_table}` WHERE option_name = 'tax_rate' LIMIT 1;";
                        $tax_rate = $this->db->query($sql_temp)->result_row("option_value");
                        $temp_amount = itemModel::getItemAmount($data['idItem']) + $service_fee;
                        $item_amount = round($temp_amount + $temp_amount*($tax_rate/100),2);
                    }
                }

            }else{
                /* invoice payment? */
                $item_amount = $data['amount'];
            }
        $sql .="service_fee = '".addslashes($service_fee)."',";
        $sql .="or_amount = '".addslashes($item_amount)."'";

        $res = $this->db->query($sql);
        if($res->count<1)
            return false;

        $this->setID($res->insert_id);
	    st_do_action("after_save_payment", $this->idPayment,$data);

        return $this->idPayment;
    }

    public function importPayment($data){
        $sql = "SELECT stripeCharge FROM `{$this->table}` WHERE stripeCharge='{$data['stripeCharge']}'";
        if($this->db->query($sql)->count)
            return false;
        if(!empty($data['stripeSubscription'])){
            $subscription = new subscriptionModel();
            $subscriptionData = $subscription->getSubscriptionByTrn($data['stripeSubscription']);
            if($subscriptionData === false)
                return false;

	        $data['idCustomer'] = $subscriptionData['idCustomer'];
            if(!empty($subscriptionData['idInvoice'])){
                $invoice = new invoiceModel();
                $invoice->setID($subscriptionData['idInvoice']);
                if($invoice->setInvoiceData()){
                    $data['idInvoice'] = $invoice->cloneInvoice($data['created']);
                }
            }
	        if(!empty($subscriptionData['idItem'])){
		        $data['idItem'] = $subscriptionData['idItem'];
	        }
        }else{
            return false;
        }

        $sql = "INSERT INTO `{$this->table}` SET
            `paypalStatus` =  '{$data['paypalStatus']}',
            `dateCreated` = '".$data['created']."',
            `customerName` = '{$data['customerName']}',
            `customerEmail` = '{$data['customerEmail']}',
            `amount` = '{$data['amount']}',
            `or_amount` = '{$data['amount']}',
            `idTransaction` = '{$data['idTransaction']}',
            `idItem` = '{$data['idItem']}',
            `processor` = '{$data['processor']}',
            `comments` = '{$data['comments']}',
            `idInvoice` = '{$data['idInvoice']}',
            `billingAddress1` = '{$data['billingAddress1']}',
            `billingAddress2` = '{$data['billingAddress2']}',
            `billingCity` = '{$data['billingCity']}',
            `billingCountry` = '{$data['billingCountry']}',
            `billingState` = '{$data['billingState']}',
            `billingZip` = '{$data['billingZip']}',
            
            `tax_amount` = '0',
            `tax_rate` = '0',
            `tax_abbreviation` = '',

            `shippingAddress1` = '{$data['shippingAddress1']}',
            `shippingAddress2` = '{$data['shippingAddress2']}',
            `shippingCity` = '{$data['shippingCity']}',
            `shippingCountry` = '{$data['shippingCountry']}',
            `shippingState` = '{$data['shippingState']}',
            `shippingZip` = '{$data['shippingZip']}',
            
            `paypalSubscription` = '',
			`service_fee` = 0,
            `currency` = '{$data['currency']}',
            `currency_symbol` = '{$data['currency_symbol']}',
            `currency_position` = '{$data['currency_position']}',
            `stripeCharge` = '{$data['stripeCharge']}',
            `stripeCustomer` = '{$data['stripeCustomer']}',
            `stripeSubscription` = '{$data['stripeSubscription']}',
            `idCustomer` = '{$data['idCustomer']}',
            `imported` = 'y'";
        if (class_exists('PT_Payment_Gateway')) {
            $sql .= PT_Payment_Gateway::sqlAssignments($data, $this->db);
        }
        $res = $this->db->query($sql);
        if($res->count<1)
            return false;
        st_do_action('after_import_payment',$data);
        return true;
    }

    public function importPayPalPayment($data){

        $sql = "SELECT idTransaction FROM `{$this->table}` WHERE idTransaction='{$data['idTransaction']}'";
        if($this->db->query($sql)->count)
            return false;

        $sql = "INSERT INTO `{$this->table}` SET
            `paypalStatus` =  '{$data['paypalStatus']}',
            `dateCreated` = '{$data['dateCreated']}',
            `customerName` = '{$data['customerName']}',
            `customerEmail` = '{$data['customerEmail']}',
            `amount` = '{$data['amount']}',
            `idTransaction` = '{$data['idTransaction']}',
            `idItem` = '{$data['idItem']}',
            `processor` = '{$data['processor']}',
            `comments` = '{$data['comments']}',
            `idInvoice` = '{$data['idInvoice']}',
            `billingAddress1` = '{$data['billingAddress1']}',
            `billingAddress2` = '{$data['billingAddress2']}',
            `billingCity` = '{$data['billingCity']}',
            `billingCountry` = '{$data['billingCountry']}',
            `billingState` = '{$data['billingState']}',
            `billingZip` = '{$data['billingZip']}',

            `shippingAddress1` = '{$data['shippingAddress1']}',
            `shippingAddress2` = '{$data['shippingAddress2']}',
            `shippingCity` = '{$data['shippingCity']}',
            `shippingCountry` = '{$data['shippingCountry']}',
            `shippingState` = '{$data['shippingState']}',
            `shippingZip` = '{$data['shippingZip']}',

            `currency` = '{$data['currency']}',
            `currency_symbol` = '{$data['currency_symbol']}',
            `currency_position` = '{$data['currency_position']}',
            `stripeCharge` = '{$data['stripeCharge']}',
            `stripeCustomer` = '{$data['stripeCustomer']}',
            `stripeSubscription` = '{$data['stripeSubscription']}',
            `paypalSubscription` = '{$data['paypalSubscription']}',
            `imported` = 'y'";
        if (class_exists('PT_Payment_Gateway')) {
            $sql .= PT_Payment_Gateway::sqlAssignments($data, $this->db);
        }
        //PT_Core::_dump($sql);
        $res = $this->db->query($sql);
        if($res->count<1)
            return false;

        return true;
    }

    /**
     * Return readable billing address
     * @return bool|string
     */
    public function getFormattedAddress(){
        if(!empty($this->paymentData['billingAddress1']) && !empty($this->paymentData['billingCity']) && !empty($this->paymentData['billingCountry'])){
            $payment = new PT_Stripe_Payment();
            $country = $payment->getCountriesListJSON($this->paymentData['billingCountry']);
            return "{$this->paymentData['billingAddress1']} {$this->paymentData['billingAddress2']}, {$this->paymentData['billingCity']}, {$this->paymentData['billingState']} {$this->paymentData['billingZip']}, {$country}";
        }else{
            return false;
        }
    }


    /**
     * Return readable shipping address
     * @return bool|string
     */
    public function getFormattedShippingAddress(){
        if(!empty($this->paymentData['shippingAddress1']) && !empty($this->paymentData['shippingCity']) && !empty($this->paymentData['shippingCountry'])){
            $payment = new PT_Stripe_Payment();
            $country = $payment->getCountriesListJSON($this->paymentData['shippingCountry']);
            return "{$this->paymentData['shippingAddress1']} {$this->paymentData['shippingAddress2']}, {$this->paymentData['shippingCity']}, {$this->paymentData['shippingState']} {$this->paymentData['shippingZip']}, {$country}";
        }else{
            return false;
        }
    }

    /**
     * @param $idItem
     * @return array|bool
     */
    public function  getPaymentByItem($idItem){
        $sql = "SELECT * FROM `{$this->table}` WHERE idItem='{$idItem}'";
        $res = $this->db->query($sql);
        if($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * @param $idItem
     * @return array|bool
     */
    public function  getPaymentByInvoice($idInvoice){
        $sql = "SELECT * FROM `{$this->table}` WHERE idInvoice = '{$idInvoice}' ORDER BY dateCreated DESC";
        return $this->db->query($sql)->result_array();
        if($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * Make payment as Paid
     * @param $idPayment
     * @param $trnId
     * @return bool
     */
    public function activatePaypalPayment($idPayment,$trnId){
        $sql = "UPDATE `{$this->table}` SET
                `paypalStatus` = 'paid',
                `idTransaction` = '{$trnId}',
                `dateCreated` = '".NOW_DATE_TIME."'
                WHERE idPayment = '{$idPayment}'";
        $res = $this->db->query($sql);
        if($res->count)
            return true;
        return false;
    }

    /**
     * Make payment as refunded
     * @param $idPayment
     * @param $trnId
     * @return bool
     */
    public function refundPayment($idPayment,$trnId,$amount,$reason){
        $sql = "INSERT INTO `{$this->refund_table}` SET
                `idPayment`	= '{$idPayment}',	
				`idTransaction` = '{$trnId}',	
				`amount`= '{$amount}',	
				`reason` = '{$reason}',	
				`dateCreated` = '".NOW_DATE_TIME."'";
        $res = $this->db->query($sql);
        if($res->count){
			$refunded_amount = $this->getRefundsTotal();
			$payment = $this->getPayment();
			$status = $refunded_amount == $payment['amount']?'refunded':'partial_refund';
	        $sql = "UPDATE `{$this->table}` SET
                `paypalStatus` = '{$status}'
                WHERE `idPayment`	= '{$idPayment}'";
	        $res = $this->db->query($sql);
	        return true;
        }

        return false;
    }

	public function getRefunds(){
			$sql = "SELECT * FROM  `{$this->refund_table}` 
					WHERE idPayment = '{$this->idPayment}'";
        $res = $this->db->query($sql);
		if($res->count)
			return $res->result_array();
		return false;
	}

	public function getRefundsTotal(){

		$sql = "SELECT SUM(amount) as total FROM  `{$this->refund_table}` 
					WHERE idPayment = '{$this->idPayment}'";
		$res = $this->db->query($sql);
		$total = $res->result_row('total');
		return$total?:0;
	}

    public function getTotalVolume($dateFrom = null,$dateTo = null){

        $where = !empty($dateFrom) && !empty($dateTo) ? "AND dateCreated BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59'":"";
        $sql = "SELECT SUM(amount) as total FROM `{$this->table}` WHERE paypalStatus = 'paid' $where";
        $res = $this->db->query($sql)->result_row("total");

        return $res?:0;
    }

    public function getTotalVolumeByDate($date){

        $sql = "SELECT SUM(amount) as total FROM `{$this->table}` WHERE paypalStatus = 'paid' AND dateCreated LIKE '%{$date}%'";
        $res = $this->db->query($sql)->result_row("total");

        return $res?:0;
    }

    public function getPaymentsCount($date=null){
        $where = !empty($date)  ? "AND  dateCreated LIKE '%{$date}%'":"";
        $sql = "SELECT count(amount) as total FROM `{$this->table}` WHERE paypalStatus = 'paid' $where";//print $sql;
        $res = $this->db->query($sql)->result_row("total");

        return $res;
    }

    /*public function isSubscriptionPayment(){

        return !empty($this->paymentData['stripeSubscription']) || !empty($this->paymentData['paypalSubscription']) ;
    }*/

    public function getSubscription(){
        $subscription = new subscriptionModel();
        if(!empty($this->paymentData['stripeSubscription'])) {
            $idTransaction = $this->paymentData['stripeSubscription'];
        }elseif(!empty($this->paymentData['paypalSubscription'])) {
            $idTransaction = $this->paymentData['paypalSubscription'];
        }else{
            return false;
        }
        if (false !== $this->subscription = $subscription->getSubscriptionByTrn($idTransaction)) {
            return $this->subscription;
        }
        return false;
    }

    public function getSubscriptionPayments($idSubscription, $idCustomer=null){
        if(empty($idSubscription) && empty($idCustomer))
            return array();

        $where = empty($idCustomer)?"paypalSubscription = '{$idSubscription}'":"stripeCustomer='{$idCustomer}' 
            AND stripeSubscription='{$idSubscription}'";
        $sql = "SELECT * FROM `{$this->table}` WHERE $where ORDER BY dateCreated DESC";
        return $this->db->query($sql)->result_array();
    }

    public function getFirstTransaction(){
        $sql = "SELECT stripeCharge FROM `{$this->table}` WHERE stripeCharge<>'' ORDER by idPayment ASC LIMIT 1";
        $res = $this->db->query($sql);
        return $res->count?$res->result_row('stripeCharge'):false;
    }
}
