<?php
/**
 * Uniform product card (homepage rows + archives).
 * Overrides woocommerce/templates/content-product.php.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'rk-card', $product ); ?>>
	<div class="rk-card__badges"><?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?></div>

	<div class="rk-card__media">
		<a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
			<?php echo $product->get_image( 'guruexpertpowertools-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore ?>
		</a>
		<?php
		/*
		 * The wishlist and quick-view hover buttons were removed: the wishlist button persisted
		 * nothing and "quick view" was a plain link to the product page the card already links to.
		 * Controls that look interactive but do nothing cost shopper trust. Re-introduce them when
		 * a real wishlist plugin and the AJAX quick-view modal land (ROADMAP.md phases 3 and 4).
		 */
		?>
	</div>

	<a href="<?php the_permalink(); ?>" class="rk-card__title"><?php echo esc_html( $product->get_name() ); ?></a>

	<?php if ( $product->get_short_description() ) : ?>
		<p class="rk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 22 ) ); ?></p>
	<?php endif; ?>

	<?php if ( wc_review_ratings_enabled() && $product->get_rating_count() ) : ?>
		<?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore ?>
	<?php endif; ?>

	<span class="rk-stock <?php echo $product->is_in_stock() ? 'rk-stock--in' : 'rk-stock--out'; ?>">
		<?php echo $product->is_in_stock() ? esc_html__( 'In stock', 'guruexpertpowertools' ) : esc_html__( 'Out of stock', 'guruexpertpowertools' ); ?>
	</span>

	<div class="rk-card__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></div>

	<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
		<button type="button" class="rk-btn rk-btn--primary rk-btn--block" data-guruexpertpowertools-add="<?php echo esc_attr( $product->get_id() ); ?>">
			<?php esc_html_e( 'Add to Cart', 'guruexpertpowertools' ); ?>
		</button>
	<?php else : ?>
		<a href="<?php the_permalink(); ?>" class="rk-btn rk-btn--navy rk-btn--block"><?php esc_html_e( 'View Product', 'guruexpertpowertools' ); ?></a>
	<?php endif; ?>

	<?php echo function_exists( 'rk_whatsapp_button' ) ? rk_whatsapp_button( $product ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper ?>
</li>
