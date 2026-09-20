/* Guru Expert Power Tools - UI behaviours (sticky header, mobile menu, cart drawer, shop filters, back-to-top). ES2024, no deps. */
(() => {
  'use strict';
  const on = (el, ev, fn, o) => el && el.addEventListener(ev, fn, o);
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  /* Sticky header */
  const header = $('.rk-header');
  if (header) {
    const mid = $('.rk-header__mid', header);
    const trigger = mid ? mid.offsetTop + mid.offsetHeight : 200;
    const onScroll = () => header.classList.toggle('is-stuck', window.scrollY > trigger);
    on(window, 'scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Mobile hamburger -> product category panel */
  const navToggle = $('.rk-nav-toggle');
  const mobile = $('.rk-mobile');
  const mobileOverlay = $('.rk-mobile__overlay');
  const closeMobile = () => {
    if (!mobile) return;
    mobile.classList.remove('is-open');
    if (mobileOverlay) mobileOverlay.classList.remove('is-open');
    if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('rk-noscroll');
  };
  if (navToggle && mobile) {
    on(navToggle, 'click', () => {
      const open = mobile.classList.toggle('is-open');
      if (mobileOverlay) mobileOverlay.classList.toggle('is-open', open);
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('rk-noscroll', open);
    });
    $$('[data-rk-mob-close]').forEach((el) => on(el, 'click', closeMobile));
    mobile.querySelectorAll('a').forEach((a) => on(a, 'click', closeMobile));
    on(document, 'keydown', (e) => { if (e.key === 'Escape') closeMobile(); });
  }

  /* Sticky add-to-cart bar on product pages */
  const stickyBar = $('.rk-sticky-atc');
  const cartForm = $('form.cart') || $('.single_add_to_cart_button');
  if (stickyBar && cartForm) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        // Show the bar once the main add-to-cart has scrolled out of view.
        const show = !e.isIntersecting && e.boundingClientRect.top < 0;
        stickyBar.classList.toggle('is-visible', show);
        document.body.classList.toggle('rk-sticky-on', show);
      });
    }, { threshold: 0 });
    io.observe(cartForm);
    const jump = $('.rk-sticky-atc__jump', stickyBar);
    if (jump) on(jump, 'click', (ev) => { ev.preventDefault(); cartForm.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
  }

  /* Shop filters slide-in drawer (mobile) */
  const filters = $('.rk-filters');
  const filtersOverlay = $('.rk-filters__overlay');
  const setFilterExpanded = (v) => $$('[data-rk-filters-open]').forEach((b) => b.setAttribute('aria-expanded', v));
  const openFilters = () => {
    if (!filters) return;
    filters.classList.add('is-open');
    if (filtersOverlay) filtersOverlay.classList.add('is-open');
    document.body.classList.add('rk-noscroll');
    setFilterExpanded('true');
  };
  const closeFilters = () => {
    if (!filters) return;
    filters.classList.remove('is-open');
    if (filtersOverlay) filtersOverlay.classList.remove('is-open');
    document.body.classList.remove('rk-noscroll');
    setFilterExpanded('false');
  };
  if (filters) {
    $$('[data-rk-filters-open]').forEach((el) => on(el, 'click', openFilters));
    $$('[data-rk-filters-close]').forEach((el) => on(el, 'click', closeFilters));
    on(document, 'keydown', (e) => { if (e.key === 'Escape') closeFilters(); });
  }

  /* Back to top */
  const top = $('.rk-backtop');
  if (top) {
    on(window, 'scroll', () => top.classList.toggle('is-visible', window.scrollY > 600), { passive: true });
    on(top, 'click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Homepage hero slider (autoplay, arrows, dots, swipe, keyboard) */
  const heroSlider = $('.gx-hero-slider[data-gx-slider]');
  if (heroSlider) {
    const slides = $$('.gx-slide', heroSlider);
    const dots = $$('.gx-slider__dot', heroSlider);
    const prevBtn = $('.gx-slider__arrow--prev', heroSlider);
    const nextBtn = $('.gx-slider__arrow--next', heroSlider);
    if (slides.length > 1) {
      heroSlider.classList.add('is-ready');
      const DURATION = 6000;
      let idx = Math.max(0, slides.findIndex((s) => s.classList.contains('is-active')));
      let timer = null;
      const render = () => {
        slides.forEach((s, i) => {
          const active = i === idx;
          s.classList.toggle('is-active', active);
          if (active) s.removeAttribute('aria-hidden'); else s.setAttribute('aria-hidden', 'true');
        });
        dots.forEach((d, i) => {
          const active = i === idx;
          d.classList.toggle('is-active', active);
          d.setAttribute('aria-current', active ? 'true' : 'false');
        });
      };
      const stop = () => { if (timer) { clearInterval(timer); timer = null; } };
      const play = () => { if (prefersReduced) return; stop(); timer = setInterval(() => { idx = (idx + 1) % slides.length; render(); }, DURATION); };
      const goTo = (n) => { idx = (n + slides.length) % slides.length; render(); play(); };
      if (prevBtn) on(prevBtn, 'click', () => goTo(idx - 1));
      if (nextBtn) on(nextBtn, 'click', () => goTo(idx + 1));
      dots.forEach((d, i) => on(d, 'click', () => goTo(i)));
      on(heroSlider, 'mouseenter', stop);
      on(heroSlider, 'mouseleave', play);
      on(heroSlider, 'focusin', stop);
      on(heroSlider, 'focusout', play);
      on(heroSlider, 'keydown', (e) => {
        if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(idx - 1); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); goTo(idx + 1); }
      });
      let touchX = null;
      on(heroSlider, 'touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
      on(heroSlider, 'touchend', (e) => {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 45) goTo(idx + (dx < 0 ? 1 : -1));
        touchX = null;
      }, { passive: true });
      on(document, 'visibilitychange', () => { if (document.hidden) stop(); else play(); });
      render();
      play();
    }
  }

  /* Homepage "Shop by category" horizontal slider */
  const catRow = $('[data-gx-catrow]');
  if (catRow) {
    const vp = $('[data-gx-catrow-vp]', catRow);
    const catPrev = $('.gx-catrow__nav--prev', catRow);
    const catNext = $('.gx-catrow__nav--next', catRow);
    if (vp) {
      const step = () => Math.max(vp.clientWidth * 0.8, 220);
      const update = () => {
        const max = vp.scrollWidth - vp.clientWidth - 2;
        const scrollable = vp.scrollWidth > vp.clientWidth + 4;
        [catPrev, catNext].forEach((b) => { if (b) b.hidden = !scrollable; });
        if (catPrev) catPrev.classList.toggle('is-disabled', vp.scrollLeft <= 2);
        if (catNext) catNext.classList.toggle('is-disabled', vp.scrollLeft >= max);
      };
      if (catPrev) on(catPrev, 'click', () => vp.scrollBy({ left: -step(), behavior: prefersReduced ? 'auto' : 'smooth' }));
      if (catNext) on(catNext, 'click', () => vp.scrollBy({ left: step(), behavior: prefersReduced ? 'auto' : 'smooth' }));
      on(vp, 'scroll', update, { passive: true });
      on(window, 'resize', update, { passive: true });
      /* Drag-to-scroll with a mouse; suppress the click that would otherwise fire after a drag. */
      let down = false, startX = 0, startLeft = 0, moved = false;
      on(vp, 'pointerdown', (e) => { if (e.pointerType !== 'mouse') return; down = true; moved = false; startX = e.clientX; startLeft = vp.scrollLeft; vp.classList.add('is-drag'); });
      on(vp, 'pointermove', (e) => { if (!down) return; const dx = e.clientX - startX; if (Math.abs(dx) > 4) moved = true; vp.scrollLeft = startLeft - dx; });
      const endDrag = () => { down = false; vp.classList.remove('is-drag'); };
      on(vp, 'pointerup', endDrag);
      on(vp, 'pointercancel', endDrag);
      on(vp, 'pointerleave', endDrag);
      on(vp, 'click', (e) => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
      update();
    }
  }

  /* Subtle reveal-on-scroll (progressive; content stays visible if JS/observer is unavailable) */
  const home = $('.gx-home');
  if (home && 'IntersectionObserver' in window && !prefersReduced) {
    const targets = $$('.gx-benefit, .gx-catrow, .gx-promo, .gx-brandrow, .gx-cta__inner, .gx-section .gx-head', home);
    if (targets.length) {
      home.classList.add('gx-animate');
      targets.forEach((el) => el.classList.add('gx-reveal'));
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); obs.unobserve(e.target); } });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      targets.forEach((el) => io.observe(el));
    }
  }

  /* Keep the mobile bottom-nav cart badge in sync with the header cart count */
  const botCart = $('.gx-botnav__count');
  if (botCart) {
    const syncCart = () => {
      const src = $('.rk-header .rk-cart-count') || $('.rk-cart-count');
      const n = src ? (src.getAttribute('data-count') || src.textContent || '0').trim() : '0';
      botCart.textContent = n;
      botCart.classList.toggle('is-empty', !n || n === '0');
    };
    on(document.body, 'wc_fragments_refreshed', syncCart);
    on(document.body, 'added_to_cart', syncCart);
    syncCart();
  }
})();


/* Guru Expert: checkout delivery note (flat KSh 500 + heavy-machine notice) */
(function(){
  function gxInsertDeliveryNote(){
    var body = document.body;
    if (body && body.classList.contains('woocommerce-checkout')) {
      if (document.querySelector('.gx-delivery-note')) { return; }
      var target = document.querySelector('.wp-block-woocommerce-checkout') || document.querySelector('form.woocommerce-checkout') || document.querySelector('.woocommerce');
      if (target && target.parentNode) {
        var note = document.createElement('div');
        note.className = 'gx-delivery-note';
        note.innerHTML = '<strong>Delivery:</strong> Standard delivery is a flat KSh 500 anywhere in Kenya. Large or heavy machines (generators, welding machines, solar panels) may need special transport - we will confirm any extra cost with you by phone or WhatsApp before dispatch.';
        target.parentNode.insertBefore(note, target);
      }
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', gxInsertDeliveryNote);
  } else {
    gxInsertDeliveryNote();
  }
})();
