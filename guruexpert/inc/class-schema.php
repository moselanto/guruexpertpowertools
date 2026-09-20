<?php
/**
 * Structured data (JSON-LD) + SEO meta: Organization, WebSite, Breadcrumb, Product,
 * plus meta description and Open Graph / Twitter cards. Output is suppressed when a
 * dedicated SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO) is active, to avoid duplicates.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Emits schema.org JSON-LD and social meta for SEO.
 */
final class Schema {

	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'meta_tags' ), 4 );
		add_action( 'wp_head', array( $this, 'organization' ), 5 );
		add_action( 'wp_head', array( $this, 'website' ), 6 );
		add_action( 'wp_head', array( $this, 'breadcrumb' ), 7 );
		// Product JSON-LD belongs in <head>: more reliable parsing, and it survives
		// aggressive full-page caching (LiteSpeed) that can truncate late footer output.
		add_action( 'wp_head', array( $this, 'product' ), 8 );

		/*
		 * WooCommerce core emits its OWN Product and BreadcrumbList JSON-LD into a
		 * footer @graph, duplicating what this class already outputs in <head>. Two
		 * Product entities on one page is precisely what raises "Product snippets
		 * structured data issues" in Search Console: Google cannot decide which is
		 * canonical. Verified on 19 Sep 2026 -- every product page carried 4 JSON-LD
		 * blocks containing 2 Product objects and 2 BreadcrumbList objects.
		 *
		 * This theme's Product block is the one retained, because it carries
		 * shippingDetails, hasMerchantReturnPolicy, itemCondition and mpn. WooCommerce's
		 * version has none of those, and all four matter for merchant listings.
		 *
		 * Guarded on has_seo_plugin(): when a dedicated SEO plugin is active this class
		 * emits nothing at all, so WooCommerce's copy must be left in place rather than
		 * stripping it and leaving the page with no Product markup whatsoever.
		 */
		if ( ! $this->has_seo_plugin() ) {
			add_filter( 'woocommerce_structured_data_product', array( $this, 'drop_duplicate_woo_schema' ) );
			add_filter( 'woocommerce_structured_data_breadcrumblist', array( $this, 'drop_duplicate_woo_schema' ) );
		}
	}

	/**
	 * Remove a WooCommerce structured-data block that this theme already emits.
	 *
	 * Returning an empty array drops the block from WooCommerce's @graph output
	 * without disturbing any other structured data it generates.
	 *
	 * @param mixed $data WooCommerce structured data for the block.
	 * @return array Always empty.
	 */
	public function drop_duplicate_woo_schema( $data ): array {
		unset( $data );
		return array();
	}

	/**
	 * True when a dedicated SEO plugin is active (so we defer to it).
	 */
	private function has_seo_plugin(): bool {
		return (
			defined( 'WPSEO_VERSION' ) || defined( 'WPSEO_FILE' )
			|| defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' )
			|| defined( 'SEOPRESS_VERSION' )
			|| function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' )
		);
	}

	/**
	 * Meta description + Open Graph + Twitter cards.
	 */
	public function meta_tags(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$title = wp_get_document_title();
		$desc  = $this->meta_description();
		$url   = $this->current_url();
		$image = $this->og_image();
		$type  = is_singular( array( 'product', 'post' ) ) ? 'article' : 'website';

		if ( '' !== $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( '' !== $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
		printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
		if ( '' !== $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
		printf( '<meta name="twitter:card" content="%s">' . "\n", '' !== $image ? 'summary_large_image' : 'summary' );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( '' !== $desc ) {
			printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		if ( '' !== $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		}
	}

	private function meta_description(): string {
		$d = '';
		if ( is_front_page() || is_home() ) {
			/*
			 * The homepage previously fell back to the site tagline, which is usually too
			 * short to be useful as a search snippet. Use a real description, overridable
			 * in the Customizer or via filter, and fall back to the tagline only if blank.
			 */
			$d = (string) get_theme_mod(
				'guruexpertpowertools_home_description',
				__( 'Buy genuine power tools, solar equipment and hardware in Kenya. Total, Ingco, Makita, Bosch, DeWalt and Honda from authorised distributors, with warranty and countrywide delivery from our Tom Mboya Street shop in Nairobi.', 'guruexpertpowertools' )
			);
			if ( '' === trim( $d ) ) {
				$d = get_bloginfo( 'description' );
			}
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$p = wc_get_product( get_queried_object_id() );
			if ( $p instanceof \WC_Product ) {
				$d = $p->get_short_description() ? $p->get_short_description() : $p->get_description();
			}
		} elseif ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$d = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$t = get_queried_object();
			if ( $t instanceof \WP_Term ) {
				$d = term_description( $t );
			}
		} elseif ( is_search() ) {
			/* translators: %s: search query. */
			$d = sprintf( __( 'Search results for "%s"', 'guruexpertpowertools' ), get_search_query() );
		}
		$d = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $d ) ) );
		if ( '' === $d ) {
			$d = get_bloginfo( 'description' );
		}
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $d ) > 160 ) {
			$d = rtrim( mb_substr( $d, 0, 157 ) ) . '...';
		}
		return $d;
	}

	private function current_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			$link = get_permalink();
			if ( $link ) {
				return (string) $link;
			}
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$link = get_term_link( get_queried_object() );
			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
			return (string) wc_get_page_permalink( 'shop' );
		}
		global $wp;
		return home_url( isset( $wp->request ) ? user_trailingslashit( $wp->request ) : '' );
	}

	private function og_image(): string {
		$id = 0;
		if ( function_exists( 'is_product' ) && is_product() ) {
			$p = wc_get_product( get_queried_object_id() );
			if ( $p instanceof \WC_Product && $p->get_image_id() ) {
				$id = (int) $p->get_image_id();
			}
		} elseif ( is_singular() ) {
			$id = (int) get_post_thumbnail_id( get_queried_object_id() );
		}
		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				return $src;
			}
		}
		$logo = get_theme_mod( 'custom_logo' );
		if ( $logo ) {
			$src = wp_get_attachment_image_url( (int) $logo, 'full' );
			if ( $src ) {
				return $src;
			}
		}
		return '';
	}

	/**
	 * Organization + Store on every page.
	 */
	public function organization(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$data = array(
			'@context'  => 'https://schema.org',
			'@type'     => array( 'Organization', 'Store' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'email'     => get_theme_mod( 'guruexpertpowertools_email', 'info@guruexpertpowertools.co.ke' ),
			'telephone' => get_theme_mod( 'guruexpertpowertools_phone', '+254 708 777192' ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street',
				'addressLocality' => 'Nairobi',
				'addressRegion'   => 'Nairobi',
				'addressCountry'  => 'KE',
			),
			'areaServed'              => array(
				'@type' => 'Country',
				'name'  => 'Kenya',
			),
			'priceRange'              => (string) get_theme_mod( 'guruexpertpowertools_price_range', 'KSh 500 - KSh 500,000' ),
			'currenciesAccepted'      => 'KES',
			'paymentAccepted'         => 'M-PESA, Cash, Bank Transfer, Credit Card',
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
					'opens'     => '09:00',
					'closes'    => '17:00',
				),
			),
		);

		/*
		 * Social profiles strengthen entity recognition, but a wrong or dead sameAs URL is
		 * worse than none. Left empty by default -- populate with the real Facebook,
		 * Instagram and TikTok URLs via this filter or by editing the array.
		 * Deliberately no "geo" block: inventing coordinates for Magomano House would put
		 * a wrong pin on the map. Add it once the exact latitude/longitude is confirmed.
		 */
		$same_as = array_filter( (array) apply_filters( 'guruexpertpowertools_schema_same_as', array() ) );
		if ( ! empty( $same_as ) ) {
			$data['sameAs'] = array_values( array_map( 'esc_url_raw', $same_as ) );
		}
		$logo = get_theme_mod( 'custom_logo' );
		if ( $logo ) {
			$src = wp_get_attachment_image_src( (int) $logo, 'full' );
			if ( $src ) {
				$data['logo']  = esc_url( $src[0] );
				$data['image'] = esc_url( $src[0] );
			}
		}
		$this->print_ld( $data );
	}

	/**
	 * WebSite + SearchAction (sitelinks search box) on the homepage.
	 */
	public function website(): void {
		if ( $this->has_seo_plugin() || ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$this->print_ld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'name'            => get_bloginfo( 'name' ),
				'url'             => home_url( '/' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			)
		);
	}

	/**
	 * BreadcrumbList on product + product taxonomy pages.
	 */
	public function breadcrumb(): void {
		if ( $this->has_seo_plugin() ) {
			return;
		}
		$items  = array();
		$has_wc = function_exists( 'wc_get_page_permalink' );
		if ( function_exists( 'is_product' ) && is_product() ) {
			$items[] = array( home_url( '/' ), __( 'Home', 'guruexpertpowertools' ) );
			if ( $has_wc ) {
				$items[] = array( wc_get_page_permalink( 'shop' ), __( 'Shop', 'guruexpertpowertools' ) );
			}
			$terms = get_the_terms( get_queried_object_id(), 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term = array_shift( $terms );
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$items[] = array( $link, $term->name );
				}
			}
			$items[] = array( get_permalink(), get_the_title() );
		} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$items[] = array( home_url( '/' ), __( 'Home', 'guruexpertpowertools' ) );
			if ( $has_wc ) {
				$items[] = array( wc_get_page_permalink( 'shop' ), __( 'Shop', 'guruexpertpowertools' ) );
			}
			$obj = get_queried_object();
			if ( $obj instanceof \WP_Term ) {
				$link = get_term_link( $obj );
				if ( ! is_wp_error( $link ) ) {
					$items[] = array( $link, $obj->name );
				}
			}
		} else {
			return;
		}

		$list = array();
		foreach ( $items as $i => $it ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( (string) $it[1] ),
				'item'     => esc_url_raw( (string) $it[0] ),
			);
		}
		$this->print_ld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $list,
			)
		);
	}

	/**
	 * Product schema on single product pages (identifiers + offer).
	 */
	public function product(): void {
		if ( $this->has_seo_plugin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			// In wp_head the loop has not started, so get_the_ID() is unreliable here.
			$product = wc_get_product( get_queried_object_id() );
		}
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		/*
		 * Google Merchant Center and merchant-listing rich results expect an offer to
		 * declare shipping and returns. Omitting them is a common cause of "missing
		 * field" warnings in Merchant Center. Both blocks are filterable so the values
		 * can be tuned without editing the theme -- see the two filters below.
		 */
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
			'sku'         => $product->get_sku(),
			'mpn'         => get_post_meta( $product->get_id(), '_mpn', true ) ? get_post_meta( $product->get_id(), '_mpn', true ) : $product->get_sku(),
			'brand'       => array(
				'@type' => 'Brand',
				'name'  => $this->brand_name( $product ),
			),
			'offers'      => array(
				'@type'           => 'Offer',
				'url'             => $product->get_permalink(),
				'priceCurrency'   => get_woocommerce_currency(),
				'price'           => wc_get_price_to_display( $product ),
				'priceValidUntil' => gmdate( 'Y-m-d', time() + YEAR_IN_SECONDS ),
				'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'itemCondition'   => 'https://schema.org/NewCondition',
				'seller'          => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
				),
				'shippingDetails'         => $this->shipping_details(),
				'hasMerchantReturnPolicy' => $this->return_policy(),
			),
		);
		$gtin = get_post_meta( $product->get_id(), '_gtin', true );
		if ( $gtin ) {
			$data['gtin'] = $gtin;
		}
		if ( $product->get_rating_count() > 0 ) {
			$data['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			);
		}
		$img = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		if ( $img ) {
			$data['image'] = esc_url( $img );
		}
		$this->print_ld( $data );
	}

	private function brand_name( \WC_Product $product ): string {
		foreach ( array( 'product_brand', 'pwb-brand', 'product-brand' ) as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				$terms = wp_get_post_terms( $product->get_id(), $tax, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					return (string) $terms[0];
				}
			}
		}
		return (string) get_post_meta( $product->get_id(), '_powerplug_brand', true );
	}

	/**
	 * Offer shipping details for merchant listings.
	 *
	 * Confirmed policy: KSh 500 flat delivery countrywide on every order, including
	 * bulky machinery. The merchant absorbs the difference on oversized items rather
	 * than quoting them separately, so a single flat shippingRate is accurate for the
	 * whole catalogue and matches the KSh 500 service configured in Merchant Center.
	 * If that ever changes, give bulky lines a per-product override via the
	 * guruexpertpowertools_schema_shipping_details filter rather than raising this
	 * baseline, so the common case keeps advertising the real price.
	 *
	 * @return array
	 */
	private function shipping_details(): array {
		return (array) apply_filters(
			'guruexpertpowertools_schema_shipping_details',
			array(
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => array(
					'@type'    => 'MonetaryAmount',
					'value'    => (string) get_theme_mod( 'guruexpertpowertools_ship_rate', '500' ),
					'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'KES',
				),
				'shippingDestination' => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'KE',
				),
				/*
				 * Kept in step with the homepage trust band, which advertises "Dispatched
				 * from our Nairobi shop in 1-5 days". Handling 0-1 plus transit 1-4 gives
				 * the same 1-5 day end-to-end window. If the visible copy changes, change
				 * this too: Merchant Center compares the two and flags a mismatch.
				 */
				'deliveryTime'        => array(
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 0,
						'maxValue' => 1,
						'unitCode' => 'DAY',
					),
					'transitTime'  => array(
						'@type'    => 'QuantitativeValue',
						'minValue' => 1,
						'maxValue' => 4,
						'unitCode' => 'DAY',
					),
				),
			)
		);
	}

	/**
	 * Merchant return policy for merchant listings.
	 *
	 * IMPORTANT: as with shipping above, the return window below is a placeholder and MUST
	 * be reconciled with the live Return & Refund page before this ships to production.
	 *
	 * @return array
	 */
	private function return_policy(): array {
		return (array) apply_filters(
			'guruexpertpowertools_schema_return_policy',
			array(
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => 'KE',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => (int) get_theme_mod( 'guruexpertpowertools_return_days', 7 ),
				'returnMethod'         => 'https://schema.org/ReturnInStore',
				'returnFees'           => 'https://schema.org/ReturnFeesCustomerResponsibleForShipping',
			)
		);
	}

	private function print_ld( array $data ): void {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
