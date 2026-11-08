// Hỏi xác nhận trước khi gửi biểu mẫu có thuộc tính data-confirm (ví dụ nút "Xoá").
// Câu hỏi lấy từ giá trị của data-confirm. Bấm "Huỷ" thì biểu mẫu không được gửi.
(function () {
  'use strict';

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
      return;
    }
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
})();
