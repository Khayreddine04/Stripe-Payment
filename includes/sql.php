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

$query = "CREATE TABLE IF NOT EXISTS `{$db_pr}users` (
  `idUser` int(11) NOT NULL AUTO_INCREMENT,
  `dateCraeted` datetime NOT NULL,
  `idRole` int(11) NOT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `tmuser` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL DEFAULT '',
  `tmpass` varchar(255) NOT NULL DEFAULT '',
  `tmhash` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`idUser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

";

// Execute users table creation
$res = $c->query($query);

// Log the result of users table creation
if (!empty($res->error)) {
    error_log('[' . date('Y-m-d H:i:s') . '] Failed to create users table: ' . $res->error . '\nQuery: ' . $query, 3, __DIR__ . '/install_errors.log');
} else {
    error_log('[' . date('Y-m-d H:i:s') . '] Successfully created users table', 3, __DIR__ . '/install_errors.log');
}

// alter users table
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}users'");
    $query = "INSERT INTO `{$db_pr}users` (`idUser`, `dateCraeted`, `idRole`, `username`, `password`, `name`, `tmpass`) 
              VALUES (1, NOW(), 0, '{$admin_username}', MD5('{$admin_password}'), 'Administrator', '')
              ON DUPLICATE KEY UPDATE 
                  `username` = VALUES(`username`), 
                  `password` = VALUES(`password`),
                  `name` = 'Administrator',
                  `dateCraeted` = NOW(),
                  `idRole` = 0,
                  `tmpass` = '';";

    try {
        $res = $c->query($query);
        
        if (empty($res->error)) {
            $affectedRows = $c->link->affected_rows;
            if ($affectedRows > 0) {
                $message = ($affectedRows === 1) ? "Added administrator account" : "Updated existing administrator account";
                $c->addSuccess($message);
                error_log('[' . date('Y-m-d H:i:s') . '] ' . $message, 3, __DIR__ . '/install_errors.log');
            } else {
                $c->addSuccess("Verified administrator account");
                error_log('[' . date('Y-m-d H:i:s') . '] Verified existing administrator account', 3, __DIR__ . '/install_errors.log');
            }
        } else {
            throw new Exception($res->error);
        }
    } catch (Exception $e) {
        $errorMsg = "Failed to create/update administrator account: " . $e->getMessage();
        error_log('[' . date('Y-m-d H:i:s') . '] ' . $errorMsg . '\nQuery: ' . $query, 3, __DIR__ . '/install_errors.log');
        $c->addError($errorMsg);
        $BWContinue = false;
    }

} else {
    $c->addError("Can't create {$db_pr}users!");
    $BWContinue = false;
}


//  alter invoices table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}invoices` (
  `idInvoice` int(11) NOT NULL AUTO_INCREMENT,
  `idCustomer` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `paymentDate` datetime DEFAULT NULL,
  `invoiceType` enum('single','recurring') NULL DEFAULT 'single',
  `invoiceStatus` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `customerName` varchar(150) NOT NULL,
  `customerEmail` varchar(150) NOT NULL,
  `invoiceNumber` varchar(100) NOT NULL,
  `orderNumber` varchar(100) NOT NULL,
  `invoiceDate` date NOT NULL,
  `invoiceBillTo` text,
  `invoiceTerm` varchar(50) NOT NULL,
  `invoiceDueDate` date NOT NULL,
  `invoiceLateFee` int(11) NOT NULL DEFAULT '0',
  `invoiceNotes` text,
  `invoiceTerms` text,
  `invoiceSubTotal` float NOT NULL DEFAULT '0',
  `invoiceTotal` float NOT NULL DEFAULT '0',
  `invoiceTax` float NOT NULL DEFAULT '0',
  `invoiceCurrency` varchar(5) NOT NULL,
  `invoiceCurrencyPosition` ENUM(  'before',  'after' ) NOT NULL DEFAULT  'before',
  `invoiceCurrencySymbol` VARCHAR( 20 ) NOT NULL,
  PRIMARY KEY (`idInvoice`),
  UNIQUE KEY `invoiceNumber` (`invoiceNumber`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}invoices'");

} else {
    $c->addError("Can't create {$db_pr}invoices!");
    $BWContinue = false;
}


//  alter invoice_items table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}invoice_items` (
  `idItem` int(11) NOT NULL AUTO_INCREMENT,
  `idInvoice` int(11) NOT NULL,
  `itemName` varchar(200) NOT NULL,
  `itemDescription` text,
  `itemQty` int(11) NOT NULL,
  `itemRate` float NOT NULL,
  `itemDiscount` int(11) DEFAULT 0,
  `itemTax` int(11) NOT NULL,
  `itemTotal` float NOT NULL,
  `itemItem` int(11) DEFAULT 0,
  PRIMARY KEY (`idItem`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}invoice_items'");

} else {
    $c->addError("Can't create {$db_pr}invoice_items!");
    $BWContinue = false;
}

//  alter invoice history table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}invoice_history` (
  `idHistory` int(11) NOT NULL AUTO_INCREMENT,
  `idInvoice` int(11) NOT NULL,
  `action` set('create','update','send','paid','view') NOT NULL,
  `text` varchar(200) NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY (`idHistory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}invoice_history'");

} else {
    $c->addError("Can't create {$db_pr}invoice_history!");
    $BWContinue = false;
}

//  alter items table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}items` (
  `idItem` int(11) NOT NULL AUTO_INCREMENT,
  `itemName` varchar(200) NOT NULL,
  `itemType` enum('service','product') NOT NULL DEFAULT 'product',
  `itemStatus` ENUM('y','n') NOT NULL DEFAULT 'y',
  `itemAmount` decimal(10,2) NOT NULL,
  `itemFrequency` varchar(100) NOT NULL,
  `itemTrial` ENUM(  'y',  'n' ) NOT NULL DEFAULT  'n',
  `itemTrialDays` INT DEFAULT 0,
   `itemBillingMax`  INT NOT NULL DEFAULT '0',
  `itemBillingMin`  INT NOT NULL DEFAULT '0',
  `itemPlan` ENUM(  'y',  'n' ) NOT NULL DEFAULT  'n',
  `allowOverride` ENUM('y','n') NOT NULL DEFAULT 'n',
  `taxExempt` ENUM('y','n') NOT NULL DEFAULT 'n',
  `itemDescription` text ,
  `itemDesign` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`idItem`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}items'");

} else {
    $c->addError("Can't create {$db_pr}items!");
    $BWContinue = false;
}

//  alter payments table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}payments` (
  `idPayment` int(11) NOT NULL AUTO_INCREMENT,
  `paypalStatus` enum('paid','pending','refunded','partial_refund') NULL DEFAULT NULL,
  `customerName` varchar(200) NOT NULL,
  `customerEmail` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `or_amount` decimal(10,2) NOT NULL,
  `service_fee` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `tax_rate` decimal(10,2) NOT NULL DEFAULT 0,
  `tax_abbreviation` varchar(255) NOT NULL DEFAULT '',
  `currency` varchar(5) NOT NULL,
  `currency_symbol` VARCHAR( 20 ) NOT NULL,
  `currency_position` ENUM(  'before',  'after' ) NOT NULL,
  `idTransaction` varchar(200) NOT NULL,
  `idInvoice` int(11) NOT NULL,
  `idItem` varchar(20) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `comments` text,
  `processor` enum('paypal','stripe','stripe_direct') NOT NULL DEFAULT 'stripe',
  `gateway_profile_id` INT UNSIGNED NULL,
  `gateway_code` VARCHAR(80) NULL,
  `gateway_type` VARCHAR(40) NULL,
  `gateway_label` VARCHAR(150) NULL,
  `billingAddress1` varchar(200) NOT NULL,
  `billingAddress2` varchar(200) NOT NULL,
  `billingCity` varchar(150) NOT NULL,
  `billingCountry` varchar(50) NOT NULL,
  `billingState` varchar(50) NOT NULL,
  `billingZip` varchar(15) NOT NULL,
  `shippingAddress1` VARCHAR( 200 ) NOT NULL ,
  `shippingAddress2` VARCHAR( 200 ) NOT NULL ,
  `shippingCity` VARCHAR( 150 ) NOT NULL ,
  `shippingCountry` VARCHAR( 50 ) NOT NULL ,
  `shippingState` VARCHAR( 50 ) NOT NULL ,
  `shippingZip` VARCHAR( 50 ) NOT NULL,
  
   `stripeCustomer` varchar(200)  NOT NULL,
   `stripeCharge` varchar(200)  NOT NULL,
   `stripeSubscription` varchar(200)  NOT NULL ,
   `paypalSubscription` varchar(200)  NOT NULL ,
   `imported` set('y','n')  NOT NULL DEFAULT 'n',
   
   `idRefund` VARCHAR(200) NOT NULL DEFAULT '',
   `refundDate` DATETIME NULL DEFAULT NULL,
  
  PRIMARY KEY (`idPayment`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);


if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}payments'");

} else {
    $c->addError("Can't create {$db_pr}payments!");
    $BWContinue = false;
}


//  alter subscriptions table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}subscriptions` (
  `idSubscription` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('active','pending','canceled') NOT NULL DEFAULT 'active',
  `paypalStatus` enum('paid','pending') NOT NULL DEFAULT 'paid',
  `customerName` varchar(200) NOT NULL,
  `customerEmail` varchar(200) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `or_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `tax_rate` decimal(10,2) NOT NULL DEFAULT 0,
  `tax_abbreviation` varchar(255) NOT NULL DEFAULT '',
  `currency` varchar(5) NOT NULL,
  `currency_symbol` VARCHAR( 20 ) NOT NULL,
  `currency_position` ENUM(  'before',  'after' ) NOT NULL,
  `idTransaction` varchar(200) NOT NULL,
  `idInvoice` int(11) NOT NULL,
  `idItem` varchar(20) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateCancelation` datetime DEFAULT NULL,
  `comments` text,
  `processor` enum('paypal','stripe') NOT NULL DEFAULT 'stripe',
  `gateway_profile_id` INT UNSIGNED NULL,
  `gateway_code` VARCHAR(80) NULL,
  `gateway_type` VARCHAR(40) NULL,
  `gateway_label` VARCHAR(150) NULL,
  `period` enum('day','week','month','year') NOT NULL,
  `period_count` int(11) NOT NULL,
  `trial_days` INT NOT NULL,
  `billingAddress1` varchar(200) NOT NULL,
  `billingAddress2` varchar(200) NOT NULL,
  `billingCity` varchar(150) NOT NULL,
  `billingCountry` varchar(50) NOT NULL,
  `billingState` varchar(50) NOT NULL,
  `billingZip` varchar(15) NOT NULL,
  `shippingAddress1` VARCHAR( 200 ) NOT NULL ,
  `shippingAddress2` VARCHAR( 200 ) NOT NULL ,
  `shippingCity` VARCHAR( 150 ) NOT NULL ,
  `shippingCountry` VARCHAR( 50 ) NOT NULL ,
  `shippingState` VARCHAR( 50 ) NOT NULL ,
  `shippingZip` VARCHAR( 50 ) NOT NULL,
  `paymentsCount` INT NOT NULL DEFAULT '0',
  
   `stripeCustomer` varchar(200) NOT NULL,
 `imported` set('y','n') NOT NULL DEFAULT 'n',
  
  PRIMARY KEY (`idSubscription`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}subscriptions'");

} else {
    $c->addError("Can't create {$db_pr}subscriptions!");
    $BWContinue = false;
}

//  payment gateway profiles table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}payment_gateways` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}payment_gateways'");
} else {
    $c->addError("Can't create {$db_pr}payment_gateways!");
    $BWContinue = false;
}

//  item payment gateway assignments table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}item_payment_gateways` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}item_payment_gateways'");
} else {
    $c->addError("Can't create {$db_pr}item_payment_gateways!");
    $BWContinue = false;
}


//  alter settings table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_name` varchar(255) NOT NULL,
  `option_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}settings'");

} else {
    $c->addError("Can't create {$db_pr}settings!");
    $BWContinue = false;
}

// add initial options

$query="INSERT INTO `{$db_pr}settings` (`id`, `option_name`, `option_value`) VALUES
(null, 'site_url', '{$site_url}'),
(null, 'email_name', '{$email_from_name}'),
(null, 'email', '{$admin_email}'),
(null, 'email_from', '{$email_from_email}'),
(null, 'page_title', 'Payments terminal'),
(null, 'payment_type', 'input'),
(null, 'show_description', 'n'),
(null, 'show_billing', 'n'),
(null, 'show_shipping', 'n'),
(null, 'redirect_https', 'n'),
(null, 'terminal_logo', ''),
(null, 'site_ssl', 'Site SSL'),
(null, 'paypal_payment_mode', 'test'),
(null, 'terminal_payment_mode', 'test'),
(null, 'use_recaptcha', 'n'),
(null, 'theme_type', 'theme'),
(null, 'selected_theme', 'light'),
(null, 'attach_pdf_invoice', 'n'),
(null, 'display_pdf_payment_options', 'y'),
(null, 'multiple_currencies', 'n'),
(null, 'terminal_currency', 'USD'),
(null, 'default_terminal_currency', 'USD'),
(null, 'multiple_currency_selector', 'n'),
(null, 'enable_paypal', 'n'),
(null, 'thank_you_message', 'Your payment was successful.'),
(null, 'thank_you_redirect', ''),
(null, 'show_terms', 'y'),

(null, 'send_mail', 'php'),
(null, 'smtp_host', ''),
(null, 'smtp_secure', ''),
(null, 'smtp_port', ''),
(null, 'smtp_username', ''),
(null, 'smtp_password', ''),

(null, 'fee_label', 'Service Fee'),
(null, 'fee_enable', 'n'),
(null, 'fee_type', '1'),
(null, 'fee_amount', '3.00'),
(null, 'tax_enable', 'n'),
(null, 'tax_abbreviation', ''),
(null, 'tax_rate', '0'),
(null, 'track_invoice', 'n'),
(null, 'buttons_enable', 'n'),
(null, 'buttons_country', ''),


(null, 'paypal_currency_converter', 'n');";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Added initial settings");

} else {
    $c->addError("Can't add initial settings");
    $BWContinue = false;
}


//  alter customers table

$query="CREATE TABLE IF NOT EXISTS `{$db_pr}customers` (
  `idCustomer` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(200) NOT NULL,
  `customerEmail` varchar(250) NOT NULL,
  `customerBill` text,
  `customerTerm` varchar(10) NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY (`idCustomer`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Created table '{$db_pr}customers'");

} else {
    $c->addError("Can't create {$db_pr}customers!");
    $BWContinue = false;
}

$query = "CREATE TABLE IF NOT EXISTS `{$db_pr}refunds` (
  `idRefund` int(11) NOT NULL AUTO_INCREMENT,
  `idPayment` int(11) NOT NULL,
  `idTransaction` varchar(200) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY (`idRefund`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
$res = $c->query($query);
if (empty($res->error)) {
	$c->addSuccess("Created table '{$db_pr}refunds'");

} else {
	$c->addError("Can't create {$db_pr}refunds!");
	$BWContinue = false;
}
