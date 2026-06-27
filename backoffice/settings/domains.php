<?php
/**
 * Domain rotation settings.
 */

include_once "../includes/bootstrap.php";
include_once "settings.php";

if (!$user->logon) {
    header("Location: ../index.php?rd=settings/domains.php");
    exit();
}

$settings->set("admin_section", $pt_section);
$settings_section = "domains";

$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");

$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;

function pt_admin_normalize_domain($value)
{
    $value = trim(strtolower((string)$value));
    if ($value === '') {
        return '';
    }

    if (strpos($value, '://') !== false) {
        $host = parse_url($value, PHP_URL_HOST);
        if (!empty($host)) {
            $value = $host;
        }
    }

    $value = preg_replace('/:\\d+$/', '', $value);
    return trim($value, " \t\n\r\0\x0B.");
}

function pt_admin_table_exists($a, $tableName)
{
    $safeTable = addslashes($tableName);
    $res = $a->query("SHOW TABLES LIKE '{$safeTable}'");
    return $res && !$res->error && $res->count > 0;
}

$domainsTable = $db_pr . "domains";
$featureReady = pt_admin_table_exists($a, $domainsTable);

$action = $a->esc("action");
$domain = $a->esc("domain");
$domainId = (int)$a->esc("domain_id", 0, true);
$status = $a->esc("status", "n");
$primaryCheckoutDomain = $a->esc("primary_checkout_domain");

if ($action === "set_default_domain") {
    $normalizedDefault = pt_admin_normalize_domain($primaryCheckoutDomain);
    if ($normalizedDefault === '') {
        $settings->updateOption("primary_checkout_domain", "");
        $a->addSuccess("Default domain cleared.");
    } else {
        if ($featureReady) {
            $safeDomain = addslashes($normalizedDefault);
            $check = $a->query("SELECT id FROM {$domainsTable} WHERE domain = '{$safeDomain}' AND is_active='1' LIMIT 1");
            if (!$check || $check->error || $check->count < 1) {
                $a->addError("Default domain must be an active domain from the list.");
            }
        }

        if (!$a->error) {
            $settings->updateOption("primary_checkout_domain", $normalizedDefault);
            $a->addSuccess("Default domain saved.");
            st_do_action('add_user_log', "Updated default checkout domain");
        }
    }
}

if ($featureReady && $action === "add_domain") {
    $normalizedDomain = pt_admin_normalize_domain($domain);

    if ($normalizedDomain === '') {
        $a->addError("Domain is required.");
    }

    if (!$a->error) {
        $safeDomain = addslashes($normalizedDomain);
        $exists = $a->query("SELECT id, is_active FROM {$domainsTable} WHERE domain = '{$safeDomain}' LIMIT 1");

        if ($exists && !$exists->error && $exists->count > 0) {
            $row = $exists->result_row();
            if ($row['is_active'] === '1') {
                $a->addWarning("Domain already exists and is active.");
            } else {
                $a->query("UPDATE {$domainsTable} SET is_active='1' WHERE id='{$row['id']}'");
                $a->addSuccess("Domain re-activated successfully.");
                st_do_action('add_user_log', "Re-activated domain: {$normalizedDomain}");
            }
        } else {
            $a->query("INSERT INTO {$domainsTable} SET domain='{$safeDomain}', is_active='1', created_at=NOW(), updated_at=NOW()");
            $a->addSuccess("Domain added successfully.");
            st_do_action('add_user_log', "Added domain: {$normalizedDomain}");
        }
    }
}

if ($featureReady && $action === "set_domain_status") {
    $status = $status === 'y' ? '1' : '0';
    if ($domainId <= 0) {
        $a->addError("Invalid domain ID.");
    } else {
        $a->query("UPDATE {$domainsTable} SET is_active='{$status}', updated_at=NOW() WHERE id='{$domainId}'");
        $a->addSuccess($status === '1' ? "Domain activated." : "Domain deactivated.");
        st_do_action('add_user_log', ($status === '1' ? "Activated" : "Deactivated") . " domain id {$domainId}");
    }
}

if ($featureReady && $action === "hard_delete_domain") {
    if ($domainId <= 0) {
        $a->addError("Invalid domain ID.");
    } else {
        $itemDomainsTable = $db_pr . "item_domains";
        $invoicesTable = $db_pr . "invoices";

        $resDomain = $a->query("SELECT id, domain, is_active FROM {$domainsTable} WHERE id='{$domainId}' LIMIT 1");
        if (!$resDomain || $resDomain->error || $resDomain->count < 1) {
            $a->addError("Domain not found.");
        } else {
            $domainRow = $resDomain->result_row();
            $domainHost = (string)$domainRow['domain'];

            if ($domainRow['is_active'] === '1') {
                $a->addError("Deactivate domain first, then hard delete.");
            }

            if (!$a->error && pt_admin_table_exists($a, $itemDomainsTable)) {
                $mapCheck = $a->query("SELECT id FROM {$itemDomainsTable} WHERE domain_id='{$domainId}' LIMIT 1");
                if ($mapCheck && !$mapCheck->error && $mapCheck->count > 0) {
                    $a->addError("Domain is mapped to one or more items. Unassign it first.");
                }
            }

            if (!$a->error && pt_admin_table_exists($a, $invoicesTable)) {
                $safeDomain = addslashes($domainHost);
                $invCheck = $a->query("SELECT idInvoice FROM {$invoicesTable} WHERE checkout_domain='{$safeDomain}' LIMIT 1");
                if ($invCheck && !$invCheck->error && $invCheck->count > 0) {
                    $a->addError("Domain is referenced by invoice history and cannot be hard deleted.");
                }
            }

            $normalizedDefault = pt_admin_normalize_domain((string)$settings->get('primary_checkout_domain'));
            if (!$a->error && $normalizedDefault !== '' && $normalizedDefault === pt_admin_normalize_domain($domainHost)) {
                $a->addError("Domain is set as default checkout domain. Change default first.");
            }

            if (!$a->error) {
                $a->query("DELETE FROM {$domainsTable} WHERE id='{$domainId}' LIMIT 1");
                $a->addSuccess("Domain hard deleted.");
                st_do_action('add_user_log', "Hard deleted domain id {$domainId}: {$domainHost}");
            }
        }
    }
}

$domains = array();
if ($featureReady) {
    $resDomains = $a->query("SELECT id, domain, is_active FROM {$domainsTable} ORDER BY domain ASC");
    if ($resDomains && !$resDomains->error && $resDomains->count > 0) {
        $domains = $resDomains->result_array();
    }
}

$defaultDomain = (string)$settings->get('primary_checkout_domain');
$defaultDomain = pt_admin_normalize_domain($defaultDomain);

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
                <h2>Domain Rotation Settings</h2>
                <hr>

                <?php if (!$featureReady) { ?>
                    <div class="alert alert-warning">
                        Domain tables are not found. Run your Laravel migration first, then refresh this page.
                    </div>
                <?php } ?>

                <form class="validate" method="post" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="set_default_domain">
                    <div class="form-group col-md-6">
                        <label for="primary_checkout_domain">Primary/Default Checkout Domain</label>
                        <select class="form-control" name="primary_checkout_domain" id="primary_checkout_domain">
                            <option value="">No default domain</option>
                            <?php foreach ($domains as $row) {
                                if ($row['is_active'] !== '1') {
                                    continue;
                                }
                                $selected = ($defaultDomain === $row['domain']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['domain']) . '" ' . $selected . '>' . htmlspecialchars($row['domain']) . '</option>';
                            } ?>
                        </select>
                        <small>Used when an item has no active domain mapping.</small>
                    </div>
                    <div class="form-group col-md-3" style="padding-top: 24px;">
                        <button type="submit" class="btn btn-success">Save Default</button>
                    </div>
                    <div class="clearfix"></div>
                </form>

                <?php if ($featureReady) { ?>
                    <form class="validate" method="post" style="margin-bottom: 20px;">
                        <input type="hidden" name="action" value="add_domain">
                        <div class="form-group col-md-6">
                            <label for="domain"><span>*</span>Add Domain</label>
                            <input type="text" class="form-control" name="domain" id="domain" placeholder="example.com" data-rule-required="true">
                            <small>Enter host only, without protocol.</small>
                        </div>
                        <div class="form-group col-md-3" style="padding-top: 24px;">
                            <button type="submit" class="btn btn-success">Add Domain</button>
                        </div>
                        <div class="clearfix"></div>
                    </form>

                    <h3>Domains</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($domains)) { ?>
                                    <tr>
                                        <td colspan="4">No domains added yet.</td>
                                    </tr>
                                <?php } else {
                                    foreach ($domains as $row) {
                                        $isActive = $row['is_active'] === '1';
                                ?>
                                        <tr>
                                            <td><?php echo (int)$row['id'] ?></td>
                                            <td><?php echo htmlspecialchars($row['domain']) ?></td>
                                            <td><?php echo $isActive ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>' ?></td>
                                            <td>
                                                <form method="post" style="display:inline-block; margin-right: 8px;">
                                                    <input type="hidden" name="action" value="set_domain_status">
                                                    <input type="hidden" name="domain_id" value="<?php echo (int)$row['id'] ?>">
                                                    <input type="hidden" name="status" value="<?php echo $isActive ? 'n' : 'y' ?>">
                                                    <button type="submit" class="btn btn-xs <?php echo $isActive ? 'btn-warning' : 'btn-success' ?>">
                                                        <?php echo $isActive ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <?php if (!$isActive) { ?>
                                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Hard delete this domain permanently?');">
                                                        <input type="hidden" name="action" value="hard_delete_domain">
                                                        <input type="hidden" name="domain_id" value="<?php echo (int)$row['id'] ?>">
                                                        <button type="submit" class="btn btn-xs btn-danger">Hard Delete</button>
                                                    </form>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            <?php } else { ?>
                You have no permissions to view this section
            <?php } ?>
        </div>
    </div>
</div>
<?php echo($a->getFooter()) ?>
