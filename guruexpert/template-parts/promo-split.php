<?php
/**
 * Homepage promo split - two feature banners (solar + contractor pricing).
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$gx_shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$gx_wa    = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '254708777192';
$gx_solar = taxonomy_exists( 'product_cat' ) ? get_term_by( 'slug', 'solar-panels', 'product_cat' ) : false;
$gx_solar_url = ( $gx_solar instanceof WP_Term ) ? get_term_link( $gx_solar ) : $gx_shop;
if ( is_wp_error( $gx_solar_url ) ) {
	$gx_solar_url = $gx_shop;
}
$gx_solar_bg = GURUEXPERTPOWERTOOLS_URI . 'assets/img/banner-solar.jpg';
?>
<section class="gx-section gx-section--tint">
	<div class="container gx-promos">
		<div class="gx-promo gx-promo--solar" style="background-image:linear-gradient(120deg,rgba(14,42,28,.90),rgba(14,42,28,.35)),url('<?php echo esc_url( $gx_solar_bg ); ?>')">
			<h3><?php esc_html_e( 'Solar & backup power', 'guruexpertpowertools' ); ?></h3>
			<p><?php esc_html_e( 'Panels, inverters, batteries and street lights - sized and supplied for Kenyan homes and businesses.', 'guruexpertpowertools' ); ?></p>
			<a class="gx-btn gx-btn--solid" href="<?php echo esc_url( $gx_solar_url ); ?>"><?php esc_html_e( 'Shop solar', 'guruexpertpowertools' ); ?></a>
		</div>
		<div class="gx-promo gx-promo--pro">
			<h3><?php esc_html_e( 'Buying for a site or shop?', 'guruexpertpowertools' ); ?></h3>
			<p><?php esc_html_e( 'Get bulk and contractor pricing on tools, generators and welding gear. Send us your list and we will quote fast.', 'guruexpertpowertools' ); ?></p>
			<?php if ( $gx_wa ) : ?>
			<a class="gx-btn gx-btn--solid" href="https://wa.me/<?php echo esc_attr( $gx_wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Request a quote', 'guruexpertpowertools' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
