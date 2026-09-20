<?php
/**
 * Theme setup: supports, menus, image sizes, i18n.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Registers core theme supports and navigation.
 */
final class Setup {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_action( 'after_setup_theme', array( $this, 'register_menus' ) );
		add_action( 'after_setup_theme', array( $this, 'image_sizes' ) );
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
		add_action( 'after_setup_theme', array( $this, 'migrate_theme_mods' ), 5 );
		// Keep business identity consistent: rewrite the legacy name in displayed content + schema.
		add_filter( 'the_title', array( $this, 'rebrand' ), 20 );
		add_filter( 'the_content', array( $this, 'rebrand' ), 20 );
		add_filter( 'get_the_excerpt', array( $this, 'rebrand' ), 20 );
		add_filter( 'woocommerce_short_description', array( $this, 'rebrand' ), 20 );
		add_filter( 'woocommerce_product_get_name', array( $this, 'rebrand' ), 20 );
		add_filter( 'woocommerce_product_get_description', array( $this, 'rebrand' ), 20 );
		add_filter( 'woocommerce_product_get_short_description', array( $this, 'rebrand' ), 20 );
	}

	/**
	 * Declare theme feature supports.
	 */
	public function theme_supports(): void {
		load_theme_textdomain( 'guruexpertpowertools', GURUEXPERTPOWERTOOLS_DIR . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 60,
				'width'       => 220,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
	}

	/**
	 * Register navigation menus used across the header/footer.
	 */
	public function register_menus(): void {
		register_nav_menus(
			array(
				'primary'        => __( 'Primary Navigation', 'guruexpertpowertools' ),
				'vertical_cats'  => __( 'Hero Vertical Categories', 'guruexpertpowertools' ),
				'footer_company' => __( 'Footer: Company', 'guruexpertpowertools' ),
				'footer_service' => __( 'Footer: Customer Service', 'guruexpertpowertools' ),
				'footer_policies'=> __( 'Footer: Policies', 'guruexpertpowertools' ),
			)
		);
	}

	/**
	 * Register 1:1 product image size for uniform cards + hero.
	 */
	public function image_sizes(): void {
		add_image_size( 'guruexpertpowertools-card', 600, 600, true );
		add_image_size( 'guruexpertpowertools-hero', 1200, 500, true );
		add_image_size( 'guruexpertpowertools-cat', 480, 360, true );
	}

	/**
	 * Register footer + shop sidebar widget areas.
	 */
	/**
	 * One-time migration: copy legacy toptech_* Customizer values to the new keys
	 * so upgrades do not lose saved brand colours or contact details.
	 */
	public function migrate_theme_mods(): void {
		if ( get_option( 'guruexpertpowertools_modmigrated' ) ) {
			return;
		}
		$mods = get_theme_mods();
		if ( is_array( $mods ) ) {
			$map = array(
				'toptech_primary'  => 'guruexpertpowertools_primary',
				'toptech_navy'     => 'guruexpertpowertools_navy',
				'toptech_phone'    => 'guruexpertpowertools_phone',
				'toptech_email'    => 'guruexpertpowertools_email',
				'toptech_hours'    => 'guruexpertpowertools_hours',
				'toptech_address'  => 'guruexpertpowertools_address',
				'toptech_whatsapp' => 'guruexpertpowertools_whatsapp',
			);
			foreach ( $map as $old => $new ) {
				if ( array_key_exists( $old, $mods ) && ! array_key_exists( $new, $mods ) ) {
					set_theme_mod( $new, $mods[ $old ] );
				}
			}
		}
		update_option( 'guruexpertpowertools_modmigrated', 1 );
	}

	/**
	 * Replace the legacy business name with the current one in output. Safety net that keeps
	 * business identity consistent for shoppers and Google Merchant review until the product
	 * database itself is updated.
	 *
	 * @param mixed $text Value passed by the filter.
	 * @return mixed
	 */
	public function rebrand( $text ) {
		if ( is_string( $text ) && stripos( (string) $text, 'toptech' ) === false ) {
			return $text;
		}
		if ( is_string( $text ) ) {
			$text = str_ireplace( 'TopTech Machinery', 'Guru Expert Power Tools', $text );
		}
		return $text;
	}

	public function register_sidebars(): void {
		$defaults = array(
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>',
		);
		register_sidebar( array_merge( $defaults, array( 'name' => __( 'Shop Sidebar', 'guruexpertpowertools' ), 'id' => 'shop-sidebar' ) ) );
		foreach ( array( 1, 2, 3, 4 ) as $i ) {
			register_sidebar( array_merge( $defaults, array(
				'name' => sprintf( __( 'Footer Column %d', 'guruexpertpowertools' ), $i ),
				'id'   => 'footer-' . $i,
			) ) );
		}
	}
}
