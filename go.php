<?php

/**
 * Domain rotation redirect entry point.
 * Accepts item_id/service or idInvoice and redirects to a selected checkout domain.
 */

include_once __DIR__ . '/includes/bootstrap.php';

function pt_go_block_request()
{
    // Intentionally blank response for blocked/unsupported access.
    http_response_code(200);
    exit;
}

$itemId = trim((string)$c->esc('item_id', ''));
if ($itemId === '') {
    $itemId = trim((string)$c->esc('service', ''));
}

$idInvoice = (int)$c->esc('idInvoice', 0, true);

if ($itemId === '' && $idInvoice > 0) {
    $resolvedItem = pt_get_item_id_by_invoice($idInvoice);
    if ($resolvedItem !== false) {
        $itemId = $resolvedItem;
    }
}

$currentHost = pt_get_request_host();
if ($itemId === '' || $currentHost === '') {
    pt_go_block_request();
}

// Select target domain from item mapping (or default checkout domain fallback).
$targetHost = pt_select_rotated_domain($itemId, $idInvoice);
if ($targetHost === false) {
    pt_go_block_request();
}

$token = pt_build_domain_redirect_token($itemId, $idInvoice, $targetHost, 300);
if ($token === false) {
    pt_go_block_request();
}

$protocol = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http';

$query = array(
    'service' => $itemId,
    'drt' => $token,
    'from_go' => 1,
);

if ($idInvoice > 0) {
    $query['idInvoice'] = $idInvoice;
}

// Keep common tracking params in the redirect.
$passthroughKeys = array('clickid', 'source', 'ctc');
foreach ($passthroughKeys as $key) {
    $val = trim((string)$c->esc($key, ''));
    if ($val !== '') {
        $query[$key] = $val;
    }
}

$destination = $protocol . '://' . $targetHost . '/index.php?' . http_build_query($query);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

header('Location: ' . $destination, true, 302);
exit;
