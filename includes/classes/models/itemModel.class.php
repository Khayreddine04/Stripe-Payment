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

//namespace models;


class itemModel
{

    /**
     * @var Item ID
     */
    public $idItem;

    /**
     * @var array Items List array
     */
    public $itemsList = array();

    /**
     * @var array Item Data
     */
    public $itemData = array();

    public $table;
	private PT_Db $db;

    /**
     * @var Item ID
     */
    public $isRecurring = false;

    function __construct()
    {
        global $db_pr;
        $this->table = $db_pr . "items";
        $this->db = new PT_Db();
    }

    public static function getTableName(){
        global $db_pr;
        return $db_pr . "items";
    }

    public static function getItemAmount($id_item){

        $db = new PT_Db();
        $sql = "SELECT * FROM `".self::getTableName()."` WHERE idItem='{$id_item}'";
        $res = $db->query($sql);
        return $res->count?$res->result_row('itemAmount'):0;
    }

    public static function getTaxExemption($id_item){

        $db = new PT_Db();
        $sql = "SELECT * FROM `".self::getTableName()."` WHERE idItem='{$id_item}'";
        $res = $db->query($sql);
        return $res->count?$res->result_row('taxExempt'):'n';
    }

    /**
     * @param $id set ID for item
     */
    public function setID($id)
    {
        $this->idItem = $id;
    }

    /**
     * @return bool
     */
    public function getItem(){

        if($this->idItem=='pt_donation'){
            $this->itemData['itemName'] = 'Donation';
            $this->itemData['itemAmount'] = 0;
            return true;
        }
        $sql = "SELECT * FROM `{$this->table}` WHERE idItem='{$this->idItem}' AND itemStatus='y' ";
        $res = $this->db->query($sql);
        if($res->count){
            $this->itemData = $res->result_row();
            if($this->itemData['itemType']=='service'&& !empty($this->itemData['itemFrequency']))
                $this->fetchSubscriptionData();

            $this->applyAmount();
            $this->checkRecurring();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function _getItem($idItem, $field=null){

        $sql = "SELECT * FROM `{$this->table}` WHERE idItem='{$idItem}' AND itemStatus='y' ";
        $res = $this->db->query($sql);
        if($res->count){
            return $res->result_row($field);
        }
        return false;
    }


    public function applyAmount(){
        $core = PT_Core::instance();
        //PT_Core::_dump($this->itemData['itemAmount']);
        if(isset($core->post['amount']) && $core->post['amount'] >0 && $this->itemData['allowOverride']=='y'){
            $this->itemData['itemAmount'] = $core->post['amount'];
        }
    }

    private function fetchSubscriptionData(){
        global $_BILLING_PERIODS;
        if(isset($_BILLING_PERIODS[$this->itemData['itemFrequency']])){
            $this->itemData['frequencyPeriod'] = $_BILLING_PERIODS[$this->itemData['itemFrequency']]['period'];
            $this->itemData['frequencyCycle'] = $_BILLING_PERIODS[$this->itemData['itemFrequency']]['cycle'];
        }
    }

    /**
     * @param string $type Type of item service|product
     * @return array array of items
     */
    public function getItems($type = 'item')
    {

        $where = $type === 'item' || $type === 'donation' ? "" : "WHERE itemType = '{$type}' ";
        if(!empty($where)){$where.= " AND itemStatus='y' ";}else{ $where.=" WHERE itemStatus='y' ";}
        $sql = "SELECT * FROM `{$this->table}` $where ORDER BY itemName ASC";
        $res = $this->db->query($sql);
        $this->itemsList = $res->result_array();
        return $this->itemsList;
    }

    /**
     * @return array array of recurring items without trial
     */
    public function getRecurringItemsWithoutTrial()
    {

        $sql = "SELECT * FROM `{$this->table}` WHERE itemType='service' AND itemTrial='n' AND itemStatus='y' ORDER BY itemName ASC";
        $res = $this->db->query($sql);
        $this->itemsList = $res->result_array();
        return $this->itemsList;
    }

    /**
     * @param $post $_POST data
     * @param string $type type of items
     * @return string
     */
    public function getItemsHTMLList($post,$type = 'item')
    {
        $html = "";
        $services = $this->getItems($type);
        foreach ($services as $v) {
            $billingPeriod = "";

            $html .= "<option data-amount='{$v['itemAmount']}' value='{$v['idItem']}' ";
            $html .= ($v['idItem'] == $post['pt_service'] ? "selected" : "") ;
            if($v['itemPlan']==='y' && $v['itemType']=='product'){
                $html .= "data-plan='1' data-pmin='{$v['itemBillingMin']}' data-pmax='{$v['itemBillingMax']}'" ;
                $html .= " data-interval='monthly' data-recurring='true'" ;
            }
            if($v['itemTrial']==='y'){
                $html .= " data-trial='" . $v['itemTrialDays'] . "'" ;
            }
            if(!empty($v['itemFrequency']) && $v['itemType']=='service') {
                $billingPeriod = $v['itemFrequency'];
                $html .= " data-interval='" . $v['itemFrequency'] . "'  data-recurring='true'" ;
            }else{
                $html .= "  data-recurring='false'" ;
            }
            $html .= " data-description='" . htmlspecialchars($v['itemDescription'],ENT_QUOTES) . "'" ;

            $html .= ">{$v['itemName']} ( " . PT_Core::getCurrencyText($v['itemAmount'],false) . $billingPeriod." )</option>\n";
        }
        return $html;
    }

    /**
     * @return array custom array of items for typeahead script
     */
    public function getItemsListForTypeahead()
    {

        $itemsArray = array();
        foreach ($this->getItems('product') as $item) {
            if($item['itemPlan'] == 'y')
                continue;
            $itemsArray[] = array(
                "label" => $item['itemName'],
                "title" => $item['itemName'] . " <small>( \${$item['itemAmount']} )</small> ",
                "rate" => $item['itemAmount'],
                "id" => $item['idItem'],
            );
        }

        return $itemsArray;
    }

    /**
     * Delete Item
     */
    public function delItem(){
        $invoice = new invoiceModel();
        $payment = new paymentModel();
        $subscription = new subscriptionModel();
        $hasInvoices =$invoice->getInvoicesByItem($this->idItem);
        $hasPayments =$payment->getPaymentByItem($this->idItem);
        $hasSubscriptionItems = $subscription->getSubscriptionsByItem($this->idItem);
        if( $hasInvoices ===false && $hasPayments === false && $hasSubscriptionItems === false ){
            $sql = "DELETE FROM `{$this->table}` WHERE idItem = '$this->idItem'";
            $this->db->query($sql);
            return true;
        }else{
            return false;
        }
    }

    public function insertItem($data){
        extract($data);
        $sql = "INSERT INTO `{$this->table}` SET
            itemName = '{$itemName}',
            itemType = '{$itemType}',
            itemFrequency = '{$itemFrequency}',
            itemAmount = '{$itemAmount}'";

        $res = $this->db->query($sql);
        if($res->count<1)
            return false;

        $this->setID($res->insert_id);
        return $this->idItem;
    }

    public function checkRecurring(){
        if(!empty($this->itemData['frequencyPeriod'])) {
            $this->isRecurring = true;
        }
        if($this->itemData['itemPlan']=='y'){
            $this->isRecurring = true;
        }
        return false;
    }


}
