<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'Cart & Checkout';
$extraCss = [BASE_URL . '/cart/cart.css?v=' . filemtime(__DIR__ . '/cart.css')];
include __DIR__ . '/../includes/header.php';
?>
<div id="fd-cart">
<div id="checkout" class="wrap">
  <section class="col">
    <!-- Restaurant Information Card -->
    <div class="card" id="restBox"></div>

    <!-- Order Items Card -->
    <div class="card">
      <div class="row">
        <h2>Your order items</h2>
        <small class="muted">All prices include applicable taxes</small>
      </div>
      <ul id="items" class="items"></ul>
      <div id="extrasWrap" hidden>
        <h3>Frequently added extras</h3>
        <div id="extras" class="chips"></div>
      </div>
    </div>

    <!-- Delivery Instructions Card -->
    <div class="card">
      <h2>Delivery &amp; Drop-off instructions</h2>
      <div class="chips">
        <button class="chip" data-act="chip" data-val="Leave at door">🚪 Leave at door</button>
        <button class="chip" data-act="chip" data-val="Avoid calling">📵 Avoid calling</button>
        <button class="chip" data-act="chip" data-val="Ring doorbell">🔔 Ring doorbell</button>
        <button class="chip" data-act="chip" data-val="Leave with guard">👮 Leave with guard</button>
      </div>
      <textarea id="custom" maxlength="150" placeholder="Add custom delivery instructions (e.g. Gate code #401, call when outside)..."></textarea>
    </div>

    <!-- Menu placeholder (Module 2 integration point) -->
    <div class="card" id="menuCard">
      <div class="row">
        <h2>Explore Menu</h2>
        <button class="link" data-act="resetDemo" title="Populate sample dishes into cart">Reset Demo Cart</button>
      </div>
      <div id="menu" class="menu"></div>
    </div>
  </section>

  <aside class="col">
    <!-- Delivery Address Selection Card -->
    <div class="card" id="addrCard">
      <div class="row">
        <h2>Delivery address</h2>
        <button class="link" data-act="addrForm" id="addrToggleBtn">+ New address</button>
      </div>
      <p class="muted hint">Choose where we should deliver this order, then click confirm.</p>
      <div id="addresses"></div>
      <p id="addrErr" class="errmsg" hidden></p>
      
      <!-- New Address Form -->
      <div id="addrForm" class="grid2" hidden>
        <select id="a_label" class="full" style="padding:9px 11px;border-radius:8px;border:1px solid var(--line);font:inherit">
          <option value="Home">Home (Flat / Residential)</option>
          <option value="Work">Work (Office / Campus)</option>
          <option value="Other">Other</option>
        </select>
        <input id="a_pin" placeholder="6-Digit PIN code" maxlength="6" inputmode="numeric">
        <input id="a_line1" class="full" placeholder="House / Flat / Building, Street">
        <input id="a_loc" placeholder="Locality / Landmark">
        <input id="a_city" placeholder="City" value="Udupi">
        <input id="a_state" class="full" placeholder="State" value="Karnataka">
        <button class="btn full" data-act="saveAddr">Save address</button>
      </div>

      <button class="btn dark full" data-act="confirmAddr" id="confirmAddrBtn" style="margin-top:10px;width:100%">Confirm delivery address</button>
      <p id="addrOk" class="ok" hidden></p>
    </div>

    <!-- Coupon Section -->
    <div class="card">
      <h2>Apply discount coupon</h2>
      <div class="inline">
        <input id="couponIn" placeholder="Enter coupon code (e.g. NEXUS50)">
        <button class="btn dark" data-act="coupon">Apply</button>
      </div>
      <div id="couponInfo"></div>
    </div>

    <!-- Payment Method Card -->
    <div class="card" id="payCard">
      <h2>Select payment method <small class="muted">(simulated)</small></h2>
      <label class="opt">
        <input type="radio" name="pay" value="UPI" checked>
        <span class="body">
          <span class="ttl">📱 UPI / QR Code</span>
          <small>Google Pay, PhonePe, Paytm, BHIM (simulated QR scan)</small>
        </span>
      </label>
      
      <label class="opt">
        <input type="radio" name="pay" value="Card">
        <span class="body">
          <span class="ttl">💳 Credit / Debit Card</span>
          <small>Visa, Mastercard, RuPay (simulated test card)</small>
        </span>
      </label>

      <!-- Simulated Card Details Form (shown when Card is selected) -->
      <div id="cardSimBox" class="card-sim" hidden>
        <div class="card-sim-head">
          <span>Demo Card Simulator</span>
          <button type="button" class="link" data-act="fillDemoCard">Auto-fill Demo Card</button>
        </div>
        <div class="grid2">
          <input id="card_num" class="full" placeholder="16-digit card number" maxlength="19" value="4532 8912 3456 7890">
          <input id="card_exp" placeholder="MM/YY" maxlength="5" value="12/28">
          <input id="card_cvv" placeholder="CVV" maxlength="4" value="888">
          <input id="card_name" class="full" placeholder="Cardholder Name" value="Demo Customer">
        </div>
        <div class="card-sim-outcome">
          <span style="font-size:12px;font-weight:600;display:block;margin-bottom:6px">Test Simulation Outcome:</span>
          <label style="font-size:13px;display:inline-flex;align-items:center;gap:6px;margin-right:14px;cursor:pointer">
            <input type="radio" name="card_sim_result" value="success" checked> ✓ Simulate Success
          </label>
          <label style="font-size:13px;display:inline-flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="card_sim_result" value="fail"> ✗ Simulate Card Failure (test error)
          </label>
        </div>
      </div>

      <label class="opt">
        <input type="radio" name="pay" value="COD">
        <span class="body">
          <span class="ttl">💵 Cash on Delivery (COD)</span>
          <small>Pay with cash or QR code when your food arrives</small>
        </span>
      </label>
    </div>

    <!-- Bill Details Card -->
    <div class="card bill">
      <h2>Bill details</h2>
      <div id="bill"></div>
      <button class="btn big" id="placeBtn" data-act="place" disabled>Place order →</button>
    </div>
  </aside>
</div>

<!-- Order Confirmation Screen (PDF page 10 design) -->
<div id="confirm" class="wrap" hidden>
  <div class="hero">
    <div class="ok-ring">
      <svg viewBox="0 0 52 52" aria-hidden="true">
        <circle cx="26" cy="26" r="24"/>
        <path d="M14 27l8 8 16-17"/>
      </svg>
    </div>
    <div>
      <p class="eyebrow">Order Successfully Placed!</p>
      <h1 id="c_code"></h1>
      <p>Thank you for ordering from <b id="c_rest"></b></p>
    </div>
    <div class="eta">
      <small>ESTIMATED DELIVERY</small>
      <span id="c_eta">--:--</span>
      <small>mins remaining</small>
    </div>
  </div>

  <div class="card">
    <h2>Live Delivery Tracker</h2>
    <ol class="track" id="track"></ol>
  </div>

  <div class="two">
    <div class="card">
      <h2>Delivery Details</h2>
      <div id="c_details" class="info"></div>
    </div>
    <div class="card">
      <div class="row">
        <h2>Item Receipt</h2>
        <button class="link" onclick="window.print()">🖨 Print Receipt</button>
      </div>
      <div id="c_receipt" class="rcpt"></div>
    </div>
  </div>

  <p class="center actions">
    <button class="btn" id="trackBtn">Track Order →</button>
    <button class="btn ghost" id="backBtn">← Back to Restaurant Menu</button>
  </p>
</div>

<!-- Simulated UPI / QR Payment View -->
<div id="payview" class="wrap" hidden>
  <div class="card paycard">
    <h2>Scan to Pay (UPI Simulation)</h2>
    <p class="muted">Order <b id="p_code"></b> · Amount to Pay <b id="p_amt"></b></p>
    <div id="qr"></div>
    <p><span class="pulse"></span>Waiting for payment… Scan this QR code with any UPI app camera (GPay / PhonePe / Paytm).</p>
    <p><a id="p_link" href="#" target="_blank" class="link" style="word-break:break-all;font-size:13px">📱 Click here to simulate scanning via phone browser</a></p>
    <p class="muted" id="p_url" style="font-size:11px"></p>
    <button class="btn dark" data-act="simPay" style="width:100%;margin-top:12px">Can't scan? Simulate Instant Approval</button>
  </div>
</div>

<div id="toast" role="status" aria-live="polite"></div>
</div>

<script>
window.FD = {
  api: '<?= BASE_URL ?>/api/cart.php',
  cur: '<?= CURRENCY ?>',
  track: '<?= BASE_URL ?>/orders/track.php',
  menu: '<?= BASE_URL ?>/cart/index.php'
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?= BASE_URL ?>/cart/cart.js?v=<?= filemtime(__DIR__ . '/cart.js') ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
