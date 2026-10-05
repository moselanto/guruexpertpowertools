<?php
/**
 * Category sales funnel ( /lp-{funnel}/ ). Routed and configured by inc/class-landing-funnels.php.
 *
 * Sections: hero -> trust strip -> popular picks -> benefits -> full range with price
 * filters -> buying guide -> how ordering works -> WhatsApp quick order -> FAQs ->
 * store details -> related ranges -> sticky mobile action bar.
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

$gx_store = Landing_Funnels::store();
$gx_picks = Landing_Funnels::picks( $gx_f );
$gx_pick_ids = array_map( static fn( $p ) => $p->get_id(), $gx_picks );
$gx_range = Landing_Funnels::range( $gx_f, $gx_pick_ids );
$gx_stats = Landing_Funnels::stats( $gx_f );
$gx_img   = Landing_Funnels::image( $gx_f );
$gx_all   = Landing_Funnels::archive_url( $gx_f );
$gx_wa    = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '';
$gx_wa_ask = $gx_wa ? 'https://wa.me/' . $gx_wa . '?text=' . rawurlencode( sprintf( 'Hello Guru Expert Power Tools, I am looking for %s. Please help me choose the right model. (%s)', strtolower( (string) $gx_f['name'] ), $gx_f['url'] ) ) : '';

/* Products shown on the page (picks + range) for the price chips and the order form. */
$gx_products = $gx_picks;
foreach ( $gx_range->posts as $gx_post ) {
	$gx_p = wc_get_product( $gx_post );
	if ( $gx_p instanceof WC_Product ) {
		$gx_products[] = $gx_p;
	}
}
$gx_bands = Landing_Funnels::bands( array_map( static fn( $p ) => (float) $p->get_price(), $gx_products ) );
$gx_ksh   = static fn( float $v ): string => 'KSh ' . number_format( $v );

/**
 * Funnel product card.
 *
 * @param WC_Product $p    Product.
 * @param bool       $big  Larger "pick" card.
 */
$gx_card = static function ( WC_Product $p, bool $big = false ): void {
	$price = (float) $p->get_price();
	$brand = '';
	$terms = get_the_terms( $p->get_id(), 'product_brand' );
	if ( is_array( $terms ) && $terms ) {
		$brand = $terms[0]->name;
	}
	$desc = wp_trim_words( wp_strip_all_tags( (string) $p->get_short_description() ), $big ? 26 : 16 );
	?>
	<li class="gx-lp-card<?php echo $big ? ' gx-lp-card--pick' : ''; ?>" data-price="<?php echo esc_attr( (string) $price ); ?>">
		<a class="gx-lp-card__media" href="<?php echo esc_url( $p->get_permalink() ); ?>" aria-label="<?php echo esc_attr( $p->get_name() ); ?>">
			<?php echo $p->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore ?>
		</a>
		<div class="gx-lp-card__body">
			<?php if ( '' !== $brand ) : ?><span class="gx-lp-card__brand"><?php echo esc_html( $brand ); ?></span><?php endif; ?>
			<a class="gx-lp-card__title" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
			<?php if ( '' !== $desc ) : ?><p class="gx-lp-card__desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
			<div class="gx-lp-card__meta">
				<span class="gx-lp-card__price"><?php echo $p->get_price_html(); // phpcs:ignore ?></span>
				<span class="gx-lp-card__stock"><?php esc_html_e( 'In stock', 'guruexpertpowertools' ); ?></span>
			</div>
			<div class="gx-lp-card__actions">
				<?php if ( $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() ) : ?>
					<button type="button" class="gx-lp-btn gx-lp-btn--primary" data-guruexpertpowertools-add="<?php echo esc_attr( (string) $p->get_id() ); ?>"><?php esc_html_e( 'Add to cart', 'guruexpertpowertools' ); ?></button>
				<?php else : ?>
					<a class="gx-lp-btn gx-lp-btn--primary" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php esc_html_e( 'View options', 'guruexpertpowertools' ); ?></a>
				<?php endif; ?>
				<button type="button" class="gx-lp-btn gx-lp-btn--wa" data-gx-order="<?php echo esc_attr( (string) $p->get_id() ); ?>"><?php esc_html_e( 'Order on WhatsApp', 'guruexpertpowertools' ); ?></button>
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
				<nav class="gx-lp-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'guruexpertpowertools' ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'guruexpertpowertools' ); ?></a> <span aria-hidden="true">/</span> <?php echo esc_html( (string) $gx_f['name'] ); ?></nav>
				<p class="gx-lp-eyebrow"><?php echo esc_html( (string) $gx_f['eyebrow'] ); ?></p>
				<h1><?php echo esc_html( (string) $gx_f['headline'] ); ?></h1>
				<p class="gx-lp-hero__sub"><?php echo esc_html( (string) $gx_f['sub'] ); ?></p>
				<?php if ( $gx_stats['count'] > 0 ) : ?>
					<p class="gx-lp-hero__stats">
						<?php if ( $gx_stats['min'] > 0 ) : ?>
							<strong><?php echo esc_html( sprintf( __( 'From %s', 'guruexpertpowertools' ), $gx_ksh( $gx_stats['min'] ) ) ); ?></strong>
							<span aria-hidden="true">&middot;</span>
						<?php endif; ?>
						<?php echo esc_html( sprintf( _n( '%d model in stock', '%d models in stock', $gx_stats['count'], 'guruexpertpowertools' ), $gx_stats['count'] ) ); ?>
					</p>
				<?php endif; ?>
				<div class="gx-lp-hero__ctas">
					<a class="gx-lp-btn gx-lp-btn--primary gx-lp-btn--lg" href="#gx-lp-range"><?php esc_html_e( 'See models & prices', 'guruexpertpowertools' ); ?></a>
					<?php if ( $gx_wa_ask ) : ?>
						<a class="gx-lp-btn gx-lp-btn--ghost gx-lp-btn--lg" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Get expert advice on WhatsApp', 'guruexpertpowertools' ); ?></a>
					<?php endif; ?>
				</div>
				<ul class="gx-lp-hero__ticks">
					<li><?php esc_html_e( 'M-PESA or cash on delivery (Nairobi)', 'guruexpertpowertools' ); ?></li>
					<li><?php esc_html_e( 'KSh 500 flat delivery countrywide', 'guruexpertpowertools' ); ?></li>
					<li><?php esc_html_e( 'Walk-in shop on Tom Mboya Street', 'guruexpertpowertools' ); ?></li>
				</ul>
			</div>
			<?php if ( '' !== $gx_img ) : ?>
				<div class="gx-lp-hero__media">
					<img src="<?php echo esc_url( $gx_img ); ?>" alt="<?php echo esc_attr( (string) $gx_f['name'] ); ?>" width="600" height="448" fetchpriority="high" decoding="async">
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="gx-lp-trust" aria-label="<?php esc_attr_e( 'Why shop with us', 'guruexpertpowertools' ); ?>">
		<div class="container gx-lp-trust__grid">
			<div><strong><?php esc_html_e( 'Fast delivery', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Nairobi same or next day. Countrywide 1-5 business days.', 'guruexpertpowertools' ); ?></span></div>
			<div><strong><?php esc_html_e( 'Pay your way', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'M-PESA, or cash on delivery within Nairobi.', 'guruexpertpowertools' ); ?></span></div>
			<div><strong><?php esc_html_e( 'Manufacturer warranty', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Where applicable. Ask us for the terms of any model.', 'guruexpertpowertools' ); ?></span></div>
			<div><strong><?php esc_html_e( 'Defective? We fix it', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Report within 7 days for repair, exchange or refund.', 'guruexpertpowertools' ); ?></span></div>
		</div>
	</section>

	<?php if ( $gx_picks ) : ?>
		<section class="gx-lp-section gx-lp-picks">
			<div class="container">
				<div class="gx-lp-head">
					<h2><?php echo esc_html( sprintf( __( 'Popular %s', 'guruexpertpowertools' ), strtolower( (string) $gx_f['name'] ) ) ); ?></h2>
					<p><?php esc_html_e( 'Models our customers choose most. Live prices and stock.', 'guruexpertpowertools' ); ?></p>
				</div>
				<ul class="gx-lp-grid gx-lp-grid--picks">
					<?php foreach ( $gx_picks as $gx_p ) { $gx_card( $gx_p, true ); } ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $gx_f['benefits'] ) ) : ?>
		<section class="gx-lp-section gx-lp-benefits">
			<div class="container gx-lp-benefits__grid">
				<?php foreach ( (array) $gx_f['benefits'] as $gx_i => $gx_b ) : ?>
					<div class="gx-lp-benefit">
						<span class="gx-lp-benefit__num"><?php echo esc_html( str_pad( (string) ( $gx_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3><?php echo esc_html( (string) $gx_b[0] ); ?></h3>
						<p><?php echo esc_html( (string) $gx_b[1] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section id="gx-lp-range" class="gx-lp-section gx-lp-range">
		<div class="container">
			<div class="gx-lp-head">
				<h2><?php echo esc_html( sprintf( __( 'Shop all %s', 'guruexpertpowertools' ), strtolower( (string) $gx_f['name'] ) ) ); ?></h2>
				<p><?php esc_html_e( 'Compare by price, then add to cart or order on WhatsApp.', 'guruexpertpowertools' ); ?></p>
			</div>
			<?php if ( $gx_bands ) : ?>
				<div class="gx-lp-chips" role="group" aria-label="<?php esc_attr_e( 'Filter by price', 'guruexpertpowertools' ); ?>">
					<button type="button" class="gx-lp-chip is-active" data-min="0" data-max="0" aria-pressed="true"><?php esc_html_e( 'All prices', 'guruexpertpowertools' ); ?></button>
					<button type="button" class="gx-lp-chip" data-min="0" data-max="<?php echo esc_attr( (string) $gx_bands[0] ); ?>" aria-pressed="false"><?php echo esc_html( sprintf( __( 'Under %s', 'guruexpertpowertools' ), $gx_ksh( $gx_bands[0] ) ) ); ?></button>
					<button type="button" class="gx-lp-chip" data-min="<?php echo esc_attr( (string) $gx_bands[0] ); ?>" data-max="<?php echo esc_attr( (string) $gx_bands[1] ); ?>" aria-pressed="false"><?php echo esc_html( $gx_ksh( $gx_bands[0] ) . ' - ' . $gx_ksh( $gx_bands[1] ) ); ?></button>
					<button type="button" class="gx-lp-chip" data-min="<?php echo esc_attr( (string) $gx_bands[1] ); ?>" data-max="0" aria-pressed="false"><?php echo esc_html( sprintf( __( '%s and above', 'guruexpertpowertools' ), $gx_ksh( $gx_bands[1] ) ) ); ?></button>
				</div>
			<?php endif; ?>
			<?php if ( $gx_range->have_posts() || $gx_picks ) : ?>
				<ul class="gx-lp-grid" data-gx-filter-target>
					<?php
					foreach ( $gx_picks as $gx_p ) {
						$gx_card( $gx_p );
					}
					foreach ( $gx_range->posts as $gx_post ) {
						$gx_p = wc_get_product( $gx_post );
						if ( $gx_p instanceof WC_Product ) {
							$gx_card( $gx_p );
						}
					}
					?>
				</ul>
				<p class="gx-lp-empty-filter" hidden><?php esc_html_e( 'No models in this price range. Try another filter or ask us on WhatsApp.', 'guruexpertpowertools' ); ?></p>
				<?php if ( $gx_stats['count'] > count( $gx_products ) ) : ?>
					<p class="gx-lp-more"><a class="gx-lp-btn gx-lp-btn--outline" href="<?php echo esc_url( $gx_all ); ?>"><?php echo esc_html( sprintf( __( 'View all %d models', 'guruexpertpowertools' ), $gx_stats['count'] ) ); ?></a></p>
				<?php endif; ?>
			<?php else : ?>
				<div class="gx-lp-empty">
					<h3><?php esc_html_e( 'Ask us about current stock', 'guruexpertpowertools' ); ?></h3>
					<p><?php esc_html_e( 'Talk to our team for availability and pricing on this range.', 'guruexpertpowertools' ); ?></p>
					<?php if ( $gx_wa_ask ) : ?><a class="gx-lp-btn gx-lp-btn--wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?></a><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php wp_reset_postdata(); ?>

	<?php if ( ! empty( $gx_f['guide'] ) ) : ?>
		<section class="gx-lp-section gx-lp-guide">
			<div class="container gx-lp-guide__inner">
				<div class="gx-lp-guide__intro">
					<p class="gx-lp-eyebrow"><?php esc_html_e( 'Buying guide', 'guruexpertpowertools' ); ?></p>
					<h2><?php echo esc_html( sprintf( __( 'How to choose %s', 'guruexpertpowertools' ), strtolower( (string) $gx_f['name'] ) ) ); ?></h2>
					<p><?php esc_html_e( 'Not sure which model fits? Send us the job details and we will recommend one.', 'guruexpertpowertools' ); ?></p>
					<?php if ( $gx_wa_ask ) : ?><a class="gx-lp-btn gx-lp-btn--wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Ask an expert', 'guruexpertpowertools' ); ?></a><?php endif; ?>
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
			<div class="gx-lp-head"><h2><?php esc_html_e( 'How ordering works', 'guruexpertpowertools' ); ?></h2></div>
			<ol class="gx-lp-how__steps">
				<li><strong><?php esc_html_e( 'Choose your model', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Compare prices above, or ask us to recommend one.', 'guruexpertpowertools' ); ?></span></li>
				<li><strong><?php esc_html_e( 'Order online or on WhatsApp', 'guruexpertpowertools' ); ?></strong><span><?php esc_html_e( 'Check out on the site, or send your order on WhatsApp and we confirm stock and delivery.', 'guruexpertpowertools' ); ?></span></li>
				<li><strong><?php esc_html_e( 'Receive and pay', 'guruexpertpowertools' ); ?></strong><span><?php echo esc_html( $gx_store['payment'] ); ?></span></li>
			</ol>
		</div>
	</section>

	<?php if ( $gx_wa && $gx_products ) : ?>
		<section id="gx-lp-order" class="gx-lp-section gx-lp-order">
			<div class="container gx-lp-order__inner">
				<div class="gx-lp-order__copy">
					<p class="gx-lp-eyebrow"><?php esc_html_e( 'Quick order', 'guruexpertpowertools' ); ?></p>
					<h2><?php esc_html_e( 'Order in under a minute on WhatsApp', 'guruexpertpowertools' ); ?></h2>
					<p><?php esc_html_e( 'Fill in your details and WhatsApp opens with your order written out. Our team confirms stock, delivery and the total before anything is dispatched.', 'guruexpertpowertools' ); ?></p>
					<ul class="gx-lp-ticks">
						<li><?php esc_html_e( 'No account needed', 'guruexpertpowertools' ); ?></li>
						<li><?php esc_html_e( 'KSh 500 flat delivery countrywide', 'guruexpertpowertools' ); ?></li>
						<li><?php esc_html_e( 'Cash on delivery available within Nairobi', 'guruexpertpowertools' ); ?></li>
					</ul>
				</div>
				<form class="gx-lp-form" data-gx-order-form novalidate>
					<label><?php esc_html_e( 'Product', 'guruexpertpowertools' ); ?>
						<select name="product" required>
							<?php foreach ( $gx_products as $gx_p ) : ?>
								<option value="<?php echo esc_attr( (string) $gx_p->get_id() ); ?>" data-name="<?php echo esc_attr( $gx_p->get_name() ); ?>" data-price="<?php echo esc_attr( $gx_ksh( (float) $gx_p->get_price() ) ); ?>" data-url="<?php echo esc_url( $gx_p->get_permalink() ); ?>"><?php echo esc_html( $gx_p->get_name() . ' - ' . $gx_ksh( (float) $gx_p->get_price() ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="gx-lp-form__row">
						<label><?php esc_html_e( 'Quantity', 'guruexpertpowertools' ); ?><input type="number" name="qty" min="1" max="99" value="1" inputmode="numeric" required></label>
						<label><?php esc_html_e( 'Delivery town', 'guruexpertpowertools' ); ?><input type="text" name="town" autocomplete="address-level2" placeholder="<?php esc_attr_e( 'e.g. Nakuru', 'guruexpertpowertools' ); ?>" required></label>
					</div>
					<div class="gx-lp-form__row">
						<label><?php esc_html_e( 'Your name', 'guruexpertpowertools' ); ?><input type="text" name="name" autocomplete="name" required></label>
						<label><?php esc_html_e( 'Phone', 'guruexpertpowertools' ); ?><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="07XX XXX XXX" required></label>
					</div>
					<label><?php esc_html_e( 'Note (optional)', 'guruexpertpowertools' ); ?><textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'Anything we should know?', 'guruexpertpowertools' ); ?>"></textarea></label>
					<p class="gx-lp-form__error" role="alert" hidden></p>
					<button type="submit" class="gx-lp-btn gx-lp-btn--wa gx-lp-btn--lg gx-lp-btn--block"><?php esc_html_e( 'Send order on WhatsApp', 'guruexpertpowertools' ); ?></button>
					<p class="gx-lp-form__note"><?php esc_html_e( 'Your details are only used to process this order.', 'guruexpertpowertools' ); ?></p>
				</form>
			</div>
		</section>
	<?php endif; ?>

	<section class="gx-lp-section gx-lp-faq">
		<div class="container gx-lp-faq__inner">
			<h2><?php esc_html_e( 'Frequently asked questions', 'guruexpertpowertools' ); ?></h2>
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

	<section class="gx-lp-section gx-lp-visit">
		<div class="container gx-lp-visit__inner">
			<div>
				<h2><?php esc_html_e( 'Visit or call us', 'guruexpertpowertools' ); ?></h2>
				<p><?php echo esc_html( $gx_store['address'] ); ?><br><?php echo esc_html( $gx_store['hours'] ); ?></p>
				<p><a href="<?php echo esc_url( 'tel:' . $gx_store['phone_tel'] ); ?>"><?php echo esc_html( $gx_store['phone'] ); ?></a> &middot; <a href="<?php echo esc_url( 'mailto:' . $gx_store['email'] ); ?>"><?php echo esc_html( $gx_store['email'] ); ?></a></p>
			</div>
			<div class="gx-lp-visit__ctas">
				<a class="gx-lp-btn gx-lp-btn--outline" href="<?php echo esc_url( $gx_store['map'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get directions', 'guruexpertpowertools' ); ?></a>
				<a class="gx-lp-btn gx-lp-btn--primary" href="<?php echo esc_url( 'tel:' . $gx_store['phone_tel'] ); ?>"><?php esc_html_e( 'Call now', 'guruexpertpowertools' ); ?></a>
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
					<?php foreach ( $gx_related as $gx_rf ) : $gx_rimg = Landing_Funnels::image( $gx_rf ); ?>
						<li><a href="<?php echo esc_url( $gx_rf['url'] ); ?>">
							<?php if ( '' !== $gx_rimg ) : ?><img src="<?php echo esc_url( $gx_rimg ); ?>" alt="" width="300" height="224" loading="lazy" decoding="async"><?php endif; ?>
							<span><?php echo esc_html( (string) $gx_rf['name'] ); ?></span>
						</a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<div class="gx-lp-sticky" aria-label="<?php esc_attr_e( 'Quick actions', 'guruexpertpowertools' ); ?>">
		<a href="<?php echo esc_url( 'tel:' . $gx_store['phone_tel'] ); ?>"><?php esc_html_e( 'Call', 'guruexpertpowertools' ); ?></a>
		<?php if ( $gx_wa_ask ) : ?><a class="is-wa" href="<?php echo esc_url( $gx_wa_ask ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp', 'guruexpertpowertools' ); ?></a><?php endif; ?>
		<a class="is-primary" href="#gx-lp-range"><?php esc_html_e( 'Shop models', 'guruexpertpowertools' ); ?></a>
	</div>

</main>
<?php
get_footer();
