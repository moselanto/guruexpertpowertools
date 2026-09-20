<?php
/**
 * Homepage: hero, benefits, category mosaic, promo split, product rows,
 * brand strip and closing call to action.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main gx-home">

	<?php get_template_part( 'template-parts/hero' ); ?>

	<?php get_template_part( 'template-parts/trust-band' ); ?>

	<?php get_template_part( 'template-parts/featured-categories' ); ?>

	<?php get_template_part( 'template-parts/promo-split' ); ?>

	<?php get_template_part( 'template-parts/brand-strip' ); ?>

	<div class="gx-home__rows">
		<?php
		/**
		 * Product rows, one per featured category. Slugs can be changed via the
		 * guruexpertpowertools_homepage_categories filter or by editing this array.
		 */
		$rk_sections = apply_filters(
			'guruexpertpowertools_homepage_categories',
			array( 'hardware-tools', 'water-pumps', 'drills', 'solar-panels', 'welding-machines', 'generators', 'saws', 'batteries' )
		);

		/**
		 * Drop slugs that do not resolve to a real, non-empty product category, so a
		 * renamed or mistyped slug fails loudly in the error log during development
		 * instead of silently removing an entire homepage row in production.
		 */
		if ( taxonomy_exists( 'product_cat' ) ) {
			$rk_sections = array_values(
				array_filter(
					$rk_sections,
					static function ( $rk_candidate ) {
						$rk_term = get_term_by( 'slug', $rk_candidate, 'product_cat' );
						if ( ! $rk_term instanceof WP_Term || $rk_term->count < 1 ) {
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( 'Guru Expert Power Tools: homepage category slug "' . $rk_candidate . '" matched no populated product_cat term.' );
							}
							return false;
						}
						return true;
					}
				)
			);
		}

		foreach ( $rk_sections as $rk_slug ) {
			set_query_var( 'rk_cat_slug', $rk_slug );
			get_template_part( 'template-parts/product-section' );
		}
		?>
	</div>

	<?php get_template_part( 'template-parts/cta-band' ); ?>

</main>
<?php
get_footer();
