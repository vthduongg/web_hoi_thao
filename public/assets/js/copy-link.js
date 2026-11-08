// Nút "Copy link": chép đường dẫn trang hiện tại vào clipboard và báo kết quả.
(function () {
  'use strict';

  document.querySelectorAll('[data-copy-link]').forEach(function (button) {
    var status = button.closest('.share-buttons').querySelector('[data-copy-status]');

    button.addEventListener('click', function () {
      var say = function (text) { if (status) status.textContent = text; };

      if (!navigator.clipboard) {
        say(button.getAttribute('data-failed-text'));
        return;
      }
      navigator.clipboard.writeText(window.location.href).then(
        function () { say(button.getAttribute('data-copied-text')); },
        function () { say(button.getAttribute('data-failed-text')); }
      );
    });
  });
})();
