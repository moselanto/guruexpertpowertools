<?php
/**
 * Category landing funnels ( /lp-{category}/ ).
 *
 * Ad and campaign traffic is sent to short URLs such as /lp-water-pumps/. Before this
 * module the theme had no code for those URLs: unless a published page existed with that
 * exact slug WordPress returned a 404 ("Nothing found"), and even when a page existed it
 * rendered only whatever text was typed into it, with no products.
 *
 * This module makes every /lp-{slug}/ URL a real landing page:
 *  - The slug after "lp-" is resolved to a WooCommerce product category (exact slug,
 *    then known aliases, then singular/plural, then a category-name match).
 *  - If no category matches, it falls back to a product keyword search, so a funnel
 *    never renders empty while the catalogue has matching products.
 *  - If a published WordPress page with the lp- slug exists, its title and content are
 *    used as the funnel headline and intro copy, so funnels can still be edited in
 *    WP admin. If no page exists, the funnel is generated automatically - no rewrite
 *    rules or permalink flush are needed.
 *
 * Extend or correct mappings with the `guruexpertpowertools_funnel_aliases` filter.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

final class Landing_Funnels {

	/** URL prefix every funnel uses. */
	private const PREFIX = 'lp-';

	/** Resolved funnel for the current request (null = not a funnel). */
	private static ?array $funnel = null;

	/** Whether the current request has already been inspected. */
	private static bool $resolved = false;

	public function hooks(): void {
		add_filter( 'pre_handle_404', array( $this, 'pre_handle_404' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_filter( 'pre_get_document_title', array( $this, 'document_title' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Default slug -> product_cat aliases. Keys are the part after "lp-".
	 *
	 * @return array<string, string[]>
	 */
	private static function aliases(): array {
		return (array) apply_filters(
			'guruexpertpowertools_funnel_aliases',
			array(
				'incubators'          => array( 'incubators', 'egg-incubators', 'incubator' ),
				'demolition-breakers' => array( 'demolition-breakers', 'demolition-hammers', 'breakers', 'demolition-hammer' ),
				'vacuum-cleaners'     => array( 'vacuum-cleaners', 'vacuum-cleaner', 'vacuums' ),
				'pressure-washers'    => array( 'pressure-washers', 'car-wash-equipment', 'pressure-washer' ),
				'water-pumps'         => array( 'water-pumps', 'pumps', 'water-pump' ),
				'hardware-tools'      => array( 'hardware-tools', 'hand-tools', 'hardware' ),
				'weighing-scales'     => array( 'weighing-scales', 'scales', 'weighing-scale' ),
				'batteries'           => array( 'batteries', 'solar-batteries', 'battery' ),
				'welding-machines'    => array( 'welding-machines', 'welding', 'welding-machine' ),
				'solar-panels'        => array( 'solar-panels', 'solar-panel', 'solar' ),
				'solar-inverters'     => array( 'solar-inverters', 'inverters', 'solar-inverter' ),
				'grinders'            => array( 'grinders', 'angle-grinders', 'grinder' ),
			)
		);
	}

	/**
	 * Read "lp-xyz" from the request path (first path segment only).
	 */
	private static function request_key(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		$path = trim( strtolower( $path ), '/' );
		if ( '' === $path || false !== strpos( $path, '/' ) || 0 !== strpos( $path, self::PREFIX ) ) {
			return '';
		}
		$key = sanitize_title( substr( $path, strlen( self::PREFIX ) ) );
		return $key;
	}

	/**
	 * Resolve the funnel for this request once.
	 */
	public static function current(): ?array {
		if ( self::$resolved ) {
			return self::$funnel;
		}
		self::$resolved = true;

		if ( is_admin() || ! taxonomy_exists( 'product_cat' ) || ! post_type_exists( 'product' ) ) {
			return null;
		}
		$key = self::request_key();
		if ( '' === $key ) {
			return null;
		}

		$page = get_page_by_path( self::PREFIX . $key );
		if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status ) {
			$page = null;
		}

		$term     = self::resolve_term( $key );
		$label    = ucwords( str_replace( '-', ' ', $key ) );
		$keywords = $term ? '' : self::keywords( $key );

		self::$funnel = array(
			'key'      => $key,
			'page'     => $page,
			'term'     => $term,
			'keywords' => $keywords,
			'title'    => $page ? get_the_title( $page ) : ( $term ? $term->name : $label ),
		);
		return self::$funnel;
	}

	/**
	 * Find the best matching, non-empty product category.
	 */
	private static function resolve_term( string $key ): ?\WP_Term {
		$aliases    = self::aliases();
		$candidates = array_merge( array( $key ), $aliases[ $key ] ?? array() );
		// Singular / plural variants.
		$candidates[] = preg_replace( '/s$/', '', $key );
		$candidates[] = $key . 's';
		$candidates   = array_values( array_unique( array_filter( $candidates ) ) );

		foreach ( $candidates as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term instanceof \WP_Term && self::term_has_products( $term ) ) {
				return $term;
			}
		}

		// Name match, e.g. "Demolition Breakers" or "Breakers".
		$name  = str_replace( '-', ' ', $key );
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'name__like' => preg_replace( '/s$/', '', $name ),
				'number'     => 10,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term && self::term_has_products( $term ) ) {
					return $term;
				}
			}
		}
		return null;
	}

	/**
	 * A term "has products" if it or any child category holds a published product.
	 * (term->count ignores children, so parent categories often report 0.)
	 */
	private static function term_has_products( \WP_Term $term ): bool {
		if ( $term->count > 0 ) {
			return true;
		}
		$q = new \WP_Query( self::base_args( array( 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true ), $term, '' ) );
		return $q->have_posts();
	}

	/**
	 * Keyword fallback: "vacuum-cleaners" -> "vacuum cleaner".
	 */
	private static function keywords( string $key ): string {
		return trim( (string) preg_replace( '/s$/', '', str_replace( '-', ' ', $key ) ) );
	}

	/**
	 * Shared product query args.
	 *
	 * @param array         $extra    Extra args.
	 * @param \WP_Term|null $term     Category.
	 * @param string        $keywords Search fallback.
	 */
	private static function base_args( array $extra, ?\WP_Term $term, string $keywords ): array {
		$tax = array( 'relation' => 'AND' );
		if ( $term ) {
			$tax[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( $term->term_id ),
				'include_children' => true,
			);
		}
		$tax[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( ! $term && '' !== $keywords ) {
			$args['s'] = $keywords;
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_stock_status', 'value' => 'outofstock', 'compare' => '!=' ),
			);
		}
		return array_merge( $args, $extra );
	}

	/**
	 * Products for the current funnel.
	 */
	public static function products( int $limit = 24 ): \WP_Query {
		$f = self::current();
		if ( ! $f ) {
			return new \WP_Query( array( 'post__in' => array( 0 ) ) );
		}
		$q = new \WP_Query(
			self::base_args(
				array(
					'posts_per_page' => $limit,
					'no_found_rows'  => false,
					'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				),
				$f['term'],
				$f['keywords']
			)
		);
		// Keyword search can miss when the slug is plural and titles are singular (or vice versa).
		if ( ! $q->have_posts() && ! $f['term'] ) {
			$q = new \WP_Query(
				self::base_args(
					array( 'posts_per_page' => $limit ),
					null,
					str_replace( '-', ' ', $f['key'] )
				)
			);
		}
		return $q;
	}

	/**
	 * Stop WordPress flagging /lp-{slug}/ as a 404 when it has no matching page.
	 *
	 * @param bool      $preempt  Whether to short-circuit.
	 * @param \WP_Query $wp_query Main query.
	 */
	public function pre_handle_404( $preempt, $wp_query ) {
		if ( $preempt ) {
			return $preempt;
		}
		$f = self::current();
		if ( ! $f ) {
			return $preempt;
		}
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404  = false;
			$wp_query->is_home = false;
		}
		status_header( 200 );
		nocache_headers();
		return true;
	}

	/**
	 * Render the funnel template for /lp-{slug}/ (with or without a WP page).
	 *
	 * @param string $template Template path.
	 */
	public function template_include( $template ) {
		if ( ! self::current() ) {
			return $template;
		}
		global $wp_query;
		if ( $wp_query instanceof \WP_Query && $wp_query->is_404 ) {
			$wp_query->is_404 = false;
			status_header( 200 );
		}
		$file = GURUEXPERTPOWERTOOLS_DIR . 'landing-funnel.php';
		return is_readable( $file ) ? $file : $template;
	}

	/**
	 * Proper <title> for generated funnels.
	 *
	 * @param string $title Title.
	 */
	public function document_title( $title ) {
		$f = self::current();
		if ( ! $f ) {
			return $title;
		}
		return sprintf(
			/* translators: 1: funnel title, 2: site name */
			__( '%1$s in Kenya | %2$s', 'guruexpertpowertools' ),
			$f['title'],
			get_bloginfo( 'name' )
		);
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		$f = self::current();
		if ( $f ) {
			$classes   = array_diff( (array) $classes, array( 'error404' ) );
			$classes[] = 'gx-funnel-page';
			$classes[] = 'gx-funnel-' . sanitize_html_class( $f['key'] );
		}
		return $classes;
	}
}
