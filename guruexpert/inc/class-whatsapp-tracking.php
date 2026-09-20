<?php
/**
 * WhatsApp order-click conversion tracking.
 *
 * A large share of this shop's orders never touch the checkout: customers tap a
 * WhatsApp button and place the order in chat. Those orders were completely
 * invisible in Google Ads, so paid traffic that converted by WhatsApp looked
 * like it converted not at all.
 *
 * This fires the Google Ads conversion "WhatsApp Order Click"
 * (action ID 7780123849) whenever a visitor taps any WhatsApp link.
 *
 * IMPORTANT -- why this is a SECONDARY conversion:
 * A WhatsApp tap is an intent signal, not a sale. It is deliberately registered
 * in Google Ads as a Secondary action in the Contact category, so it is measured
 * and reported but never used for bidding. Promoting it to Primary would teach
 * Performance Max to buy traffic that taps WhatsApp rather than traffic that
 * buys, which is precisely the fault that the old "/checkout page load" purchase
 * action introduced before it was demoted on 19 September 2026. Do not promote
 * this action to Primary.
 *
 * Consent: the theme's Cookie_Consent module sets Google Consent Mode v2
 * defaults to denied and updates them on the visitor's choice. gtag applies
 * those signals itself, so this event is withheld or modelled automatically
 * when advertising consent has not been granted. No extra gating is needed
 * here, and adding any would double-handle consent.
 *
 * Delegation rather than inline onclick: Google's generated snippet asks for an
 * onclick handler on each link. A product page carries nine WhatsApp links
 * (floating button, mobile bottom nav, footer link, and the per-product "Order
 * on WhatsApp" buttons), and more are added over time. One delegated listener
 * covers all of them, including any added later.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Reports WhatsApp link taps to Google Ads as a Secondary conversion.
 */
final class Whatsapp_Tracking {

	/**
	 * Google Ads conversion target for "WhatsApp Order Click".
	 *
	 * Format is AW-<conversion ID>/<conversion label>. Note this is the Google
	 * Ads tag ID, NOT the conversion-action ID 7780123849 shown in the interface.
	 */
	const SEND_TO = 'AW-18454223190/AwzyCMmJ7f0cENay1N9E';

	/**
	 * Milliseconds during which repeat taps are ignored.
	 *
	 * The Ads action counts "One" per interaction, so Google dedupes server
	 * side, but suppressing double-taps keeps the reporting cleaner.
	 */
	const DEDUPE_MS = 2000;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_footer', array( $this, 'script' ), 40 );
	}

	/**
	 * Emit the delegated click listener.
	 */
	public function script(): void {
		/**
		 * Filter the Google Ads conversion target for WhatsApp taps.
		 *
		 * Return an empty string to disable WhatsApp conversion tracking without
		 * removing this module.
		 *
		 * @param string $send_to The AW-<id>/<label> target.
		 */
		$send_to = (string) apply_filters(
			'guruexpertpowertools_whatsapp_conversion_send_to',
			self::SEND_TO
		);

		if ( '' === $send_to ) {
			return;
		}
		?>
<script id="gxpt-wa-conversion">
( function () {
	var SEND_TO = '<?php echo esc_js( $send_to ); ?>';
	var DEDUPE  = <?php echo (int) self::DEDUPE_MS; ?>;
	var locked  = false;

	document.addEventListener( 'click', function ( evt ) {
		if ( ! evt.target || ! evt.target.closest ) { return; }

		var link = evt.target.closest( 'a[href*="wa.me/"], a[href*="api.whatsapp.com"]' );
		if ( ! link ) { return; }

		// gtag may be absent if an ad blocker stripped it; the link must still work.
		if ( 'function' !== typeof window.gtag ) { return; }

		if ( locked ) { return; }
		locked = true;
		window.setTimeout( function () { locked = false; }, DEDUPE );

		// WhatsApp links open in a new tab, so this page is not unloaded and the
		// request completes normally. No event_callback redirect is required, and
		// using one would risk blocking navigation if the callback never fired.
		window.gtag( 'event', 'conversion', { 'send_to': SEND_TO } );
	}, true );
}() );
</script>
		<?php
	}
}
