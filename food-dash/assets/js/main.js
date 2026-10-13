// Food Dash - Module 1 client-side behaviour (the server validates again on every form):
// show/hide password, live password checklist, regex validation, duplicate check (AJAX),
// password match, confirmation prompts, and auto-submit switches.
(function () {
  'use strict';

  function passwordOk(v) {
    return v.length >= 8 && v.length <= 64 && /[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v);
  }

  var RULES = {
    name:     { test: function (v) { return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v); }, msg: "Use letters, spaces, dots, hyphens or apostrophes (2-50 characters)." },
    email:    { test: function (v) { return v.length <= 100 && /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }, msg: 'Enter a valid email address, like john@example.com.' },
    phone:    { test: function (v) { return /^[6-9][0-9]{9}$/.test(v); }, msg: 'Enter a valid 10-digit mobile number starting with 6-9.' },
    line:     { test: function (v) { return v.length >= 3 && v.length <= 100; }, msg: 'Enter 3-100 characters.' },
    place:    { test: function (v) { return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v); }, msg: 'Use letters only (2-50 characters).' },
    pin:      { test: function (v) { return /^[1-9][0-9]{5}$/.test(v); }, msg: 'Enter a 6-digit PIN code that does not start with 0.' },
    password: { test: passwordOk, msg: 'Use 8-64 characters with uppercase and lowercase letters, a number and a special symbol.' }
  };

  var CHECKS = [
    function (v) { return v.length >= 8; },
    function (v) { return /[a-z]/.test(v) && /[A-Z]/.test(v); },
    function (v) { return /\d/.test(v); },
    function (v) { return /[^A-Za-z0-9]/.test(v); }
  ];
  var LEVEL_TEXT = ['Too Short', 'Too Short', 'Weak', 'Good', 'Strong'];

  function setState(input, message) {
    var field = input.closest('.field');
    if (!field) return !message;
    var err = field.querySelector('.error');
    field.classList.toggle('has-error', !!message);
    if (err) err.textContent = message || '';
    return !message;
  }

  // Returns true when the field is valid (or not yet required)
  function validateInput(input) {
    if (input.hasAttribute('data-match')) {
      var other = document.getElementById(input.getAttribute('data-match'));
      if (!input.value) return setState(input, 'Please confirm your password.');
      return setState(input, other && other.value !== input.value ? 'The two passwords do not match.' : '');
    }
    var rule = RULES[input.getAttribute('data-validate')];
    if (!rule) return true;
    var value = input.getAttribute('data-validate') === 'password' ? input.value : input.value.trim();
    if (!value) return setState(input, input.required ? 'This field is required.' : '');
    return setState(input, rule.test(value) ? '' : rule.msg);
  }

  function updateMeter(input) {
    var field = input.closest('.field');
    var results = CHECKS.map(function (fn) { return fn(input.value); });
    var passed = results.filter(Boolean).length;
    field.querySelectorAll('.rules li').forEach(function (li, i) { li.classList.toggle('ok', results[i]); });
    var bar = field.querySelector('.bar');
    if (bar) bar.style.width = (passed / CHECKS.length * 100) + '%';
    field.setAttribute('data-level', passed);
    var text = field.querySelector('.meter-text');
    if (text) text.textContent = input.value ? LEVEL_TEXT[passed] : 'Too Short';
  }

  /* Show / hide password */
  document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
    });
  });

  /* Live password checklist and strength meter */
  document.querySelectorAll('input[data-meter]').forEach(function (input) {
    input.addEventListener('input', function () { updateMeter(input); });
  });

  /* Duplicate check (AJAX) */
  var timers = {};
  function checkUnique(input) {
    var value = input.value.trim();
    var field = input.getAttribute('data-unique');
    if (!value) return;
    clearTimeout(timers[field]);
    timers[field] = setTimeout(function () {
      fetch('ajax/check_duplicate.php?field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.exists) setState(input, 'This ' + field + ' is already registered.');
          else if (d.valid) setState(input, '');
        })
        .catch(function () { /* the server still checks on submit */ });
    }, 200);
  }

  /* Blur validation */
  document.querySelectorAll('input[data-validate], input[data-match]').forEach(function (input) {
    input.addEventListener('blur', function () {
      if (validateInput(input) && input.hasAttribute('data-unique')) checkUnique(input);
    });
  });

  /* Validate every field before a form is sent; confirm sensitive actions */
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var ok = true;
      form.querySelectorAll('input[data-validate], input[data-match]').forEach(function (input) {
        if (!validateInput(input)) ok = false;
      });
      var agree = form.querySelector('input[name="agree"]');
      if (agree && !agree.checked) {
        ok = false;
        alert('Please agree to the Terms of Service and Privacy Policy.');
      }
      if (ok && form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) ok = false;
      if (!ok) event.preventDefault();
    });
  });

  /* Switches submit their form straight away */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
