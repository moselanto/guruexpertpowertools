<?php
/**
 * Homepage closing call-to-action band.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$gx_phone = get_theme_mod( 'guruexpertpowertools_phone', '+254 708 777192' );
$gx_wa    = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '254708777192';
$gx_tel   = preg_replace( '/\s+/', '', (string) $gx_phone );
?>
<section class="gx-cta">
	<div class="container gx-cta__inner">
		<div class="gx-cta__copy">
			<h2><?php esc_html_e( 'Not sure which tool or kit you need?', 'guruexpertpowertools' ); ?></h2>
			<p><?php esc_html_e( 'Talk to our team - we will help you choose the right gear for the job and your budget.', 'guruexpertpowertools' ); ?></p>
		</div>
		<div class="gx-cta__btns">
			<a class="gx-btn gx-btn--solid" href="tel:<?php echo esc_attr( $gx_tel ); ?>"><?php echo esc_html( sprintf( __( 'Call %s', 'guruexpertpowertools' ), $gx_phone ) ); ?></a>
			<?php if ( $gx_wa ) : ?>
			<a class="gx-btn gx-btn--outline" href="https://wa.me/<?php echo esc_attr( $gx_wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
