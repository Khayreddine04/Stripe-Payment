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

class subscriptionModel
{
    /**
     * @var Payment ID
     */
    public $idSubscription;

    private $table;

    private PT_Db $db;

    /**
     * @var array payment array data
     */
    public $subscriptionData = array();

    function __construct()
    {
        global $db_pr;
        $this->table = $db_pr . "subscriptions";
        $this->db = new PT_Db();
    }

    /**
     * @return array|bool|null
     */
    public function getSubscription()
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE idSubscription = '{$this->idSubscription}'";
        $res = $this->db->query($sql);
        if ($res->count)
            return $this->subscriptionData = $res->result_row();
        return false;
    }

    public function getSubscriptionByTrn($idTransaction)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE idTransaction = '{$idTransaction}'";
        $res = $this->db->query($sql);
        if ($res->count)
            return $this->subscriptionData = $res->result_row();
        return false;
    }
    /**
     * @param $id set ID for item
     */
    public function setID($id)
    {
        $this->idSubscription = $id;
    }

    /**
     * @param $data
     * @return bool
     */
    // Replace the existing addSubscription method with this updated version
    public function addSubscription($data)
    {
        // Add debugging
        error_log("=== ADDSUBSCRIPTION DEBUG ===");

        // Ensure clickid and source are captured from POST if not already set
        if (empty($data['clickid']) && !empty($_POST['clickid'])) {
            $data['clickid'] = $_POST['clickid'];
        }
        if (empty($data['source']) && !empty($_POST['source'])) {
            $data['source'] = $_POST['source'];
        }

        error_log("Received clickid: " . ($data['clickid'] ?? 'NOT SET'));
        error_log("Received source: " . ($data['source'] ?? 'NOT SET'));
        error_log("DB Link type: " . get_class($this->db));
        error_log("=== END DEBUG ===");

        $data['paypalStatus'] = empty($data['paypalStatus']) ? "paid" : $data['paypalStatus'];

        // Get payment method from POST if not provided
        if (empty($data['payment_method']) && !empty($_POST['payment_method'])) {
            $data['payment_method'] = $_POST['payment_method'];
        }

        // Get upfront fee from item if not provided
        if (!isset($data['upfront_fee']) && !empty($data['idItem'])) {
            global $db_pr; // Get the table prefix
            $itemSql = "SELECT itemTrialUpfront, itemTrialDays FROM `vcp_pt_items` 
                   WHERE idItem = '" . mysqli_real_escape_string($this->db->link, $data['idItem']) . "'";
            $itemRes = $this->db->query($itemSql);

            if ($itemRes && $itemRes->count > 0) {
                $itemData = $itemRes->result_row();
                $data['upfront_fee'] = (float)($itemData['itemTrialUpfront'] ?? 0);
                $trialDays = (int)($itemData['itemTrialDays'] ?? 0);

                // If there's an upfront fee and trial days, mark it as paid if it's a new subscription
                if ($data['upfront_fee'] > 0 && $trialDays > 0) {
                    $data['upfront_fee_paid'] = 1;
                    $data['upfront_fee_paid_date'] = date('Y-m-d H:i:s');
                }

                error_log("Found itemTrialUpfront: " . $data['upfront_fee'] . ", trial days: " . $trialDays);
            } else {
                $data['upfront_fee'] = 0;
                error_log("Item not found or no itemTrialUpfront set, defaulting to 0");
            }
        }


        // Escape all data to prevent SQL injection
        $escapedData = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $escapedData[$key] = mysqli_real_escape_string($this->db->link, $value);
            } else {
                $escapedData[$key] = $value;
            }
        }

        // Set default values for required fields
        $sql = "INSERT INTO `{$this->table}` SET
            `status` = 'active',
            `paypalStatus` = 'paid',
            `customerName` = '" . $escapedData['customerName'] . "',
            `customerEmail` = '" . $escapedData['customerEmail'] . "',
            `amount` = '" . $escapedData['amount'] . "',
            `upfront_fee` = '" . ($data['upfront_fee'] ?? '0.00') . "',
            `tax_amount` = '0.00',
            `tax_rate` = '0.00',
            `tax_abbreviation` = '',
            `currency` = '" . (!empty($data['currency']) ? $data['currency'] : 'USD') . "',
            `currency_symbol` = '" . (!empty($data['currency_symbol']) ? $data['currency_symbol'] : '$') . "',
            `currency_position` = 'before',
            `idTransaction` = '" . $escapedData['idTransaction'] . "',
            `idInvoice` = '0',
            `clickid` = '" . ($escapedData['clickid'] ?? '') . "',
            `source` = '" . ($escapedData['source'] ?? '') . "',
            `payment_method` = '" . ($escapedData['payment_method'] ?? 'card') . "',
            `idItem` = '" . $escapedData['idItem'] . "',
            `dateCreated` = '" . NOW_DATE_TIME . "',
            `comments` = '" . ($escapedData['comments'] ?? '') . "',
            `processor` = 'stripe',
            `period` = 'month',
            `period_count` = '1',
            `trial_days` = '0',
            `billingAddress1` = '',
            `billingAddress2` = '',
            `billingCity` = '',
            `billingCountry` = '',
            `billingState` = '',
            `billingZip` = '',
            `shippingAddress1` = '',
            `shippingAddress2` = '',
            `shippingCity` = '',
            `shippingCountry` = '',
            `shippingState` = '',
            `shippingZip` = '',
            `paymentsCount` = '0',
            `stripeCustomer` = '',
            `imported` = 'n'";

        if (class_exists('PT_Payment_Gateway')) {
            $sql .= PT_Payment_Gateway::sqlAssignments($data, $this->db);
        }

        if (!empty($data['idItem'])) {
            $item_amount = itemModel::getItemAmount($data['idItem']);
        } else {
            $item_amount = $data['amount'];
        }
        // Set or_amount based on item amount
        if (!empty($data['amount'])) {
            $sql .= ", or_amount = '" . mysqli_real_escape_string($this->db->link, $data['amount']) . "'";
        } else {
            $sql .= ", or_amount = '0.00'";
        }

        $res = $this->db->query($sql);
        if ($res->count < 1) {
            return false;
        }

        $this->setID($res->insert_id);
        st_do_action("after_save_subscription", $this->idSubscription, $data);

        return $this->idSubscription;
    }

    /**
     * Return readable billing address
     * @return bool|string
     */
    public function getFormattedAddress()
    {
        if (!empty($this->subscriptionData['billingAddress1']) && !empty($this->subscriptionData['billingCity']) && !empty($this->subscriptionData['billingCountry'])) {
            return "{$this->subscriptionData['billingAddress1']} {$this->subscriptionData['billingAddress2']}, {$this->subscriptionData['billingCity']}, {$this->subscriptionData['billingState']} {$this->subscriptionData['billingZip']}, {$this->subscriptionData['billingState']}";
        } else {
            return false;
        }
    }

    /**
     * Return readable shipping address
     * @return bool|string
     */
    public function getFormattedShippingAddress()
    {
        if (!empty($this->subscriptionData['shippingAddress1']) && !empty($this->subscriptionData['shippingCity']) && !empty($this->subscriptionData['shippingCountry'])) {
            return "{$this->subscriptionData['shippingAddress1']} {$this->subscriptionData['shippingAddress2']}, {$this->subscriptionData['shippingCity']}, {$this->subscriptionData['shippingState']} {$this->subscriptionData['shippingZip']}, {$this->subscriptionData['shippingState']}";
        } else {
            return false;
        }
    }

    /**
     * Return subscription frequency text
     * @return string
     */
    public function getFrequencyText()
    {
        $str = "Every ";
        $str .= $this->subscriptionData['period_count'];
        $str .= " " . $this->subscriptionData['period'];
        $str .= " starting " . PT_Core::_getDateFormat($this->subscriptionData['dateCreated']);
        return $str;
    }

    /**
     * @param $idItem
     * @return array|bool
     */
    public function  getSubscriptionsByItem($idItem)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE idItem='{$idItem}'";
        $res = $this->db->query($sql);
        if ($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * Cancel subscription
     * @param $id_transaction  - transaction ID
     * @return bool
     */
    public function cancelSubscription($id_transaction)
    {

        $data = $this->getSubscriptionByTrn($id_transaction);

        $sql = "UPDATE `{$this->table}` SET
                `status` = 'canceled',
                `dateCancelation` = '" . NOW_DATE_TIME . "'
                WHERE idTransaction = '{$id_transaction}'";
        $res = $this->db->query($sql);
        if ($res->count) {
            $user = new PT_User();
            if ($user->logon)
                st_do_action('add_user_log', "Cancelled subscription {$id_transaction} for customer {$data['customerName']} {$data['customerEmail']}");
            return true;
        }
        return false;
    }

    /**
     * Subscription suspended
     * @param $id_transaction  - transaction ID
     * @return bool
     */
    public function suspendSubscription($id_transaction)
    {

        $data = $this->getSubscriptionByTrn($id_transaction);

        $sql = "UPDATE `{$this->table}` SET
                `status` = 'payment_failed',
                `dateCancelation` = '" . NOW_DATE_TIME . "'
                WHERE idTransaction = '{$id_transaction}'";
        $res = $this->db->query($sql);
        if ($res->count) {
            $user = new PT_User();
            if ($user->logon)
                st_do_action('add_user_log', "Payment failed for subscription {$id_transaction}, customer {$data['customerName']} {$data['customerEmail']}");
            return true;
        }
        return false;
    }

    /**
     * Make subscription as active
     * @param $idSubscription
     * @param $trnId
     * @return bool
     */
    public function activateSubscription($id_transaction)
    {
        $data = $this->getSubscriptionByTrn($id_transaction);

        $sql = "UPDATE `{$this->table}` SET
                `status` = 'active',
                `dateCancelation` = '" . NOW_DATE_TIME . "'
                WHERE idTransaction = '{$id_transaction}'";
        $res = $this->db->query($sql);
        if ($res->count) {
            $user = new PT_User();
            if ($user->logon)
                st_do_action('add_user_log', "Activate subscription {$id_transaction}, customer {$data['customerName']} {$data['customerEmail']}");
            return true;
        }
        return false;
    }

    /**
     * Make subscription as active
     * @param $idSubscription
     * @param $trnId
     * @return bool
     */
    public function activatePaypalSubscription($idSubscription, $trnId)
    {
        $sql = "UPDATE `{$this->table}` SET
                `status` = 'active',
                `idTransaction` = '{$trnId}',
                `paypalStatus` = 'paid',
                `dateCreated` = '" . NOW_DATE_TIME . "'
                WHERE idSubscription = '{$idSubscription}'";
        $res = $this->db->query($sql);
        if ($res->count)
            return true;
        return false;
    }

    public function getTotalVolume($dateFrom = null, $dateTo = null)
    {

        $where = !empty($dateFrom) && !empty($dateTo) ? "AND dateCreated BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59'" : "";
        $sql = "SELECT SUM(amount) as total FROM `{$this->table}` WHERE ((paypalStatus = 'paid' AND processor='paypal') OR processor='stripe') AND status = 'active' $where";
        $res = $this->db->query($sql)->result_row("total");

        return $res;
    }

    public function getTotalVolumeByDate($date)
    {


        $sql = "SELECT SUM(amount) as total FROM `{$this->table}` WHERE ((paypalStatus = 'paid' AND processor='paypal') OR processor='stripe') AND status = 'active' AND dateCreated LIKE '%{$date}%'";
        $res = $this->db->query($sql)->result_row("total");

        return $res;
    }

    public function getSubscriptionsCount($date = null)
    {
        $where = !empty($date)  ? "AND  dateCreated LIKE '%{$date}%'" : "";
        $sql = "SELECT count(amount) as total FROM `{$this->table}` WHERE ((paypalStatus = 'paid' AND processor='paypal') OR processor='stripe') AND status = 'active' $where";
        $res = $this->db->query($sql)->result_row("total");

        return $res;
    }


    public function getCancelSubscriptionUrl($id = null)
    {
        if (!empty($id)) {
            $this->setID($id);
            $this->getSubscription();
        }
        $settings = PT_Settings::instance();

        if ($this->subscriptionData['processor'] == 'paypal') {
            return false;
        } else {
            return $settings->site_url . "/cancel.php?pt_subscription_id=" . $this->subscriptionData['idTransaction'];
        }
    }

    public function getSubscriptionStartDateText()
    {

        if ($this->subscriptionData['trial_days'] == 0)
            return PT_Core::_getDateFormat($this->subscriptionData['dateCreated']);

        return PT_Core::_getDateFormat(date("Y-m-d", strtotime("{$this->subscriptionData['dateCreated']} +{$this->subscriptionData['trial_days']} days"))) .
            " ( Trial period {$this->subscriptionData['trial_days']} days )";
    }

    public function getPayments()
    {
        $payment = new paymentModel();

        if (!empty($this->subscriptionData['processor'] == 'paypal')) {
            return $payment->getSubscriptionPayments($this->subscriptionData['idTransaction']);
        }

        if (!empty($this->subscriptionData['stripeCustomer'])) {

            return $payment->getSubscriptionPayments($this->subscriptionData['idTransaction'], $this->subscriptionData['stripeCustomer']);
        }

        return array();
    }

    public function getSubscriptionByInvoice($idInvoice)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE idInvoice='{$idInvoice}'";
        $res = $this->db->query($sql);
        if ($res->count)
            return $res->result_array();
        return false;
    }

    /**
     * Get the upfront fee payment for this subscription
     * @return array|bool
     */
    /**
     * Update the upfront fee payment status for a subscription
     * @param string $paymentId The payment ID
     * @param float $amount The payment amount
     * @param string $status The payment status (paid, pending, failed)
     * @return bool True on success, false on failure
     */
    /**
     * Update the upfront fee payment status for a subscription
     * @param string $paymentId The payment ID
     * @param float $amount The payment amount
     * @param string $status The payment status (paid, pending, failed)
     * @return bool True on success, false on failure
     */
    public function updateUpfrontFeePayment($paymentId, $amount, $status = 'paid')
    {
        // Check if this is an update from the endpoint
        $isEndpointUpdate = (strpos($paymentId, 'endpoint_update_') === 0);
        
        // Ensure we have the subscription ID
        if (empty($this->idSubscription) || empty($this->subscriptionData)) {
            if (!$this->getSubscription()) {
                error_log("Failed to load subscription data for ID: " . $this->idSubscription);
                return false;
            }
        }
        $subscriptionId = $this->idSubscription;
        error_log("Updating upfront fee payment for subscription: " . $subscriptionId);

        // For endpoint updates, just update the subscription data
        if ($isEndpointUpdate) {
            $updateData = [
                'upfront_fee' => $amount,
                'upfront_fee_paid' => ($status === 'paid' ? 1 : 0),
                'upfront_fee_payment_id' => $paymentId,
                'upfront_fee_paid_date' => ($status === 'paid' ? date('Y-m-d H:i:s') : null)
            ];
            return $this->saveSubscriptionData($updateData);
        }

        global $db_pr;

        // First, ensure the payment record exists
        $paymentTable = $db_pr . "payments";
        $paymentSql = "SELECT idPayment, idInvoice, upfront_subscription_id FROM `{$paymentTable}` 
                   WHERE (idTransaction = '" . mysqli_real_escape_string($this->db->link, $paymentId) . "' 
                   OR idPayment = '" . mysqli_real_escape_string($this->db->link, $paymentId) . "')
                   LIMIT 1";
        $paymentRes = $this->db->query($paymentSql);

        // If payment doesn't exist, create it
        if (!$paymentRes || $paymentRes->count == 0) {
            // Get currency information from subscription data or use defaults
            $currency = !empty($this->subscriptionData['currency']) ? $this->subscriptionData['currency'] : 'USD';
            $currency_symbol = !empty($this->subscriptionData['currency_symbol']) ? $this->subscriptionData['currency_symbol'] : '$';

            $createPaymentSql = "INSERT INTO `{$paymentTable}` SET
                `paypalStatus` = '" . mysqli_real_escape_string($this->db->link, $status) . "',
                `customerName` = '" . mysqli_real_escape_string($this->db->link, $this->subscriptionData['customerName']) . "',
                `customerEmail` = '" . mysqli_real_escape_string($this->db->link, $this->subscriptionData['customerEmail']) . "',
                `amount` = '" . floatval($amount) . "',
                `or_amount` = '" . floatval($amount) . "',
                `service_fee` = '0.00',
                `tax_amount` = '0.00',
                `tax_rate` = '0.00',
                `tax_abbreviation` = '',
                `currency` = '" . $currency . "',
                `currency_symbol` = '" . $currency_symbol . "',
                `currency_position` = 'before',
                `idTransaction` = '" . mysqli_real_escape_string($this->db->link, $paymentId) . "',
                `idInvoice` = '0',
                `clickid` = '" . mysqli_real_escape_string($this->db->link, $this->subscriptionData['clickid'] ?? '') . "',
                `source` = '" . mysqli_real_escape_string($this->db->link, $this->subscriptionData['source'] ?? '') . "',
                `idItem` = '" . mysqli_real_escape_string($this->db->link, $this->subscriptionData['idItem']) . "',
                `dateCreated` = NOW(),
                `comments` = 'Upfront fee payment',
                `processor` = 'stripe',
                `is_upfront_fee` = '1',
                `upfront_subscription_id` = '" . mysqli_real_escape_string($this->db->link, $subscriptionId) . "',
                `imported` = 'n'";

            if (class_exists('PT_Payment_Gateway')) {
                $createPaymentSql .= PT_Payment_Gateway::sqlAssignments($this->subscriptionData, $this->db);
            }

            $createRes = $this->db->query($createPaymentSql);
            if (!$createRes) {
                error_log('Failed to create upfront fee payment record');
                return false;
            }
        }

        // Update the payment record to ensure it's properly linked
        if ($paymentRes && $paymentRes->count > 0) {
            $paymentData = $paymentRes->result_row();
            $updatePaymentSql = "UPDATE `{$paymentTable}` SET 
                `upfront_subscription_id` = '" . mysqli_real_escape_string($this->db->link, $subscriptionId) . "',
                `stripeCustomer` = '" . mysqli_real_escape_string($this->db->link, $subscriptionId) . "',
                `is_upfront_fee` = '1'
                WHERE idPayment = '" . mysqli_real_escape_string($this->db->link, $paymentData['idPayment']) . "'";
            $this->db->query($updatePaymentSql);
            error_log("Updated payment record " . $paymentData['idPayment'] . " with subscription ID: " . $subscriptionId);
        }

        // Now update the subscription
        $sql = "UPDATE `{$this->table}` SET 
            `upfront_fee_paid` = " . ($status === 'paid' ? 1 : 0) . ",
            `upfront_fee_payment_id` = '" . mysqli_real_escape_string($this->db->link, $paymentId) . "',
            `upfront_fee_paid_date` = " . ($status === 'paid' ? "NOW()" : 'NULL') . "
            WHERE idSubscription = '" . $subscriptionId . "'";

        $res = $this->db->query($sql);

        if ($res === false) {
            error_log('Error updating upfront fee payment status: ' . mysqli_error($this->db->link));
            return false;
        }

        // Update local data
        $this->subscriptionData['upfront_fee_paid'] = ($status === 'paid' ? 1 : 0);
        $this->subscriptionData['upfront_fee_payment_id'] = $paymentId;
        $this->subscriptionData['upfront_fee_paid_date'] = ($status === 'paid' ? date('Y-m-d H:i:s') : null);

        return true;
    }

    /**
     * Get the upfront fee payment for this subscription
     * @return array|bool
     */
    /**
     * Get the upfront fee payment for this subscription - REALISTIC VERSION
     * @return array|bool
     */
    public function getUpfrontFeePayment()
    {
        if (empty($this->subscriptionData['idSubscription'])) {
            return false;
        }

        global $db_pr;

        $subscriptionId = $this->subscriptionData['idSubscription'];
        $upfrontFee = $this->subscriptionData['upfront_fee'] ?? 0;

        error_log("=== getUpfrontFeePayment REALISTIC ===");
        error_log("Subscription ID: " . $subscriptionId);
        error_log("Upfront fee amount: " . $upfrontFee);

        // If no upfront fee, return false immediately
        if ($upfrontFee <= 0) {
            error_log("No upfront fee configured for this subscription");
            return false;
        }

        // Strategy 1: Look for payments specifically marked as upfront fees for this subscription
        $sql = "SELECT p.* FROM `{$db_pr}payments` p 
            WHERE p.upfront_subscription_id = '" . mysqli_real_escape_string($this->db->link, $subscriptionId) . "'
            AND p.is_upfront_fee = '1'
            AND p.paypalStatus IN ('paid', 'pending')
            ORDER BY p.dateCreated DESC 
            LIMIT 1";

        error_log("Strategy 1 SQL: " . $sql);
        $res = $this->db->query($sql);

        if ($res && $res->count > 0) {
            $result = $res->result_row();
            error_log("FOUND upfront fee payment via Strategy 1");
            return $result;
        }

        // Strategy 2: Look for any payment matching the upfront fee amount for this customer/item
        $customerEmail = $this->subscriptionData['customerEmail'];
        $idItem = $this->subscriptionData['idItem'];
        $dateCreated = $this->subscriptionData['dateCreated'];

        $sql2 = "SELECT p.* FROM `{$db_pr}payments` p 
             WHERE p.customerEmail = '" . mysqli_real_escape_string($this->db->link, $customerEmail) . "'
             AND p.idItem = '" . mysqli_real_escape_string($this->db->link, $idItem) . "'
             AND ABS(p.amount - " . floatval($upfrontFee) . ") < 0.01
             AND p.paypalStatus IN ('paid', 'pending')";

        if (!empty($dateCreated)) {
            $sql2 .= " AND p.dateCreated BETWEEN 
                  DATE_SUB('" . mysqli_real_escape_string($this->db->link, $dateCreated) . "', INTERVAL 24 HOUR) 
                  AND DATE_ADD('" . mysqli_real_escape_string($this->db->link, $dateCreated) . "', INTERVAL 24 HOUR)";
        }

        $sql2 .= " ORDER BY p.dateCreated DESC LIMIT 1";

        error_log("Strategy 2 SQL: " . $sql2);
        $res2 = $this->db->query($sql2);

        if ($res2 && $res2->count > 0) {
            $result = $res2->result_row();
            error_log("FOUND payment via Strategy 2");
            return $result;
        }

        // Strategy 3: If no payment found, check if we should create a synthetic payment record
        if ($this->subscriptionData['upfront_fee_paid'] == 1) {
            error_log("Subscription marked as upfront fee paid but no payment record found");
            // Return a synthetic payment array based on subscription data
            return $this->createSyntheticPaymentData();
        }

        error_log("NO upfront fee payment found for subscription ID: " . $subscriptionId);
        return false;
    }

    /**
     * Create synthetic payment data when payment record is missing but subscription is marked as paid
     * @return array
     */
    private function createSyntheticPaymentData()
    {
        return [
            'idPayment' => 0,
            'paypalStatus' => 'paid',
            'customerName' => $this->subscriptionData['customerName'],
            'customerEmail' => $this->subscriptionData['customerEmail'],
            'amount' => $this->subscriptionData['upfront_fee'],
            'or_amount' => $this->subscriptionData['upfront_fee'],
            'currency' => $this->subscriptionData['currency'],
            'idTransaction' => 'synthetic_upfront_' . $this->subscriptionData['idTransaction'],
            'idItem' => $this->subscriptionData['idItem'],
            'dateCreated' => $this->subscriptionData['dateCreated'],
            'processor' => $this->subscriptionData['processor'],
            'is_upfront_fee' => 1,
            'upfront_subscription_id' => $this->subscriptionData['idSubscription'],
            'synthetic' => true // Flag to indicate this is not a real database record
        ];
    }
    
    /**
     * Save subscription data to the database
     * @param array $data The data to save
     * @return bool True on success, false on failure
     */
    public function saveSubscriptionData($data) {
        if (empty($this->idSubscription)) {
            return false;
        }

        $updates = array();
        $allowedFields = array(
            'amount', 'currency', 'currency_symbol', 'period', 'upfront_fee',
            'upfront_fee_paid', 'upfront_fee_payment_id', 'upfront_fee_paid_date'
        );

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                if (is_numeric($value)) {
                    $updates[] = "`$key` = " . floatval($value);
                } else {
                    $updates[] = "`$key` = '" . mysqli_real_escape_string($this->db->link, $value) . "'";
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $updates) . 
               " WHERE idSubscription = '" . $this->idSubscription . "'";
        
        $result = $this->db->query($sql);
        
        if ($result) {
            // Update local data
            $this->subscriptionData = array_merge($this->subscriptionData, $data);
            return true;
        }
        
        return false;
    }
}
