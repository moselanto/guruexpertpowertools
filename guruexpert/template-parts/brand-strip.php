<?php
/**
 * Homepage brand strip - the names we stock.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$gx_brands = array( 'Total', 'Ingco', 'Makita', 'Bosch', 'DeWalt', 'Honda', 'Solarmax', 'Maxmech', 'DCA' );
?>
<section class="gx-section gx-brands">
	<div class="container">
		<div class="gx-head gx-head--center">
			<div>
				<p class="gx-head__eyebrow"><?php esc_html_e( 'Authorised stock', 'guruexpertpowertools' ); ?></p>
				<h2><?php esc_html_e( 'Brands we stock', 'guruexpertpowertools' ); ?></h2>
			</div>
		</div>
		<div class="gx-brandrow">
			<?php foreach ( $gx_brands as $gx_b ) : ?>
				<span><?php echo esc_html( $gx_b ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
