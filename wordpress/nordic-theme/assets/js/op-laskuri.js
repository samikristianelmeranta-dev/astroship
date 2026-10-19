/**
 * OP-rahoituslaskuri. Sama laskenta kuin teeman nordic_op_plan():
 * ensimmäinen kuukausi koroton, sitten tasaerä; erä sisältää laskutuspalkkion
 * eikä alita minimilyhennystä.
 */
(function () {
  'use strict';
  var root = document.getElementById('rahoituslaskuri');
  if (!root) return;

  var rate = parseFloat(root.dataset.rate) / 1200;
  var fee = parseFloat(root.dataset.fee);
  var minPct = parseFloat(root.dataset.minpct) / 100;
  var range = document.getElementById('op-amount');
  var num = document.getElementById('op-amount-n');
  var years = document.getElementById('op-years');
  var yearsOut = document.getElementById('op-years-out');
  var pay = document.getElementById('op-pay');
  var sub = document.getElementById('op-sub');
  var min = parseInt(range.min, 10);
  var max = parseInt(range.max, 10);

  function fmt(v) {
    return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function plan(amount, months) {
    var k = rate > 0 ? (1 - Math.pow(1 + rate, -(months - 1))) / rate : months - 1;
    var p = Math.max(amount / (1 + k) + fee, amount * minPct);
    var bal = amount, total = 0, n = 0;
    while (bal > 0.005 && n < 600) {
      n++;
      if (n > 1) bal += bal * rate;
      var part = Math.min(p - fee, bal);
      bal -= part;
      total += part + fee;
    }
    return { payment: Math.round(p), total: total, months: n };
  }

  function fill(el) {
    var pct = (el.value - el.min) / (el.max - el.min) * 100;
    el.style.setProperty('--fill', pct + '%');
  }

  function update() {
    var amount = Math.min(max, Math.max(min, parseInt(range.value, 10)));
    var y = parseInt(years.value, 10);
    var r = plan(amount, y * 12);
    yearsOut.textContent = y + (y === 1 ? ' vuosi' : ' vuotta');
    pay.textContent = fmt(r.payment);
    sub.textContent = 'Maksat yhteensä arviolta ' + fmt(r.total) + ' €';
    fill(range);
    fill(years);
  }

  range.addEventListener('input', function () {
    num.value = range.value;
    update();
  });
  years.addEventListener('input', update);

  num.addEventListener('input', function () {
    var v = parseInt(num.value, 10);
    if (!isNaN(v) && v >= min && v <= max) {
      range.value = v;
      update();
    }
  });
  num.addEventListener('change', function () {
    var v = parseInt(num.value, 10);
    if (isNaN(v)) v = min;
    v = Math.min(max, Math.max(min, v));
    num.value = v;
    range.value = v;
    update();
  });

  update();
})();
