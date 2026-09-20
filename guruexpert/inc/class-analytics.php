<?php
/**
 * Google Analytics 4 tagging.
 *
 * Why this exists: a GA4 property for this business already existed
 * (account 408829548, property 555112133, measurement ID G-J98C50VRME) but the
 * website was never tagged with it. GA4's own Home screen reported "No data
 * received from your website yet", and the Google Ads remarketing audience sat
 * at zero members with "Too small to serve", because nothing was ever collected.
 *
 * The property was orphaned by an account split: Site Kit is authenticated as
 * guruexpertpowertools@gmail.com, while the GA4 property belongs to
 * expertpowert@gmail.com. Site Kit therefore could not see the property to
 * connect it, and Analytics was left unconnected in Site Kit indefinitely.
 *
 * Ordering: Cookie_Consent writes the Consent Mode v2 denied defaults at
 * wp_head priority 1 and declares the global gtag() shim. This module runs at
 * priority 2 so the defaults are always in place before GA4 is configured.
 * Consent Mode then withholds or models GA4 traffic according to the visitor's
 * choice, so no additional gating belongs here.
 *
 * DOUBLE-COUNTING GUARD: if Site Kit's Analytics module is ever connected it
 * will inject its own GA4 tag. Two tags on one page double every session and
 * every event. site_kit_owns_ga4() detects a measurement ID configured in Site
 * Kit and stands this module down, so whichever is connected second wins
 * cleanly rather than silently corrupting the data.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the GA4 tag, unless Site Kit is already doing so.
 */
final class Analytics {

	/**
	 * GA4 measurement ID for the Guru Expert Power Tools property.
	 */
	const MEASUREMENT_ID = 'G-J98C50VRME';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		// Priority 2: after Cookie_Consent's denied defaults at priority 1.
		add_action( 'wp_head', array( $this, 'tag' ), 2 );
	}

	/**
	 * True when Site Kit already has a GA4 measurement ID configured.
	 */
	private function site_kit_owns_ga4(): bool {
		$settings = get_option( 'googlesitekit_analytics-4_settings' );
		if ( ! is_array( $settings ) ) {
			return false;
		}
		$id = isset( $settings['measurementID'] ) ? trim( (string) $settings['measurementID'] ) : '';

		return '' !== $id;
	}

	/**
	 * Output the GA4 loader and configuration.
	 */
	public function tag(): void {
		if ( $this->site_kit_owns_ga4() ) {
			return;
		}

		/**
		 * Filter whether logged-in administrators are excluded from GA4.
		 *
		 * Excluded by default: staff traffic otherwise pollutes the property and
		 * looks like customer behaviour, which matters a great deal on a property
		 * starting from zero sessions.
		 *
		 * NOTE WHEN TESTING: because of this, an administrator visiting the site
		 * while logged in will NOT appear in GA4 Realtime. Verify the tag in a
		 * private or logged-out window, or return false from this filter.
		 *
		 * @param bool $exclude Whether to exclude administrators.
		 */
		if ( (bool) apply_filters( 'guruexpertpowertools_ga4_exclude_admins', true ) && current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * Filter the GA4 measurement ID.
		 *
		 * Return an empty string to disable GA4 tagging from the theme, for
		 * example if tagging is moved to Site Kit or a tag manager.
		 *
		 * @param string $id GA4 measurement ID.
		 */
		$id = (string) apply_filters( 'guruexpertpowertools_ga4_measurement_id', self::MEASUREMENT_ID );
		$id = trim( $id );

		if ( '' === $id ) {
			return;
		}
		?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo rawurlencode( $id ); ?>"></script>
<script id="gxpt-ga4">
window.dataLayer = window.dataLayer || [];
/* Cookie_Consent declares gtag() at priority 1; this is a safety net only. */
if ( 'function' !== typeof window.gtag ) {
	window.gtag = function () { window.dataLayer.push( arguments ); };
}
gtag( 'js', new Date() );
gtag( 'config', '<?php echo esc_js( $id ); ?>' );
</script>
		<?php
	}
}
