<?php
/**
 * Site footer.
 *
 * @package GuruExpertPowerTools
 */

defined( 'ABSPATH' ) || exit;

$rk_phone    = get_theme_mod( 'guruexpertpowertools_phone', '+254 708 777192' );
$rk_email    = get_theme_mod( 'guruexpertpowertools_email', 'info@guruexpertpowertools.co.ke' );
$rk_address  = get_theme_mod( 'guruexpertpowertools_address', 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi' );
$rk_whatsapp = get_theme_mod( 'guruexpertpowertools_whatsapp', '254708777192' );
?>
</div><!-- #content -->
<footer class="rk-footer">
	<div class="container">
		<div class="rk-footer__cols">
			<div>
				<h3><?php bloginfo( 'name' ); ?></h3>
				<p class="rk-footer__tag"><?php esc_html_e( 'Power tools, solar and hardware, delivered right across Kenya.', 'guruexpertpowertools' ); ?></p>
				<ul class="rk-footer__contact">
					<li><svg class="rk-fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><span><?php echo esc_html( $rk_address ); ?></span></li>
					<li><svg class="rk-fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7 12.8 12.8 0 0 0 .7 2.8 2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.1a2 2 0 0 1 2.1-.5 12.8 12.8 0 0 0 2.8.7 2 2 0 0 1 1.7 2z"/></svg><a href="tel:<?php echo esc_attr( str_replace( ' ', '', $rk_phone ) ); ?>"><?php echo esc_html( $rk_phone ); ?></a></li>
					<li><svg class="rk-fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><a href="mailto:<?php echo esc_attr( $rk_email ); ?>"><?php echo esc_html( $rk_email ); ?></a></li>
					<li><svg class="rk-fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><?php esc_html_e( 'Mon - Sat, 9:00am - 5:00pm', 'guruexpertpowertools' ); ?></span></li>
				</ul>
				<a class="rk-footer__wa" href="https://wa.me/<?php echo esc_attr( $rk_whatsapp ); ?>" target="_blank" rel="noopener nofollow"><svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.6 4.2 1.6 6L4 29l8.2-1.6c1.7.9 3.7 1.4 5.8 1.4C24.6 28.8 30 23.4 30 16.8 30 9.4 24.6 3 16 3zm0 23.6c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-4.8 1 1-4.7-.3-.4C5.5 19 5 17 5 15c0-5.5 4.5-10 11-10s11 4.5 11 10-4.5 11.6-11 11.6zm6-8.3c-.3-.2-2-1-2.3-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.3-.2-.3 0-.5.1-.7.1-.1.3-.4.5-.6.1-.2.2-.3.3-.5.1-.2 0-.4 0-.6-.1-.2-.8-1.9-1.1-2.6-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.4 1.4 3.6c.2.2 2.4 3.7 5.8 5.1.8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 2-.8 2.3-1.6.3-.8.3-1.4.2-1.6-.1-.1-.3-.2-.6-.4z"/></svg><span><?php esc_html_e( 'Chat with us on WhatsApp', 'guruexpertpowertools' ); ?></span></a>
			</div>
			<div>
				<h3><?php esc_html_e( 'Customer Service', 'guruexpertpowertools' ); ?></h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer_service', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			</div>
			<div>
				<h3><?php esc_html_e( 'Policies', 'guruexpertpowertools' ); ?></h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer_policies', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			</div>
			<div>
				<h3><?php esc_html_e( 'We Accept', 'guruexpertpowertools' ); ?></h3>
				<div class="rk-payments">
					<span>M-PESA</span><span>Cash on Delivery (Nairobi)</span><span>Visa &amp; Mastercard (in shop)</span>
				</div>
				<h3 style="margin-top:18px"><?php esc_html_e( 'Secure Shopping', 'guruexpertpowertools' ); ?></h3>
				<div class="rk-payments"><span>SSL Secured</span><span><?php esc_html_e( 'Walk-in Shop in Nairobi', 'guruexpertpowertools' ); ?></span></div>
			</div>
		</div>
	</div>
	<div class="rk-footer__bar">
		<div class="container">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'guruexpertpowertools' ); ?>
			<?php /* Lets visitors withdraw or change consent after the banner is dismissed, which the DPA 2019 requires. */ ?>
			<a class="rk-footer__cookies" href="#" data-gxpt-cc-open><?php esc_html_e( 'Cookie settings', 'guruexpertpowertools' ); ?></a>
		</div>
	</div>
</footer>

<a class="rk-whatsapp" href="https://wa.me/<?php echo esc_attr( $rk_whatsapp ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'guruexpertpowertools' ); ?>">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.1-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1-1.4-1-2.6s.6-1.8.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .5l-.4.5c-.2.2-.3.4-.1.6.2.4.9 1.4 1.9 2.3 1.3 1.1 2.3 1.4 2.5 1.5.2.1.4.1.6-.1l.7-.9c.2-.3.4-.2.6-.1l1.8.9c.2.1.4.2.5.3.1.3.1.7-.1 1.3Z"/></svg>
</a>
<button class="rk-backtop" aria-label="<?php esc_attr_e( 'Back to top', 'guruexpertpowertools' ); ?>">&uarr;</button>

<?php
/* Mobile bottom navigation bar (shown on small screens only). */
$gx_bn_home    = home_url( '/' );
$gx_bn_shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $gx_bn_home;
$gx_bn_cart    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $gx_bn_home;
$gx_bn_account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : $gx_bn_home;
$gx_bn_count   = ( function_exists( 'WC' ) && WC() && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
?>
<nav class="gx-botnav" aria-label="<?php esc_attr_e( 'Quick navigation', 'guruexpertpowertools' ); ?>">
	<a class="gx-botnav__item" href="<?php echo esc_url( $gx_bn_home ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
		<span><?php esc_html_e( 'Home', 'guruexpertpowertools' ); ?></span>
	</a>
	<a class="gx-botnav__item" href="<?php echo esc_url( $gx_bn_shop ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9h16l-1.2-4.3A1 1 0 0 0 17.8 4H6.2a1 1 0 0 0-1 .7L4 9z"/><path d="M5 9v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9"/><path d="M9.5 20v-5h5v5"/></svg>
		<span><?php esc_html_e( 'Shop', 'guruexpertpowertools' ); ?></span>
	</a>
	<?php if ( $rk_whatsapp ) : ?>
	<a class="gx-botnav__item gx-botnav__item--wa" href="https://wa.me/<?php echo esc_attr( $rk_whatsapp ); ?>" target="_blank" rel="noopener nofollow">
		<span class="gx-botnav__wa"><svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.6 4.2 1.6 6L4 29l8.2-1.6c1.7.9 3.7 1.4 5.8 1.4C24.6 28.8 30 23.4 30 16.8 30 9.4 24.6 3 16 3zm0 23.6c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-4.8 1 1-4.7-.3-.4C5.5 19 5 17 5 15c0-5.5 4.5-10 11-10s11 4.5 11 10-4.5 11.6-11 11.6zm6-8.3c-.3-.2-2-1-2.3-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.3-.2-.3 0-.5.1-.7.1-.1.3-.4.5-.6.1-.2.2-.3.3-.5.1-.2 0-.4 0-.6-.1-.2-.8-1.9-1.1-2.6-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.4 1.4 3.6c.2.2 2.4 3.7 5.8 5.1.8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 2-.8 2.3-1.6.3-.8.3-1.4.2-1.6-.1-.1-.3-.2-.6-.4z"/></svg></span>
		<span><?php esc_html_e( 'WhatsApp', 'guruexpertpowertools' ); ?></span>
	</a>
	<?php endif; ?>
	<a class="gx-botnav__item" href="<?php echo esc_url( $gx_bn_cart ); ?>" data-rk-drawer-open>
		<span class="gx-botnav__ico">
			<?php echo rk_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
			<span class="gx-botnav__count<?php echo $gx_bn_count ? '' : ' is-empty'; ?>"><?php echo esc_html( $gx_bn_count ); ?></span>
		</span>
		<span><?php esc_html_e( 'Cart', 'guruexpertpowertools' ); ?></span>
	</a>
	<a class="gx-botnav__item" href="<?php echo esc_url( $gx_bn_account ); ?>">
		<?php echo rk_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
		<span><?php esc_html_e( 'Account', 'guruexpertpowertools' ); ?></span>
	</a>
</nav>

<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
<div class="rk-drawer" aria-hidden="true">
	<div class="rk-drawer__overlay" data-rk-drawer-close></div>
	<aside class="rk-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'guruexpertpowertools' ); ?>">
		<div class="rk-drawer__head">
			<h3><?php esc_html_e( 'Your Cart', 'guruexpertpowertools' ); ?></h3>
			<button type="button" class="rk-drawer__close" data-rk-drawer-close aria-label="<?php esc_attr_e( 'Close cart', 'guruexpertpowertools' ); ?>">&times;</button>
		</div>
		<div class="rk-drawer__body">
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</div>
		<div class="rk-drawer__foot">
			<a class="rk-btn rk-btn--ghost rk-btn--block" href="#" data-rk-drawer-close><?php esc_html_e( 'Continue shopping', 'guruexpertpowertools' ); ?></a>
			<a class="rk-btn rk-btn--primary rk-btn--block rk-drawer__checkout" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '' ); ?>"><?php esc_html_e( 'Proceed to checkout', 'guruexpertpowertools' ); ?></a>
		</div>
		<div class="rk-drawer__spin" aria-hidden="true"><span class="rk-spinner"></span></div>
	</aside>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
