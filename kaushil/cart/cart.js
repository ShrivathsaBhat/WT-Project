const $ = s => document.querySelector(s);
const money = n => FD.cur + Number(n || 0).toFixed(2);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

let S = null;
let addrId = null;
let addrOk = false;
let pay = 'UPI';
let tickTimer = null;
let pollTimer = null;
let statusPollTimer = null;
let payCode = null;
let currentOrderCode = null;

function toast(msg) {
  const t = $('#toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t.h);
  t.h = setTimeout(() => t.classList.remove('show'), 3000);
}

function addrError(msg) {
  const c = $('#addrCard');
  const errEl = $('#addrErr');
  if (errEl) {
    errEl.textContent = msg;
    errEl.hidden = false;
  }
  if (c) {
    c.classList.remove('err');
    void c.offsetWidth;
    c.classList.add('err');
    c.scrollIntoView({behavior: 'smooth', block: 'center'});
  }
  toast(msg);
}

function addrClear() {
  const errEl = $('#addrErr');
  if (errEl) errEl.hidden = true;
  const c = $('#addrCard');
  if (c) c.classList.remove('err');
}

async function api(action, body) {
  try {
    const isPost = body !== undefined && body !== null;
    const url = FD.api + '?action=' + action;
    const opts = isPost
      ? { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body) }
      : { method: 'GET' };
    const r = await fetch(url, opts);
    const d = await r.json();
    if (!d.success) {
      toast(d.message || 'Operation failed');
      return null;
    }
    return d.data;
  } catch (e) {
    toast('Cannot connect to server. Please check Apache and MySQL.');
    return null;
  }
}

async function upd(action, body) {
  const d = await api(action, body);
  if (d && !d.conflict) {
    S = d;
    render();
  }
  return d;
}

function render() {
  if (!S) return;
  const {items, rest, extras, b, addresses, coupons} = S;

  // Header Cart pill
  const pill = $('#cartPill');
  const totalCount = (items || []).reduce((n, i) => n + Number(i.quantity), 0);
  if (pill) {
    pill.textContent = totalCount ? `${totalCount} items · ${money(b.total)}` : '';
  }

  // Place order button state
  const placeBtn = $('#placeBtn');
  if (placeBtn) {
    placeBtn.disabled = !items || !items.length;
    placeBtn.textContent = 'Place order →';
  }

  // Restaurant card
  const restBox = $('#restBox');
  if (restBox) {
    restBox.hidden = !rest;
    if (rest) {
      restBox.innerHTML = `
        <div class="row">
          <div>
            <h2 style="margin:0">${esc(rest.name)}</h2>
            <span class="muted">${esc(rest.cuisine)} · ★ ${rest.rating} · ${rest.delivery_time || rest.delivery_mins + ' mins'}</span>
          </div>
          <button class="link" data-act="clear">Clear cart</button>
        </div>
      `;
    }
  }

  // Items list
  const itemsEl = $('#items');
  if (itemsEl) {
    if (items && items.length) {
      itemsEl.innerHTML = items.map(i => `
        <li>
          <span class="em">${esc(i.emoji || '🍽️')}</span>
          <div>
            <b>${esc(i.name)}</b>
            <div class="price">${money(i.price)} each</div>
          </div>
          <div class="step">
            <button data-act="qty" data-id="${i.id}" data-q="${i.quantity - 1}" aria-label="Decrease quantity">−</button>
            <b>${i.quantity}</b>
            <button data-act="qty" data-id="${i.id}" data-q="${i.quantity + 1}" aria-label="Increase quantity">+</button>
          </div>
          <input data-note="${i.id}" maxlength="150" value="${esc(i.instructions || '')}" placeholder="Add cooking note (e.g. less spicy, no onions)">
        </li>
      `).join('');
    } else {
      itemsEl.innerHTML = '<p class="empty">Your cart is empty. Add delicious food from the menu below!</p>';
    }
  }

  // Frequently added extras
  const extrasWrap = $('#extrasWrap');
  const extrasEl = $('#extras');
  if (extrasWrap && extrasEl) {
    extrasWrap.hidden = !extras || !extras.length;
    if (extras && extras.length) {
      extrasEl.innerHTML = extras.map(x => `
        <button class="chip add" data-act="add" data-id="${x.id}">
          ${esc(x.emoji || '🍽️')} ${esc(x.name)} · ${money(x.price)} +
        </button>
      `).join('');
    }
  }

  // Delivery Address handling
  if (addrId && !addresses.find(a => a.id == addrId)) {
    addrId = null;
    addrOk = false;
    const okEl = $('#addrOk');
    if (okEl) okEl.hidden = true;
  }
  // Auto-select default address if none chosen yet
  if (!addrId && addresses && addresses.length) {
    const def = addresses.find(a => a.is_default == 1) || addresses[0];
    if (def) {
      addrId = def.id;
      addrOk = true;
      const okEl = $('#addrOk');
      if (okEl) {
        okEl.textContent = 'Delivering to ' + def.label + ': ' + (def.house || def.line1) + ', ' + def.locality;
        okEl.hidden = false;
      }
    }
  }

  const addrEl = $('#addresses');
  if (addrEl) {
    if (addresses && addresses.length) {
      addrEl.innerHTML = addresses.map(a => `
        <label class="opt">
          <input type="radio" name="addr" value="${a.id}" ${a.id == addrId ? 'checked' : ''}>
          <span class="body">
            <span class="ttl">${esc(a.label)}${a.is_default == 1 ? ' <em class="tag">Default</em>' : ''}</span>
            <small>${esc(a.house || a.line1)}, ${esc(a.locality)}, ${esc(a.city)}, ${esc(a.state)} - ${esc(a.pincode)}</small>
          </span>
        </label>
      `).join('');
    } else {
      addrEl.innerHTML = '<p class="muted">No saved address yet. Please add an address to continue.</p>';
    }
  }

  // Coupon Section
  const cInfo = $('#couponInfo');
  if (cInfo) {
    if (b.coupon) {
      cInfo.innerHTML = `
        <p class="ok">
          <b>${esc(b.coupon)}</b> applied – you save ${money(b.discount || 0)}!
          <button class="link" data-act="uncoupon" style="margin-left:8px">Remove</button>
        </p>
      `;
    } else {
      cInfo.innerHTML = `
        <div class="chips" style="margin-top:10px">
          ${(coupons || []).map(c => `
            <button class="chip" data-act="coupon" data-code="${esc(c.code)}" title="${esc(c.description || c.title || '')}">
              ${esc(c.code)}
            </button>
          `).join('')}
        </div>
      `;
    }
  }

  // Bill summary breakdown
  const billEl = $('#bill');
  if (billEl) {
    const L = (k, v, c = '') => `<div class="l ${c}"><span>${k}</span><span>${v}</span></div>`;
    billEl.innerHTML =
      L('Item subtotal', money(b.subtotal)) +
      (b.discount > 0 ? L('Discount (' + esc(b.coupon || '') + ')', '− ' + money(b.discount), 'd') : '') +
      L('Packaging &amp; Handling', money(b.packaging)) +
      L('Government Taxes (5% GST)', money(b.tax)) +
      L('Delivery fee', b.delivery > 0 ? money(b.delivery) : 'Free') +
      `<div class="l t"><span>To pay</span><span>${money(b.total)}</span></div>`;
  }
}

async function addItem(id, quantity = 1) {
  let d = await api('add', {item_id: id, quantity});
  if (d?.conflict) {
    if (confirm(d.message)) {
      d = await api('add', {item_id: id, quantity, force: 1});
    }
  }
  if (d && !d.conflict) {
    S = d;
    render();
    toast('Item added to cart');
  }
}

document.addEventListener('click', async e => {
  const t = e.target.closest('#fd-cart [data-act]');
  if (!t) return;
  const a = t.dataset.act;
  const id = +t.dataset.id;

  if (a === 'add') {
    addItem(id);
  }
  else if (a === 'qty') {
    upd('update', {item_id: id, quantity: +t.dataset.q});
  }
  else if (a === 'clear') {
    if (confirm('Are you sure you want to clear your cart?')) {
      upd('clear', {});
      toast('Cart cleared');
    }
  }
  else if (a === 'uncoupon') {
    upd('coupon', {code: ''});
    toast('Coupon removed');
  }
  else if (a === 'coupon') {
    const code = t.dataset.code || ($('#couponIn') ? $('#couponIn').value : '');
    if (!code.trim()) {
      toast('Please enter a coupon code');
      return;
    }
    const d = await upd('coupon', {code});
    if (d?.b?.coupon_error) {
      toast(d.b.coupon_error);
    } else if (d?.b?.coupon) {
      toast('Coupon ' + d.b.coupon + ' applied successfully!');
    }
  }
  else if (a === 'chip') {
    const on = t.classList.toggle('on');
    t.setAttribute('aria-pressed', on ? 'true' : 'false');
  }
  else if (a === 'addrForm') {
    const f = $('#addrForm');
    if (f) f.hidden = !f.hidden;
  }
  else if (a === 'saveAddr') {
    const v = k => $('#a_' + k) ? $('#a_' + k).value.trim() : '';
    const body = {
      label: v('label') || 'Home',
      line1: v('line1'),
      locality: v('loc'),
      city: v('city'),
      state: v('state'),
      pincode: v('pin')
    };
    if (!body.line1 || !body.locality || !body.city || !body.state || !body.pincode) {
      toast('Please fill in all address fields');
      return;
    }
    if (!/^\d{6}$/.test(body.pincode)) {
      toast('PIN code must be a 6-digit number');
      return;
    }
    const d = await upd('address_add', body);
    if (d?.new_address_id) {
      addrId = d.new_address_id;
      addrOk = true;
      addrClear();
      const f = $('#addrForm');
      if (f) f.hidden = true;
      const okEl = $('#addrOk');
      if (okEl) {
        okEl.textContent = 'Delivering to ' + body.label + ': ' + body.line1 + ', ' + body.locality;
        okEl.hidden = false;
      }
      render();
      toast('New address saved and selected');
    }
  }
  else if (a === 'confirmAddr') {
    const ad = S?.addresses?.find(x => x.id == addrId);
    if (!ad) {
      return addrError(S?.addresses?.length ? 'Please select a delivery address from the list' : 'Please add a delivery address first');
    }
    addrOk = true;
    addrClear();
    const okEl = $('#addrOk');
    if (okEl) {
      okEl.textContent = 'Delivering to ' + ad.label + ': ' + (ad.house || ad.line1) + ', ' + ad.locality;
      okEl.hidden = false;
    }
    toast('Delivery address confirmed');
  }
  else if (a === 'fillDemoCard') {
    if ($('#card_num')) $('#card_num').value = '4532 8912 3456 7890';
    if ($('#card_exp')) $('#card_exp').value = '12/28';
    if ($('#card_cvv')) $('#card_cvv').value = '888';
    if ($('#card_name')) $('#card_name').value = 'Demo Customer';
    toast('Demo card details filled');
  }
  else if (a === 'resetDemo') {
    const d = await api('reset_demo');
    if (d) {
      S = d;
      render();
      toast('Demo cart reloaded with sample dishes');
    }
  }
  else if (a === 'simPay') {
    t.disabled = true;
    t.textContent = 'Simulating payment...';
    const res = await api('pay_simulate', {code: payCode});
    if (res) {
      clearInterval(pollTimer);
      showConfirm(payCode);
    } else {
      t.disabled = false;
      t.textContent = "Can't scan? Simulate Instant Approval";
    }
  }
  else if (a === 'place') {
    if (!S || !S.items || !S.items.length) {
      toast('Your cart is empty. Add items to checkout.');
      return;
    }
    if (!addrId) {
      return addrError(S.addresses && S.addresses.length ? 'Please select a delivery address' : 'Please add a delivery address');
    }
    if (!addrOk) {
      addrOk = true;
    }

    const chips = [...document.querySelectorAll('#fd-cart .chip.on')].map(c => c.dataset.val || c.textContent.trim());
    const customTxt = $('#custom') ? $('#custom').value.trim() : '';
    const instructions = [...chips, customTxt].filter(Boolean).join('; ');

    const payload = {
      address_id: addrId,
      payment: pay,
      instructions: instructions
    };

    if (pay === 'Card') {
      payload.card_number = $('#card_num') ? $('#card_num').value : '';
      payload.card_expiry = $('#card_exp') ? $('#card_exp').value : '';
      payload.card_cvv = $('#card_cvv') ? $('#card_cvv').value : '';
      payload.card_name = $('#card_name') ? $('#card_name').value : '';
      const outcomeEl = document.querySelector('input[name="card_sim_result"]:checked');
      payload.card_outcome = outcomeEl ? outcomeEl.value : 'success';
    }

    t.disabled = true;
    t.textContent = 'Processing order...';

    const d = await api('place_order', payload);
    if (d?.order_code) {
      history.pushState({}, '', '?order=' + d.order_code);
      if (d.card_failed) {
        t.disabled = false;
        t.textContent = 'Place order →';
        toast(d.message || 'Card payment declined. Please try again.');
        return;
      }
      if (d.pay_url) {
        showPay(d.order_code, d.pay_url, d.total);
      } else {
        showConfirm(d.order_code);
      }
    } else {
      t.disabled = false;
      t.textContent = 'Place order →';
    }
  }
});

document.addEventListener('change', e => {
  if (e.target.name === 'addr') {
    addrId = +e.target.value;
    addrOk = false;
    const okEl = $('#addrOk');
    if (okEl) okEl.hidden = true;
    addrClear();
  }
  if (e.target.name === 'pay') {
    pay = e.target.value;
    const cardSim = $('#cardSimBox');
    if (cardSim) {
      cardSim.hidden = (pay !== 'Card');
    }
  }
  if (e.target.dataset.note) {
    api('note', {item_id: +e.target.dataset.note, text: e.target.value});
  }
});

function renderTracker(status) {
  const st = ['Placed', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered'];
  const ic = ['🧾', '✅', '👨‍🍳', '🛵', '🏠'];
  const sub = ['Order received', 'Restaurant accepted', 'Chef cooking', 'Rider on the way', 'Delivered at door'];
  const cur = Math.max(0, st.indexOf(status));
  const trackEl = $('#track');
  if (trackEl) {
    trackEl.innerHTML = st.map((s, i) => `
      <li data-i="${ic[i]}" class="${i <= cur ? 'done' : ''} ${i === cur ? 'now' : ''}">
        <b>${s}</b>
        <small class="track-sub">${sub[i]}</small>
      </li>
    `).join('');
  }
}

async function showConfirm(code) {
  clearInterval(pollTimer);
  clearInterval(statusPollTimer);
  currentOrderCode = code;
  const o = await api('order&code=' + encodeURIComponent(code));
  if (!o) return;

  const payview = $('#payview');
  if (payview) payview.hidden = true;
  const checkout = $('#checkout');
  if (checkout) checkout.hidden = true;
  const confirmEl = $('#confirm');
  if (confirmEl) confirmEl.hidden = false;

  const pill = $('#cartPill');
  if (pill) pill.textContent = ''; // Cart is cleared after order
  window.scrollTo({top: 0, behavior: 'smooth'});

  if ($('#c_rest')) $('#c_rest').textContent = o.restaurant;
  if ($('#c_code')) $('#c_code').textContent = 'Order #' + o.order_code;

  let currentStatus = o.status;
  renderTracker(currentStatus);

  // Auto-check for status progression (e.g. Placed -> Confirmed -> Preparing) every 3 seconds
  statusPollTimer = setInterval(async () => {
    const fresh = await api('order&code=' + encodeURIComponent(code));
    if (fresh && fresh.status && fresh.status !== currentStatus) {
      currentStatus = fresh.status;
      renderTracker(currentStatus);
      toast(`Order update: Now ${currentStatus}!`);
      if (currentStatus === 'Delivered') {
        clearInterval(statusPollTimer);
      }
    }
  }, 3000);

  // Delivery Details
  const pmMap = {UPI: 'UPI / QR Code', Card: 'Credit / Debit Card', COD: 'Cash on Delivery'};
  const pmLabel = pmMap[o.payment_method] || o.payment_method;
  const isPaid = (o.payment_status === 'Paid');
  const detailsEl = $('#c_details');
  if (detailsEl) {
    const R = (icon, k, v) => `
      <div class="r">
        <span class="ic">${icon}</span>
        <div><small>${k}</small><b>${v}</b></div>
      </div>
    `;
    const statusBadge = `<span class="badge ${isPaid ? 'paid' : 'pend'}">${isPaid ? 'Paid' : (o.payment_method === 'COD' ? 'Pay on delivery' : 'Pending')}</span>`;
    detailsEl.innerHTML =
      R('📍', 'Deliver to Address', esc(o.address_text || o.delivery_address)) +
      R('📝', 'Delivery Instructions', esc(o.delivery_instructions || o.delivery_note || 'None')) +
      R('💳', 'Payment Method &amp; Status', `${esc(pmLabel)} ${statusBadge}`);
  }

  // Item Receipt
  const rcptEl = $('#c_receipt');
  if (rcptEl) {
    const L = (k, v, c = '') => `<div class="l ${c}"><span>${k}</span><span>${v}</span></div>`;
    rcptEl.innerHTML =
      (o.items || []).map(i => L(
        `${i.quantity}× ${esc(i.name)}${i.instructions ? `<br><small class="muted">Note: ${esc(i.instructions)}</small>` : ''}`,
        money(i.price * i.quantity),
        'it'
      )).join('') +
      L('Subtotal', money(o.subtotal), 'sum') +
      (Number(o.discount) > 0 ? L('Discount (' + esc(o.coupon_code || '') + ')', '− ' + money(o.discount), 'sum d') : '') +
      L('Packaging &amp; Handling', money(o.packaging), 'sum') +
      L('Government Taxes (5% GST)', money(o.tax), 'sum') +
      L('Delivery fee', Number(o.delivery_fee) > 0 ? money(o.delivery_fee) : 'Free', 'sum') +
      L(isPaid ? 'Total paid' : 'Total payable', money(o.total), 'tot');
  }

  // Action buttons
  const trackBtn = $('#trackBtn');
  if (trackBtn) {
    trackBtn.onclick = () => {
      location.href = FD.track + '?code=' + encodeURIComponent(code);
    };
  }
  const backBtn = $('#backBtn');
  if (backBtn) {
    backBtn.onclick = () => {
      location.href = FD.menu;
    };
  }

  // Live countdown timer based on stored eta_seconds
  let left = Math.max(0, Number(o.eta_seconds || 0));
  clearInterval(tickTimer);
  const drawEta = () => {
    const etaEl = $('#c_eta');
    if (!etaEl) return;
    if (left <= 0) {
      etaEl.textContent = '00:00';
      const sm = etaEl.nextElementSibling;
      if (sm) sm.textContent = 'Arriving any moment!';
    } else {
      const mm = String(Math.floor(left / 60)).padStart(2, '0');
      const ss = String(left % 60).padStart(2, '0');
      etaEl.textContent = `${mm}:${ss}`;
    }
  };
  drawEta();
  tickTimer = setInterval(() => {
    left = Math.max(0, left - 1);
    drawEta();
    if (left <= 0) clearInterval(tickTimer);
  }, 1000);
}

function showPay(code, url, total) {
  payCode = code;
  clearInterval(statusPollTimer);
  const checkout = $('#checkout');
  if (checkout) checkout.hidden = true;
  const confirmEl = $('#confirm');
  if (confirmEl) confirmEl.hidden = true;
  const payview = $('#payview');
  if (payview) payview.hidden = false;

  if ($('#p_code')) $('#p_code').textContent = '#' + code;
  if ($('#p_amt')) $('#p_amt').textContent = money(total);

  const qrEl = $('#qr');
  if (qrEl) {
    qrEl.innerHTML = '';
    if (window.QRCode) {
      new QRCode(qrEl, {text: url, width: 220, height: 220});
    } else {
      qrEl.innerHTML = `<a href="${esc(url)}" target="_blank" class="link">Open Simulated Payment Link</a>`;
    }
  }

  const pLink = $('#p_link');
  if (pLink) {
    pLink.href = url;
    pLink.textContent = '📱 Click here to simulate scanning via phone / new tab';
  }
  const pUrl = $('#p_url');
  if (pUrl) {
    pUrl.textContent = url;
  }

  // Poll payment status every 2 seconds
  clearInterval(pollTimer);
  pollTimer = setInterval(async () => {
    const d = await fetch(FD.api + '?action=pay_status&code=' + encodeURIComponent(code))
      .then(r => r.json())
      .catch(() => null);
    if (d?.success && d.data?.paid) {
      clearInterval(pollTimer);
      showConfirm(code);
    }
  }, 2000);
}

// Initial page bootstrap
(async () => {
  const code = new URLSearchParams(location.search).get('order');
  if (code) {
    const o = await api('order&code=' + encodeURIComponent(code));
    if (o) {
      if (o.payment_method === 'UPI' && o.payment_status === 'Pending' && o.pay_url) {
        return showPay(code, o.pay_url, o.total);
      }
      return showConfirm(code);
    }
  }

  // Load cart data
  S = await api('cart');
  if (S) render();
})();
