# Guru Expert Power Tools

Source code for **[guruexpertpowertools.co.ke](https://guruexpertpowertools.co.ke/)** — a Kenyan
e-commerce store selling power tools, solar equipment, and hardware, built on WordPress and
WooCommerce.

This repository holds the two custom pieces of that site: the storefront theme and a WebP image
optimizer plugin. Everything else (WordPress core, WooCommerce, third-party plugins) is installed
normally and is not tracked here.

| | |
|---|---|
| **Live site** | https://guruexpertpowertools.co.ke/ |
| **Stack** | WordPress 6.5+, WooCommerce 9+, PHP 8.1+ (tested to 8.3) |
| **Theme version** | 1.24.x |
| **Catalogue size** | ~845 products |
| **License** | GPL v2 or later |

---

## Repository layout

```
guruexpert/             The Guru Expert Power Tools WooCommerce theme
  assets/               CSS, JS, fonts, images (WebP-optimized)
  inc/                  Namespaced PHP classes (GuruExpertPowerTools\)
  template-parts/       Reusable template partials
  woocommerce/          WooCommerce template overrides
  demo/                 Demo import content
  languages/            Translation files (.pot)
  README.md             Theme documentation and install guide
  ROADMAP.md            Remaining build phases
  SETUP-FLOW.md         First-run setup walkthrough
  IMPORT-PRODUCTS.md    Product CSV import instructions
  PERFORMANCE-htaccess-rules.txt   Optional caching/compression rules

guruexpert-webp/        WebP Optimizer plugin (one-click WebP for the Media Library)
```

## The theme (`guruexpert/`)

A conversion-focused WooCommerce theme written specifically for hardware and power-tool retail in
Kenya. Brand colours: green `#208050` and deep green `#0E2A1C`.

Highlights:

- **Object-oriented and namespaced** (`GuruExpertPowerTools\`) with an autoloader — no global functions soup.
- **Header** with contact bar (phone, WhatsApp, email, hours), sticky-on-scroll behaviour, and
  debounced AJAX search across products, categories, brands, and SKUs.
- **Homepage** (`front-page.php`): hero slider with touch/keyboard/autoplay support, "Shop by
  Category" cards with live product counts, and one product row per category.
- **Uniform product cards**: 1:1 lazy-loaded images, clamped titles and descriptions, sale/stock/
  featured badges, star ratings, AJAX add-to-cart, and an "Order on WhatsApp" button.
- **Performance**: WebP image pipeline, deferred non-critical JS, inlined critical CSS, deferred
  Google Fonts, and WooCommerce/block-library asset trimming on non-shop pages.
- **Security**: hardening headers, version disclosure removal, `DISALLOW_FILE_EDIT`, xmlrpc
  disabled, and nonce-verified, capability-checked AJAX endpoints.
- **SEO and Merchant Center**: JSON-LD Organization, Store, and LocalBusiness schema sitewide, plus
  Product schema (SKU, MPN, GTIN, brand, price, availability, condition, shipping, return policy)
  emitted in `wp_head` on product pages.
- **Compliance**: cookie-consent module with Google Consent Mode v2 defaults, written against
  Kenya's Data Protection Act 2019.
- **Content**: legal and policy pages (Privacy, Terms, Shipping & Delivery, Returns & Refunds,
  Warranty, Payment Methods, Cookie Policy, FAQ, Track Order) are created automatically on
  activation with real, Kenya-specific copy — not placeholders.
- **Accessibility**: skip link, visible focus states, ARIA labels, reduced-motion support.
- **Translation-ready**, **RTL-ready**, and **child-theme ready**.

Not yet implemented: quick view, wishlist, compare, and the advanced AJAX filter sidebar. See
[`guruexpert/ROADMAP.md`](guruexpert/ROADMAP.md).

## The plugin (`guruexpert-webp/`)

**Guru Expert Power Tools WebP Optimizer** (v1.1.0, requires WordPress 5.5+ / PHP 7.2+):

- Converts every JPEG and PNG in the Media Library to WebP, keeping originals intact.
- Auto-converts new uploads, including every generated thumbnail size.
- Serves WebP to supporting browsers via auto-managed `.htaccess` rules — URLs stay `.jpg`/`.png`
  while the delivered bytes are WebP.
- Deactivating removes the rules and stops conversion; images are never deleted.

Activate it, then run **Media → WebP Optimizer → Optimize all images now**.

## Installation

1. Zip the `guruexpert/` folder and upload it under **Appearance → Themes → Add New → Upload Theme**, then activate.
2. Install the prompted plugins (TGMPA) — at minimum WooCommerce and Perfect Brands for WooCommerce.
   Note: the TGMPA library itself must be placed at `guruexpert/inc/tgmpa/class-tgm-plugin-activation.php`
   (download from https://tgmpluginactivation.com/).
3. Zip and install `guruexpert-webp/` under **Plugins → Add New → Upload Plugin**, then activate.
4. Upload your logo at **Appearance → Customize → Site Identity**; set brand colours and contact
   details under **Customize → Guru Expert Power Tools**.
5. Import products — see [`guruexpert/IMPORT-PRODUCTS.md`](guruexpert/IMPORT-PRODUCTS.md).
6. Optional: apply the caching and compression rules in
   [`guruexpert/PERFORMANCE-htaccess-rules.txt`](guruexpert/PERFORMANCE-htaccess-rules.txt).

Full walkthrough: [`guruexpert/SETUP-FLOW.md`](guruexpert/SETUP-FLOW.md).

Requirements: WordPress 6.5+, WooCommerce 9+, PHP 8.1+, and HTTPS (required for secure checkout
and Google Merchant Center).

## Store policies reflected in the code

These values are hard-coded into templates, copy, and structured data. Keep them in sync if the
business policy changes:

- **Delivery:** 1–5 business days, countrywide.
- **Delivery fee:** flat KSh 500 countrywide; bulky items quoted separately.
- **Cash on Delivery:** Nairobi only. Orders outside Nairobi are paid before dispatch.
- **Returns:** 7 days.
- **Warranty:** manufacturer warranty (not a store-issued warranty).

## Contributing and conventions

- Work is committed **directly to `main`**; feature branches and pull requests are used only when
  explicitly requested.
- Live-site changes and repository commits are kept in sync — GitHub commits do not auto-deploy,
  so a change made in WordPress admin should also land here (and vice versa) to prevent a future
  theme upload reverting live edits.
- Put custom code in a child theme so theme updates do not overwrite it.

## Contact

- Phone / WhatsApp: +254 708 777192
- Email: info@guruexpertpowertools.co.ke
- Address: Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi, Kenya

## License

GNU General Public License v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
