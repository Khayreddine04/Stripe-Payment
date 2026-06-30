<?php

class PT_Payment_Gateway
{
    public static function ensureSchema()
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $gatewayTable = $db->db_pr . 'payment_gateways';
        $itemGatewayTable = $db->db_pr . 'item_payment_gateways';
        $paymentsTable = $db->db_pr . 'payments';
        $subscriptionsTable = $db->db_pr . 'subscriptions';

        $db->query("CREATE TABLE IF NOT EXISTS `{$gatewayTable}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `gateway_code` VARCHAR(80) NOT NULL,
            `gateway_type` VARCHAR(40) NOT NULL DEFAULT 'stripe',
            `label` VARCHAR(150) NOT NULL,
            `mode` ENUM('test','live') NOT NULL DEFAULT 'test',
            `public_key` TEXT NULL,
            `secret_key` TEXT NULL,
            `webhook_secret` TEXT NULL,
            `config_json` LONGTEXT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `priority` INT NOT NULL DEFAULT 100,
            `supports_one_time` TINYINT(1) NOT NULL DEFAULT 1,
            `supports_recurring` TINYINT(1) NOT NULL DEFAULT 1,
            `supports_payment_request` TINYINT(1) NOT NULL DEFAULT 1,
            `supported_currencies` TEXT NULL,
            `supported_countries` TEXT NULL,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `gateway_code_unique` (`gateway_code`),
            KEY `gateway_type_index` (`gateway_type`),
            KEY `is_active_index` (`is_active`),
            KEY `is_default_index` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `{$itemGatewayTable}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `item_id` VARCHAR(80) NOT NULL,
            `gateway_profile_id` INT UNSIGNED NOT NULL,
            `payment_type` VARCHAR(40) NOT NULL DEFAULT 'any',
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `priority` INT NOT NULL DEFAULT 100,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_gateway_payment_unique` (`item_id`, `gateway_profile_id`, `payment_type`),
            KEY `item_id_index` (`item_id`),
            KEY `gateway_profile_id_index` (`gateway_profile_id`),
            KEY `payment_type_index` (`payment_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::addColumnIfMissing($db, $paymentsTable, 'gateway_profile_id', "INT UNSIGNED NULL AFTER `processor`");
        self::addColumnIfMissing($db, $paymentsTable, 'gateway_code', "VARCHAR(80) NULL AFTER `gateway_profile_id`");
        self::addColumnIfMissing($db, $paymentsTable, 'gateway_type', "VARCHAR(40) NULL AFTER `gateway_code`");
        self::addColumnIfMissing($db, $paymentsTable, 'gateway_label', "VARCHAR(150) NULL AFTER `gateway_type`");
        self::addIndexIfMissing($db, $paymentsTable, 'pt_payments_gateway_profile_id_index', 'gateway_profile_id');
        self::addIndexIfMissing($db, $paymentsTable, 'pt_payments_gateway_code_index', 'gateway_code');

        self::addColumnIfMissing($db, $subscriptionsTable, 'gateway_profile_id', "INT UNSIGNED NULL AFTER `processor`");
        self::addColumnIfMissing($db, $subscriptionsTable, 'gateway_code', "VARCHAR(80) NULL AFTER `gateway_profile_id`");
        self::addColumnIfMissing($db, $subscriptionsTable, 'gateway_type', "VARCHAR(40) NULL AFTER `gateway_code`");
        self::addColumnIfMissing($db, $subscriptionsTable, 'gateway_label', "VARCHAR(150) NULL AFTER `gateway_type`");
        self::addIndexIfMissing($db, $subscriptionsTable, 'pt_subscriptions_gateway_profile_id_index', 'gateway_profile_id');
        self::addIndexIfMissing($db, $subscriptionsTable, 'pt_subscriptions_gateway_code_index', 'gateway_code');

        self::ensureDefaultStripeGateway();
        self::backfillExistingStripeRecords();

        return true;
    }

    private static function addColumnIfMissing($db, $table, $column, $definition)
    {
        if (!self::tableExists($db, $table) || self::columnExists($db, $table, $column)) {
            return;
        }
        $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private static function addIndexIfMissing($db, $table, $indexName, $column)
    {
        if (!self::tableExists($db, $table) || !self::columnExists($db, $table, $column)) {
            return;
        }

        $safeIndex = addslashes($indexName);
        $res = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$safeIndex}'");
        if ($res && !$res->error && $res->count > 0) {
            return;
        }

        $db->query("ALTER TABLE `{$table}` ADD KEY `{$indexName}` (`{$column}`)");
    }

    private static function tableExists($db, $table)
    {
        $safeTable = addslashes($table);
        $res = $db->query("SHOW TABLES LIKE '{$safeTable}'");
        return $res && !$res->error && $res->count > 0;
    }

    private static function columnExists($db, $table, $column)
    {
        if (!self::tableExists($db, $table)) {
            return false;
        }

        $safeColumn = addslashes($column);
        $res = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$safeColumn}'");
        return $res && !$res->error && $res->count > 0;
    }

    public static function ensureDefaultStripeGateway()
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $gatewayTable = $db->db_pr . 'payment_gateways';
        if (!self::tableExists($db, $gatewayTable)) {
            return false;
        }
        if (!self::tableExists($db, $db->db_pr . 'settings')) {
            return true;
        }

        $exists = $db->query("SELECT id FROM `{$gatewayTable}` WHERE gateway_code = 'stripe_default' LIMIT 1");
        if ($exists && !$exists->error && $exists->count > 0) {
            return true;
        }

        $settings = PT_Settings::instance();
        $mode = $settings->terminal_payment_mode === 'live' ? 'live' : 'test';
        $publicKey = $mode === 'live' ? $settings->live_public_key : $settings->test_public_key;
        $secretKey = $mode === 'live' ? $settings->live_secret_key : $settings->test_secret_key;
        $webhookSecret = $settings->webhook_secret_key;

        $db->query("INSERT INTO `{$gatewayTable}` SET
            gateway_code = 'stripe_default',
            gateway_type = 'stripe',
            label = 'Stripe Default',
            mode = '{$mode}',
            public_key = '" . mysqli_real_escape_string($db->link, (string)$publicKey) . "',
            secret_key = '" . mysqli_real_escape_string($db->link, (string)$secretKey) . "',
            webhook_secret = '" . mysqli_real_escape_string($db->link, (string)$webhookSecret) . "',
            is_active = '1',
            is_default = '1',
            priority = '100'");

        return true;
    }

    public static function backfillExistingStripeRecords()
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $gateway = self::getByCode('stripe_default', false);
        if (!$gateway) {
            return false;
        }

        $paymentsTable = $db->db_pr . 'payments';
        $subscriptionsTable = $db->db_pr . 'subscriptions';
        $profileId = (int)$gateway['id'];

        if (self::tableExists($db, $paymentsTable) && self::columnExists($db, $paymentsTable, 'gateway_profile_id')) {
            $db->query("UPDATE `{$paymentsTable}` SET
                gateway_profile_id = '{$profileId}',
                gateway_code = 'stripe_default',
                gateway_type = 'stripe',
                gateway_label = 'Stripe Default'
                WHERE processor IN ('stripe', 'stripe_direct') AND gateway_profile_id IS NULL");
        }

        if (self::tableExists($db, $subscriptionsTable) && self::columnExists($db, $subscriptionsTable, 'gateway_profile_id')) {
            $db->query("UPDATE `{$subscriptionsTable}` SET
                gateway_profile_id = '{$profileId}',
                gateway_code = 'stripe_default',
                gateway_type = 'stripe',
                gateway_label = 'Stripe Default'
                WHERE processor = 'stripe' AND gateway_profile_id IS NULL");
        }

        return true;
    }

    public static function getAll($activeOnly = false, $type = null)
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return array();
        }

        $table = $db->db_pr . 'payment_gateways';
        if (!self::tableExists($db, $table)) {
            return array();
        }

        $where = array();
        if ($activeOnly) {
            $where[] = "is_active = '1'";
        }
        if (!empty($type)) {
            $where[] = "gateway_type = '" . mysqli_real_escape_string($db->link, $type) . "'";
        }

        $sql = "SELECT * FROM `{$table}`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY is_default DESC, priority ASC, label ASC";

        $res = $db->query($sql);
        return $res && !$res->error ? $res->result_array() : array();
    }

    public static function getById($id, $activeOnly = true)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        return self::find("id = '{$id}'", $activeOnly);
    }

    public static function getByCode($code, $activeOnly = true)
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $code = mysqli_real_escape_string($db->link, trim((string)$code));
        if ($code === '') {
            return false;
        }

        return self::find("gateway_code = '{$code}'", $activeOnly);
    }

    private static function find($where, $activeOnly = true)
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $table = $db->db_pr . 'payment_gateways';
        if (!self::tableExists($db, $table)) {
            return false;
        }

        if ($activeOnly) {
            $where .= " AND is_active = '1'";
        }

        $res = $db->query("SELECT * FROM `{$table}` WHERE {$where} LIMIT 1");
        if ($res && !$res->error && $res->count > 0) {
            return $res->result_row();
        }

        return false;
    }

    public static function getDefault($type = 'stripe')
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $table = $db->db_pr . 'payment_gateways';
        if (!self::tableExists($db, $table)) {
            return false;
        }

        $safeType = mysqli_real_escape_string($db->link, $type);
        $res = $db->query("SELECT * FROM `{$table}`
            WHERE gateway_type = '{$safeType}' AND is_active = '1'
            ORDER BY is_default DESC, priority ASC, id ASC
            LIMIT 1");

        if ($res && !$res->error && $res->count > 0) {
            return $res->result_row();
        }

        return false;
    }

    public static function resolve($itemId = '', $paymentType = 'card', $gatewayCode = '')
    {
        self::ensureDefaultStripeGateway();

        $db = new PT_Db();
        if (!$db->is_connected) {
            return self::getDefault('stripe');
        }

        $itemId = trim((string)$itemId);
        $paymentType = trim((string)$paymentType);
        $itemGatewayTable = $db->db_pr . 'item_payment_gateways';
        $gatewayTable = $db->db_pr . 'payment_gateways';

        if ($itemId !== '' && self::tableExists($db, $itemGatewayTable) && self::tableExists($db, $gatewayTable)) {
            $safeItemId = mysqli_real_escape_string($db->link, $itemId);
            $safePaymentType = mysqli_real_escape_string($db->link, $paymentType ?: 'any');
            $res = $db->query("SELECT g.*
                FROM `{$itemGatewayTable}` ig
                INNER JOIN `{$gatewayTable}` g ON g.id = ig.gateway_profile_id
                WHERE ig.item_id = '{$safeItemId}'
                    AND ig.payment_type IN ('{$safePaymentType}', 'any')
                    AND g.gateway_type = 'stripe'
                    AND g.is_active = '1'
                ORDER BY ig.payment_type = '{$safePaymentType}' DESC, ig.is_default DESC, ig.priority ASC, g.priority ASC
                LIMIT 1");

            if ($res && !$res->error && $res->count > 0) {
                return $res->result_row();
            }
        }

        if ($itemId === '' && !empty($gatewayCode)) {
            $gateway = self::getByCode($gatewayCode);
            if ($gateway && $gateway['gateway_type'] === 'stripe') {
                return $gateway;
            }
        }

        return self::getDefault('stripe');
    }

    public static function getSelectedForItem($itemId)
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return 0;
        }

        $table = $db->db_pr . 'item_payment_gateways';
        if (!self::tableExists($db, $table)) {
            return 0;
        }

        $safeItemId = mysqli_real_escape_string($db->link, trim((string)$itemId));
        $res = $db->query("SELECT gateway_profile_id FROM `{$table}`
            WHERE item_id = '{$safeItemId}' AND payment_type = 'any'
            ORDER BY is_default DESC, priority ASC
            LIMIT 1");

        return $res && !$res->error && $res->count > 0 ? (int)$res->result_row('gateway_profile_id') : 0;
    }

    public static function setItemGateway($itemId, $gatewayId)
    {
        $db = new PT_Db();
        if (!$db->is_connected) {
            return false;
        }

        $table = $db->db_pr . 'item_payment_gateways';
        if (!self::tableExists($db, $table)) {
            self::ensureSchema();
        }

        $safeItemId = mysqli_real_escape_string($db->link, trim((string)$itemId));
        if ($safeItemId === '') {
            return false;
        }

        $db->query("DELETE FROM `{$table}` WHERE item_id = '{$safeItemId}' AND payment_type = 'any'");

        $gatewayId = (int)$gatewayId;
        if ($gatewayId > 0) {
            $db->query("INSERT INTO `{$table}` SET
                item_id = '{$safeItemId}',
                gateway_profile_id = '{$gatewayId}',
                payment_type = 'any',
                is_default = '1',
                priority = '100'");
        }

        return true;
    }

    public static function fieldsFromGateway($gateway)
    {
        if (empty($gateway) || !is_array($gateway)) {
            return array(
                'gateway_profile_id' => null,
                'gateway_code' => '',
                'gateway_type' => '',
                'gateway_label' => ''
            );
        }

        return array(
            'gateway_profile_id' => (int)$gateway['id'],
            'gateway_code' => $gateway['gateway_code'],
            'gateway_type' => $gateway['gateway_type'],
            'gateway_label' => $gateway['label']
        );
    }

    public static function sqlAssignments(array $data, $db)
    {
        $parts = array();
        foreach (array('gateway_profile_id', 'gateway_code', 'gateway_type', 'gateway_label') as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if ($field === 'gateway_profile_id') {
                $value = empty($data[$field]) ? 'NULL' : "'" . (int)$data[$field] . "'";
            } else {
                $value = "'" . mysqli_real_escape_string($db->link, (string)$data[$field]) . "'";
            }
            $parts[] = "`{$field}` = {$value}";
        }

        return empty($parts) ? '' : ",\n            " . implode(",\n            ", $parts);
    }
}
