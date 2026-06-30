<?php

include_once "../includes/bootstrap.php";
include_once "settings.php";

if (!$user->logon) {
    header("Location: ../index.php?rd=settings/payment_gateways.php");
    exit();
}

$settings->set("admin_section", $pt_section);
$settings_section = "payment_gateways";
$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");

PT_Payment_Gateway::ensureSchema();

$gatewayTable = $db_pr . "payment_gateways";
$action = $a->esc("action");
$gatewayId = (int)$a->esc("gateway_id", 0);

function pt_gateway_admin_slug($value)
{
    $value = strtolower(trim((string)$value));
    $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
    $value = trim($value, '_');
    return $value !== '' ? $value : 'stripe_gateway';
}

if ($action === 'delete' && $gatewayId > 0) {
    $gateway = PT_Payment_Gateway::getById($gatewayId, false);
    if (!$gateway) {
        $a->addError("Gateway not found");
    } elseif ((int)$gateway['is_default'] === 1) {
        $a->addError("Default gateway cannot be deleted. Set another gateway as default first.");
    } else {
        $a->query("DELETE FROM `{$gatewayTable}` WHERE id = '{$gatewayId}'");
        $a->addSuccess("Gateway deleted");
        st_do_action('add_user_log', "Deleted payment gateway {$gateway['gateway_code']}");
    }
}

if ($action === 'save_gateway') {
    $label = trim($a->esc("label"));
    $gatewayCode = pt_gateway_admin_slug($a->esc("gateway_code"));
    $mode = $a->esc("mode", "test") === "live" ? "live" : "test";
    $publicKey = trim($a->esc("public_key"));
    $secretKey = trim($a->esc("secret_key"));
    $webhookSecret = trim($a->esc("webhook_secret"));
    $isActive = $a->esc("is_active", "1") === "1" ? 1 : 0;
    $isDefault = $a->esc("is_default", "0") === "1" ? 1 : 0;
    $priority = (int)$a->esc("priority", 100);

    if ($label === '') {
        $a->addError("Gateway label is required");
    }
    if ($gatewayCode === '') {
        $a->addError("Gateway code is required");
    }
    if ($publicKey === '' || $secretKey === '') {
        $a->addError("Stripe public and secret keys are required");
    }

    if (!$a->error) {
        $safeCode = mysqli_real_escape_string($a->link, $gatewayCode);
        $duplicateSql = "SELECT id FROM `{$gatewayTable}` WHERE gateway_code = '{$safeCode}'";
        if ($gatewayId > 0) {
            $duplicateSql .= " AND id <> '{$gatewayId}'";
        }
        $duplicateSql .= " LIMIT 1";
        $duplicate = $a->query($duplicateSql);
        if ($duplicate && !$duplicate->error && $duplicate->count > 0) {
            $a->addError("Gateway code already exists");
        }
    }

    if (!$a->error) {
        if ($isDefault) {
            $a->query("UPDATE `{$gatewayTable}` SET is_default = '0' WHERE gateway_type = 'stripe'");
        }

        $sqlSet = "
            gateway_code = '" . mysqli_real_escape_string($a->link, $gatewayCode) . "',
            gateway_type = 'stripe',
            label = '" . mysqli_real_escape_string($a->link, $label) . "',
            mode = '{$mode}',
            public_key = '" . mysqli_real_escape_string($a->link, $publicKey) . "',
            secret_key = '" . mysqli_real_escape_string($a->link, $secretKey) . "',
            webhook_secret = '" . mysqli_real_escape_string($a->link, $webhookSecret) . "',
            is_active = '{$isActive}',
            is_default = '{$isDefault}',
            priority = '{$priority}',
            supports_one_time = '1',
            supports_recurring = '1',
            supports_payment_request = '1'";

        if ($gatewayId > 0) {
            $a->query("UPDATE `{$gatewayTable}` SET {$sqlSet} WHERE id = '{$gatewayId}'");
            $a->addSuccess("Gateway updated");
            st_do_action('add_user_log', "Edited payment gateway {$gatewayCode}");
        } else {
            $a->query("INSERT INTO `{$gatewayTable}` SET {$sqlSet}, created = NOW()");
            $a->addSuccess("Gateway added");
            st_do_action('add_user_log', "Added payment gateway {$gatewayCode}");
        }

        if ($isDefault) {
            $settings->updateOption("terminal_payment_mode", $mode);
            if ($mode === 'live') {
                $settings->updateOption("live_public_key", $publicKey);
                $settings->updateOption("live_secret_key", $secretKey);
            } else {
                $settings->updateOption("test_public_key", $publicKey);
                $settings->updateOption("test_secret_key", $secretKey);
            }
            $settings->updateOption("webhook_secret_key", $webhookSecret);
        }
    }
}

$editGateway = $gatewayId > 0 && $action === 'edit' ? PT_Payment_Gateway::getById($gatewayId, false) : false;
$gateways = PT_Payment_Gateway::getAll(false, 'stripe');

$a->getHeader();
?>
<div class="container" role="main">
    <div class="row">
        <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
            <?php $settings_menu->render(true) ?>
        </div>
        <div class="clearfix visible-xs-block"></div>
        <div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
            <?php echo($a->getMessages()) ?>
            <?php if ($can_view) { ?>
                <h2>Payment Gateways</h2>
                <p class="text-muted">Manage Stripe accounts here, then assign one to each item. Items without an assignment use the default gateway.</p>

                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Label</th>
                        <th>Code</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Webhook URL</th>
                        <th>Manage</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($gateways)) { ?>
                        <tr><td colspan="7" class="text-muted">No gateways found.</td></tr>
                    <?php } ?>
                    <?php foreach ($gateways as $gateway) {
                        $webhookUrl = rtrim($settings->site_url, '/') . '/webhook.php?gateway=' . rawurlencode($gateway['gateway_code']);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($gateway['label']) ?></td>
                            <td><code><?php echo htmlspecialchars($gateway['gateway_code']) ?></code></td>
                            <td><?php echo htmlspecialchars($gateway['mode']) ?></td>
                            <td><?php echo ((int)$gateway['is_active'] === 1) ? '<span class="text-success">Active</span>' : '<span class="text-muted">Disabled</span>' ?></td>
                            <td><?php echo ((int)$gateway['is_default'] === 1) ? '<span class="label label-success">Default</span>' : '' ?></td>
                            <td><input class="form-control input-sm" readonly value="<?php echo htmlspecialchars($webhookUrl) ?>"></td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="payment_gateways.php?action=edit&gateway_id=<?php echo (int)$gateway['id'] ?>">Edit</a>
                                <?php if ((int)$gateway['is_default'] !== 1) { ?>
                                    <a class="btn btn-xs btn-danger" href="payment_gateways.php?action=delete&gateway_id=<?php echo (int)$gateway['id'] ?>" onclick="return confirm('Delete this gateway?')">Delete</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <h2><?php echo $editGateway ? 'Edit Gateway' : 'Add Stripe Gateway' ?></h2>
                <hr>
                <form class="validate" role="form" method="post">
                    <input type="hidden" name="action" value="save_gateway">
                    <input type="hidden" name="gateway_id" value="<?php echo $editGateway ? (int)$editGateway['id'] : 0 ?>">

                    <div class="form-group col-md-4">
                        <label>Label <span>*</span></label>
                        <input type="text" class="form-control" name="label" data-rule-required="true" value="<?php echo htmlspecialchars($editGateway['label'] ?? '') ?>">
                        <small>Example: Stripe Main, Stripe Backup FR.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Gateway Code <span>*</span></label>
                        <input type="text" class="form-control" name="gateway_code" data-rule-required="true" value="<?php echo htmlspecialchars($editGateway['gateway_code'] ?? '') ?>">
                        <small>Example: stripe_main. Use letters, numbers, and underscores.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Mode</label>
                        <select name="mode" class="form-control">
                            <?php $mode = $editGateway['mode'] ?? 'test'; ?>
                            <option value="test" <?php echo $mode === 'test' ? 'selected' : '' ?>>Test</option>
                            <option value="live" <?php echo $mode === 'live' ? 'selected' : '' ?>>Live</option>
                        </select>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group col-md-6">
                        <label>Public Key <span>*</span></label>
                        <input type="text" class="form-control" name="public_key" data-rule-required="true" value="<?php echo htmlspecialchars($editGateway['public_key'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Secret Key <span>*</span></label>
                        <input type="text" class="form-control" name="secret_key" data-rule-required="true" value="<?php echo htmlspecialchars($editGateway['secret_key'] ?? '') ?>">
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group col-md-6">
                        <label>Webhook Signing Secret</label>
                        <input type="text" class="form-control" name="webhook_secret" value="<?php echo htmlspecialchars($editGateway['webhook_secret'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Priority</label>
                        <input type="text" class="form-control" name="priority" value="<?php echo htmlspecialchars($editGateway['priority'] ?? '100') ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Status</label>
                        <?php $isActive = (string)($editGateway['is_active'] ?? '1'); ?>
                        <select name="is_active" class="form-control">
                            <option value="1" <?php echo $isActive === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?php echo $isActive === '0' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Default</label>
                        <?php $isDefault = (string)($editGateway['is_default'] ?? '0'); ?>
                        <select name="is_default" class="form-control">
                            <option value="0" <?php echo $isDefault === '0' ? 'selected' : '' ?>>No</option>
                            <option value="1" <?php echo $isDefault === '1' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                    <div class="clearfix"></div>

                    <button type="submit" class="btn btn-success btn-lg">Save Gateway</button>
                    <?php if ($editGateway) { ?>
                        <a href="payment_gateways.php" class="btn btn-default btn-lg">Cancel</a>
                    <?php } ?>
                </form>
            <?php } else { ?>
                You have no permissions to view this section
            <?php } ?>
        </div>
    </div>
</div>
<?php echo($a->getFooter()) ?>
