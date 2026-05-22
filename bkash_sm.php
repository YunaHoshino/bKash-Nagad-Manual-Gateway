<?php
/**
 * bKash (Send Money) Manual Payment Gateway
 *
 * Compatible with WHMCS 8.x+
 * Place this file in: /modules/gateways/bkash_sm.php
 *
 * @author   Custom Gateway
 * @version  2.0.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function bkash_sm_MetaData()
{
    return [
        'DisplayName'              => 'bKash (Send Money)',
        'APIVersion'               => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage'         => false,
    ];
}

function bkash_sm_config()
{
    return [
        'FriendlyName' => [
            'Type'  => 'System',
            'Value' => 'bKash (Send Money)',
        ],
        'bkash_number' => [
            'FriendlyName' => 'bKash Number',
            'Type'         => 'text',
            'Size'         => '30',
            'Default'      => '01XXXXXXXXX',
            'Description'  => 'Your personal/merchant bKash number where customers will send money.',
        ],
        'account_type' => [
            'FriendlyName' => 'Account Type',
            'Type'         => 'text',
            'Size'         => '30',
            'Default'      => 'Personal',
            'Description'  => 'e.g. Personal or Merchant',
        ],
        'instructions' => [
            'FriendlyName' => 'Additional Instructions',
            'Type'         => 'textarea',
            'Rows'         => '4',
            'Default'      => 'Please send the exact amount and include your Invoice ID as a reference. After payment, submit the Transaction ID below.',
            'Description'  => 'Extra instructions displayed on the payment page.',
        ],
        'show_qr' => [
            'FriendlyName' => 'Show QR Code Placeholder',
            'Type'         => 'yesno',
            'Description'  => 'Show a QR code icon/placeholder section on the payment page.',
        ],
    ];
}

function bkash_sm_link($params)
{
    $bkashNumber  = htmlspecialchars($params['bkash_number'] ?? '01XXXXXXXXX');
    $accountType  = htmlspecialchars($params['account_type'] ?? 'Personal');
    $instructions = nl2br(htmlspecialchars($params['instructions'] ?? ''));
    $showQr       = !empty($params['show_qr']);

    $invoiceId    = (int) $params['invoiceid'];
    $invoiceNum   = htmlspecialchars($params['invoicenum']);
    $amount       = htmlspecialchars($params['amount']);
    $currency     = htmlspecialchars($params['currency']);
    $clientName   = htmlspecialchars($params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname']);

    $systemUrl    = $params['systemurl'];
    $returnUrl    = $params['returnurl'];
    $langPayNow   = htmlspecialchars($params['langpaynow'] ?? 'Submit Payment');

    // Where WHMCS will redirect after the form POST (callback URL handled below)
    $callbackUrl  = $systemUrl . 'modules/gateways/callback/bkash_sm.php';

    $qrSection = '';
    if ($showQr) {
        $qrSection = <<<HTML
        <div class="bksm-qr">
            <div class="bksm-qr-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                    <rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                    <rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/>
                    <path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/>
                </svg>
                <span>Scan to Pay</span>
            </div>
            <p class="bksm-qr-note">Use your bKash app to scan the merchant QR code, or send manually to the number above.</p>
        </div>
HTML;
    }

    return <<<HTML
<style>
  /* ── bKash Send Money Gateway Styles ── */
  .bksm-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 520px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,0.12);
  }

  /* Header */
  .bksm-header {
    background: linear-gradient(135deg, #e2136e 0%, #b5125c 100%);
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #fff;
  }
  .bksm-logo {
    width: 48px;
    height: 48px;
    background: #fff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bksm-logo svg { width: 32px; height: 32px; }
  .bksm-header-text h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: .3px;
  }
  .bksm-header-text p {
    margin: 2px 0 0;
    font-size: .82rem;
    opacity: .85;
  }

  /* Body */
  .bksm-body {
    background: #ffffff;
    padding: 24px 28px;
  }

  /* Amount badge */
  .bksm-amount-badge {
    background: #fff5f9;
    border: 2px solid #fce0ec;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  .bksm-amount-badge .label {
    font-size: .8rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 600;
  }
  .bksm-amount-badge .value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #e2136e;
  }
  .bksm-amount-badge .inv {
    font-size: .78rem;
    color: #aaa;
    margin-top: 2px;
  }

  /* Info rows */
  .bksm-info {
    background: #f9f9f9;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 18px;
  }
  .bksm-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: .875rem;
    border-bottom: 1px solid #efefef;
  }
  .bksm-info-row:last-child { border-bottom: none; }
  .bksm-info-row .k { color: #888; font-weight: 500; }
  .bksm-info-row .v {
    font-weight: 700;
    color: #222;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .bksm-copy-btn {
    background: #fce0ec;
    border: none;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: .72rem;
    color: #e2136e;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
  }
  .bksm-copy-btn:hover { background: #f9b8d4; }

  /* Instructions */
  .bksm-instructions {
    font-size: .84rem;
    color: #555;
    line-height: 1.6;
    margin-bottom: 18px;
    padding: 12px 14px;
    background: #fffbf0;
    border-left: 3px solid #e2136e;
    border-radius: 0 8px 8px 0;
  }

  /* QR placeholder */
  .bksm-qr {
    text-align: center;
    padding: 16px;
    background: #fafafa;
    border: 2px dashed #e8e8e8;
    border-radius: 10px;
    margin-bottom: 18px;
  }
  .bksm-qr-icon { color: #ccc; }
  .bksm-qr-icon svg { width: 64px; height: 64px; }
  .bksm-qr-icon span { display: block; font-size: .78rem; color: #aaa; margin-top: 4px; }
  .bksm-qr-note { font-size: .78rem; color: #aaa; margin: 8px 0 0; }

  /* Form fields */
  .bksm-field { margin-bottom: 14px; }
  .bksm-field label {
    display: block;
    font-size: .82rem;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
  }
  .bksm-field input,
  .bksm-field textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: .9rem;
    color: #222;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
    font-family: inherit;
  }
  .bksm-field input:focus,
  .bksm-field textarea:focus {
    border-color: #e2136e;
    box-shadow: 0 0 0 3px rgba(226,19,110,.12);
  }
  .bksm-field .hint {
    font-size: .75rem;
    color: #aaa;
    margin-top: 4px;
  }

  /* Submit button */
  .bksm-submit {
    width: 100%;
    background: linear-gradient(135deg, #e2136e 0%, #b5125c 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: .3px;
    transition: opacity .2s, transform .1s;
    margin-top: 4px;
  }
  .bksm-submit:hover  { opacity: .92; }
  .bksm-submit:active { transform: scale(.98); }

  /* Footer note */
  .bksm-footer-note {
    text-align: center;
    font-size: .75rem;
    color: #bbb;
    margin-top: 14px;
  }
</style>

<div class="bksm-wrap">

  <!-- Header -->
  <div class="bksm-header">
    <div class="bksm-logo">
      <!-- bKash-style "b" logo -->
      <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="20" r="20" fill="#e2136e"/>
        <text x="20" y="27" text-anchor="middle" font-size="22" font-weight="900" fill="#fff" font-family="Arial,sans-serif">b</text>
      </svg>
    </div>
    <div class="bksm-header-text">
      <h2>bKash Send Money</h2>
      <p>Manual Bank Transfer · {$accountType} Account</p>
    </div>
  </div>

  <!-- Body -->
  <div class="bksm-body">

    <!-- Amount -->
    <div class="bksm-amount-badge">
      <div>
        <div class="label">Amount Due</div>
        <div class="value">{$amount} {$currency}</div>
        <div class="inv">Invoice #{$invoiceNum}</div>
      </div>
      <div>
        <div class="label" style="text-align:right">Customer</div>
        <div style="font-weight:600;font-size:.9rem;color:#333;text-align:right">{$clientName}</div>
      </div>
    </div>

    <!-- bKash account info -->
    <div class="bksm-info">
      <div class="bksm-info-row">
        <span class="k">Send Money To</span>
        <span class="v">
          {$bkashNumber}
          <button class="bksm-copy-btn" onclick="navigator.clipboard.writeText('{$bkashNumber}').then(()=>{this.textContent='✓ Copied';setTimeout(()=>{this.textContent='Copy'},2000)});return false;">Copy</button>
        </span>
      </div>
      <div class="bksm-info-row">
        <span class="k">Account Type</span>
        <span class="v">{$accountType}</span>
      </div>
      <div class="bksm-info-row">
        <span class="k">Payment Type</span>
        <span class="v">Send Money</span>
      </div>
      <div class="bksm-info-row">
        <span class="k">Reference / Note</span>
        <span class="v">Invoice #{$invoiceNum}</span>
      </div>
    </div>

    <!-- Instructions -->
    <div class="bksm-instructions">{$instructions}</div>

    {$qrSection}

    <!-- Transaction form -->
    <form method="post" action="{$callbackUrl}">
      <input type="hidden" name="invoice_id"  value="{$invoiceId}">
      <input type="hidden" name="invoice_num" value="{$invoiceNum}">
      <input type="hidden" name="amount"      value="{$amount}">
      <input type="hidden" name="currency"    value="{$currency}">

      <div class="bksm-field">
        <label for="bksm_txn">bKash Transaction ID *</label>
        <input type="text" id="bksm_txn" name="transaction_id"
               placeholder="e.g. 8K7H3GX2A1" required autocomplete="off">
        <div class="hint">Found in your bKash app under "My History"</div>
      </div>

      <div class="bksm-field">
        <label for="bksm_sender">Sender bKash Number *</label>
        <input type="tel" id="bksm_sender" name="sender_number"
               placeholder="01XXXXXXXXX" required>
      </div>

      <div class="bksm-field">
        <label for="bksm_note">Additional Note (optional)</label>
        <textarea id="bksm_note" name="note" rows="2"
                  placeholder="Any extra details for the admin..."></textarea>
      </div>

      <button type="submit" class="bksm-submit">✓ &nbsp;{$langPayNow}</button>
    </form>

    <p class="bksm-footer-note">Your payment will be verified manually within 24 hours. Do not send duplicate payments.</p>
  </div>

</div>
HTML;
}
