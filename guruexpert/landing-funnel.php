<?php
/**
 * Category sales funnel ( /lp-{funnel}/ ). Routed and configured by inc/class-landing-funnels.php.
 *
 * Sections: hero -> trust strip + in-page nav -> recommended models -> benefits -> full
 * range (price filters, show more) -> buying guide + sizing table -> how ordering works ->
 * WhatsApp quick order -> FAQs -> visit/call -> related ranges -> sticky mobile bar.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

use GuruExpertPowerTools\Landing_Funnels;

$gx_f = class_exists( Landing_Funnels::class ) ? Landing_Funnels::current() : null;
if ( ! $gx_f ) {
	get_template_part( '404' );
	return;
}

$gx_store    = Landing_Funnels::store();
$gx_picks    = Landing_Funnels::picks( $gx_f );
$gx_pick_ids = array_map( static fn( $p ) => $p->get_id(), $gx_picks );
$gx_range    = Landing_Funnels::range( $gx_f, $gx_pick_ids );
$gx_stats    = Landing_Funnels::stats( $gx_f );
$gx_img      = Landing_Funnels::image( $gx_f );
$gx_all      = Landing_Funnels::archive_url( $gx_f );
$gx_name_lc  = strtolower( (string) $gx_f['name'] );
$gx_wa       = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '';
$gx_wa_ask   = $gx_wa ? 'https://wa.me/' . $gx_wa . '?text=' . rawurlencode( sprintf( 'Hello Guru Expert Power Tools, I am looking for %s. Please help me choose the right model. (%s)', $gx_name_lc, $gx_f['url'] ) ) : '';
$gx_products = array_merge( $gx_picks, $gx_range );
$gx_bands    = Landing_Funnels::bands( array_map( static fn( $p ) => (float) $p->get_price(), $gx_products ) );
$gx_ksh      = static fn( float $v ): string => 'KSh ' . number_format( $v );
$gx_first    = 12; // Grid cards shown before "Show more".
$gx_unit     = (string) ( $gx_f['num'] ?? '' );
$gx_chooser  = (array) ( $gx_f['chooser'] ?? array() );

/**
 * Inline SVG icon.
 */
$gx_icon = static function ( string $name ): string {
	$paths = array(
		'truck'  => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="17" r="1.8"/><circle cx="17" cy="17" r="1.8"/>',
		'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M16 12.5h2M3 9h18"/>',
		'shield' => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
		'tool'   => '<path d="M14.5 6.5a4 4 0 0 0 5 5L12 19a2 2 0 0 1-3-3z"/><path d="M14.5 6.5L17 4l3 3-2.5 2.5"/>',
		'pin'    => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
	);
	return '<svg class="gx-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
};

/**
 * Funnel product card.
 */
$gx_card = static function ( WC_Product $p, bool $pick = false, bool $hidden = false ) use ( $gx_unit ): void {
	$num = '' !== $gx_unit ? Landing_Funnels::num( $p->get_name(), $gx_unit ) : 0.0;
	$brand = '';
	$terms = get_the_terms( $p->get_id(), 'product_brand' );
	if ( is_array( $terms ) && $terms ) {
		$brand = $terms[0]->name;
	}
	$specs = Landing_Funnels::specs( $p->get_name() );
	?>
	<li class="gx-lp-card<?php echo $pick ? ' gx-lp-card--pick' : ''; ?>" data-price="<?php echo esc_attr( (string) (float) $p->get_price() ); ?>" data-num="<?php echo esc_attr( (string) $num ); ?>" data-title="<?php echo esc_attr( strtolower( $p->get_name() ) ); ?>"<?php echo $hidden ? ' data-gx-more hidden' : ''; ?>>
		<a class="gx-lp-card__media" href="<?php echo esc_url( $p->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $p->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); // phpcs:ignore ?>
			<?php if ( $p->is_on_sale() ) : ?><span class="gx-lp-card__flag"><?php esc_html_e( 'Offer', 'guruexpertpowertools' ); ?></span><?php endif; ?>
		</a>
		<div class="gx-lp-card__body">
			<?php if ( '' !== $brand ) : ?><span class="gx-lp-card__brand"><?php echo esc_html( $brand ); ?></span><?php endif; ?>
			<h3 class="gx-lp-card__title"><a href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></h3>
			<?php if ( $specs ) : ?>
				<ul class="gx-lp-specs"><?php foreach ( $specs as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?></ul>
			<?php endif; ?>
			<div class="gx-lp-card__buy">
				<div class="gx-lp-card__price"><?php echo $p->get_price_html(); // phpcs:ignore ?></div>
				<span class="gx-lp-card__stock"><?php esc_html_e( 'In stock', 'guruexpertpowertools' ); ?></span>
			</div>
			<div class="gx-lp-card__actions">
				<?php if ( $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() ) : ?>
					<button type="button" class="gx-lp-btn gx-lp-btn--primary" data-guruexpertpowertools-add="<?php echo esc_attr( (string) $p->get_id() ); ?>"><?php esc_html_e( 'Add to cart', 'guruexpertpowertools' ); ?></button>
				<?php else : ?>
					<a class="gx-lp-btn gx-lp-btn--primary" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php esc_html_e( 'View options', 'guruexpertpowertools' ); ?></a>
				<?php endif; ?>
				<button type="button" class="gx-lp-btn gx-lp-btn--wa-soft" data-gx-order="<?php echo esc_attr( (string) $p->get_id() ); ?>"><?php esc_html_e( 'Order on WhatsApp', 'guruexpertpowertools' ); ?></button>
			</div>
		</div>
	</li>
	<?php
};

get_header();
?>
<main id="primary" class="site-main gx-lp" data-gx-wa="<?php echo esc_attr( $gx_wa ); ?>" data-gx-page="<?php echo esc_url( $gx_f['url'] ); ?>">

	<section class="gx-lp-hero">
		<div class="container gx-lp-hero__inner">
			<div class="gx-lp-hero__copy">
				<nav class="gx-lp-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'guruexpertpowertools' ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'guruexpertpowertools' ); ?></a><span aria-hidden="true">/</span><?php echo esc_html( (string) $gx_f['name'] ); ?></nav>
				<p class="gx-lp-eyebrow"><?php echo esc_html( (string) $gx_f['eyebrow'] ); ?></p>
				<h1><?php echo esc_html( (string) $gx_f['headline'] ); ?></h1>
				<p class="gx-lp-hero__sub"><?php echo esc_html( (string) $gx_f['sub'] ); ?></p>
				<div class="gx-lp-hero__ctas">
					<a class="gx-lp-btn gx-lp-btn--accent gx-lp-btn--lg" href="#gx-lp-range"><?php esc_html_e( 'See models & prices', 'guruexpertpowertools' ); ?></a>
					<?php if ( $gx_wa_ask ) : ?>
						<a class="gx-lp-btn gx-lp-btn--ghost gx-lp-btn--lg" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Ask an expert on WhatsApp', 'guruexpertpowertools' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $gx_stats['count'] > 0 ) : ?>
					<dl class="gx-lp-hero__facts">
						<?php if ( $gx_stats['min'] > 0 ) : ?>
							<div><dt><?php esc_html_e( 'Prices from', 'guruexpertpowertools' ); ?></dt><dd><?php echo esc_html( $gx_ksh( $gx_stats['min'] ) ); ?></dd></div>
						<?php endif; ?>
						<div><dt><?php esc_html_e( 'In stock now', 'guruexpertpowertools' ); ?></dt><dd><?php echo esc_html( sprintf( _n( '%d model', '%d models', $gx_stats['count'], 'guruexpertpowertools' ), $gx_stats['count'] ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Delivery', 'guruexpertpowertools' ); ?></dt><dd><?php esc_html_e( 'KSh 500 flat', 'guruexpertpowertools' ); ?></dd></div>
					</dl>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $gx_img ) : ?>
				<div class="gx-lp-hero__media">
					<img src="<?php echo esc_url( $gx_img ); ?>" alt="<?php echo esc_attr( (string) $gx_f['name'] ); ?>" width="600" height="448" fetchpriority="high" decoding="async">
					<div class="gx-lp-hero__badge"><?php echo $gx_icon( 'pin' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Walk-in shop', 'guruexpertpowertools' ); ?></strong><?php esc_html_e( 'Tom Mboya St, Nairobi · Mon-Sat', 'guruexpertpowertools' ); ?></span></div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="gx-lp-trust" aria-label="<?php esc_attr_e( 'Why shop with us', 'guruexpertpowertools' ); ?>">
		<div class="container gx-lp-trust__grid">
			<div><?php echo $gx_icon( 'truck' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Fast delivery', 'guruexpertpowertools' ); ?></strong><?php esc_html_e( 'Nairobi same or next day', 'guruexpertpowertools' ); ?></span></div>
			<div><?php echo $gx_icon( 'wallet' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'M-PESA or cash', 'guruexpertpowertools' ); ?></strong><?php esc_html_e( 'Cash on delivery in Nairobi', 'guruexpertpowertools' ); ?></span></div>
			<div><?php echo $gx_icon( 'shield' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Manufacturer warranty', 'guruexpertpowertools' ); ?></strong><?php esc_html_e( 'Where applicable', 'guruexpertpowertools' ); ?></span></div>
			<div><?php echo $gx_icon( 'tool' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Defective? We fix it', 'guruexpertpowertools' ); ?></strong><?php esc_html_e( 'Report within 7 days', 'guruexpertpowertools' ); ?></span></div>
		</div>
	</section>

	<nav class="gx-lp-jump" aria-label="<?php esc_attr_e( 'On this page', 'guruexpertpowertools' ); ?>">
		<div class="container">
			<a href="#gx-lp-range"><?php esc_html_e( 'Models & prices', 'guruexpertpowertools' ); ?></a>
			<?php if ( ! empty( $gx_f['guide'] ) ) : ?><a href="#gx-lp-guide"><?php esc_html_e( 'Buying guide', 'guruexpertpowertools' ); ?></a><?php endif; ?>
			<?php if ( $gx_wa && $gx_products ) : ?><a href="#gx-lp-order"><?php esc_html_e( 'Quick order', 'guruexpertpowertools' ); ?></a><?php endif; ?>
			<a href="#gx-lp-faq"><?php esc_html_e( 'FAQs', 'guruexpertpowertools' ); ?></a>
		</div>
	</nav>

	<?php if ( $gx_chooser && $gx_products ) : ?>
		<section class="gx-lp-chooser" aria-labelledby="gx-lp-chooser-title">
			<div class="container">
				<div class="gx-lp-chooser__head">
					<h2 id="gx-lp-chooser-title"><?php esc_html_e( 'What do you need it for?', 'guruexpertpowertools' ); ?></h2>
					<p><?php esc_html_e( 'Pick one and we will show the models that fit.', 'guruexpertpowertools' ); ?></p>
				</div>
				<div class="gx-lp-chooser__grid">
					<?php foreach ( $gx_chooser as $gx_c ) : ?>
						<button type="button" class="gx-lp-use" data-gx-use data-label="<?php echo esc_attr( (string) $gx_c['label'] ); ?>" data-min="<?php echo esc_attr( (string) ( $gx_c['min'] ?? 0 ) ); ?>" data-max="<?php echo esc_attr( (string) ( $gx_c['max'] ?? 0 ) ); ?>" data-tag="<?php echo esc_attr( strtolower( (string) ( $gx_c['tag'] ?? '' ) ) ); ?>">
							<strong><?php echo esc_html( (string) $gx_c['label'] ); ?></strong>
							<span><?php echo esc_html( (string) $gx_c['desc'] ); ?></span>
							<em aria-hidden="true">&rarr;</em>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $gx_picks ) : ?>
		<section class="gx-lp-section gx-lp-picks">
			<div class="container">
				<div class="gx-lp-head">
					<p class="gx-lp-eyebrow"><?php esc_html_e( 'Recommended', 'guruexpertpowertools' ); ?></p>
					<h2><?php echo esc_html( sprintf( __( 'Our recommended %s', 'guruexpertpowertools' ), $gx_name_lc ) ); ?></h2>
					<p><?php esc_html_e( 'A good place to start. Live prices and stock.', 'guruexpertpowertools' ); ?></p>
				</div>
				<ul class="gx-lp-grid gx-lp-grid--picks">
					<?php foreach ( $gx_picks as $gx_p ) { $gx_card( $gx_p, true ); } ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $gx_f['benefits'] ) ) : ?>
		<section class="gx-lp-benefits" aria-label="<?php esc_attr_e( 'Benefits', 'guruexpertpowertools' ); ?>">
			<div class="container gx-lp-benefits__grid">
				<?php foreach ( (array) $gx_f['benefits'] as $gx_b ) : ?>
					<div class="gx-lp-benefit"><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><div><h3><?php echo esc_html( (string) $gx_b[0] ); ?></h3><p><?php echo esc_html( (string) $gx_b[1] ); ?></p></div></div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section id="gx-lp-range" class="gx-lp-section gx-lp-range">
		<div class="container">
			<div class="gx-lp-head gx-lp-head--row">
				<div>
					<h2><?php echo esc_html( sprintf( __( 'All %s', 'guruexpertpowertools' ), $gx_name_lc ) ); ?></h2>
					<p><?php esc_html_e( 'Filter by budget, then add to cart or order on WhatsApp.', 'guruexpertpowertools' ); ?></p>
				</div>
				<?php if ( $gx_bands ) : ?>
					<div class="gx-lp-chips" role="group" aria-label="<?php esc_attr_e( 'Filter by price', 'guruexpertpowertools' ); ?>">
						<button type="button" class="gx-lp-chip is-active" data-min="0" data-max="0" aria-pressed="true"><?php esc_html_e( 'All', 'guruexpertpowertools' ); ?></button>
						<button type="button" class="gx-lp-chip" data-min="0" data-max="<?php echo esc_attr( (string) $gx_bands[0] ); ?>" aria-pressed="false"><?php echo esc_html( sprintf( __( 'Under %s', 'guruexpertpowertools' ), $gx_ksh( $gx_bands[0] ) ) ); ?></button>
						<button type="button" class="gx-lp-chip" data-min="<?php echo esc_attr( (string) $gx_bands[0] ); ?>" data-max="<?php echo esc_attr( (string) $gx_bands[1] ); ?>" aria-pressed="false"><?php echo esc_html( $gx_ksh( $gx_bands[0] ) . ' to ' . number_format( $gx_bands[1] - 1 ) ); ?></button>
						<button type="button" class="gx-lp-chip" data-min="<?php echo esc_attr( (string) $gx_bands[1] ); ?>" data-max="0" aria-pressed="false"><?php echo esc_html( sprintf( __( '%s and above', 'guruexpertpowertools' ), $gx_ksh( $gx_bands[1] ) ) ); ?></button>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $gx_products ) : ?>
				<div class="gx-lp-active" data-gx-active hidden>
					<span><?php esc_html_e( 'Showing models for:', 'guruexpertpowertools' ); ?> <strong data-gx-active-label></strong></span>
					<button type="button" data-gx-use-clear><?php esc_html_e( 'Show all', 'guruexpertpowertools' ); ?> &times;</button>
				</div>
				<ul class="gx-lp-grid" data-gx-filter-target>
					<?php
					foreach ( $gx_products as $gx_i => $gx_p ) {
						$gx_card( $gx_p, false, $gx_i >= $gx_first );
					}
					?>
				</ul>
				<p class="gx-lp-empty-filter" hidden><?php esc_html_e( 'No models in this price range. Try another filter or ask us on WhatsApp.', 'guruexpertpowertools' ); ?></p>
				<div class="gx-lp-more">
					<p class="gx-lp-count" data-gx-count data-total="<?php echo esc_attr( (string) max( $gx_stats['count'], count( $gx_products ) ) ); ?>"><?php echo esc_html( sprintf( __( 'Showing %1$d of %2$d models', 'guruexpertpowertools' ), min( $gx_first, count( $gx_products ) ), max( $gx_stats['count'], count( $gx_products ) ) ) ); ?></p>
					<?php if ( count( $gx_products ) > $gx_first ) : ?>
						<button type="button" class="gx-lp-btn gx-lp-btn--outline" data-gx-show-more><?php echo esc_html( sprintf( __( 'Show %d more', 'guruexpertpowertools' ), count( $gx_products ) - $gx_first ) ); ?></button>
					<?php endif; ?>
					<?php if ( $gx_stats['count'] > count( $gx_products ) ) : ?>
						<a class="gx-lp-link" href="<?php echo esc_url( $gx_all ); ?>"><?php echo esc_html( sprintf( __( 'Browse all %d in the shop', 'guruexpertpowertools' ), $gx_stats['count'] ) ); ?> &rarr;</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="gx-lp-empty">
					<h3><?php esc_html_e( 'Ask us about current stock', 'guruexpertpowertools' ); ?></h3>
					<p><?php esc_html_e( 'Talk to our team for availability and pricing on this range.', 'guruexpertpowertools' ); ?></p>
					<?php if ( $gx_wa_ask ) : ?><a class="gx-lp-btn gx-lp-btn--wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?></a><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( ! empty( $gx_f['guide'] ) ) : ?>
		<section id="gx-lp-guide" class="gx-lp-section gx-lp-guide">
			<div class="container gx-lp-guide__inner">
				<div class="gx-lp-guide__intro">
					<p class="gx-lp-eyebrow"><?php esc_html_e( 'Buying guide', 'guruexpertpowertools' ); ?></p>
					<h2><?php echo esc_html( sprintf( __( 'How to choose %s', 'guruexpertpowertools' ), $gx_name_lc ) ); ?></h2>
					<p><?php esc_html_e( 'Four things to check before you buy. Still unsure? Send us the job details and we will recommend a model.', 'guruexpertpowertools' ); ?></p>
					<?php if ( ! empty( $gx_f['sizes'] ) ) : ?>
						<div class="gx-lp-sizes">
							<h3><?php echo esc_html( (string) ( $gx_f['sizes_title'] ?: __( 'Quick sizing guide', 'guruexpertpowertools' ) ) ); ?></h3>
							<table>
								<?php $gx_sh = (array) ( $gx_f['sizes_head'] ?: array( __( 'Job', 'guruexpertpowertools' ), __( 'Look at', 'guruexpertpowertools' ) ) ); ?>
								<thead><tr><th scope="col"><?php echo esc_html( (string) $gx_sh[0] ); ?></th><th scope="col"><?php echo esc_html( (string) $gx_sh[1] ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( (array) $gx_f['sizes'] as $gx_row ) : ?>
										<tr><td><?php echo esc_html( (string) $gx_row[0] ); ?></td><td><?php echo esc_html( (string) $gx_row[1] ); ?></td></tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<p class="gx-lp-sizes__note"><?php echo esc_html( (string) ( $gx_f['sizes_note'] ?: __( 'Typical figures only. Send us your details for an exact recommendation.', 'guruexpertpowertools' ) ) ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $gx_wa_ask ) : ?><a class="gx-lp-btn gx-lp-btn--wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Get a recommendation', 'guruexpertpowertools' ); ?></a><?php endif; ?>
				</div>
				<ol class="gx-lp-guide__steps">
					<?php foreach ( (array) $gx_f['guide'] as $gx_g ) : ?>
						<li><h3><?php echo esc_html( (string) $gx_g[0] ); ?></h3><p><?php echo esc_html( (string) $gx_g[1] ); ?></p></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
	<?php endif; ?>

	<section class="gx-lp-section gx-lp-how">
		<div class="container">
			<div class="gx-lp-head gx-lp-head--center"><h2><?php esc_html_e( 'How ordering works', 'guruexpertpowertools' ); ?></h2></div>
			<ol class="gx-lp-how__steps">
				<li><strong><?php esc_html_e( 'Pick your model', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Compare prices above or ask us to recommend one.', 'guruexpertpowertools' ); ?></span></li>
				<li><strong><?php esc_html_e( 'Order your way', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Check out on the site, or send your order on WhatsApp.', 'guruexpertpowertools' ); ?></span></li>
				<li><strong><?php esc_html_e( 'We confirm and deliver', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Nairobi same or next day, other towns 1-5 business days.', 'guruexpertpowertools' ); ?></span></li>
				<li><strong><?php esc_html_e( 'Pay', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'M-PESA, or cash on delivery within Nairobi.', 'guruexpertpowertools' ); ?></span></li>
			</ol>
		</div>
	</section>

	<?php if ( $gx_wa && $gx_products ) : ?>
		<section id="gx-lp-order" class="gx-lp-section gx-lp-order">
			<div class="container gx-lp-order__inner">
				<div class="gx-lp-order__copy">
					<p class="gx-lp-eyebrow"><?php esc_html_e( 'Quick order', 'guruexpertpowertools' ); ?></p>
					<h2><?php esc_html_e( 'Order in under a minute on WhatsApp', 'guruexpertpowertools' ); ?></h2>
					<p><?php esc_html_e( 'Fill in your details and WhatsApp opens with your order written out. We confirm stock, delivery and the total before anything is dispatched.', 'guruexpertpowertools' ); ?></p>
					<ul class="gx-lp-ticks">
						<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'No account needed', 'guruexpertpowertools' ); ?></li>
						<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'KSh 500 flat delivery countrywide', 'guruexpertpowertools' ); ?></li>
						<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Cash on delivery within Nairobi', 'guruexpertpowertools' ); ?></li>
					</ul>
				</div>
				<form class="gx-lp-form" data-gx-order-form novalidate>
					<label><?php esc_html_e( 'Product', 'guruexpertpowertools' ); ?>
						<select name="product" required>
							<option value="" selected disabled><?php esc_html_e( 'Choose a product', 'guruexpertpowertools' ); ?></option>
							<?php foreach ( $gx_products as $gx_p ) : ?>
								<option value="<?php echo esc_attr( (string) $gx_p->get_id() ); ?>" data-name="<?php echo esc_attr( $gx_p->get_name() ); ?>" data-price="<?php echo esc_attr( $gx_ksh( (float) $gx_p->get_price() ) ); ?>" data-url="<?php echo esc_url( $gx_p->get_permalink() ); ?>"><?php echo esc_html( wp_trim_words( $gx_p->get_name(), 9, '...' ) . ' - ' . $gx_ksh( (float) $gx_p->get_price() ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="gx-lp-form__row">
						<label><?php esc_html_e( 'Your name', 'guruexpertpowertools' ); ?><input type="text" name="name" autocomplete="name" required></label>
						<label><?php esc_html_e( 'Phone', 'guruexpertpowertools' ); ?><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="07XX XXX XXX" required></label>
					</div>
					<div class="gx-lp-form__row gx-lp-form__row--qty">
						<label><?php esc_html_e( 'Delivery town', 'guruexpertpowertools' ); ?><input type="text" name="town" autocomplete="address-level2" placeholder="<?php esc_attr_e( 'e.g. Nakuru', 'guruexpertpowertools' ); ?>" required></label>
						<label><?php esc_html_e( 'Qty', 'guruexpertpowertools' ); ?><input type="number" name="qty" min="1" max="99" value="1" inputmode="numeric" required></label>
					</div>
					<label><?php esc_html_e( 'Note (optional)', 'guruexpertpowertools' ); ?><textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'Anything we should know?', 'guruexpertpowertools' ); ?>"></textarea></label>
					<p class="gx-lp-form__error" role="alert" hidden></p>
					<button type="submit" class="gx-lp-btn gx-lp-btn--wa gx-lp-btn--lg gx-lp-btn--block"><?php esc_html_e( 'Send order on WhatsApp', 'guruexpertpowertools' ); ?></button>
					<p class="gx-lp-form__note"><?php esc_html_e( 'Your details are only used to process this order.', 'guruexpertpowertools' ); ?></p>
				</form>
			</div>
		</section>
	<?php endif; ?>

	<section id="gx-lp-faq" class="gx-lp-section gx-lp-faq">
		<div class="container gx-lp-faq__inner">
			<div class="gx-lp-faq__intro">
				<p class="gx-lp-eyebrow"><?php esc_html_e( 'FAQs', 'guruexpertpowertools' ); ?></p>
				<h2><?php esc_html_e( 'Questions, answered', 'guruexpertpowertools' ); ?></h2>
				<p><?php esc_html_e( 'Can\'t find your answer? Call or WhatsApp us during shop hours.', 'guruexpertpowertools' ); ?></p>
			</div>
			<div class="gx-lp-faq__list">
				<?php foreach ( array_merge( (array) $gx_f['faqs'], Landing_Funnels::common_faqs() ) as $gx_qa ) : ?>
					<details>
						<summary><?php echo esc_html( (string) $gx_qa[0] ); ?></summary>
						<p><?php echo esc_html( (string) $gx_qa[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gx-lp-close">
		<div class="container gx-lp-close__inner">
			<div class="gx-lp-close__copy">
				<p class="gx-lp-eyebrow"><?php esc_html_e( 'Ready when you are', 'guruexpertpowertools' ); ?></p>
				<h2><?php echo esc_html( sprintf( __( 'Get the right %s, delivered to your door', 'guruexpertpowertools' ), $gx_name_lc ) ); ?></h2>
				<ul class="gx-lp-close__points">
					<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Advice from our team before you pay', 'guruexpertpowertools' ); ?></li>
					<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'KSh 500 flat delivery countrywide', 'guruexpertpowertools' ); ?></li>
					<li><?php echo $gx_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'M-PESA, or cash on delivery in Nairobi', 'guruexpertpowertools' ); ?></li>
				</ul>
				<div class="gx-lp-close__ctas">
					<a class="gx-lp-btn gx-lp-btn--accent gx-lp-btn--lg" href="#gx-lp-range"><?php esc_html_e( 'See models & prices', 'guruexpertpowertools' ); ?></a>
					<?php if ( $gx_wa_ask ) : ?><a class="gx-lp-btn gx-lp-btn--wa gx-lp-btn--lg" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?></a><?php endif; ?>
					<a class="gx-lp-btn gx-lp-btn--ghost gx-lp-btn--lg" href="<?php echo esc_url( 'tel:' . $gx_store['phone_tel'] ); ?>"><?php echo $gx_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $gx_store['phone'] ); ?></a>
				</div>
			</div>
			<div class="gx-lp-close__visit">
				<h3><?php esc_html_e( 'Prefer to see it first?', 'guruexpertpowertools' ); ?></h3>
				<ul>
					<li><?php echo $gx_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $gx_store['address'] ); ?></span></li>
					<li><?php echo $gx_icon( 'clock' ); // phpcs:ignore ?><span><?php echo esc_html( $gx_store['hours'] ); ?></span></li>
				</ul>
				<a class="gx-lp-link gx-lp-link--light" href="<?php echo esc_url( $gx_store['map'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get directions', 'guruexpertpowertools' ); ?> &rarr;</a>
			</div>
		</div>
	</section>

	<?php
	$gx_related = array();
	foreach ( (array) $gx_f['related'] as $gx_key ) {
		$gx_rf = Landing_Funnels::build( (string) $gx_key );
		if ( $gx_rf ) {
			$gx_related[] = $gx_rf;
		}
	}
	if ( $gx_related ) :
		?>
		<section class="gx-lp-section gx-lp-related">
			<div class="container">
				<div class="gx-lp-head"><h2><?php esc_html_e( 'Customers also shop', 'guruexpertpowertools' ); ?></h2></div>
				<ul class="gx-lp-related__list">
					<?php
					foreach ( $gx_related as $gx_rf ) :
						$gx_rimg  = Landing_Funnels::image( $gx_rf );
						$gx_rstat = Landing_Funnels::stats( $gx_rf );
						?>
						<li><a href="<?php echo esc_url( $gx_rf['url'] ); ?>">
							<?php if ( '' !== $gx_rimg ) : ?><img src="<?php echo esc_url( $gx_rimg ); ?>" alt="" width="300" height="224" loading="lazy" decoding="async"><?php endif; ?>
							<span class="gx-lp-related__text">
								<strong><?php echo esc_html( (string) $gx_rf['name'] ); ?></strong>
								<?php if ( $gx_rstat['count'] > 0 ) : ?><small><?php echo esc_html( sprintf( _n( '%d model', '%d models', $gx_rstat['count'], 'guruexpertpowertools' ), $gx_rstat['count'] ) . ( $gx_rstat['min'] > 0 ? ' · from ' . $gx_ksh( $gx_rstat['min'] ) : '' ) ); ?></small><?php endif; ?>
							</span>
						</a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<div class="gx-lp-sticky" aria-label="<?php esc_attr_e( 'Quick actions', 'guruexpertpowertools' ); ?>">
		<a href="<?php echo esc_url( 'tel:' . $gx_store['phone_tel'] ); ?>"><?php echo $gx_icon( 'phone' ); // phpcs:ignore ?><?php esc_html_e( 'Call', 'guruexpertpowertools' ); ?></a>
		<?php if ( $gx_wa_ask ) : ?><a class="is-wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp', 'guruexpertpowertools' ); ?></a><?php endif; ?>
		<a class="is-primary" href="#gx-lp-range"><?php esc_html_e( 'See models', 'guruexpertpowertools' ); ?></a>
	</div>

</main>
<?php
get_footer();
