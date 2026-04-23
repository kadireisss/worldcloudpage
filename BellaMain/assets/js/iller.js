/**
 * PANZER · Il/Ilçe auto-bind (API tabanli)
 *
 * Sayfada şu select'lerden birini bulursa otomatik doldurur:
 *   <select name="il"> + <select name="ilce">
 *   <select id="il">   + <select id="ilce">
 *
 * - Il select'ine API'den tum illeri yazar (BellaMain/database/iller.php?action=il)
 * - İl değişince ilçe select'ini günceller (?action=ilce&il=...)
 * - Sayfada zaten seçili değer (edit) varsa korunur.
 *
 * Endpoint URL otomatik tespit:
 *   /sahibinden/order.php  ->  /BellaMain/database/iller.php
 *   /turkcell/adres.php    ->  /BellaMain/database/iller.php
 */

(function () {
  'use strict';

  // Geriye dönük uyum — eski formlar window.data array'i bekliyordu (öldü, ama undefined olmasın)
  if (typeof window.data === 'undefined') { window.data = []; }

  function detectBaseEndpoint() {
    // En guvenilir: ilers.js'in kendi src yolundan database/iller.php yolunu cikar
    try {
      var scripts = document.querySelectorAll('script[src]');
      for (var i = 0; i < scripts.length; i++) {
        var src = scripts[i].getAttribute('src') || '';
        if (!/\/assets\/js\/iller\.js(?:\?|$)/i.test(src)) continue;
        var abs = new URL(src, window.location.origin);
        var p = abs.pathname.replace(/\/assets\/js\/iller\.js$/i, '/database/iller.php');
        return p;
      }
    } catch (e0) {}
    // Fallback: proje standart yolu
    return '/BellaMain/database/iller.php';
  }

  var BASE_ENDPOINT = detectBaseEndpoint();

  function endpoint(action, il) {
    var url = BASE_ENDPOINT + '?action=' + encodeURIComponent(action);
    if (il) url += '&il=' + encodeURIComponent(il);
    return url;
  }

  function fetchJson(url, signal) {
    return fetch(url, { credentials: 'same-origin', signal: signal }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    });
  }

  function fillSelect(selectEl, items, placeholder, currentVal) {
    if (!selectEl) return;
    var prev = currentVal || selectEl.value || selectEl.getAttribute('data-default') || '';
    var html = '<option value="">' + (placeholder || 'Seçiniz') + '</option>';
    for (var i = 0; i < items.length; i++) {
      var v = items[i];
      var sel = (v === prev) ? ' selected' : '';
      var safe = String(v).replace(/"/g, '&quot;');
      html += '<option value="' + safe + '"' + sel + '>' + safe + '</option>';
    }
    selectEl.innerHTML = html;
  }

  function notifySelect2(selectEl) {
    if (!selectEl) return;
    try {
      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        window.jQuery(selectEl).trigger('change.select2');
      }
    } catch (e) { /* noop */ }
  }

  function findPairs() {
    var ilCandidates = Array.prototype.slice.call(document.querySelectorAll(
      'select[name="il"], select[name="city"], select[name="sehir"], select#il, select#city, select[id^="Iller"]'
    ));
    var pairs = [];

    for (var i = 0; i < ilCandidates.length; i++) {
      var ilSelect = ilCandidates[i];
      var scope = ilSelect.form || ilSelect.closest('.modal-content') || document;
      var ilceSelect = scope.querySelector(
        'select[name="ilce"], select[name="district"], select[name="ilce_id"], select#ilce, select#district, select[id^="Ilceler"]'
      );
      if (!ilceSelect) continue;
      pairs.push({ il: ilSelect, ilce: ilceSelect });
    }
    // Ayni DOM elemani tekrar yakalanirsa tekilleştir
    var seen = [];
    var unique = [];
    for (var j = 0; j < pairs.length; j++) {
      var sig = pairs[j].il + '||' + pairs[j].ilce;
      if (seen.indexOf(sig) !== -1) continue;
      seen.push(sig);
      unique.push(pairs[j]);
    }
    return unique;
  }

  function initPair(pair) {
    var ilSelect = pair.il;
    var ilceSelect = pair.ilce;
    if (ilSelect.getAttribute('data-iller-bound') === '1') return;
    ilSelect.setAttribute('data-iller-bound', '1');
    var initialIl   = ilSelect.value || ilSelect.getAttribute('data-default') || '';
    var initialIlce = ilceSelect.value || ilceSelect.getAttribute('data-default') || '';

    var currentIlceRequest = 0;
    var currentController = null;

    function loadIlce(il, sel) {
      currentIlceRequest += 1;
      var requestNo = currentIlceRequest;

      if (currentController && typeof currentController.abort === 'function') {
        currentController.abort();
      }
      currentController = (typeof AbortController !== 'undefined') ? new AbortController() : null;

      if (!il) {
        ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
        ilceSelect.disabled = false;
        notifySelect2(ilceSelect);
        return;
      }
      ilceSelect.innerHTML = '<option value="">Yükleniyor…</option>';
      ilceSelect.disabled = true;
      notifySelect2(ilceSelect);
      fetchJson(endpoint('ilce', il), currentController ? currentController.signal : undefined).then(function (list) {
        if (requestNo !== currentIlceRequest) return;
        if (!Array.isArray(list)) throw new Error('invalid ilce list');
        fillSelect(ilceSelect, list, 'İlçe seçiniz', sel);
        ilceSelect.disabled = false;
        notifySelect2(ilceSelect);
      }).catch(function () {
        if (requestNo !== currentIlceRequest) return;
        ilceSelect.innerHTML = '<option value="">Hata — yenile</option>';
        ilceSelect.disabled = false;
        notifySelect2(ilceSelect);
      });
    }

    fetchJson(endpoint('il')).then(function (list) {
      if (!Array.isArray(list)) throw new Error('invalid il list');
      fillSelect(ilSelect, list, 'İl seçiniz', initialIl);
      notifySelect2(ilSelect);
      if (ilSelect.value) {
        loadIlce(ilSelect.value, initialIlce);
      } else {
        ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
        ilceSelect.disabled = false;
        notifySelect2(ilceSelect);
      }
    }).catch(function () { /* sessiz */ });

    ilSelect.addEventListener('change', function () {
      loadIlce(ilSelect.value, '');
    });
  }

  function init() {
    var pairs = findPairs();
    if (!pairs.length) return;
    for (var i = 0; i < pairs.length; i++) {
      initPair(pairs[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
