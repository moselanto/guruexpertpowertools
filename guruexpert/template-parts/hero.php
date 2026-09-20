<?php
/**
 * Homepage hero: a rotating image slider (headline, CTAs) + quick category chips.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$gx_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$gx_wa   = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '254708777192';
$gx_cats = function_exists( 'rk_cached_terms' ) ? rk_cached_terms( 'product_cat', 10 ) : array();

/**
 * Resolve a product-category permalink by slug, falling back to the shop page.
 *
 * @param string $slug Category slug.
 * @return string
 */
$gx_cat_link = static function ( $slug ) use ( $gx_shop ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $gx_shop;
	}
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term instanceof WP_Term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return $gx_shop;
};

$gx_slides = array(
	array(
		'img'      => GURUEXPERTPOWERTOOLS_URI . 'assets/img/slides/slide-power-tools.jpg',
		'eyebrow'  => __( 'Power tools . Hardware', 'guruexpertpowertools' ),
		'title'    => __( 'Genuine tools,', 'guruexpertpowertools' ),
		'title_hl' => __( 'priced for Kenya', 'guruexpertpowertools' ),
		'lead'     => __( 'Total, Ingco, Makita, Bosch and more - authorised stock, honest prices and quick delivery from our Tom Mboya Street shop to your door.', 'guruexpertpowertools' ),
		'cta_url'  => $gx_shop,
		'cta_text' => __( 'Shop all products', 'guruexpertpowertools' ),
	),
	array(
		'img'      => GURUEXPERTPOWERTOOLS_URI . 'assets/img/slides/slide-solar.jpg',
		'eyebrow'  => __( 'Solar . Backup power', 'guruexpertpowertools' ),
		'title'    => __( 'Solar power that', 'guruexpertpowertools' ),
		'title_hl' => __( 'pays for itself', 'guruexpertpowertools' ),
		'lead'     => __( 'Panels, inverters, batteries and street lights - sized and supplied for Kenyan homes and businesses.', 'guruexpertpowertools' ),
		'cta_url'  => $gx_cat_link( 'solar-panels' ),
		'cta_text' => __( 'Shop solar', 'guruexpertpowertools' ),
	),
	array(
		'img'      => GURUEXPERTPOWERTOOLS_URI . 'assets/img/slides/slide-generators.jpg',
		'eyebrow'  => __( 'Generators . Welding', 'guruexpertpowertools' ),
		'title'    => __( 'Power for', 'guruexpertpowertools' ),
		'title_hl' => __( 'every job site', 'guruexpertpowertools' ),
		'lead'     => __( 'Generators, welding machines and site equipment built to work as hard as you do.', 'guruexpertpowertools' ),
		'cta_url'  => $gx_cat_link( 'generators' ),
		'cta_text' => __( 'Shop equipment', 'guruexpertpowertools' ),
	),
);
$gx_total = count( $gx_slides );
?>
<section class="gx-hero-slider" data-gx-slider aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured highlights', 'guruexpertpowertools' ); ?>">
	<h1 class="sr-only"><?php esc_html_e( 'Guru Expert Power Tools - genuine power tools, solar and hardware in Kenya', 'guruexpertpowertools' ); ?></h1>

	<div class="gx-slides">
		<?php foreach ( $gx_slides as $gx_i => $gx_s ) : ?>
		<article class="gx-slide<?php echo 0 === $gx_i ? ' is-active' : ''; ?>"
			role="group" aria-roledescription="<?php esc_attr_e( 'slide', 'guruexpertpowertools' ); ?>"
			aria-label="<?php echo esc_attr( sprintf( /* translators: 1: current slide, 2: total slides. */ __( 'Slide %1$d of %2$d', 'guruexpertpowertools' ), $gx_i + 1, $gx_total ) ); ?>"
			<?php echo 0 === $gx_i ? '' : 'aria-hidden="true"'; ?>
			style="background-image:linear-gradient(90deg,rgba(9,28,19,.92) 0%,rgba(9,28,19,.74) 40%,rgba(9,28,19,.22) 74%,rgba(9,28,19,.05) 100%),url('<?php echo esc_url( $gx_s['img'] ); ?>')">
			<div class="container gx-slide__inner">
				<div class="gx-slide__content">
					<span class="gx-eyebrow"><?php echo esc_html( $gx_s['eyebrow'] ); ?></span>
					<h2 class="gx-slide__title"><?php echo esc_html( $gx_s['title'] ); ?> <span><?php echo esc_html( $gx_s['title_hl'] ); ?></span></h2>
					<p class="gx-slide__lead"><?php echo esc_html( $gx_s['lead'] ); ?></p>
					<div class="gx-slide__cta">
						<a class="gx-btn gx-btn--solid" href="<?php echo esc_url( $gx_s['cta_url'] ); ?>"><?php echo esc_html( $gx_s['cta_text'] ); ?></a>
						<?php if ( $gx_wa ) : ?>
						<a class="gx-btn gx-btn--wa" href="https://wa.me/<?php echo esc_attr( $gx_wa ); ?>" target="_blank" rel="noopener nofollow">
							<svg viewBox="0 0 32 32" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.6 4.2 1.6 6L4 29l8.2-1.6c1.7.9 3.7 1.4 5.8 1.4C24.6 28.8 30 23.4 30 16.8 30 9.4 24.6 3 16 3zm0 23.6c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-4.8 1 1-4.7-.3-.4C5.5 19 5 17 5 15c0-5.5 4.5-10 11-10s11 4.5 11 10-4.5 11.6-11 11.6z"/></svg>
							<?php esc_html_e( 'Order on WhatsApp', 'guruexpertpowertools' ); ?>
						</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</article>
		<?php endforeach; ?>

		<button type="button" class="gx-slider__arrow gx-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'guruexpertpowertools' ); ?>">
			<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
		</button>
		<button type="button" class="gx-slider__arrow gx-slider__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'guruexpertpowertools' ); ?>">
			<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
		</button>

		<div class="gx-slider__dots" role="group" aria-label="<?php esc_attr_e( 'Choose slide', 'guruexpertpowertools' ); ?>">
			<?php for ( $gx_d = 0; $gx_d < $gx_total; $gx_d++ ) : ?>
			<button type="button" class="gx-slider__dot<?php echo 0 === $gx_d ? ' is-active' : ''; ?>"
				aria-current="<?php echo 0 === $gx_d ? 'true' : 'false'; ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'guruexpertpowertools' ), $gx_d + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>
	</div>

	<?php if ( ! empty( $gx_cats ) && ! is_wp_error( $gx_cats ) ) : ?>
	<div class="gx-quickcats">
		<div class="container gx-quickcats__inner">
			<span class="gx-quickcats__label"><?php esc_html_e( 'Popular:', 'guruexpertpowertools' ); ?></span>
			<?php foreach ( $gx_cats as $gx_c ) : ?>
				<a href="<?php echo esc_url( get_term_link( $gx_c ) ); ?>"><?php echo esc_html( $gx_c->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
</section>
