/* Guru Expert Power Tools - category sales funnels: price filters, WhatsApp quick order, sticky bar. */
(() => {
  'use strict';
  const root = document.querySelector('.gx-lp');
  if (!root) return;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const wa = (root.getAttribute('data-gx-wa') || '').replace(/[^0-9]/g, '');
  const pageUrl = root.getAttribute('data-gx-page') || window.location.href;

  /* Price-band filter chips */
  const grid = $('[data-gx-filter-target]', root);
  const emptyMsg = $('.gx-lp-empty-filter', root);
  $$('.gx-lp-chip', root).forEach((chip) => {
    chip.addEventListener('click', () => {
      const min = parseFloat(chip.dataset.min || '0');
      const max = parseFloat(chip.dataset.max || '0');
      $$('.gx-lp-chip', root).forEach((c) => { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
      if (!grid) return;
      let shown = 0;
      $$('.gx-lp-card', grid).forEach((card) => {
        const p = parseFloat(card.dataset.price || '0');
        const ok = p >= min && (max === 0 || p < max);
        card.hidden = !ok;
        if (ok) shown++;
      });
      if (emptyMsg) emptyMsg.hidden = shown > 0;
    });
  });

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
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const qty = $('input[name="qty"]', form);
      window.setTimeout(() => { (qty || select).focus({ preventScroll: true }); }, 450);
      return;
    }
    const card = btn.closest('.gx-lp-card');
    const name = card ? (card.querySelector('.gx-lp-card__title') || {}).textContent : '';
    openWa('Hello Guru Expert Power Tools, I would like to order: ' + (name || '').trim() + '\n' + pageUrl);
  });

  if (form) {
    const err = $('.gx-lp-form__error', form);
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const v = (n) => (form.elements[n] ? String(form.elements[n].value || '').trim() : '');
      const fields = ['name', 'phone', 'town', 'qty'];
      let bad = null;
      fields.forEach((n) => {
        const el = form.elements[n];
        if (!el) return;
        let ok = v(n) !== '';
        if (n === 'phone') ok = v(n).replace(/[^0-9]/g, '').length >= 9;
        if (n === 'qty') ok = parseInt(v(n), 10) >= 1;
        el.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (!ok && !bad) bad = el;
      });
      if (bad) {
        if (err) { err.textContent = 'Please fill in your name, a valid phone number, delivery town and quantity.'; err.hidden = false; }
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
