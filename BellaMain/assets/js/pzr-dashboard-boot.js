/**
 * PANZER dashboard kabugu — overlay temizligi, mobil drawer, kullanici menusu.
 * Bootstrap + Swal yuklendikten HEMEN sonra baglanir.
 */
(function () {
  'use strict';

  /**
   * Sadece bilinen takili katmanlari temizle.
   * Bilinmeyen elemanlara dokunma: aksi halde panel elemanlarini da kilitleyebilir.
   */
  function pzrCleanupKnownStaleLayers() {
    try {
      document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach(function (el) {
        if (!document.querySelector('.modal.show, .offcanvas.show')) {
          el.remove();
        }
      });
      if (!document.querySelector('.modal.show, .offcanvas.show')) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.documentElement.style.removeProperty('overflow');
      }

      var swalContainer = document.querySelector('.swal2-container');
      if (swalContainer && typeof Swal !== 'undefined' && typeof Swal.isVisible === 'function' && !Swal.isVisible()) {
        swalContainer.remove();
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
      }
    } catch (e0) {}
  }

  function pzrUnblockPointerOverlays() {
    pzrCleanupKnownStaleLayers();
  }

  window.pzrUnblockPointerOverlays = pzrUnblockPointerOverlays;
  pzrUnblockPointerOverlays();
  document.addEventListener('DOMContentLoaded', pzrUnblockPointerOverlays);
  window.addEventListener('load', pzrUnblockPointerOverlays);
  [300, 900, 1800, 3200].forEach(function (ms) {
    setTimeout(pzrUnblockPointerOverlays, ms);
  });

  // Bilinen stale overlay temizligi; DOM'a agressif dokunma yok.
  setInterval(function () {
    pzrCleanupKnownStaleLayers();
  }, 4000);

  // Yalniz backdrop hedeflenirse temizle.
  document.addEventListener(
    'pointerdown',
    function (e) {
      var t = e.target;
      if (!t || !t.classList) return;
      if (t.classList.contains('modal-backdrop') || t.classList.contains('offcanvas-backdrop')) {
        pzrCleanupKnownStaleLayers();
      }
      if (t.classList.contains('swal2-container')) {
        if (!(typeof Swal !== 'undefined' && typeof Swal.isVisible === 'function' && Swal.isVisible())) {
          pzrCleanupKnownStaleLayers();
        }
      }
    },
    true
  );

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
