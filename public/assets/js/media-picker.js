// Hộp chọn tệp từ thư viện.
//
// 1) Trong cửa sổ hộp chọn (/admin/media/picker): bấm "Chọn" → gửi thông tin tệp về cửa sổ đã mở nó rồi tự đóng.
// 2) Ở các trang quản trị khác:
//    - window.MediaPicker.open(kind, callback) mở hộp chọn; callback nhận {id, url, previewUrl, name, alt, kind}.
//    - Ô chọn ảnh [data-media-field] (nút "Chọn ảnh"/"Bỏ ảnh") ghi id ảnh vào ô ẩn và hiện ảnh xem trước.
(function () {
  'use strict';

  var MESSAGE_TYPE = 'media-picked';

  // ----- 1) Trong cửa sổ hộp chọn -----
  if (document.body.classList.contains('picker-page')) {
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-media-select]');
      if (!button || !window.opener) return;
      var item = button.closest('[data-media-item]');
      window.opener.postMessage({
        type: MESSAGE_TYPE,
        id: item.getAttribute('data-media-id'),
        url: item.getAttribute('data-media-url'),
        previewUrl: item.getAttribute('data-media-preview-url'),
        name: item.getAttribute('data-media-name'),
        alt: item.getAttribute('data-media-alt'),
        kind: item.getAttribute('data-media-kind'),
      }, window.location.origin);
      window.close();
    });
    return;
  }

  // ----- 2) Ở trang gọi hộp chọn -----
  function open(kind, callback) {
    var url = '/admin/media/picker' + (kind ? '?kind=' + encodeURIComponent(kind) : '');
    var popup = window.open(url, 'media-picker', 'width=960,height=720');

    function onMessage(event) {
      if (event.origin !== window.location.origin || !event.data || event.data.type !== MESSAGE_TYPE) return;
      window.removeEventListener('message', onMessage);
      callback(event.data);
    }
    window.addEventListener('message', onMessage);
    if (popup) popup.focus();
  }

  window.MediaPicker = { open: open };

  document.querySelectorAll('[data-media-field]').forEach(function (field) {
    var input = field.querySelector('input[type="hidden"]');
    var preview = field.querySelector('[data-media-preview]');

    field.querySelector('[data-media-choose]').addEventListener('click', function () {
      open(field.getAttribute('data-media-kind') || '', function (item) {
        input.value = item.id;
        preview.innerHTML = '';
        if (item.kind === 'image') {
          var img = document.createElement('img');
          img.src = item.previewUrl;
          img.alt = item.alt || item.name;
          img.width = 200;
          preview.appendChild(img);
        } else {
          var name = document.createElement('span');
          name.textContent = item.name;
          preview.appendChild(name);
        }
      });
    });

    field.querySelector('[data-media-clear]').addEventListener('click', function () {
      input.value = '';
      preview.innerHTML = '';
    });
  });
})();
