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

class invoiceModel
{
    /**
     * @var Invoice ID
     */
    public $idInvoice;

    /**
     * @var Invoice Number
     */
    public $invoiceNumber = "";

    /**
     * @var string
     */
    public $currencySymbol="";

    /**
     * @var array Items List array
     */
    public $invoicesList = array();

    /**
     * @var array Invoice Data
     */
    public $invoiceData = array();

    /**
     * @var array Invoice items Data
     */
    public $invoiceItemsData = array();

    public $invoiceTotal = 0;
    public $invoiceSubTotal = 0;
    public $invoiceTax = 0;

	private $table_items;
	private $table_history;
	private $history_actions;
	private PT_Db $db;

	public $invoiceType;
	public $invoiceCurrencySymbol;
	public $invoiceCurrencyPosition;
	public $invoiceCurrency;
	public $frequencyPeriod;
	public $frequencyCycle;
	public $billingPeriod;
	public $serviceName;
	public $serviceId;
	public $invoiceHashNumber;


    private $table;

    function __construct()
    {
        global $db_pr;
        $this->table = $db_pr . "invoices";
        $this->table_items = $db_pr . "invoice_items";
        $this->table_history = $db_pr . "invoice_history";
        $this->history_actions = array("create","update","send","paid","view");
        $this->db = new PT_Db();
    }

    /**
     * @param $id set ID for invoice
     */
    public function setID($id)
    {
        $this->idInvoice = $id;
    }

    /**
     * @param $id set invoice number
     */
    public function setNumber($num)
    {
        $this->invoiceNumber = $num;
    }

    public function setHashNumber($num)
    {
        $this->invoiceHashNumber = explode("-",$num);
        $this->invoiceHashNumber = $this->invoiceHashNumber[1];
    }

    /**
     * @return bool set invoice data
     */
    public function setInvoiceData()
    {

        if(!empty($this->invoiceNumber)){
            $where = "invoiceNumber='{$this->invoiceNumber}'";
        }
        if(!empty($this->invoiceHashNumber)){
            $where = "MD5(CONCAT('".SALT."',idInvoice)) = '{$this->invoiceHashNumber}'";
        }

        if(!empty($this->idInvoice)){
            $where = "idInvoice='{$this->idInvoice}'";
        }
        if(empty($where))
            return false;

        $sql = "SELECT * FROM `{$this->table}` WHERE $where";
        $res = $this->db->query($sql);

        if ($res->count) {
            $this->invoiceData = $res->result_row();
            $this->invoiceType = $this->invoiceData['invoiceType'];
            $this->idInvoice = $this->invoiceData['idInvoice'];
            $this->invoiceTax = $this->invoiceData['invoiceTax'];
            $this->invoiceTotal = $this->invoiceData['invoiceTotal'];
            $this->invoiceSubTotal = $this->invoiceData['invoiceSubTotal'];
            $this->invoiceNumber = $this->invoiceData['invoiceNumber'];
            $this->currencySymbol = $this->invoiceData['invoiceCurrencySymbol'];
            $this->invoiceCurrencySymbol = $this->invoiceData['invoiceCurrencySymbol'];
            $this->invoiceCurrencyPosition = $this->invoiceData['invoiceCurrencyPosition'];
            $this->invoiceCurrency = $this->invoiceData['invoiceCurrency'];

            $this->setInvoiceItems();
            if($this->invoiceType == 'recurring'){
                $this->setRecurringData();
            }

            return true;
        }
        return false;

    }

	/**
	 * @return bool
	 */
    public function isPaid(){
        return $this->invoiceData['invoiceStatus'] == 'paid';
    }

    /**
     * @return bool add history record
     */
    public function addHistory($action,$text)
    {
        if(in_array($action,$this->history_actions)) {
            $text = addslashes($text);
            $sql = "INSERT INTO `{$this->table_history}` SET
                    `action`='{$action}',
                    `idInvoice`='{$this->idInvoice}',
                    `text`='{$text}',
                    `dateCreated`= '" . NOW_DATE_TIME . "'";
            $res = $this->db->query($sql);
        }

    }

    /**
     * @return array get history records
     */
    public function getHistory()
    {

            $sql = "SELECT * FROM `{$this->table_history}` WHERE `idInvoice`='{$this->idInvoice}' ORDER BY idHistory DESC";
            $res = $this->db->query($sql);
            return $res->result_array();

    }

    /**
     * @return bool set invoice items data
     */
    public function setInvoiceItems()
    {
        $sql = "SELECT * FROM `{$this->table_items}` WHERE idInvoice='{$this->idInvoice}'";
        $res = $this->db->query($sql);
        if ($res->count) {
            $this->invoiceItemsData = $res->result_array();

            return true;
        }
        return false;

    }

    public function setRecurringData(){
        if(count($this->invoiceItemsData) ==1){
            $recurringItem = $this->invoiceItemsData[0];
            $recurringItemId = $recurringItem['itemItem'];
            $item = new itemModel();
            $item->setID($recurringItemId);
            if($item->getItem() && !empty($item->itemData['frequencyPeriod'])){
                $this->frequencyPeriod = $item->itemData['frequencyPeriod'];
                $this->frequencyCycle = $item->itemData['frequencyCycle'];
                $this->billingPeriod = $item->itemData['itemFrequency'];
                $this->serviceName = $item->itemData['itemName'];
                $this->serviceId = $item->itemData['idItem'];
            }
        }
    }

    /**
     * @param $idItem
     * @return array|bool
     */
    public function getInvoicesByItem($idItem){
        $sql = "SELECT * FROM `{$this->table_items}` WHERE itemItem='{$idItem}'";
        $res = $this->db->query($sql);
        if($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * @param $idCustomer
     * @return array|bool
     */
    public function getInvoicesByCustomer($idCustomer){
        $sql = "SELECT * FROM `{$this->table_items}` WHERE itemIcustomer='{$idCustomer}'";
        $res = $this->db->query($sql);
        if($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * Delete invoice item
     * @param $idItem
     * @return bool
     */
    public function deleteItem($idItem){
        $sql = "DELETE FROM `{$this->table_items}` WHERE idItem='{$idItem}'";
        $res = $this->db->query($sql);
        if($res->count)
            return true;
        return false;
    }

    /**
     * Delete invoice history
     * @param $idItem
     * @return bool
     */
    public function deleteHistory(){
        $sql = "DELETE FROM `{$this->table_history}` WHERE idInvoice='{$this->idInvoice}'";
        $res = $this->db->query($sql);
        if($res->count)
            return true;
        return false;
    }

    /**
     * Delete invoice items
     * @param $idItem
     * @return bool
     */
    public function deleteItems(){
        $sql = "DELETE FROM `{$this->table_items}` WHERE idInvoice='{$this->idInvoice}'";
        $res = $this->db->query($sql);
        if($res->count)
            return true;
        return false;
    }

    /**
     * Get readable due date text
     * @return string
     */
    public function getDueText()
    {
        $str = "-";
        $date = date("Y-m-d", strtotime(NOW_DATE_TIME));

        $invoiceDate = $this->invoiceData['invoiceDate'];
        if ($this->invoiceData['invoiceStatus'] !== 'paid') {
            if ($date == $this->invoiceData['invoiceDueDate']) {
                $str = "Today";
            } elseif ($date > $this->invoiceData['invoiceDueDate']) {
                $str = "<span class='warning'><i></i>Overdue</span>";
            } elseif ($date < $this->invoiceData['invoiceDueDate']) {
                $days = PT_Core::dateDiff($this->invoiceData['invoiceDueDate'],$date);
                $str = $days.($days>1?" DAYS":" DAY");
            }
        }
        return $str;
    }

    /**
     * Make invoice paid
     */
    public function setUsPaid(){

        $sql = "UPDATE `{$this->table}` SET
                `invoiceStatus` = 'paid',
                `paymentDate` = '".NOW_DATE_TIME."'
                WHERE idInvoice = '{$this->idInvoice}'";
        $res = $this->db->query($sql);

    }

    /**
     * Make invoice refunded
     */
    public function setUsRefunded(){

        $sql = "UPDATE `{$this->table}` SET
                `invoiceStatus` = 'refunded',
                `paymentDate` = '".NOW_DATE_TIME."'
                WHERE idInvoice = '{$this->idInvoice}'";
        $res = $this->db->query($sql);

    }


    /**
     * return total invoices value
     * @return array|null
     */
    public function totalInvoicesValue(){
        $sql = "SELECT SUM(invoiceTotal) as total FROM  `{$this->table}` ";
        $res = $this->db->query($sql)->result_row('total');
        return $res;
    }

    /**
     * return total unpaid invoices value
     * @return array|null
     */
    public function totalUnpaidInvoicesValue(){
        $sql = "SELECT SUM(invoiceTotal) as total FROM  `{$this->table}`
                WHERE invoiceStatus <> 'paid'";
        $res = $this->db->query($sql)->result_row('total');
        return $res;
    }

    /**
     * @return array|null
     */
    public function totalCurrentInvoicesValue(){

        $sql = "SELECT SUM(invoiceTotal) as total FROM  `{$this->table}`
                WHERE invoiceStatus = 'paid'";
        $res = $this->db->query($sql)->result_row('total');
        return $res;
    }

    public function formattedAmount($amount){
        $amount = PT_Core::_decFormat($amount);
        $c = $this->invoiceData['invoiceCurrencySymbol'];
        $p = $this->invoiceData['invoiceCurrencyPosition'];
        return ($p=='before'?$c:"").$amount.($p=='after'?$c:"");
    }

    /**
     * @param $rangeFrom int
     * @param $rangeTo int
     * @return array|null
     */
    public function totalOverdueInvoicesValue($rangeFrom,$rangeTo){

        $sql = "SELECT SUM(invoiceTotal) as total FROM  `{$this->table}`
                WHERE DATEDIFF('".NOW_DATE."',invoiceDueDate) <= '$rangeTo'
                AND  DATEDIFF('".NOW_DATE."',invoiceDueDate) >= '$rangeFrom'
                AND invoiceStatus <> 'paid' AND invoiceDueDate < '".NOW_DATE."'";
        $res = $this->db->query($sql)->result_row('total');
        return $res;
    }

    public function getViewLink(){
        $settings = PT_Settings::instance();
        $hashID = md5(SALT.$this->idInvoice);
        return $settings->site_url."/viewInvoice.php?inv-{$hashID}";

    }

    public function getPaymentLink(){
        $settings = PT_Settings::instance();
        $hashID = md5(SALT.$this->idInvoice);
        return $settings->site_url."/?invoice=inv-{$hashID}";

    }

    public function getTrackLink(){
        $settings = PT_Settings::instance();
        $hashID = md5(SALT.$this->idInvoice);
        return $settings->site_url."/pixel.php?invoice=inv-{$hashID}&a=open_mail";

    }

    /**Generate unique Order Number
     * @param $number
     * @param $id
     * @return string
     */
    public function getUniqueInvoiceNumber($number,$id){

        if (!empty($number)) {
            $number = stripslashes($number);

            $_number = $number;
            $i = 1;
            do {

                $sql = "SELECT * FROM `{$this->table}` WHERE `invoiceNumber`='".addslashes($number)."' AND idInvoice!={$id}";
                $res = $this->db->query($sql);

                if ($res->count > 0) {
                    $number=$_number."-$i";
                } else {
                    return addslashes($number);
                }
                $i++;
            } while ($res->count > 0);
        } else {
            return "";
        }
    }


    public function generateInvoice($render=true){

        //require_once(HOME_DIR.'/includes/tcpdf/config/tcpdf_config.php');
        require_once(HOME_DIR.'/includes/tcpdf_new/tcpdf.php');
        $settings = PT_Settings::instance();


// create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('CriticalGears');
        $pdf->SetTitle('Invoice #'.$this->invoiceNumber);
        $pdf->SetSubject('Invoice #'.$this->invoiceNumber);


// set default header data
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

// set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
        $pdf->setImageScale(1.2);



// ---------------------------------------------------------

// set font
        $pdf->SetFont('tahoma', '', 9);

// add a page
        $pdf->AddPage();

        $invoice_template = new PT_Template("pdf/invoice.php");
        $invoice_template->invoiceData = $this->invoiceData;
        $invoice_template->invoiceItems = $this->invoiceItemsData;
        $invoice_template->viewLink = $this->getPaymentLink();
        $invoice_template->currency_symbol = $this->currencySymbol;
        $invoice_template->c = empty($this->currencySymbol)?" ".$this->invoiceData['invoiceCurrency']:"";

// create some HTML content
        $html = $invoice_template->render(false);

// output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        if($this->invoiceData['invoiceStatus'] == 'paid'){
            $pdf->StartTransform();
            $pdf->Rotate(30);
            $pdf->writeHTMLCell('','',130,70,'<font size="30" color="red">PAID</font>', false, false, false, false, '');
            $pdf->StopTransform();
        }

        // reset pointer to the last page
        $pdf->lastPage();

// ---------------------------------------------------------

    if($render){
//Close and output PDF document
        $pdf->Output('Invoice.pdf', 'I');

    }else{
        return $pdf->Output('example_006.pdf', 'S');
    }
    }


    public function cloneInvoice($dateCreated = NOW_DATE_TIME){
        $idInvoice = 0;
        $invoiceNumber = $this->getUniqueInvoiceNumber($this->invoiceData['invoiceNumber'],0);
        $sql = "INSERT INTO `{$this->table}` SET 
                ";
        foreach ($this->invoiceData as $field => $data){
            switch ($field) {
                case "idInvoice":
                case "invoiceType":
                case "invoiceStatus":
                case "invoiceNumber";
                    break;

                case "dateCreated":
                    $sql .= "`{$field}` = '" . addslashes($dateCreated) . "',";
                    break;

                case "paymentDate":
                    $sql .= "`{$field}` = '" . addslashes($dateCreated) . "',";
                    break;

                case "invoiceDate":
                    $sql .= "`{$field}` = '" . addslashes($dateCreated) . "',";
                    break;

                case "invoiceDueDate":
                    $sql .= "`{$field}` = '" . addslashes($dateCreated) . "',";
                    break;

                default:
                    $sql .= "`{$field}` = '" . addslashes($data) . "',";

            }
        }
        $sql .="`invoiceType` = 'single',";
        $sql .="`invoiceStatus` = 'paid',";
        $sql .="`invoiceNumber` = '".addslashes($invoiceNumber)."'";

        $res = $this->db->query($sql);
        if($res->count){
            $idInvoice = $res->insert_id;
            foreach ($this->invoiceItemsData as $item){

                $sql = "INSERT INTO `{$this->table_items}` SET ";
                foreach ($item as $field => $data) {
                    if($field =='idItem' || $field == 'idInvoice')
                        continue;

                    $sql .= "`{$field}` = '".addslashes($data)."',";

                }
                $sql .=" `idInvoice` = '{$idInvoice}'";
                $res = $this->db->query($sql);
            }
        }
        return $idInvoice;
    }

    public function getPayments(){
        $paymentModel = new paymentModel();
        return $paymentModel->getPaymentByInvoice($this->idInvoice);
    }

    public function getSubscription(){
        $subscriptionModel = new subscriptionModel();
        return $subscriptionModel->getSubscriptionByInvoice($this->idInvoice);
    }

    public function deleteInvoice($id){
        $this->setID($id);
        $this->deleteItems();
        $this->deleteHistory();
        $sql = "DELETE FROM {$this->table} WHERE idInvoice = '{$this->idInvoice}'";
        $res = $this->db->query($sql);
        if($res->count)
            return true;
        return false;

    }
} 
