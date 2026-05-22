<?php
/**
 * Nagad (Send Money) Manual Payment Gateway
 *
 * Compatible with WHMCS 8.x+
 * Place this file in: /modules/gateways/nagad_sm.php
 *
 * @author   Custom Gateway
 * @version  2.0.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function nagad_sm_MetaData()
{
    return [
        'DisplayName'              => 'Nagad (Send Money)',
        'APIVersion'               => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage'         => false,
    ];
}

function nagad_sm_config()
{
    return [
        'FriendlyName' => [
            'Type'  => 'System',
            'Value' => 'Nagad (Send Money)',
        ],
        'nagad_number' => [
            'FriendlyName' => 'Nagad Number',
            'Type'         => 'text',
            'Size'         => '30',
            'Default'      => '01XXXXXXXXX',
            'Description'  => 'Your personal/merchant Nagad number where customers will send money.',
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

function nagad_sm_link($params)
{
    $nagadNumber  = htmlspecialchars($params['nagad_number'] ?? '01XXXXXXXXX');
    $accountType  = htmlspecialchars($params['account_type'] ?? 'Personal');
    $instructions = nl2br(htmlspecialchars($params['instructions'] ?? ''));
    $showQr       = !empty($params['show_qr']);

    $invoiceId    = (int) $params['invoiceid'];
    $invoiceNum   = htmlspecialchars($params['invoicenum']);
    $amount       = htmlspecialchars($params['amount']);
    $currency     = htmlspecialchars($params['currency']);
    $clientName   = htmlspecialchars($params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname']);

    $systemUrl    = $params['systemurl'];
    $langPayNow   = htmlspecialchars($params['langpaynow'] ?? 'Submit Payment');

    $callbackUrl  = $systemUrl . 'modules/gateways/callback/nagad_sm.php';

    $qrSection = '';
    if ($showQr) {
        $qrSection = <<<HTML
        <div class="ngsm-qr">
            <div class="ngsm-qr-icon">
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
            <p class="ngsm-qr-note">Use your Nagad app to scan the merchant QR code, or send manually to the number above.</p>
        </div>
HTML;
    }

    return <<<HTML
<style>
  /* ── Nagad Send Money Gateway Styles ── */
  .ngsm-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 520px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,0.12);
  }

  /* Header */
  .ngsm-header {
    background: linear-gradient(135deg, #f05a22 0%, #c94a18 100%);
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #fff;
  }
  .ngsm-logo {
    width: 48px;
    height: 48px;
    background: #fff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .ngsm-logo svg { width: 32px; height: 32px; }
  .ngsm-header-text h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: .3px;
  }
  .ngsm-header-text p {
    margin: 2px 0 0;
    font-size: .82rem;
    opacity: .85;
  }

  /* Body */
  .ngsm-body {
    background: #ffffff;
    padding: 24px 28px;
  }

  /* Amount badge */
  .ngsm-amount-badge {
    background: #fff8f5;
    border: 2px solid #fdddd0;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  .ngsm-amount-badge .label {
    font-size: .8rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 600;
  }
  .ngsm-amount-badge .value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #f05a22;
  }
  .ngsm-amount-badge .inv {
    font-size: .78rem;
    color: #aaa;
    margin-top: 2px;
  }

  /* Info rows */
  .ngsm-info {
    background: #f9f9f9;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 18px;
  }
  .ngsm-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: .875rem;
    border-bottom: 1px solid #efefef;
  }
  .ngsm-info-row:last-child { border-bottom: none; }
  .ngsm-info-row .k { color: #888; font-weight: 500; }
  .ngsm-info-row .v {
    font-weight: 700;
    color: #222;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .ngsm-copy-btn {
    background: #fdddd0;
    border: none;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: .72rem;
    color: #f05a22;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
  }
  .ngsm-copy-btn:hover { background: #fac8b4; }

  /* Instructions */
  .ngsm-instructions {
    font-size: .84rem;
    color: #555;
    line-height: 1.6;
    margin-bottom: 18px;
    padding: 12px 14px;
    background: #fffbf8;
    border-left: 3px solid #f05a22;
    border-radius: 0 8px 8px 0;
  }

  /* QR placeholder */
  .ngsm-qr {
    text-align: center;
    padding: 16px;
    background: #fafafa;
    border: 2px dashed #e8e8e8;
    border-radius: 10px;
    margin-bottom: 18px;
  }
  .ngsm-qr-icon { color: #ccc; }
  .ngsm-qr-icon svg { width: 64px; height: 64px; }
  .ngsm-qr-icon span { display: block; font-size: .78rem; color: #aaa; margin-top: 4px; }
  .ngsm-qr-note { font-size: .78rem; color: #aaa; margin: 8px 0 0; }

  /* Form fields */
  .ngsm-field { margin-bottom: 14px; }
  .ngsm-field label {
    display: block;
    font-size: .82rem;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
  }
  .ngsm-field input,
  .ngsm-field textarea {
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
  .ngsm-field input:focus,
  .ngsm-field textarea:focus {
    border-color: #f05a22;
    box-shadow: 0 0 0 3px rgba(240,90,34,.12);
  }
  .ngsm-field .hint {
    font-size: .75rem;
    color: #aaa;
    margin-top: 4px;
  }

  /* Submit button */
  .ngsm-submit {
    width: 100%;
    background: linear-gradient(135deg, #f05a22 0%, #c94a18 100%);
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
  .ngsm-submit:hover  { opacity: .92; }
  .ngsm-submit:active { transform: scale(.98); }

  /* Footer note */
  .ngsm-footer-note {
    text-align: center;
    font-size: .75rem;
    color: #bbb;
    margin-top: 14px;
  }
</style>

<div class="ngsm-wrap">

  <!-- Header -->
  <div class="ngsm-header">
    <div class="ngsm-logo">
      <!-- Nagad-style "N" logo -->
      <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="20" r="20" fill="#f05a22"/>
        <text x="20" y="27" text-anchor="middle" font-size="22" font-weight="900" fill="#fff" font-family="Arial,sans-serif">N</text>
      </svg>
    </div>
    <div class="ngsm-header-text">
      <h2>Nagad Send Money</h2>
      <p>Manual Bank Transfer · {$accountType} Account</p>
    </div>
  </div>

  <!-- Body -->
  <div class="ngsm-body">

    <!-- Amount -->
    <div class="ngsm-amount-badge">
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

    <!-- Nagad account info -->
    <div class="ngsm-info">
      <div class="ngsm-info-row">
        <span class="k">Send Money To</span>
        <span class="v">
          {$nagadNumber}
          <button class="ngsm-copy-btn" onclick="navigator.clipboard.writeText('{$nagadNumber}').then(()=>{this.textContent='✓ Copied';setTimeout(()=>{this.textContent='Copy'},2000)});return false;">Copy</button>
        </span>
      </div>
      <div class="ngsm-info-row">
        <span class="k">Account Type</span>
        <span class="v">{$accountType}</span>
      </div>
      <div class="ngsm-info-row">
        <span class="k">Payment Type</span>
        <span class="v">Send Money</span>
      </div>
      <div class="ngsm-info-row">
        <span class="k">Reference / Note</span>
        <span class="v">Invoice #{$invoiceNum}</span>
      </div>
    </div>

    <!-- Instructions -->
    <div class="ngsm-instructions">{$instructions}</div>

    {$qrSection}

    <!-- Transaction form -->
    <form method="post" action="{$callbackUrl}">
      <input type="hidden" name="invoice_id"  value="{$invoiceId}">
      <input type="hidden" name="invoice_num" value="{$invoiceNum}">
      <input type="hidden" name="amount"      value="{$amount}">
      <input type="hidden" name="currency"    value="{$currency}">

      <div class="ngsm-field">
        <label for="ngsm_txn">Nagad Transaction ID *</label>
        <input type="text" id="ngsm_txn" name="transaction_id"
               placeholder="e.g. ABCD1234XY" required autocomplete="off">
        <div class="hint">Found in your Nagad app under "Transaction History"</div>
      </div>

      <div class="ngsm-field">
        <label for="ngsm_sender">Sender Nagad Number *</label>
        <input type="tel" id="ngsm_sender" name="sender_number"
               placeholder="01XXXXXXXXX" required>
      </div>

      <div class="ngsm-field">
        <label for="ngsm_note">Additional Note (optional)</label>
        <textarea id="ngsm_note" name="note" rows="2"
                  placeholder="Any extra details for the admin..."></textarea>
      </div>

      <button type="submit" class="ngsm-submit">✓ &nbsp;{$langPayNow}</button>
    </form>

    <p class="ngsm-footer-note">Your payment will be verified manually within 24 hours. Do not send duplicate payments.</p>
  </div>

</div>
HTML;
}
