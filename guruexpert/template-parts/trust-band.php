<?php
/**
 * Homepage benefits band - light icon cards (replaces the old dark strip).
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$gx_items = array(
	array(
		'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/></svg>',
		'title' => __( 'Fast countrywide delivery', 'guruexpertpowertools' ),
		'desc'  => __( 'Dispatched from our Nairobi shop in 1-5 days', 'guruexpertpowertools' ),
	),
	array(
		'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v6c0 4.4-3 7.5-7 9-4-1.5-7-4.6-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>',
		'title' => __( 'Genuine brands & warranty', 'guruexpertpowertools' ),
		'desc'  => __( 'Sourced from authorised distributors', 'guruexpertpowertools' ),
	),
	array(
		'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18"/><path d="M7 15h4"/></svg>',
		'title' => __( 'Pay your way', 'guruexpertpowertools' ),
		'desc'  => __( 'M-PESA, cards or cash on delivery', 'guruexpertpowertools' ),
	),
	array(
		'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M20 19a4 4 0 0 1-4 3h-2"/></svg>',
		'title' => __( 'Expert help on hand', 'guruexpertpowertools' ),
		'desc'  => __( 'Talk to us on +254 708 777192', 'guruexpertpowertools' ),
	),
);
?>
<section class="gx-benefits">
	<div class="container gx-benefits__grid">
		<?php foreach ( $gx_items as $gx_i ) : ?>
			<div class="gx-benefit">
				<span class="gx-benefit__icon"><?php echo $gx_i['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?></span>
				<span class="gx-benefit__text">
					<strong><?php echo esc_html( $gx_i['title'] ); ?></strong>
					<span><?php echo esc_html( $gx_i['desc'] ); ?></span>
				</span>
			</div>
		<?php endforeach; ?>
	</div>
</section>
