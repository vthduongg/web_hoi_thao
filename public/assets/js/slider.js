// Slider ảnh trang chủ: tự chuyển ảnh sau data-interval mili giây, có nút dừng/chạy.
// Không tự chuyển khi người dùng bật "giảm chuyển động" trong hệ điều hành (prefers-reduced-motion).
(function () {
  'use strict';

  document.querySelectorAll('[data-slider]').forEach(function (slider) {
    var slides = slider.querySelectorAll('[data-slide]');
    var toggle = slider.querySelector('[data-slider-toggle]');
    var interval = parseInt(slider.getAttribute('data-interval') || '5000', 10);
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var current = 0;
    var timer = null;

    if (slides.length < 2) return;

    function show(index) {
      slides[current].classList.remove('is-active');
      slides[current].setAttribute('aria-hidden', 'true');
      current = index;
      slides[current].classList.add('is-active');
      slides[current].removeAttribute('aria-hidden');
    }

    function play() {
      timer = window.setInterval(function () { show((current + 1) % slides.length); }, interval);
      if (toggle) toggle.textContent = toggle.getAttribute('data-pause-text');
    }

    function pause() {
      window.clearInterval(timer);
      timer = null;
      if (toggle) toggle.textContent = toggle.getAttribute('data-play-text');
    }

    if (toggle) {
      toggle.addEventListener('click', function () { timer === null ? play() : pause(); });
    }

    if (reduceMotion) {
      pause();
    } else {
      play();
    }
  });
})();
