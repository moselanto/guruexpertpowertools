<?php
/**
 * Theme Customizer: brand colours + contact/support details.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Customizer settings and outputs brand CSS variables.
 */
final class Customizer {

	public function hooks(): void {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'wp_head', array( $this, 'output_css_vars' ), 20 );
	}

	/**
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public function register( $wp_customize ): void {
		$wp_customize->add_panel( 'guruexpertpowertools_panel', array( 'title' => __( 'Guru Expert Power Tools', 'guruexpertpowertools' ), 'priority' => 20 ) );

		// Branding / header logo.
		$wp_customize->add_section( 'guruexpertpowertools_branding', array( 'title' => __( 'Branding', 'guruexpertpowertools' ), 'panel' => 'guruexpertpowertools_panel', 'priority' => 1 ) );
		$wp_customize->add_setting( 'guruexpertpowertools_logo', array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
		$wp_customize->add_control( new \WP_Customize_Media_Control( $wp_customize, 'guruexpertpowertools_logo', array(
			'label'       => __( 'Brand Logo', 'guruexpertpowertools' ),
			'description' => __( 'Upload your header logo (transparent PNG recommended). Leave empty to use the bundled logo.', 'guruexpertpowertools' ),
			'section'     => 'guruexpertpowertools_branding',
			'mime_type'   => 'image',
		) ) );

		// Colours.
		$wp_customize->add_section( 'guruexpertpowertools_colors', array( 'title' => __( 'Brand Colours', 'guruexpertpowertools' ), 'panel' => 'guruexpertpowertools_panel' ) );
		$this->color( $wp_customize, 'guruexpertpowertools_primary', '#208050', __( 'Primary (Green)', 'guruexpertpowertools' ) );
		$this->color( $wp_customize, 'guruexpertpowertools_navy', '#0E2A1C', __( 'Secondary (Dark Green)', 'guruexpertpowertools' ) );

		// Contact + support.
		$wp_customize->add_section( 'guruexpertpowertools_contact', array( 'title' => __( 'Contact & Support', 'guruexpertpowertools' ), 'panel' => 'guruexpertpowertools_panel' ) );
		$this->text( $wp_customize, 'guruexpertpowertools_phone', '+254 708 777192', __( 'Phone / WhatsApp', 'guruexpertpowertools' ) );
		$this->text( $wp_customize, 'guruexpertpowertools_email', 'info@guruexpertpowertools.co.ke', __( 'Email', 'guruexpertpowertools' ) );
		$this->text( $wp_customize, 'guruexpertpowertools_hours', 'Mon - Sat, 9AM - 5PM', __( 'Support Hours', 'guruexpertpowertools' ) );
		$this->text( $wp_customize, 'guruexpertpowertools_address', 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi', __( 'Business Address', 'guruexpertpowertools' ) );
		$this->text( $wp_customize, 'guruexpertpowertools_whatsapp', '254708777192', __( 'WhatsApp number (intl, no +)', 'guruexpertpowertools' ) );
	}

	private function color( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, $id, array( 'label' => $label, 'section' => 'guruexpertpowertools_colors' ) ) );
	}

	private function text( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'guruexpertpowertools_contact', 'type' => 'text' ) );
	}

	/**
	 * Print brand colours as CSS custom properties.
	 */
	public function output_css_vars(): void {
		$yellow = sanitize_hex_color( (string) get_theme_mod( 'guruexpertpowertools_primary', '#208050' ) );
		$navy   = sanitize_hex_color( (string) get_theme_mod( 'guruexpertpowertools_navy', '#0E2A1C' ) );
		printf(
			'<style id="guruexpertpowertools-brand">:root{--rk-primary:%s;--rk-navy:%s}</style>' . "\n",
			esc_html( $yellow ),
			esc_html( $navy )
		);
	}
}
