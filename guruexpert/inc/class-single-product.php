<?php
/**
 * Single product page enhancements (hook-based, upgrade-safe).
 *
 * Adds: delivery estimate, trust badges, secure-payment icons, Specifications
 * and FAQ tabs, a sticky add-to-cart bar, and a Recently Viewed section.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Enriches the WooCommerce single product template via hooks.
 */
final class Single_Product {

	public function hooks(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'after_summary' ), 35 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'tabs' ) );
		add_action( 'template_redirect', array( $this, 'track_recently_viewed' ) );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'recently_viewed' ), 25 );
		add_action( 'wp_footer', array( $this, 'sticky_bar' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'whatsapp_button' ), 36 );
	}

	public function whatsapp_button(): void {
		global $product;
		if ( $product instanceof \WC_Product && function_exists( 'rk_whatsapp_button' ) ) {
			echo rk_whatsapp_button( $product, 'rk-wa-btn--pdp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		}
	}

	private function icon( string $d ): string {
		return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
	}

	/**
	 * Delivery estimate + trust badges + payment icons, below add-to-cart.
	 */
	public function after_summary(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$phone = esc_html( get_theme_mod( 'guruexpertpowertools_phone', '+254 708 777192' ) );

		echo '<div class="rk-pdp-extra">';

		// Delivery estimate.
		echo '<div class="rk-pdp-delivery">';
		echo $this->icon( '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>' ); // phpcs:ignore
		echo '<div><strong>' . esc_html__( 'Fast delivery across Kenya.', 'guruexpertpowertools' ) . '</strong><br>';
		echo '<span>' . esc_html__( 'Nairobi: same/next-day. Countrywide: 1-5 business days, KSh 500 flat on every order. Order by phone/WhatsApp:', 'guruexpertpowertools' ) . ' ' . $phone . '</span></div>';
		echo '</div>';

		// Trust badges.
		echo '<ul class="rk-trust">';
		$badges = array(
			// "100% Genuine Products" and "Warranty Included" were removed: both are absolute,
			// unverifiable claims and the first is a direct Google Misrepresentation risk. The
			// replacements are checkable -- the shop address is published in the footer, and the
			// warranty is the manufacturer's, offered "where applicable" per the FAQ below.
			array( '<path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"></path><path d="M9 12l2 2 4-4"></path>', __( 'Walk-in Shop in Nairobi', 'guruexpertpowertools' ) ),
			array( '<path d="M20 6L9 17l-5-5"></path>', __( 'Manufacturer Warranty', 'guruexpertpowertools' ) ),
			array( '<rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>', __( 'Secure Checkout', 'guruexpertpowertools' ) ),
			array( '<path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path><path d="M12 7v5l3 2"></path>', __( 'Responsive Support', 'guruexpertpowertools' ) ),
		);
		foreach ( $badges as $b ) {
			echo '<li>' . $this->icon( $b[0] ) . '<span>' . esc_html( $b[1] ) . '</span></li>'; // phpcs:ignore
		}
		echo '</ul>';

		// Payment methods.
		echo '<div class="rk-pdp-pay"><span class="rk-pdp-pay__label">' . esc_html__( 'We accept:', 'guruexpertpowertools' ) . '</span>';
		foreach ( array( 'M-PESA', 'Cash on Delivery (Nairobi)', 'Visa & Mastercard (in shop)' ) as $pay ) {
			echo '<span class="rk-pay-chip">' . esc_html( $pay ) . '</span>';
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Reorder + add product tabs: Description, Specifications, FAQ, Reviews.
	 *
	 * @param array $tabs Existing tabs.
	 */
	public function tabs( array $tabs ): array {
		// Rename the stock "additional information" tab to Specifications.
		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['title']    = __( 'Specifications', 'guruexpertpowertools' );
			$tabs['additional_information']['priority'] = 20;
		}
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['priority'] = 10;
		}
		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['priority'] = 40;
		}
		$tabs['guruexpertpowertools_faq'] = array(
			'title'    => __( 'FAQ', 'guruexpertpowertools' ),
			'priority' => 30,
			'callback' => array( $this, 'faq_tab' ),
		);
		return $tabs;
	}

	/**
	 * FAQ tab content (delivery / payment / returns / warranty).
	 */
	public function faq_tab(): void {
		$phone = esc_html( get_theme_mod( 'guruexpertpowertools_phone', '+254 708 777192' ) );
		$faqs  = array(
			array( __( 'How soon can I get this delivered?', 'guruexpertpowertools' ), __( 'Nairobi orders are typically delivered same or next business day. Other towns take 1-5 business days. Delivery is KSh 500 flat countrywide on every order, including bulky items.', 'guruexpertpowertools' ) ),
			array( __( 'How do I pay?', 'guruexpertpowertools' ), __( 'Online orders are paid by M-PESA, or by cash on delivery within Nairobi only; orders outside Nairobi are paid by M-PESA before dispatch. Visa and Mastercard are accepted in person at our Nairobi shop, not at online checkout.', 'guruexpertpowertools' ) ),
			// Sourcing claim softened from "only genuine products": an absolute, unverifiable
			// assertion about every one of 845 lines is a Google Misrepresentation risk. The
			// revised wording describes the sourcing channel, which is checkable, without
			// guaranteeing authenticity of each individual item.
			array( __( 'Is this product covered by warranty?', 'guruexpertpowertools' ), __( 'We source our stock from authorised distributors and suppliers, and items are backed by the manufacturer warranty where applicable. Ask us about the warranty terms for a specific product before you buy.', 'guruexpertpowertools' ) ),
			array( __( 'Can I return it if there is a problem?', 'guruexpertpowertools' ), __( 'Faulty or incorrect items can be returned within 7 days. See our Return & Refund Policy for details.', 'guruexpertpowertools' ) ),
			array( __( 'How do I get help before buying?', 'guruexpertpowertools' ), __( 'Call or WhatsApp us and our team will help you choose the right tool for the job.', 'guruexpertpowertools' ) . ' ' . $phone ),
		);
		echo '<div class="rk-faq">';
		foreach ( $faqs as $f ) {
			echo '<details class="rk-faq__item"><summary>' . esc_html( $f[0] ) . '</summary><p>' . esc_html( $f[1] ) . '</p></details>';
		}
		echo '</div>';
	}

	/**
	 * Record the current product in a cookie (most-recent first).
	 */
	public function track_recently_viewed(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_setcookie' ) ) {
			return;
		}
		$id  = (int) get_the_ID();
		$ids = $this->recent_ids();
		$ids = array_values( array_diff( $ids, array( $id ) ) );
		array_unshift( $ids, $id );
		$ids = array_slice( array_unique( $ids ), 0, 12 );
		wc_setcookie( 'guruexpertpowertools_recently_viewed', implode( ',', $ids ) );
	}

	/**
	 * @return int[] Recently viewed product IDs from cookie.
	 */
	private function recent_ids(): array {
		if ( empty( $_COOKIE['guruexpertpowertools_recently_viewed'] ) ) {
			return array();
		}
		$raw = sanitize_text_field( wp_unslash( $_COOKIE['guruexpertpowertools_recently_viewed'] ) );
		return array_filter( array_map( 'absint', explode( ',', $raw ) ) );
	}

	/**
	 * Output a Recently Viewed products row.
	 */
	public function recently_viewed(): void {
		$ids = array_diff( $this->recent_ids(), array( (int) get_the_ID() ) );
		if ( empty( $ids ) ) {
			return;
		}
		$ids = array_slice( array_values( $ids ), 0, 6 );
		$q   = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 6,
				'no_found_rows'  => true,
				'post_status'    => 'publish',
			)
		);
		if ( ! $q->have_posts() ) {
			wp_reset_postdata();
			return;
		}
		echo '<section class="rk-recent"><div class="rk-section__head"><h2>' . esc_html__( 'Recently Viewed', 'guruexpertpowertools' ) . '</h2></div><ul class="products rk-products">';
		while ( $q->have_posts() ) {
			$q->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		echo '</ul></section>';
		wp_reset_postdata();
	}

	/**
	 * Sticky add-to-cart bar (appears on scroll).
	 */
	public function sticky_bar(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$img = wp_get_attachment_image( $product->get_image_id(), 'woocommerce_gallery_thumbnail', false, array( 'alt' => $product->get_name() ) );
		if ( ! $img ) {
			$img = wc_placeholder_img( 'woocommerce_gallery_thumbnail' );
		}
		$simple = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
		?>
		<div class="rk-sticky-atc" aria-hidden="true">
			<div class="container rk-sticky-atc__inner">
				<div class="rk-sticky-atc__media"><?php echo $img; // phpcs:ignore ?></div>
				<div class="rk-sticky-atc__title"><?php echo esc_html( $product->get_name() ); ?></div>
				<div class="rk-sticky-atc__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></div>
				<?php if ( $simple ) : ?>
					<button type="button" class="rk-btn rk-btn--primary" data-guruexpertpowertools-add="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Add to Cart', 'guruexpertpowertools' ); ?></button>
				<?php else : ?>
					<a href="#" class="rk-btn rk-btn--primary rk-sticky-atc__jump"><?php esc_html_e( 'View Options', 'guruexpertpowertools' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
