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


//  alter items table
$query = "ALTER TABLE `{$db_pr}items`
ADD `itemBillingMax`  int NOT NULL DEFAULT '0',
ADD `itemBillingMin`  int NOT NULL DEFAULT '0',
ADD `itemPlan` enum('y','n')  NOT NULL DEFAULT 'n';

";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Updated table '{$db_pr}items'");

}


//  alter subscriptions table
$query = "ALTER TABLE `{$db_pr}subscriptions`
ADD `stripeCustomer` varchar(200) NOT NULL,
ADD `paymentsCount` int NOT NULL DEFAULT '0',
ADD `imported` set('y','n') NOT NULL DEFAULT 'n' ;
";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Updated table '{$db_pr}subscriptions'");

}

//  alter payments table
$query = "ALTER TABLE `{$db_pr}payments`
ADD `stripeCustomer` varchar(200)  NOT NULL,
ADD `stripeCharge` varchar(200)  NOT NULL,
ADD `stripeSubscription` varchar(200)  NOT NULL ,
ADD `paypalSubscription` varchar(200)  NOT NULL ,
ADD `imported` set('y','n')  NOT NULL DEFAULT 'n';
";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Updated table '{$db_pr}payments'");

}


//  alter payments table
$query = "ALTER TABLE `{$db_pr}payments`
CHANGE `processor` `processor` enum('paypal','stripe','stripe_direct')  NOT NULL DEFAULT 'stripe' ;
";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments'");

}


// alter items table
$query = "ALTER TABLE `{$db_pr}items`
ADD `allowOverride` enum('y','n') NOT NULL DEFAULT 'n';";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}items'");
}

// alter items table for item status
$query = "ALTER TABLE `{$db_pr}items`
ADD `itemStatus` enum('y','n') NOT NULL DEFAULT 'y';";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}items - added item status field;'");
}

$query = "ALTER TABLE `{$db_pr}payments`
ADD `or_amount` decimal(10,2) NOT NULL;";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments'");

}

$query = "ALTER TABLE `{$db_pr}subscriptions`
ADD `or_amount` decimal(10,2) NOT NULL;";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}subscriptions'");

}

$query = "ALTER TABLE `{$db_pr}payments`
CHANGE `idItem` `idItem` varchar(20) NOT NULL;";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments'");

}

$query = "ALTER TABLE `{$db_pr}subscriptions`
CHANGE `idItem` `idItem` varchar(20) NOT NULL;";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}subscriptions'");

}

$query = "ALTER TABLE `{$db_pr}payments`
ADD `idRefund` VARCHAR(200) NOT NULL DEFAULT '', 
ADD `refundDate` DATETIME NULL DEFAULT NULL;";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments'");

}

$query = "ALTER TABLE `{$db_pr}invoices`
ADD `invoiceType` enum('single','recurring') NULL DEFAULT 'single';";

$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}invoices'");

}

$query = "ALTER TABLE `{$db_pr}payments`
ADD `tax_amount` decimal(10,2) NOT NULL DEFAULT 0 AFTER `or_amount`,
ADD `tax_rate` decimal(10,2) NOT NULL DEFAULT 0 AFTER `tax_amount`,
ADD `tax_abbreviation` varchar(255) NOT NULL DEFAULT '' AFTER `tax_rate`;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments for taxes'");
}

$query = "ALTER TABLE `{$db_pr}payments`
ADD `service_fee` decimal(10,2) NOT NULL AFTER `or_amount`;";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments for service charge'");
}

// alter items table
$query = "ALTER TABLE `{$db_pr}items`
ADD `taxExempt` enum('y','n') NOT NULL DEFAULT 'n';";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}items for taxes'");

}

// alter subscription table
$query = "ALTER TABLE `{$db_pr}subscriptions`
ADD `tax_amount` decimal(10,2) NOT NULL DEFAULT 0 AFTER `or_amount`,
ADD `tax_rate` decimal(10,2) NOT NULL DEFAULT 0 AFTER `tax_amount`,
ADD `tax_abbreviation` varchar(255) NOT NULL DEFAULT '' AFTER `tax_rate`;";
$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments for taxes'");

}

// alter invoice_history table
$query = "ALTER TABLE `{$db_pr}invoice_history`
CHANGE `action` `action` set('create','update','send','paid','view') COLLATE 'utf8_general_ci' NOT NULL;";
$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}payments for taxes'");

}

// alter items table
$query = "ALTER TABLE `{$db_pr}items`
ADD `itemDescription` text COLLATE 'utf8_general_ci' NOT NULL DEFAULT '';";
$res = $c->query($query);

if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}items for description'");

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

$query = "ALTER TABLE `{$db_pr}payments`
CHANGE `paypalStatus` `paypalStatus` enum('paid','pending','refunded','partial_refund') COLLATE 'utf8_general_ci' NULL AFTER `idPayment`;";
if (empty($res->error)) {
	$c->addSuccess("Update Table '{$db_pr}payments'");

}

// alter items table - add itemDesign (per-item theme)
$query = "ALTER TABLE `{$db_pr}items`
ADD `itemDesign` varchar(50) NOT NULL DEFAULT ''";
$res = $c->query($query);
if (empty($res->error)) {
    $c->addSuccess("Alter table '{$db_pr}items' - added itemDesign");
}