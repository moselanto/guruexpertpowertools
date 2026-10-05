/* Guru Expert Power Tools - category sales funnels v2: price filters, show more, WhatsApp quick order, jump nav, sticky bar. */
(() => {
  'use strict';
  const root = document.querySelector('.gx-lp');
  if (!root) return;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const wa = (root.getAttribute('data-gx-wa') || '').replace(/[^0-9]/g, '');
  const pageUrl = root.getAttribute('data-gx-page') || window.location.href;

  /* Grid: price-band filter + "Show more" */
  const grid = $('[data-gx-filter-target]', root);
  const emptyMsg = $('.gx-lp-empty-filter', root);
  const moreBtn = $('[data-gx-show-more]', root);
  let expanded = false;
  let band = { min: 0, max: 0 };
  const applyGrid = () => {
    if (!grid) return;
    const filtering = band.min > 0 || band.max > 0;
    let shown = 0;
    $$('.gx-lp-card', grid).forEach((card) => {
      const p = parseFloat(card.dataset.price || '0');
      const inBand = p >= band.min && (band.max === 0 || p < band.max);
      const collapsed = card.hasAttribute('data-gx-more') && !expanded && !filtering;
      card.hidden = !inBand || collapsed;
      if (!card.hidden) shown++;
    });
    if (emptyMsg) emptyMsg.hidden = shown > 0;
    if (moreBtn) moreBtn.hidden = expanded || filtering;
  };
  $$('.gx-lp-chip', root).forEach((chip) => {
    chip.addEventListener('click', () => {
      band = { min: parseFloat(chip.dataset.min || '0'), max: parseFloat(chip.dataset.max || '0') };
      $$('.gx-lp-chip', root).forEach((c) => {
        const on = c === chip;
        c.classList.toggle('is-active', on);
        c.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      applyGrid();
    });
  });
  if (moreBtn) {
    moreBtn.addEventListener('click', () => {
      const firstHidden = grid ? grid.querySelector('[data-gx-more]') : null;
      expanded = true;
      applyGrid();
      if (firstHidden) {
        const link = firstHidden.querySelector('.gx-lp-card__title a');
        if (link) link.focus({ preventScroll: true });
      }
    });
  }

  /* Open WhatsApp through a real link click so the theme's WhatsApp conversion
     listener (class-whatsapp-tracking.php) records it like any other WhatsApp tap. */
  const openWa = (text) => {
    if (!wa) return;
    const a = document.createElement('a');
    a.href = 'https://wa.me/' + wa + '?text=' + encodeURIComponent(text);
    a.target = '_blank';
    a.rel = 'noopener nofollow';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    a.remove();
  };

  /* Quick-order form */
  const form = $('[data-gx-order-form]', root);
  const select = form ? $('select[name="product"]', form) : null;

  /* Card "Order on WhatsApp": preselect the product in the form and scroll to it. */
  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-gx-order]');
    if (!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-gx-order');
    if (form && select && select.querySelector('option[value="' + id + '"]')) {
      select.value = id;
      select.setAttribute('aria-invalid', 'false');
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const name = $('input[name="name"]', form);
      window.setTimeout(() => { (name || select).focus({ preventScroll: true }); }, 450);
      return;
    }
    const card = btn.closest('.gx-lp-card');
    const title = card ? card.querySelector('.gx-lp-card__title') : null;
    openWa('Hello Guru Expert Power Tools, I would like to order: ' + (title ? title.textContent.trim() : '') + '\n' + pageUrl);
  });

  if (form) {
    const err = $('.gx-lp-form__error', form);
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const v = (n) => (form.elements[n] ? String(form.elements[n].value || '').trim() : '');
      let bad = null;
      ['product', 'name', 'phone', 'town', 'qty'].forEach((n) => {
        const el = form.elements[n];
        if (!el) return;
        let ok = v(n) !== '';
        if (n === 'phone') ok = v(n).replace(/[^0-9]/g, '').length >= 9;
        if (n === 'qty') ok = parseInt(v(n), 10) >= 1;
        el.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (!ok && !bad) bad = el;
      });
      if (bad) {
        if (err) { err.textContent = 'Please choose a product and fill in your name, a valid phone number, delivery town and quantity.'; err.hidden = false; }
        bad.focus();
        return;
      }
      if (err) err.hidden = true;
      const opt = select ? select.options[select.selectedIndex] : null;
      const lines = [
        'Hello Guru Expert Power Tools, I would like to order:',
        'Product: ' + (opt ? opt.dataset.name : '') + (opt && opt.dataset.price ? ' (' + opt.dataset.price + ')' : ''),
        'Quantity: ' + v('qty'),
        'Name: ' + v('name'),
        'Phone: ' + v('phone'),
        'Delivery town: ' + v('town'),
      ];
      if (v('note')) lines.push('Note: ' + v('note'));
      if (opt && opt.dataset.url) lines.push(opt.dataset.url);
      openWa(lines.join('\n'));
      form.classList.add('is-sent');
    });
    $$('input, select, textarea', form).forEach((el) => {
      el.addEventListener('input', () => { if (el.getAttribute('aria-invalid') === 'true' && String(el.value).trim() !== '') el.setAttribute('aria-invalid', 'false'); });
    });
  }

  /* Jump nav: highlight the section in view. */
  const jumpLinks = $$('.gx-lp-jump a', root);
  if (jumpLinks.length && 'IntersectionObserver' in window) {
    const map = new Map();
    jumpLinks.forEach((a) => {
      const id = (a.getAttribute('href') || '').slice(1);
      const sec = id ? document.getElementById(id) : null;
      if (sec) map.set(sec, a);
    });
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        jumpLinks.forEach((a) => a.classList.remove('is-current'));
        const a = map.get(en.target);
        if (a) a.classList.add('is-current');
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    map.forEach((_a, sec) => io.observe(sec));
  }

  /* Sticky mobile action bar: show after the hero scrolls out of view. */
  const sticky = $('.gx-lp-sticky', root);
  const hero = $('.gx-lp-hero', root);
  if (sticky && hero && 'IntersectionObserver' in window) {
    new IntersectionObserver((entries) => {
      entries.forEach((en) => sticky.classList.toggle('is-visible', !en.isIntersecting));
    }, { threshold: 0 }).observe(hero);
  } else if (sticky) {
    sticky.classList.add('is-visible');
  }
})();
