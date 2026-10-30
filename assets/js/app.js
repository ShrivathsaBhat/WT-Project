(function () {
  const root = document.getElementById('progress');
  if (!root || !window.FD_ORDER_ID) return;
  const orderId = Number(window.FD_ORDER_ID);
  const steps = ['Placed', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered'];
  function render(status) {
    root.dataset.status = status;
    const index = steps.indexOf(status);
    root.querySelectorAll('.progress-step').forEach((el) => {
      const stepIndex = steps.indexOf(el.dataset.step);
      el.classList.toggle('complete', index >= 0 && stepIndex < index);
      el.classList.toggle('current', stepIndex === index);
      const dot = el.querySelector('.step-dot');
      if (dot) dot.textContent = (index >= 0 && stepIndex < index) ? '✓' : String(stepIndex + 1);
    });
    const pill = document.getElementById('status-pill');
    if (pill) { pill.textContent = status; pill.className = 'status ' + status.toLowerCase().replaceAll(' ', '-'); }
  }
  async function refresh() {
    try {
      const response = await fetch('../api/order-status.php?id=' + encodeURIComponent(orderId), { headers: { 'Accept': 'application/json' } });
      if (!response.ok) return;
      const result = await response.json();
      if (result.success) {
        render(result.data.status);
        const stamp = document.getElementById('last-updated');
        if (stamp) stamp.textContent = 'Last checked: ' + new Date().toLocaleTimeString();
      }
    } catch (_) { /* keep last known status if temporarily offline */ }
  }
  render(root.dataset.status);
  window.setInterval(refresh, 15000);
})();
