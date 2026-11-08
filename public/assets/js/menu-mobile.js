// Menu chính: nút "Menu" trên màn hình nhỏ, mở/đóng menu con bằng chuột, chạm và bàn phím.
//
// - Nút [data-menu-toggle] mở/đóng toàn bộ menu (màn hình < 900px).
// - Nút [data-submenu-toggle] mở/đóng menu con; Tab bằng bàn phím vào nút cũng mở menu con.
// - Phím Esc đóng menu con đang mở và đưa focus về nút cha.
// Khi tắt JavaScript, CSS vẫn mở menu con bằng :hover / :focus-within (xem public.css).
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  var menuToggle = document.querySelector('[data-menu-toggle]');
  var menu = document.querySelector('[data-main-menu]');
  var skipFocusOpen = false; // không tự mở lại khi chính script trả focus về nút cha

  function setSubmenu(toggle, open) {
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.parentElement.classList.toggle('is-open', open);
  }

  function closeOtherSubmenus(except) {
    document.querySelectorAll('[data-submenu-toggle][aria-expanded="true"]').forEach(function (toggle) {
      if (toggle !== except) setSubmenu(toggle, false);
    });
  }

  if (menuToggle && menu) {
    menuToggle.addEventListener('click', function () {
      var open = menuToggle.getAttribute('aria-expanded') !== 'true';
      menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      menu.classList.toggle('is-open', open);
    });
  }

  document.querySelectorAll('[data-submenu-toggle]').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') !== 'true';
      closeOtherSubmenus(toggle);
      setSubmenu(toggle, open);
    });

    // Tab bằng bàn phím vào mục cha thì mở menu con (focus bằng chuột/chạm thì để sự kiện click xử lý)
    toggle.addEventListener('focus', function () {
      if (skipFocusOpen) {
        skipFocusOpen = false;
        return;
      }
      if (toggle.matches(':focus-visible')) {
        closeOtherSubmenus(toggle);
        setSubmenu(toggle, true);
      }
    });

    // Focus rời khỏi cả mục cha lẫn menu con thì đóng menu con
    toggle.parentElement.addEventListener('focusout', function (event) {
      if (!toggle.parentElement.contains(event.relatedTarget)) {
        setSubmenu(toggle, false);
      }
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var item = document.activeElement && document.activeElement.closest('.main-menu__item');
    var toggle = item && item.querySelector('[data-submenu-toggle]');
    if (toggle && toggle.getAttribute('aria-expanded') === 'true') {
      setSubmenu(toggle, false);
      skipFocusOpen = document.activeElement !== toggle;
      toggle.focus();
    }
  });
})();
