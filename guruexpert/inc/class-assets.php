<?php
/**
 * Front-end + editor asset loading with performance defaults.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues styles/scripts, preloads fonts, defers non-critical JS.
 */
final class Assets {

	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'preload_and_critical' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
		add_filter( 'style_loader_tag', array( $this, 'async_font_css' ), 10, 4 );
		// Trim WooCommerce bloat on non-woo pages (perf).
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_woo_bloat' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_unused_plugin_css' ), 100 );
			add_action( 'init', array( $this, 'trim_head' ) );
	}

	public function enqueue(): void {
		$css_rel = file_exists( GURUEXPERTPOWERTOOLS_DIR . 'assets/css/theme.min.css' ) ? 'assets/css/theme.min.css' : 'assets/css/theme.css';
		wp_enqueue_style( 'guruexpertpowertools-theme', GURUEXPERTPOWERTOOLS_URI . $css_rel, array(), GURUEXPERTPOWERTOOLS_VERSION );
		wp_style_add_data( 'guruexpertpowertools-theme', 'rtl', 'replace' );
		// Oswald 500/600 were requested but never used: every Oswald rule either declares
		// 700 or inherits a heading weight of 700/800. Dropping them saves two font files.
		// All five Inter weights are genuinely used (400 body default; 500/600/700/800
		// appear 2/12/45/38 times in the CSS), so they are deliberately retained.
		wp_enqueue_style( 'guruexpertpowertools-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@700&display=swap', array(), null );
		wp_enqueue_style( 'guruexpertpowertools-industrial', GURUEXPERTPOWERTOOLS_URI . 'assets/css/theme-industrial.css', array( 'guruexpertpowertools-theme' ), GURUEXPERTPOWERTOOLS_VERSION );

		// Homepage-only design layer.
		if ( is_front_page() ) {
			wp_enqueue_style( 'guruexpertpowertools-home', GURUEXPERTPOWERTOOLS_URI . 'assets/css/home.css', array( 'guruexpertpowertools-industrial' ), GURUEXPERTPOWERTOOLS_VERSION );
		}

		wp_enqueue_script( 'guruexpertpowertools-theme', GURUEXPERTPOWERTOOLS_URI . 'assets/js/theme.js', array(), GURUEXPERTPOWERTOOLS_VERSION, true );

		if ( class_exists( 'WooCommerce' ) ) {
			wp_enqueue_script( 'guruexpertpowertools-ajax', GURUEXPERTPOWERTOOLS_URI . 'assets/js/ajax-cart.js', array( 'guruexpertpowertools-theme' ), GURUEXPERTPOWERTOOLS_VERSION, true );
			wp_localize_script(
				'guruexpertpowertools-ajax',
				'GuruExpertPowerToolsAjax',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'guruexpertpowertools_ajax' ),
					'cartUrl'   => wc_get_cart_url(),
					'i18n'      => array(
						'added'   => esc_html__( 'Added to cart', 'guruexpertpowertools' ),
						'adding'  => esc_html__( 'Adding...', 'guruexpertpowertools' ),
						'error'   => esc_html__( 'Something went wrong. Please try again.', 'guruexpertpowertools' ),
						'viewCart'=> esc_html__( 'View cart', 'guruexpertpowertools' ),
					),
				)
			);
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Inline minimal critical CSS for fast FCP. Uses system fonts (no webfont download).
	 */
	public function preload_and_critical(): void {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		if ( is_front_page() ) {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( GURUEXPERTPOWERTOOLS_URI . 'assets/img/slides/slide-power-tools.jpg' ) );
		}
		echo '<style id="guruexpertpowertools-critical">:root{--rk-primary:#208050;--rk-navy:#0E2A1C}body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#1a1f2e;background:#fff}.rk-header{background:var(--rk-navy)}img{max-width:100%;height:auto}</style>' . "\n";
	}

	/**
	 * Load the Google Fonts stylesheet without blocking first paint.
	 *
	 * The font CSS sits on a third-party origin, so requesting it as a normal
	 * stylesheet blocks rendering until it resolves. "display=swap" only governs
	 * how the font FILE swaps in; it does nothing for this CSS request. Fetching
	 * it as media="print" and promoting it to "all" on load keeps it off the
	 * critical path, and the inline critical CSS above renders text in the system
	 * stack until Inter arrives. The noscript copy preserves the fonts when
	 * JavaScript is unavailable.
	 */
	public function async_font_css( $tag, $handle, $href = '', $media = '' ) {
		if ( 'guruexpertpowertools-fonts' === $handle && is_string( $tag ) && is_string( $href ) && '' !== $href ) {
			return sprintf(
				'<link rel="stylesheet" id="guruexpertpowertools-fonts-css" href="%1$s" media="print" onload="this.media=\'all\';this.onload=null;">' . "\n"
				. '<noscript><link rel="stylesheet" href="%1$s"></noscript>' . "\n",
				esc_url( $href )
			);
		}
		return $tag;
	}

	/**
	 * Defer all theme JS to remove render-blocking.
	 */
	public function defer_scripts( $tag, $handle = '', $src = '' ) {
		$defer = array( 'guruexpertpowertools-theme', 'guruexpertpowertools-ajax' );
		if ( is_string( $tag ) && in_array( $handle, $defer, true ) && false === strpos( $tag, 'defer' ) ) {
			$tag = str_replace( ' src', ' defer src', $tag );
		}
		return $tag;
	}

	/**
	 * Only load WooCommerce cart/checkout assets where needed.
	 */
	public function dequeue_woo_bloat(): void {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
			wp_dequeue_style( 'wc-blocks-style' );
		}
	}

	/**
	 * Drop plugin CSS that the storefront templates never actually use.
	 *
	 * Measured against the live markup on the front page, shop archive and a
	 * single product: zero wp-block-* classes and zero Contact Form 7 form
	 * markup on any of them, so both stylesheets were pure render-blocking
	 * weight. (The site is built with Elementor, which is why Gutenberg's
	 * block styles are dead weight here.)
	 *
	 * Scope is deliberately narrow rather than site-wide:
	 * - Cart, checkout and account bail out early: WooCommerce Blocks on those
	 *   screens depends on block-library styles, and removing it breaks layout.
	 * - WooCommerce's own core stylesheets are intentionally NOT touched. The
	 *   front page renders real product loops (48 li.product items), so
	 *   dequeuing them would risk the grid even though the theme restyles
	 *   those selectors.
	 */
	public function dequeue_unused_plugin_css(): void {
		if ( is_admin() ) {
			return;
		}

		// WooCommerce Blocks on these screens rely on block-library CSS.
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return;
		}

		// Only the contexts actually measured.
		$is_storefront = is_front_page();
		if ( function_exists( 'is_shop' ) ) {
			$is_storefront = $is_storefront || is_shop() || is_product() || is_product_category() || is_product_tag();
		}
		if ( false === $is_storefront ) {
			return;
		}

		$post = get_post();

		// Gutenberg styles: keep them if the page genuinely contains blocks.
		if ( ( ! $post instanceof \WP_Post ) || ( ! has_blocks( $post ) ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}

		// Contact Form 7: keep the CSS wherever a form is embedded. Elementor
		// stores its widgets outside post_content, so check that payload too
		// before concluding the page has no form.
		$has_form = false;
		if ( $post instanceof \WP_Post ) {
			$has_form = has_shortcode( (string) $post->post_content, 'contact-form-7' );
			if ( false === $has_form ) {
				$elementor = get_post_meta( $post->ID, '_elementor_data', true );
				if ( is_string( $elementor ) && false !== strpos( $elementor, 'contact-form-7' ) ) {
					$has_form = true;
				}
			}
		}
		if ( false === $has_form ) {
			wp_dequeue_style( 'contact-form-7' );
		}
	}

	/**
	 * Strip front-end bloat: emoji detection, embed script, generator/rsd meta.
	 */
	public function trim_head(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter(
			'tiny_mce_plugins',
			static function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
			}
		);
		add_action(
			'wp_footer',
			static function () {
				wp_dequeue_script( 'wp-embed' );
			},
			1
		);
	}

}
