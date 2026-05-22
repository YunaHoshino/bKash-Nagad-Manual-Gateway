<?php
/**
 * bKash Send Money – Callback / Payment Notification Handler
 *
 * Place this file in: /modules/gateways/callback/bkash_sm.php
 *
 * @version 2.0.0
 */

// ── Bootstrap WHMCS ──────────────────────────────────────────────────────────
$whmcsRoot = dirname(__FILE__, 4) . '/';
require_once $whmcsRoot . 'init.php';
require_once $whmcsRoot . 'includes/gatewayfunctions.php';
require_once $whmcsRoot . 'includes/invoicefunctions.php';

// ── Identify gateway ─────────────────────────────────────────────────────────
$gatewayModuleName = basename(__FILE__, '.php');         // "bkash_sm"
$gatewayParams     = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module not activated.");
}

// ── Collect POST data ─────────────────────────────────────────────────────────
$invoiceId     = (int)   filter_input(INPUT_POST, 'invoice_id',     FILTER_SANITIZE_NUMBER_INT);
$transactionId = trim(   filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$senderNumber  = trim(   filter_input(INPUT_POST, 'sender_number',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$amount        = (float) filter_input(INPUT_POST, 'amount',         FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$currency      = trim(   filter_input(INPUT_POST, 'currency',       FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$note          = trim(   filter_input(INPUT_POST, 'note',           FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

// ── Validate invoice ──────────────────────────────────────────────────────────
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

// ── Prevent duplicate transactions ───────────────────────────────────────────
checkCbTransID($transactionId);

// ── Build a descriptive transaction note ─────────────────────────────────────
$txnNote = "bKash Send Money | TxnID: {$transactionId} | Sender: {$senderNumber}";
if ($note) {
    $txnNote .= " | Note: {$note}";
}

// ── Add payment (sets invoice to "Payment Pending" status) ───────────────────
// Use addInvoicePayment for manual/unverified gateways so the admin can confirm.
addInvoicePayment(
    $invoiceId,
    $transactionId,
    $amount,
    0,              // fee
    $gatewayModuleName
);

// Log the transaction for the admin activity log
logTransaction(
    $gatewayParams['name'],
    [
        'invoice_id'     => $invoiceId,
        'transaction_id' => $transactionId,
        'sender_number'  => $senderNumber,
        'amount'         => $amount,
        'currency'       => $currency,
        'note'           => $note,
    ],
    'Pending Verification'
);

// ── Redirect back to invoice ──────────────────────────────────────────────────
header("Location: " . $gatewayParams['systemurl'] . "viewinvoice.php?id={$invoiceId}");
exit;
