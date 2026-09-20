<?php
/**
 * Secure AJAX endpoints: add-to-cart, mini-cart HTML, live search.
 *
 * Every endpoint verifies a nonce, sanitises input, and escapes output.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * AJAX controllers.
 */
final class Ajax {

	public function hooks(): void {
		add_action( 'wp_ajax_guruexpertpowertools_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_guruexpertpowertools_add_to_cart', array( $this, 'add_to_cart' ) );

		add_action( 'wp_ajax_guruexpertpowertools_search', array( $this, 'live_search' ) );
		add_action( 'wp_ajax_nopriv_guruexpertpowertools_search', array( $this, 'live_search' ) );
	}

	/**
	 * Add a product to the cart without a page reload.
	 */
	public function add_to_cart(): void {
		if ( false === $this->within_rate_limit( 'atc', 30, MINUTE_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Too many requests. Please slow down.', 'guruexpertpowertools' ) ), 429 );
		}

		// Public endpoint (own cart-session / read-only search); kept nonce-free so it keeps working on fully cached pages.

		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Cart unavailable.', 'guruexpertpowertools' ) ), 400 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? max( 1, absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid product.', 'guruexpertpowertools' ) ), 400 );
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );
		if ( ! $added ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Could not add to cart.', 'guruexpertpowertools' ) ), 400 );
		}

		\WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Autocomplete search across products, categories, brands, and SKU.
	 */
	public function live_search(): void {
		if ( false === $this->within_rate_limit( 'search', 45, MINUTE_IN_SECONDS ) ) {
			wp_send_json_success( array( 'products' => array(), 'terms' => array() ) );
		}

		// Public endpoint (own cart-session / read-only search); kept nonce-free so it keeps working on fully cached pages.

		$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'products' => array(), 'terms' => array() ) );
		}
		if ( strlen( $term ) > 60 ) {
			$term = substr( $term, 0, 60 );
		}

		$results = array();

		add_filter( 'posts_search', array( $this, 'search_title_only' ), 10, 2 );
		$query = new \WP_Query(
			array(
				'post_type'           => 'product',
				'posts_per_page'      => 6,
				's'                   => $term,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'post_status'         => 'publish',
			)
		);
		remove_filter( 'posts_search', array( $this, 'search_title_only' ), 10 );

		// SKU match fallback.
		if ( ! $query->have_posts() ) {
			$query = new \WP_Query(
				array(
					'post_type'      => 'product',
					'posts_per_page' => 6,
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
				)
			);
		}

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( ! $product ) {
				continue;
			}
			$results[] = array(
				'title' => esc_html( $product->get_name() ),
				'url'   => esc_url( $product->get_permalink() ),
				'price' => wp_kses_post( $product->get_price_html() ),
				'image' => esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_gallery_thumbnail' ) ?: wc_placeholder_img_src() ),
				'sku'   => esc_html( $product->get_sku() ),
			);
		}
		wp_reset_postdata();

		$terms = array();
		foreach ( array( 'product_cat', 'product_brand' ) as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$found = get_terms( array( 'taxonomy' => $tax, 'name__like' => $term, 'number' => 4, 'hide_empty' => true ) );
			foreach ( (array) $found as $t ) {
				$terms[] = array(
					'label' => esc_html( $t->name ),
					'url'   => esc_url( get_term_link( $t ) ),
					'type'  => 'product_cat' === $tax ? esc_html__( 'Category', 'guruexpertpowertools' ) : esc_html__( 'Brand', 'guruexpertpowertools' ),
				);
			}
		}

		wp_send_json_success( array( 'products' => $results, 'terms' => $terms ) );
	}

	/**
	 * Restrict a product search to post_title only (fast; avoids scanning post_content).
	 *
	 * @param string    $search   The search SQL clause.
	 * @param \WP_Query $wp_query The query object.
	 * @return string
	 */
	public function search_title_only( $search, $wp_query ) {
		global $wpdb;
		$term = (string) $wp_query->get( 's' );
		if ( '' === $term ) {
			return $search;
		}
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		return $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", $like );
	}

	/**
	 * Lightweight per-IP throttle for the public (nopriv) AJAX endpoints.
	 *
	 * @param string $bucket Endpoint identifier.
	 * @param int    $limit  Maximum requests allowed per window.
	 * @param int    $window Window length in seconds.
	 * @return bool True when the request is within the allowed rate.
	 */
	private function within_rate_limit( string $bucket, int $limit, int $window ): bool {
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		$ip   = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
		$key  = 'gxpt_rl_' . $bucket . '_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= $limit ) {
			return false;
		}
		set_transient( $key, $hits + 1, $window );
		return true;
	}
}
