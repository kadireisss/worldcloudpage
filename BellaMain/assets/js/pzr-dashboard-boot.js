/**
 * PANZER dashboard kabugu — overlay temizligi, mobil drawer, kullanici menusu.
 * Bootstrap + Swal yuklendikten HEMEN sonra baglanir.
 */
(function () {
  'use strict';

  /** Bootstrap acik modal: DOM'da .show ama aria-hidden=true genelde takili kalintidir */
  function pzrStripGhostModalShow() {
    try {
      document.querySelectorAll('.modal.show[aria-hidden="true"]').forEach(function (m) {
        m.classList.remove('show');
        m.style.removeProperty('display');
        m.setAttribute('aria-hidden', 'true');
      });
    } catch (e0) {}
  }

  function pzrModalLayerOpen() {
    try {
      pzrStripGhostModalShow();
      var list = document.querySelectorAll('.modal.show');
      for (var i = 0; i < list.length; i++) {
        var el = list[i];
        if (el.getAttribute('aria-hidden') === 'true') continue;
        try {
          if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getInstance) {
            var inst = bootstrap.Modal.getInstance(el);
            if (inst && typeof inst._isShown === 'boolean' && inst._isShown === false) continue;
          }
        } catch (e1) {}
        var r = el.getBoundingClientRect();
        if (r.width > 8 && r.height > 8) return true;
      }
    } catch (e2) {}
    return false;
  }

  function pzrSwalOpen() {
    try {
      var byApi = typeof Swal !== 'undefined' && typeof Swal.isVisible === 'function' && Swal.isVisible();
      var container = document.querySelector('.swal2-container');
      if (!container) return !!byApi;

      var cs = window.getComputedStyle(container);
      if (!cs || cs.display === 'none' || cs.visibility === 'hidden' || Number(cs.opacity || 0) < 0.05) {
        return false;
      }

      var popup = container.querySelector('.swal2-popup');
      if (!popup) return !!byApi;

      var ps = window.getComputedStyle(popup);
      var popupVisible = !!ps && ps.display !== 'none' && ps.visibility !== 'hidden' && Number(ps.opacity || 0) > 0.05;
      var popupShownClass = popup.classList.contains('swal2-show') || popup.classList.contains('swal2-popup');

      return !!(byApi && popupVisible && popupShownClass);
    } catch (e) {
      return false;
    }
  }

  /** Gercek modal / Swal acik degilse: backdrop, body kilidi, olusu Swal kalintisi temizle */
  function pzrForceStaleOverlayCleanup() {
    try {
      pzrStripGhostModalShow();

      if (pzrModalLayerOpen() || pzrSwalOpen()) return;

      document.querySelectorAll('.modal-backdrop').forEach(function (el) {
        el.remove();
      });
      document.querySelectorAll('.offcanvas-backdrop').forEach(function (el) {
        el.remove();
      });
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
      document.documentElement.style.removeProperty('overflow');

      document.querySelectorAll('.modal.show').forEach(function (m) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getInstance) {
          if (bootstrap.Modal.getInstance(m)) return;
        }
        m.classList.remove('show');
        m.setAttribute('aria-hidden', 'true');
      });

      document.body.classList.remove('swal2-shown', 'swal2-height-auto');
      document.querySelectorAll('.swal2-container').forEach(function (el) {
        var popup = el.querySelector('.swal2-popup');
        if (!popup) {
          el.remove();
          return;
        }
        var p = window.getComputedStyle(popup);
        var hidden = !p || p.display === 'none' || p.visibility === 'hidden' || Number(p.opacity || 0) < 0.05;
        if (hidden) el.remove();
      });
    } catch (e3) {}
  }

  /**
   * Bazi eklentiler (veya yarim kalmis animasyonlar) viewport'u tamamen kaplayan
   * fixed katman birakabiliyor. Gercek modal/swal yokken bu katmanlari devre disi birak.
   */
  function pzrNeutralizeUnknownBlockingLayers() {
    try {
      if (pzrModalLayerOpen() || pzrSwalOpen()) return;

      var vw = window.innerWidth || document.documentElement.clientWidth || 0;
      var vh = window.innerHeight || document.documentElement.clientHeight || 0;
      if (!vw || !vh) return;

      var all = document.body ? document.body.querySelectorAll('*') : [];
      for (var i = 0; i < all.length; i++) {
        var el = all[i];
        if (!el || !el.getBoundingClientRect) continue;
        if (el.classList && (el.classList.contains('modal') || el.classList.contains('modal-dialog'))) continue;
        if (el.classList && (el.classList.contains('swal2-popup') || el.classList.contains('swal2-html-container'))) continue;

        var cs = window.getComputedStyle(el);
        if (!cs) continue;
        if (cs.display === 'none' || cs.visibility === 'hidden') continue;
        if (cs.position !== 'fixed' && cs.position !== 'sticky') continue;
        if (cs.pointerEvents === 'none') continue;

        var z = parseInt(cs.zIndex, 10);
        if (!isFinite(z) || z < 1040) continue;

        var r = el.getBoundingClientRect();
        if (r.width < vw * 0.9 || r.height < vh * 0.9) continue;
        if (r.top > 4 || r.left > 4) continue;

        // Uygulamamiz ana kokleri ve gercek icerik katmanlari asla devre disi birakma
        if (el.id === 'pzrDashRoot') continue;
        if (el.closest && (el.closest('#pzrDashRoot') || el.closest('.modal.show') || el.closest('.swal2-container'))) continue;

        el.style.setProperty('pointer-events', 'none', 'important');
        el.style.setProperty('opacity', '0', 'important');
      }
    } catch (e4) {}
  }

  function pzrUnblockPointerOverlays() {
    pzrForceStaleOverlayCleanup();
    pzrNeutralizeUnknownBlockingLayers();
  }

  function pzrResolveTargetModal(triggerEl) {
    try {
      if (!triggerEl) return null;
      var selector = triggerEl.getAttribute('data-bs-target') || triggerEl.getAttribute('href') || '';
      if (!selector || selector.charAt(0) !== '#') return null;
      return document.querySelector(selector);
    } catch (e0) {
      return null;
    }
  }

  function pzrShowModalFallback(modalEl) {
    if (!modalEl) return;
    modalEl.classList.add('show');
    modalEl.style.display = 'block';
    modalEl.removeAttribute('aria-hidden');
    modalEl.setAttribute('aria-modal', 'true');
    modalEl.setAttribute('role', 'dialog');
    document.body.classList.add('modal-open');
  }

  function pzrHideModalFallback(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.removeAttribute('aria-modal');
    if (!document.querySelector('.modal.show')) {
      document.body.classList.remove('modal-open');
    }
    pzrUnblockPointerOverlays();
  }

  window.pzrUnblockPointerOverlays = pzrUnblockPointerOverlays;
  window.pzrForceStaleOverlayCleanup = pzrForceStaleOverlayCleanup;

  pzrUnblockPointerOverlays();
  document.addEventListener('DOMContentLoaded', pzrUnblockPointerOverlays);
  window.addEventListener('load', pzrUnblockPointerOverlays);
  [200, 500, 1200, 2500, 5000, 9000].forEach(function (ms) {
    setTimeout(pzrUnblockPointerOverlays, ms);
  });

  /* Takili tam ekran katman: modal/Swal yokken periyodik sok (hafif) */
  setInterval(function () {
    if (pzrModalLayerOpen() || pzrSwalOpen()) return;
    pzrForceStaleOverlayCleanup();
    pzrNeutralizeUnknownBlockingLayers();
  }, 4000);

  /* Ilk dokunusta backdrop / olu Swal konteyneri — tiklamayi yutan katman */
  document.addEventListener(
    'pointerdown',
    function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (pzrModalLayerOpen() || pzrSwalOpen()) return;
      if (t.classList.contains('modal-backdrop')) {
        pzrForceStaleOverlayCleanup();
        return;
      }
      if (t.classList.contains('swal2-container') || (t.closest && t.closest('.swal2-container'))) {
        if (!pzrSwalOpen()) pzrForceStaleOverlayCleanup();
      }
    },
    true
  );

  /*
   * Bootstrap data-api fallback:
   * Eger herhangi bir nedenle bootstrap modal trigger calismazsa
   * data-bs-toggle=modal tiklamasindan hemen sonra manuel ac.
   */
  document.addEventListener(
    'click',
    function (e) {
      var trigger = e.target && e.target.closest ? e.target.closest('[data-bs-toggle="modal"]') : null;
      if (!trigger) return;

      var modal = pzrResolveTargetModal(trigger);
      if (!modal) return;

      setTimeout(function () {
        var isOpen = modal.classList.contains('show') && modal.style.display !== 'none';
        if (isOpen) return;
        pzrShowModalFallback(modal);
      }, 90);
    },
    true
  );

  document.addEventListener(
    'click',
    function (e) {
      var closeBtn = e.target && e.target.closest ? e.target.closest('[data-bs-dismiss="modal"]') : null;
      if (!closeBtn) return;
      var modal = closeBtn.closest ? closeBtn.closest('.modal') : null;
      if (!modal) return;
      pzrHideModalFallback(modal);
    },
    true
  );

  document.addEventListener(
    'click',
    function (e) {
      var modal = e.target && e.target.classList && e.target.classList.contains('modal') ? e.target : null;
      if (!modal || !modal.classList.contains('show')) return;
      pzrHideModalFallback(modal);
    },
    true
  );

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var active = document.querySelector('.modal.show');
    if (active) pzrHideModalFallback(active);
  });

  /* ====== Mobil sidebar ====== */
  (function () {
    var body = document.body;
    var btn = document.getElementById('pzrMenuBtn');
    var sidebar = document.getElementById('pzrSidebar');
    function close() {
      body.classList.remove('is-sidebar-open');
    }
    function isMobile() {
      return window.innerWidth <= 991;
    }
    if (btn) {
      btn.addEventListener('click', function (e) {
        if (!isMobile()) return;
        e.stopPropagation();
        body.classList.toggle('is-sidebar-open');
      });
    }
    document.addEventListener(
      'click',
      function (e) {
        if (!isMobile() || !body.classList.contains('is-sidebar-open')) return;
        if (!sidebar) return;
        if (sidebar.contains(e.target)) return;
        if (btn && (e.target === btn || btn.contains(e.target))) return;
        close();
      },
      true
    );
    document.querySelectorAll('#pzrSidebar .pzr-mkt').forEach(function (el) {
      el.addEventListener('click', function () {
        if (window.innerWidth <= 991) close();
      });
    });
    window.addEventListener('resize', function () {
      if (!isMobile()) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  })();

  /* ====== Kullanici acilir menusu ====== */
  (function () {
    var t = document.getElementById('pzrUserToggle');
    var d = document.getElementById('pzrUserDropdown');
    if (!t || !d) return;
    t.addEventListener('click', function (e) {
      e.stopPropagation();
      d.classList.toggle('is-open');
    });
    document.addEventListener('click', function (e) {
      if (!d.contains(e.target) && e.target !== t) d.classList.remove('is-open');
    });
  })();

  /* ====== Bekleyen cekim uyarisi (HTML onclick) ====== */
  window.beklemedeUyari = function () {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
      html:
        '<strong>Zaten beklemede olan islemin var.<br>Lutfen islem tamamlanincaya kadar bekle.</strong>',
      icon: 'warning',
      buttonsStyling: false,
      confirmButtonText: 'Anladim',
      customClass: { confirmButton: 'btn btn-primary' },
    });
  };

  setTimeout(pzrUnblockPointerOverlays, 80);
})();
