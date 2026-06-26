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


class customerModel
{

    /**
     * @var Customer ID
     */
    public $idCustomer;


    /**
     * @var array Customer Data
     */
    public $customerData = array();

    public $table;
	private PT_Db $db;
	public $customerList;

    function __construct()
    {
        global $db_pr;
        $this->table = $db_pr . "customers";
        $this->db = new PT_Db();
    }

    /**
     * @param $id set ID for customer
     */
    public function setID($id)
    {
        $this->idCustomer = $id;
    }

    /**
     * @return bool
     */
    public function setData()
    {

        $sql = "SELECT * FROM `{$this->table}` WHERE idCustomer='{$this->idCustomer}'";
        $res = $this->db->query($sql);
        if ($res->count) {
            $this->customerData = $res->result_row();
            $this->customerName = $this->customerData['customerName'];
            $this->customerEmail = $this->customerData['customerEmail'];
            $this->customerTerm = $this->customerData['customerTerm'];

            return true;
        }
        return false;


    }

    /**
     * Delete Customer
     */
    public function delCustomer(){
        $invoice = new invoiceModel();
        $hasInvoices =$invoice->getInvoicesByCustomer($this->idCustomer);

        if( $hasInvoices ===false ){
            $sql = "DELETE FROM `{$this->table}` WHERE idCustomer = '$this->idCustomer'";
            $this->db->query($sql);
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get Customer by email
     */
    public function getCustomerByEmail($email){


            $sql = "SELECT * FROM `{$this->table}` WHERE customerEmail = '$email'";
            $res = $this->db->query($sql);
            if($res->count)
                return $res->result_row();
            return false;

    }

    /**
     * @return array array of items
     */
    public function getCustomers()
    {

        $sql = "SELECT * FROM `{$this->table}` ORDER BY customerName ASC";
        $res = $this->db->query($sql);
        $this->customerList = $res->result_array();
        return st_apply_filter('customers_list',$this->customerList);
    }

    /**
     * @return array custom array of customers for typeahead script
     */
    public function getCustomersListForTypeahead(){
        $itemsArray = array();
        foreach ($this->getCustomers() as $customer) {
            $itemsArray[] = array(
                "label" => isset($customer['first_name'])?"{$customer['first_name']} {$customer['last_name']}":$customer['customerName'],
                "title" => isset($customer['first_name'])?"{$customer['first_name']} {$customer['last_name']}":$customer['customerName'] ,
                "customerName" => isset($customer['first_name'])?"{$customer['first_name']} {$customer['last_name']}":$customer['customerName'],
                "customerEmail" => isset($customer['email'])?"{$customer['email']}":$customer['customerEmail'],
                "invoiceBillTo" => isset($customer['first_name'])?"":$customer['customerBill'],
                "invoiceTerm" => isset($customer['first_name'])?"":$customer['customerTerm'],
                "idCustomer" => $customer['idCustomer'],
            );
        }

        return $itemsArray;
    }


}
