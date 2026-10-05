<?php
/**
 * Category landing funnel ( /lp-{category}/ ). Routed by inc/class-landing-funnels.php.
 *
 * Layout: hero (headline, intro, CTAs) -> product grid -> trust band -> CTA band.
 * Headline/intro come from a published WP page with the same lp- slug when one exists,
 * otherwise they are generated from the matched product category.
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

$gx_term  = $gx_f['term'];
$gx_page  = $gx_f['page'];
$gx_title = $gx_f['title'];
$gx_q     = \GuruExpertPowerTools\Landing_Funnels::products( 24 );
$gx_total = (int) $gx_q->found_posts;
$gx_wa    = function_exists( 'rk_whatsapp_number' ) ? rk_whatsapp_number() : '';
$gx_more  = $gx_term ? get_term_link( $gx_term ) : add_query_arg( array( 's' => $gx_f['keywords'], 'post_type' => 'product' ), home_url( '/' ) );
$gx_img   = '';
foreach ( array( $gx_term ? $gx_term->slug : '', $gx_f['key'] ) as $gx_slug ) {
	if ( '' !== $gx_slug && is_readable( GURUEXPERTPOWERTOOLS_DIR . 'assets/img/categories/' . $gx_slug . '.jpg' ) ) {
		$gx_img = GURUEXPERTPOWERTOOLS_URI . 'assets/img/categories/' . $gx_slug . '.jpg';
		break;
	}
}

$gx_intro = '';
if ( $gx_page ) {
	$gx_intro = apply_filters( 'the_content', $gx_page->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
} elseif ( $gx_term && '' !== trim( (string) $gx_term->description ) ) {
	$gx_intro = wpautop( wp_kses_post( $gx_term->description ) );
} else {
	$gx_intro = '<p>' . esc_html(
		sprintf(
			/* translators: %s: category name */
			__( 'Shop %s with countrywide delivery in 1-5 business days. Pay by M-Pesa or Cash on Delivery (Nairobi). Talk to our team on WhatsApp for help choosing the right model.', 'guruexpertpowertools' ),
			$gx_title
		)
	) . '</p>';
}

$gx_wa_link = $gx_wa ? 'https://wa.me/' . $gx_wa . '?text=' . rawurlencode( sprintf( __( 'Hello, I am interested in %s. Please advise.', 'guruexpertpowertools' ), $gx_title ) ) : '';

get_header();
?>
<style>
.gx-funnel-hero{background:#0E2A1C;color:#fff;padding:48px 0}
.gx-funnel-hero__inner{display:grid;grid-template-columns:1.3fr 1fr;gap:32px;align-items:center}
.gx-funnel-hero h1{color:#fff;margin:0 0 12px;font-size:clamp(1.8rem,3.5vw,2.6rem)}
.gx-funnel-hero__intro,.gx-funnel-hero__intro p{color:rgba(255,255,255,.88)}
.gx-funnel-hero__btns{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px}
.gx-funnel-hero__media img{width:100%;height:auto;border-radius:12px;display:block}
.gx-funnel-hero__count{opacity:.8;font-size:.95rem;margin-top:8px}
.gx-funnel-products{padding:40px 0}
.gx-funnel-products .rk-products{list-style:none;margin:0;padding:0}
.gx-funnel-empty{padding:32px 0;text-align:center}
@media (max-width:782px){.gx-funnel-hero__inner{grid-template-columns:1fr}.gx-funnel-hero__media{order:-1}}
</style>
<main id="primary" class="site-main gx-funnel">

	<section class="gx-funnel-hero">
		<div class="container gx-funnel-hero__inner">
			<div class="gx-funnel-hero__copy">
				<h1><?php echo esc_html( $gx_title ); ?></h1>
				<div class="gx-funnel-hero__intro"><?php echo wp_kses_post( $gx_intro ); ?></div>
				<?php if ( $gx_total > 0 ) : ?>
					<p class="gx-funnel-hero__count"><?php echo esc_html( sprintf( _n( '%d product available', '%d products available', $gx_total, 'guruexpertpowertools' ), $gx_total ) ); ?></p>
				<?php endif; ?>
				<div class="gx-funnel-hero__btns">
					<a class="gx-btn gx-btn--solid" href="#gx-funnel-products"><?php esc_html_e( 'Shop now', 'guruexpertpowertools' ); ?></a>
					<?php if ( $gx_wa_link ) : ?>
						<a class="gx-btn gx-btn--outline" href="<?php echo esc_url( $gx_wa_link ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Ask on WhatsApp', 'guruexpertpowertools' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $gx_img ) : ?>
				<div class="gx-funnel-hero__media"><img src="<?php echo esc_url( $gx_img ); ?>" alt="<?php echo esc_attr( $gx_title ); ?>" width="600" height="600" fetchpriority="high"></div>
			<?php endif; ?>
		</div>
	</section>

	<section id="gx-funnel-products" class="gx-funnel-products">
		<div class="container">
			<?php if ( $gx_q->have_posts() ) : ?>
				<div class="rk-section__head">
					<h2><?php echo esc_html( sprintf( __( 'Shop %s', 'guruexpertpowertools' ), $gx_title ) ); ?></h2>
					<?php if ( ! is_wp_error( $gx_more ) && $gx_total > $gx_q->post_count ) : ?>
						<a class="rk-viewmore" href="<?php echo esc_url( $gx_more ); ?>"><?php esc_html_e( 'View all', 'guruexpertpowertools' ); ?> &rarr;</a>
					<?php endif; ?>
				</div>
				<ul class="rk-products products">
					<?php
					while ( $gx_q->have_posts() ) {
						$gx_q->the_post();
						wc_get_template_part( 'content', 'product' );
					}
					?>
				</ul>
			<?php else : ?>
				<div class="gx-funnel-empty">
					<h2><?php esc_html_e( 'New stock arriving soon', 'guruexpertpowertools' ); ?></h2>
					<p><?php esc_html_e( 'Talk to our team for current availability and pricing.', 'guruexpertpowertools' ); ?></p>
					<?php if ( $gx_wa_link ) : ?>
						<a class="rk-btn rk-btn--primary" href="<?php echo esc_url( $gx_wa_link ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php wp_reset_postdata(); ?>

	<?php get_template_part( 'template-parts/trust-band' ); ?>
	<?php get_template_part( 'template-parts/cta-band' ); ?>

</main>
<?php
get_footer();
