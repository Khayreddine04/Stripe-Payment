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

include_once "../includes/bootstrap.php";
include_once "settings.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

error_log("=== New Request ===");
error_log("POST data: " . print_r($_POST, true));

$settings->set("admin_section", $pt_section);

$can_view = st_apply_filter('have_permissions', true, 'can_manage_items');

if (!$user->logon) {
    header("Location: ../index.php");
    exit();
}
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");

$action = $a->esc("action");
$$pt_id = $a->esc($pt_id);
$itemName = $a->esc("itemName");
$itemType = $a->esc("itemType", 'product');
$itemFrequency = $a->esc("itemFrequency");
$itemAmount = (float)$a->esc("itemAmount");
$itemTrial = $a->esc("itemTrial", 'n');
$itemPlan = $a->esc("itemPlan", 'n');
$itemTrialDays = (float)$a->esc("itemTrialDays", 7);
$taxExempt = $a->esc("taxExempt", 'n');
$itemDescription = $a->esc("itemDescription");
$itemStatus = $a->esc("itemStatus", 'y');
$itemDesign = $a->esc("itemDesign", "");

$itemBillingMin = (int)($a->esc("itemBillingMin", 1));
$itemBillingMax = (int)($a->esc("itemBillingMax", 5));

$allowOverride = $a->esc("allowOverride", 'n');

$domainsTable = $db_pr . "domains";
$itemDomainsTable = $db_pr . "item_domains";
$activeDomains = array();
$selectedItemDomainIds = array();

function pt_admin_edit_table_exists($a, $tableName)
{
    $safeTable = addslashes($tableName);
    $res = $a->query("SHOW TABLES LIKE '{$safeTable}'");
    return $res && !$res->error && $res->count > 0;
}

function pt_admin_edit_column_exists($a, $tableName, $columnName)
{
    static $cache = array();
    $key = $tableName . ':' . $columnName;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $safeColumn = addslashes($columnName);
    $res = $a->query("SHOW COLUMNS FROM {$tableName} LIKE '{$safeColumn}'");
    $cache[$key] = $res && !$res->error && $res->count > 0;

    return $cache[$key];
}

function pt_admin_save_item_domains($a, $itemId, $domainIds, $itemDomainsTable)
{
    $itemId = trim((string)$itemId);
    if ($itemId === '') {
        return;
    }

    $cleanDomainIds = array();
    if (is_array($domainIds)) {
        foreach ($domainIds as $domainId) {
            $domainId = (int)$domainId;
            if ($domainId > 0) {
                $cleanDomainIds[$domainId] = $domainId;
            }
        }
    }

    $safeItemId = addslashes($itemId);
    $a->query("DELETE FROM {$itemDomainsTable} WHERE item_id = '{$safeItemId}'");

    foreach ($cleanDomainIds as $domainId) {
        $a->query("INSERT INTO {$itemDomainsTable} SET item_id='{$safeItemId}', domain_id='{$domainId}', created_at=NOW(), updated_at=NOW()");
    }
}

$domainFeatureReady = pt_admin_edit_table_exists($a, $domainsTable) && pt_admin_edit_table_exists($a, $itemDomainsTable);
$hasItemTrialUpfrontColumn = pt_admin_edit_column_exists($a, $pt_table, 'itemTrialUpfront');
$itemTrialUpfrontValue = $a->esc("itemTrialUpfront", '0');

if ($action == 'update') {
    error_log("Processing update action");
    error_log("Item ID: " . ($$pt_id ?: 'Not set'));
    if (empty($itemName)) {
        $a->addError("Item Name is required");
    }
    if (empty($itemAmount)) {
        $a->addError("Item Amount is required");
    } elseif (!is_numeric($itemAmount)) {
        $a->addError("Item Amount must be numeric");
    }
    if ($itemType == 'service' && empty($itemFrequency)) {
        $a->addError("Billing Cycle required");
    }

    if ($itemPlan == 'y' && ($itemBillingMax == $itemBillingMin || $itemBillingMax < $itemBillingMin)) {
        $a->addError("Incorrect min/max payment");
    }

    error_log("Validation errors: " . ($a->error ? 'Yes' : 'No'));
    if (!$a->error) {
        error_log("Item ID status: " . (empty($$pt_id) ? 'Empty (new item)' : 'Has value: ' . $$pt_id));
        if (empty($$pt_id)) {

            // Simple UUID generation
            $newUuid = sprintf(
                '%08x-%04x-%04x-%04x-%012x',
                time(),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff) | 0x4000,
                mt_rand(0, 0xffff) | 0x8000,
                mt_rand(0, 0xffffffffffff)
            );

            $$pt_id = $newUuid;
            error_log("Generated UUID: " . $newUuid);

            $itemTrialUpfrontSql = $hasItemTrialUpfrontColumn ? "itemTrialUpfront = '{$itemTrialUpfrontValue}'," : "";

            $sql = "INSERT INTO $pt_table SET
                {$pt_id} = '{$newUuid}',
                itemName = '{$itemName}',
                itemType = '{$itemType}',
                itemStatus = '{$itemStatus}',
                itemFrequency = '{$itemFrequency}',
                itemAmount = '{$itemAmount}',
                itemTrial = '{$itemTrial}',
                {$itemTrialUpfrontSql}
                itemPlan = '{$itemPlan}',
                allowOverride = '{$allowOverride}',
                itemBillingMax = '{$itemBillingMax}',
                itemBillingMin = '{$itemBillingMin}',
                taxExempt = '{$taxExempt}',
                itemDescription = '{$itemDescription}',
                itemTrialDays = '{$itemTrialDays}',
                itemDesign = '{$itemDesign}'";
            error_log("Executing SQL: " . $sql);
            $res = $a->query($sql);
            if ($res === false) {
                global $mysqli;
                error_log("SQL Error: " . $mysqli->error);
                error_log("SQL State: " . $mysqli->sqlstate);
            } else {
                error_log("Insert successful with UUID: " . $newUuid);
            }

            $a->addSuccess("Item '" . $a->crl($itemName) . "' has been successfully created");
            st_do_action('add_user_log', "Added an item: {$itemName}");

            if ($domainFeatureReady) {
                $postedDomainIds = isset($_POST['item_domains']) && is_array($_POST['item_domains']) ? $_POST['item_domains'] : array();
                pt_admin_save_item_domains($a, $$pt_id, $postedDomainIds, $itemDomainsTable);
            }
        } else {
            $itemTrialUpfrontSql = $hasItemTrialUpfrontColumn ? "itemTrialUpfront = '{$itemTrialUpfrontValue}'," : "";

            $sql = "UPDATE $pt_table SET
                itemName = '{$itemName}',
                itemType = '{$itemType}',
                itemStatus = '{$itemStatus}',
                itemFrequency = '{$itemFrequency}',
                itemAmount = '{$itemAmount}',
                itemTrial = '{$itemTrial}',
                {$itemTrialUpfrontSql}
                itemPlan = '{$itemPlan}',
                allowOverride = '{$allowOverride}',
                itemBillingMax = '{$itemBillingMax}',
                itemBillingMin = '{$itemBillingMin}',
                itemDescription = '{$itemDescription}',
                itemTrialDays = '{$itemTrialDays}',
                itemDesign = '{$itemDesign}'
                WHERE {$pt_id} = '{$$pt_id}'";
            error_log("Executing SQL: " . $sql);
            $res = $a->query($sql);
            if ($res === false) {
                global $mysqli;
                error_log("SQL Error: " . $mysqli->error);
                error_log("SQL State: " . $mysqli->sqlstate);
            } else {
                error_log("Update successful");
            }
            $a->addSuccess("Item '" . $a->crl($itemName) . "' has been successfully updated");
            st_do_action('add_user_log', "Edit an item: {$itemName}");

            if ($domainFeatureReady) {
                $postedDomainIds = isset($_POST['item_domains']) && is_array($_POST['item_domains']) ? $_POST['item_domains'] : array();
                pt_admin_save_item_domains($a, $$pt_id, $postedDomainIds, $itemDomainsTable);
            }
        }
    }
}

// First set default values for new items
$itemType = $a->esc("itemType", 'product');
$itemPlan = $a->esc("itemPlan", 'n');
$itemTrial = $a->esc("itemTrial", 'n');
$itemTrialDays = (float)$a->esc("itemTrialDays", 7);

// Debug log initial values
error_log("Initial values - Type: $itemType, Plan: $itemPlan, Trial: $itemTrial");

// Load values from database if editing
if (!empty($$pt_id)) {
    global $mysqli;
    $sql = "SELECT * FROM $pt_table WHERE {$pt_id} = '" . $mysqli->real_escape_string($$pt_id) . "'";
    error_log("Fetching item with SQL: " . $sql);
    $result = $a->query($sql);
    if ($result) {
        $row = $result->result_row();
        if (!empty($row)) {
            foreach ($row as $k => $v) {
                $$k = $v;
            }
        }
    }
}

if ($domainFeatureReady) {
    $activeRes = $a->query("SELECT id, domain FROM {$domainsTable} WHERE is_active='1' ORDER BY domain ASC");
    if ($activeRes && !$activeRes->error && $activeRes->count > 0) {
        foreach ($activeRes->result_array() as $domainRow) {
            if (!pt_is_checkout_domain_forced_inactive($domainRow['domain'] ?? '')) {
                $activeDomains[] = $domainRow;
            }
        }
    }

    if (!empty($$pt_id)) {
        $safeItemId = addslashes($$pt_id);
        $assignedRes = $a->query("SELECT domain_id FROM {$itemDomainsTable} WHERE item_id='{$safeItemId}'");
        if ($assignedRes && !$assignedRes->error && $assignedRes->count > 0) {
            foreach ($assignedRes->result_array() as $assignedRow) {
                $selectedItemDomainIds[(int)$assignedRow['domain_id']] = true;
            }
        }
    }

    if ($action === 'update' && isset($_POST['item_domains']) && is_array($_POST['item_domains'])) {
        $selectedItemDomainIds = array();
        foreach ($_POST['item_domains'] as $postedDomainId) {
            $postedDomainId = (int)$postedDomainId;
            if ($postedDomainId > 0) {
                $selectedItemDomainIds[$postedDomainId] = true;
            }
        }
    }
}

// Calculate showTrial after values are loaded from database or form
$showTrial = ($itemType === 'service') || ($itemType === 'product' && $itemPlan === 'y');

// Debug log the showTrial calculation
error_log("Show Trial: " . ($showTrial ? 'true' : 'false') . " (Type: $itemType, Plan: $itemPlan)");

// Build list of available themes by scanning assets/css/*/style.css
$available_themes = array();
$base = HOME_DIR . "/assets/css";
if (is_dir($base)) {
    foreach (glob($base . '/*/style.css') as $cssFile) {
        $slug = basename(dirname($cssFile));
        if ($slug === 'invoice') {
            continue;
        }
        $available_themes[$slug] = ucfirst($slug);
    }
}
// Set available themes
$available_themes = array(
    'CardStyle' => 'CardStyle',
    'Minimalist' => 'Minimalist',
    'Colorful' => 'Colorful',
    'Normal' => 'Normal',
    'PhysicalProduct' => 'PhysicalProduct',
    'adaptive-lp' => 'Adaptive LP'
);

$a->getHeader();
?>
<?php
// Determine initial preview theme: item-specific or global fallback (default to Minimalist)
$__initial_theme_slug = !empty($itemDesign) ? $itemDesign : (empty($settings->selected_theme) ? 'Minimalist' : $settings->selected_theme);
?>
<!-- Right-side theme preview (out of the form section) -->
<style>
    #themePreviewSidebar {
        width: 50%;
        max-width: 500px;
        min-width: 300px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
        margin: 0 20px 20px 0;
    }

    #themePreviewSidebar:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        transform: translateY(-3px);
    }

    .preview-header {
        background: #2c3e50;
        color: white;
        padding: 15px 20px;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .preview-header i {
        color: #ecf0f1;
        font-size: 1.1em;
        margin-right: 8px;
    }

    .preview-container {
        padding: 20px;
        background: #f8f9fa;
        text-align: center;
    }

    #theme_preview_img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
        transition: transform 0.3s ease;
        display: block;
        margin: 0 auto;
    }

    #theme_preview_img:hover {
        transform: scale(1.02);
    }

    .preview-caption {
        margin: 15px 0 0 0;
        padding: 10px;
        background: white;
        border-radius: 6px;
        font-size: 0.95em;
        text-align: center;
        border: 1px solid #eee;
        color: #555;
    }

    @media (max-width: 1399px) {}

    @media (max-width: 1199px) {
        #themePreviewSidebar {
            position: relative;
            top: auto;
            right: auto;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            display: block;
        }
    }
</style>




<div class="container" role="main">
    <div class="flex-box" style="    display: flex; align-items: center; justify-content: center;">
        <?php if ($can_view) { ?>
            <form class="validate" role="form" action="edit.php" method="post">
                <input type="hidden" name="action" value="update">
                <div class="row">
                    <div class="col-md-9 col-lg-9 col-sm-9 col-xs-6 vcenter">
                        <h2><?php echo (!empty($$pt_id) ? "Edit" : "Add") ?> item</h2>
                    </div>
                    <div class="col-md-3 col-lg-3 col-sm-3 col-xs-6 vcenter text-right"><span class="back_to_list">&larr;<a href="index.php">Back to list</a></span> </div>
                </div>
                <?php echo ($a->getMessages()) ?>
                <div class="col-md-9 col-lg-12 col-sm-12 col-xs-12 form_section">
                    <div class="rowItem">
                        <input type="hidden" name="<?php echo ($pt_id) ?>" value="<?php echo ($$pt_id) ?>" id="<?php echo ($pt_id) ?>">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="form-group col-md-6 col-sm-6">
                                <label for="itemName"><span>*</span>Name</label>
                                <input type="text" class="form-control" name="itemName" id="itemName" placeholder=""
                                    value="<?php echo ($a->crl($itemName)) ?>"
                                    data-rule-required="true">
                            </div>
                            <div class="form-group  col-md-6 col-sm-6">
                                <label for="itemStatus"><span>*</span>Item Status</label>
                                <div class="clearfix"></div>
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-default <?php echo ($itemStatus == 'y') ? "active" : "" ?>">
                                        <input type="radio" name="itemStatus" id="itemStatus1"
                                            value="y" <?php echo ($itemStatus == 'y' ? "checked" : "") ?>>Enabled
                                    </label>
                                    <label class="btn btn-default <?php echo ($itemStatus == 'n') ? "active" : "" ?>">
                                        <input type="radio" name="itemStatus" id="itemStatus0"
                                            value="n" <?php echo ($itemStatus == 'n' ? "checked" : "") ?>>Disabled
                                    </label>
                                </div>
                            </div>
                            <div class="clearfix"></div>

                            <div class="form-group col-md-6 col-sm-6">
                                <label for="itemDescription">Description</label>
                                <textarea name="itemDescription"
                                    id="itemDescription"
                                    class="form-control"><?php echo ($a->crl($itemDescription)) ?></textarea>
                            </div>
                            <?php if ($domainFeatureReady) { ?>
                                <div class="form-group col-md-12 col-sm-12">
                                    <label>Allowed Domains for This Item</label>
                                    <?php if (empty($activeDomains)) { ?>
                                        <p class="text-muted">No active domains found. Add and activate domains in Settings > Domains.</p>
                                    <?php } else { ?>
                                        <div class="checkbox" style="margin-bottom: 10px;">
                                            <label>
                                                <input type="checkbox" id="selectAllItemDomains"> Select All Domains
                                            </label>
                                        </div>
                                        <div style="border:1px solid #ddd; padding:12px; border-radius:4px; max-height:240px; overflow:auto;">
                                            <?php foreach ($activeDomains as $domainRow) {
                                                $domainId = (int)$domainRow['id'];
                                                $checked = isset($selectedItemDomainIds[$domainId]) ? 'checked' : '';
                                            ?>
                                                <div class="checkbox" style="margin: 6px 0;">
                                                    <label>
                                                        <input type="checkbox" class="itemDomainCheckbox" name="item_domains[]" value="<?php echo $domainId ?>" <?php echo $checked ?>>
                                                        <?php echo htmlspecialchars($domainRow['domain']) ?>
                                                    </label>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <small class="text-muted">If none are selected, fallback default domain logic will be used in checkout.</small>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <div class="form-group  col-md-6 col-sm-6">
                                <label for="itemDesign">Design / Theme</label>
                                <select name="itemDesign" id="itemDesign" class="form-control">
                                    <option value="">Default (Global)</option>
                                    <?php foreach ($available_themes as $slug => $label): ?>
                                        <option value="<?php echo htmlspecialchars($slug) ?>" <?php echo ($itemDesign === $slug ? "selected" : "") ?>><?php echo htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">If empty, the global theme (Settings > Customize) will be used.</small>
                            </div>
                            <!-- Design preview moved to right-side sidebar -->
                            <div class="form-group col-md-6 col-sm-6">
                                <label for="itemType"><span>*</span>Item Type</label>
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-default <?php echo ($itemType == 'product') ? "active" : "" ?>">
                                        <input type="radio" name="itemType" id="itemTypep" onchange="updateFrequency(this)"
                                            value="product" <?php echo ($itemType == 'product' ? "checked" : "") ?>>Fixed-Price Payment
                                    </label>
                                    <label class="btn btn-default <?php echo ($itemType == 'service') ? "active" : "" ?>">
                                        <input type="radio" name="itemType" id="itemTypeS" onchange="updateFrequency(this)"
                                            value="service" <?php echo ($itemType == 'service' ? "checked" : "") ?>>Recurring Service
                                    </label>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <div class="form-group col-md-6 col-sm-6">
                                <label for="itemAmount"><span>*</span>Price</label>
                                <div class="input-group">
                                    <?php if ($settings->currency_position == 'before') { ?>
                                        <div class="input-group-addon"><?php echo $settings->display_currency ?></div>
                                    <?php } ?>
                                    <input type="text" class="form-control" name="itemAmount" id="itemAmount"
                                        placeholder="0.00" value="<?php echo ($itemAmount) ?>"
                                        data-rule-required="true" data-rule-number="true"
                                        data-msg-number="Only numbers">
                                    <?php if ($settings->currency_position == 'after') { ?>
                                        <div class="input-group-addon"><?php echo $settings->display_currency ?></div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="form-group  col-md-6 col-sm-6 single-item" style="display: <?php echo ($itemType == 'service' ? "none" : "block") ?>">
                                <label for="itemPlany"><span>*</span>Enable Payment Plan</label>
                                <div class="clearfix"></div>
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-default <?php echo ($itemPlan == 'y') ? "active" : "" ?>">
                                        <input type="radio" name="itemPlan" id="itemPlan" onchange="updatePlan(this)"
                                            value="y" <?php echo ($itemPlan == 'y' ? "checked" : "") ?>>Yes
                                    </label>
                                    <label class="btn btn-default <?php echo ($itemPlan == 'n') ? "active" : "" ?>">
                                        <input type="radio" name="itemPlan" id="itemPlanS" onchange="updatePlan(this)"
                                            value="n" <?php echo ($itemPlan == 'n' ? "checked" : "") ?>>No
                                    </label>
                                </div>
                            </div>

                            <div class="form-group col-md-6 col-sm-6 recurring-item" style="display: <?php echo ($itemType == 'service' ? "block" : "none") ?>">
                                <label for="itemFrequency">Billing Cycle</label>
                                <select class="form-control" name="itemFrequency" id="itemFrequency" data-rule-required="true">
                                    <option value="">Select</option>
                                    <?php foreach ($_BILLING_PERIODS as $key => $billing) { ?>
                                        <option value="<?php echo ($key) ?>" <?php echo ($key == $itemFrequency ? "selected" : "") ?>><?php echo ($key) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="clearfix"></div>

                            <div class="planCont" id="paymentsCont" style="display: <?php echo ($itemPlan == 'y' && $itemType !== 'service' ? "block" : "none") ?>">

                                <div class="form-group  col-md-3 col-sm-3">
                                    <label for="itemBillingMin">Minimum payment</label>
                                    <div class="input-group">

                                        <input type="text" class="form-control" name="itemBillingMin" id="itemBillingMin"
                                            placeholder="1" value="<?php echo ($itemBillingMin) ?>"
                                            data-rule-required="true" data-rule-number="true"
                                            data-msg-number="Only numbers">

                                    </div>
                                </div>

                                <div class="form-group  col-md-3 col-sm-3">
                                    <label for="itemBillingMax">Maximum payment</label>
                                    <div class="input-group">

                                        <input type="text" class="form-control" name="itemBillingMax" id="itemBillingMax"
                                            placeholder="1" value="<?php echo ($itemBillingMax) ?>"
                                            data-rule-required="true" data-rule-number="true"
                                            data-msg-number="Only numbers">

                                    </div>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <div id="trialCont" class="planCont" style="display: <?php echo ($showTrial ? "block" : "none") ?>">
                                <div class="form-group  col-md-6 col-sm-6">
                                    <label for="itemTrial">Free trial</label>
                                    <div class="clearfix"></div>
                                    <div class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default <?php echo ($itemTrial == 'y') ? "active" : "" ?>">
                                            <input type="radio" name="itemTrial" id="itemTrialY" value="y" <?php echo ($itemTrial == 'y' ? "checked" : "") ?> onchange="updateTrial(this)"> Yes
                                        </label>
                                        <label class="btn btn-default <?php echo ($itemTrial != 'y') ? "active" : "" ?>">
                                            <input type="radio" name="itemTrial" id="itemTrialN" value="n" <?php echo ($itemTrial != 'y' ? "checked" : "") ?> onchange="updateTrial(this)"> No
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-md-6 col-sm-6" id="itemTrialDaysCont" style="display: <?php echo ($itemTrial == 'y') ? "block" : "none" ?>">
                                    <label for="itemTrialDays">Free trial length (days)</label>
                                    <input type="text" class="form-control" name="itemTrialDays" id="itemTrialDays" placeholder=""
                                        value="<?php echo ($itemTrialDays) ?>"
                                        data-rule-number="true"
                                        data-msg-number="Only numbers">
                                </div>
                                <div class="form-group col-md-6 col-sm-6" id="itemTrialUpfrontCont" style="display: <?php echo ($itemTrial == 'y') ? "block" : "none" ?>">
                                    <label for="itemTrialUpfront">Upfront Fee (0 for no fee)</label>
                                    <div class="input-group">
                                        <?php
                                        if (!isset($CURRENCY_CODES)) {
                                            include(__DIR__ . '/../../../includes/currency_codes.php');
                                        }
                                        // Use the main item's currency for the upfront fee
                                        $mainCurrency = $settings->terminal_currency; // Default to terminal currency
                                        $currencySymbol = isset($CURRENCY_CODES[$mainCurrency]) ? $CURRENCY_CODES[$mainCurrency] : '$';
                                        ?>
                                        <span class="input-group-addon"><?php echo htmlspecialchars($currencySymbol) ?></span>
                                        <input type="text" class="form-control" name="itemTrialUpfront" id="itemTrialUpfront"
                                            placeholder="0.00"
                                            value="<?php echo isset($itemTrialUpfront) ? htmlspecialchars($itemTrialUpfront) : '0.00' ?>"
                                            data-rule-number="true"
                                            data-msg-number="Only numbers">
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <div id="overrideCont" class="single-item" style="display: <?php echo ($itemType == 'product' ? "block" : "none") ?>">
                                <div class="form-group  col-md-6 col-sm-6">
                                    <label for="allowOverridey">Allow overrides in URL</label>
                                    <div class="clearfix"></div>
                                    <div class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default <?php echo ($allowOverride == 'y') ? "active" : "" ?>">
                                            <input type="radio" name="allowOverride" id="allowOverrideS"
                                                value="y" <?php echo ($allowOverride == 'y' ? "checked" : "") ?>>Enabled
                                        </label>
                                        <label class="btn btn-default <?php echo ($allowOverride == 'n') ? "active" : "" ?>">
                                            <input type="radio" name="allowOverride" id="allowOverride"
                                                value="n" <?php echo ($allowOverride == 'n' ? "checked" : "") ?>>Disabled
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <div id="taxExemptCont" style="display: <?php echo ($settings->tax_enable == 'y' ? "block" : "none") ?>">
                                <div class="form-group  col-md-6 col-sm-6">
                                    <label for="itemTaxExempty">Product is <?php echo $settings->tax_abbreviation; ?> tax exempt?</label>
                                    <div class="clearfix"></div>
                                    <div class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default <?php echo ($taxExempt == 'y') ? "active" : "" ?>">
                                            <input type="radio" name="taxExempt" id="taxExempt1"
                                                value="y" <?php echo ($taxExempt == 'y' ? "checked" : "") ?>>Yes
                                        </label>
                                        <label class="btn btn-default <?php echo ($taxExempt == 'n') ? "active" : "" ?>">
                                            <input type="radio" name="taxExempt" id="taxExempt2"
                                                value="n" <?php echo ($taxExempt == 'n' ? "checked" : "") ?>>No
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <p>&nbsp;</p>
                <div class="col-md-12 ">
                    <button type="submit" class="btn btn-success btn-lg">Save</button>
                    <?php if (!empty($$pt_id)) { ?>
                        <a class="btn btn-success btn-lg" href="edit.php">Add New</a>
                    <?php } ?>
                </div>
            </form>
            <aside id="themePreviewSidebar" aria-label="Design Preview">
                <div class="preview-header">
                    <span><i class="fa fa-paint-brush"></i> Theme Preview</span>
                    <i class="fa fa-expand"></i>
                </div>
                <div class="preview-container">
                    <img
                        id="theme_preview_img"
                        src="<?php echo rtrim($settings->site_url, '/'); ?>/assets/images/<?php echo htmlspecialchars($__initial_theme_slug); ?>.png"
                        alt="Design preview" />
                    <p class="preview-caption">
                        Previewing: <strong><?php echo htmlspecialchars($__initial_theme_slug); ?></strong>
                    </p>
                </div>
            </aside>
        <?php } else { ?>
            You have no permissions to view this section
        <?php } ?>
    </div>
</div>






<script>
    // Define all functions in the global scope
    function updateFrequency(el) {
        var $el = $(el);
        var isService = $el.val() === 'service';
        var isProduct = $el.val() === 'product';

        console.log('updateFrequency called with:', $el.val());

        if (isProduct) {
            // For products
            $(".recurring-item").hide();
            $(".single-item").show();

            // Show trial section only if it's a product with a plan
            var hasPlan = $("input[name='itemPlan']:checked").val() === 'y';
            console.log('Product - hasPlan:', hasPlan);

            $("#trialCont").toggle(hasPlan);
            $("#paymentsCont").toggle(hasPlan);
        } else if (isService) {
            // For services
            console.log('Service - showing recurring items and trial section');
            $(".recurring-item").show();
            $(".single-item").hide();

            // Always show trial section for services
            $("#trialCont").show();
            $("#paymentsCont").hide();

            // Make sure trial fields are visible if trial is set to 'y'
            var trialEnabled = $("input[name='itemTrial']:checked").val() === 'y';
            console.log('Service - trial enabled:', trialEnabled);
            $("#itemTrialDaysCont").toggle(trialEnabled);
            $("#itemTrialUpfrontCont").toggle(trialEnabled);
        }
    }

    function updatePlan(el) {
        var $el = $(el);
        if ($el.val() == 'y') {
            $(".planCont").show();
        } else {
            $(".planCont").hide();
        }
    }

    function updateTrial(el) {
        var $el = $(el);
        var trialEnabled = $el.val() === 'y';
        console.log('updateTrial called with:', $el.val(), 'enabled:', trialEnabled);

        // Always update the visibility of trial fields
        $("#itemTrialDaysCont").toggle(trialEnabled);
        $("#itemTrialUpfrontCont").toggle(trialEnabled);

        // Make sure the trial container is visible for services or products with a plan
        var isService = $('input[name="itemType"]:checked').val() === 'service';
        var hasPlan = $('input[name="itemPlan"]:checked').val() === 'y';

        if (isService || (hasPlan && $('input[name="itemType"]:checked').val() === 'product')) {
            $("#trialCont").show();
        }
    }

    $(document).ready(function() {
        // Debug log for JavaScript initialization
        console.log('Document ready - Initializing form');
        console.log('Item Type:', $('input[name="itemType"]:checked').val());
        console.log('Item Plan:', $('input[name="itemPlan"]:checked').val());
        console.log('Item Trial:', $('input[name="itemTrial"]:checked').val());

        // Initialize form validation
        $(".validate").validate({
            errorPlacement: function(error, element) {
                element.parents(".form-group").addClass("has-error");
                element.wrap("<div class='control-wrap'>");
                error.appendTo(element.parent());
            },
            success: function(label) {
                label.parents(".form-group").removeClass("has-error").addClass("has-success");
            }
        });

        // --- Theme preview (image) wiring ---
        var previewBase = "<?php echo rtrim($settings->site_url, '/'); ?>/assets/images/";
        var globalTheme = "<?php echo htmlspecialchars($settings->selected_theme) ?>";

        function updateThemePreview(slug) {
            var theme = (slug && slug.trim() !== '') ? slug : (globalTheme || 'Minimalist');
            var previewImage = theme + '.png';
            $("#theme_preview_img").attr('src', previewBase + previewImage);
        }

        // Initialize form state
        console.log('Initializing form state...');

        // First, update based on item type
        var $itemType = $('input[name="itemType"]:checked');
        if ($itemType.length > 0) {
            console.log('Updating frequency for item type:', $itemType.val());
            updateFrequency($itemType[0]);
        } else {
            // Default to product if nothing is checked
            console.log('No item type selected, defaulting to product');
            $('input[name="itemType"][value="product"]').prop('checked', true).trigger('change');
        }

        // Then update based on plan
        var $itemPlan = $('input[name="itemPlan"]:checked');
        if ($itemPlan.length > 0) {
            updatePlan($itemPlan[0]);
        } else {
            // Default to 'n' if nothing is checked
            $('input[name="itemPlan"][value="n"]').prop('checked', true).trigger('change');
        }

        // Finally update trial settings
        var $itemTrial = $('input[name="itemTrial"]:checked');
        if ($itemTrial.length > 0) {
            console.log('Updating trial settings:', $itemTrial.val());
            updateTrial($itemTrial[0]);
        } else {
            // Default to 'n' if nothing is checked
            $('input[name="itemTrial"][value="n"]').prop('checked', true).trigger('change');
        }


        // Set up event handlers
        $('input[name="itemType"]').on('change', function() {
            updateFrequency(this);
        });

        $('input[name="itemPlan"]').on('change', function() {
            updatePlan(this);
        });

        $('input[name="itemTrial"]').on('change', function() {
            updateTrial(this);
        });

        // Initial render for theme preview
        updateThemePreview($("#itemDesign").val());

        // Update theme preview on change
        $("#itemDesign").on('change', function() {
            updateThemePreview(this.value);
        });

        // Item domain helpers
        $('#selectAllItemDomains').on('change', function() {
            $('.itemDomainCheckbox').prop('checked', this.checked);
        });

        $('.itemDomainCheckbox').on('change', function() {
            var total = $('.itemDomainCheckbox').length;
            var checked = $('.itemDomainCheckbox:checked').length;
            $('#selectAllItemDomains').prop('checked', total > 0 && total === checked);
        });

        $('.itemDomainCheckbox').trigger('change');
    });
</script>
<?php echo ($a->getFooter()) ?>
