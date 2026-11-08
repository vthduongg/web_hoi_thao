// Trình soạn thảo trực quan (TinyMCE) cho ô [data-rich-editor].
//
// - Giao diện tiếng Việt, các công cụ: định dạng, danh sách, bảng, liên kết, ảnh, video nhúng, mã HTML.
// - Nút "Chèn từ thư viện": chọn ảnh (chèn ảnh) hoặc tài liệu PDF/Word (chèn liên kết) — dùng media-picker.js.
// - Trước khi lưu: nếu có ảnh chưa có mô tả (alt) thì hiện cảnh báo và chưa gửi biểu mẫu (FR-042).
(function () {
  'use strict';

  var textarea = document.querySelector('[data-rich-editor]');
  if (!textarea || !window.tinymce) return;

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML.replace(/"/g, '&quot;');
  }

  function insertFromLibrary(editor) {
    window.MediaPicker.open('', function (item) {
      if (item.kind === 'image') {
        editor.insertContent('<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.alt) + '">');
      } else {
        editor.insertContent('<a href="' + escapeHtml(item.url) + '">' + escapeHtml(item.name) + '</a>');
      }
    });
  }

  // data-rich-editor="compact": thanh công cụ rút gọn (ví dụ tiểu sử diễn giả)
  var isCompact = textarea.getAttribute('data-rich-editor') === 'compact';
  var fullToolbar = 'undo redo | blocks | bold italic underline | bullist numlist | link image media table | library | code';
  var compactToolbar = 'undo redo | bold italic | bullist numlist | link';

  window.tinymce.init({
    target: textarea,
    license_key: 'gpl',
    base_url: '/assets/vendor/tinymce',
    suffix: '.min',
    language: 'vi',
    language_url: '/assets/vendor/tinymce/langs/vi.js',
    plugins: 'lists table link image media code',
    menubar: false,
    toolbar: isCompact ? compactToolbar : fullToolbar,
    promotion: false,
    branding: false,
    height: isCompact ? 260 : 480,
    relative_urls: false,
    convert_urls: false,
    image_description: true,
    // Không chèn style viết trong thẻ (bị CSP trang công khai chặn); bảng lấy kiểu từ public.css
    table_default_styles: {},
    table_style_by_css: false,
    table_sizing_mode: 'responsive',
    setup: function (editor) {
      editor.ui.registry.addButton('library', {
        text: textarea.getAttribute('data-library-label'),
        onAction: function () { insertFromLibrary(editor); },
      });
    },
  });

  // Kiểm tra mô tả ảnh trước khi lưu
  var form = textarea.closest('form');
  var warning = document.querySelector('[data-alt-warning]');
  form.addEventListener('submit', function (event) {
    var editor = window.tinymce.get(textarea.id);
    if (!editor) return;
    editor.save();
    var missing = editor.dom.select('img').filter(function (img) {
      return !(img.getAttribute('alt') || '').trim();
    }).length;
    if (missing > 0 && warning) {
      event.preventDefault();
      warning.textContent = warning.getAttribute('data-template').replace('{count}', missing);
      warning.hidden = false;
      warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
})();
