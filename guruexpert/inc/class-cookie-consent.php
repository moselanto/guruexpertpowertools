<?php
/**
 * Cookie consent + Google Consent Mode v2 defaults.
 *
 * Why this exists: the Google for WooCommerce plugin emits a Consent Mode default that
 * denies ad and analytics storage, but scopes it to a region list of EEA, UK and Swiss
 * country codes. Kenya is not in that list, so for this shop's actual market storage was
 * implicitly GRANTED with no consent ever collected -- a gap under the Kenyan Data
 * Protection Act, 2019.
 *
 * This module emits an UNSCOPED denied default at wp_head priority 1, so it lands before
 * any tag that reads those signals, including the plugin's own. Consent Mode is
 * last-write-wins per region, so ordering is what makes this correct.
 *
 * Cache safety: the banner markup is identical for every visitor and is hidden by
 * default. JavaScript reads the cookie and decides whether to reveal it. Nothing is
 * rendered per-user server-side, so LiteSpeed or any other full-page cache cannot serve
 * one visitor's consent state to another.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Consent Mode v2 defaults plus a theme-native consent banner.
 */
final class Cookie_Consent {

	/**
	 * Cookie name holding the visitor's choice.
	 */
	const COOKIE = 'gxpt_consent';

	/**
	 * Bump when the categories or policy change, to re-prompt returning visitors.
	 */
	const REVISION = '1';

	public function hooks(): void {
		// Priority 1 is deliberate and load-bearing: the denied default must be written
		// before the Google for WooCommerce tag emits its region-scoped default.
		add_action( 'wp_head', array( $this, 'consent_default' ), 1 );
		add_action( 'wp_footer', array( $this, 'banner' ), 20 );
	}

	/**
	 * Emit the unscoped Consent Mode v2 default, then re-apply any stored choice.
	 */
	public function consent_default(): void {
		$revision = esc_js( self::REVISION );
		$cookie   = esc_js( self::COOKIE );
		?>
<script id="gxpt-consent-default">
window.dataLayer = window.dataLayer || [];
function gtag(){ dataLayer.push( arguments ); }
gtag( 'consent', 'default', {
	ad_storage: 'denied',
	ad_user_data: 'denied',
	ad_personalization: 'denied',
	analytics_storage: 'denied',
	functionality_storage: 'granted',
	security_storage: 'granted',
	wait_for_update: 500
} );
gtag( 'set', 'ads_data_redaction', true );
gtag( 'set', 'url_passthrough', true );
window.gxptConsent = ( function () {
	var NAME = '<?php echo $cookie; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_js above. ?>';
	var REV  = '<?php echo $revision; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_js above. ?>';
	function read() {
		try {
			var m = document.cookie.match( new RegExp( '(?:^|;\\s*)' + NAME + '=([^;]*)' ) );
			if ( ! m ) { return null; }
			var parts = decodeURIComponent( m[1] ).split( '|' );
			if ( parts[0] !== REV ) { return null; }
			return { analytics: parts[1] === '1', ads: parts[2] === '1' };
		} catch ( e ) { return null; }
	}
	function write( analytics, ads ) {
		var val = REV + '|' + ( analytics ? '1' : '0' ) + '|' + ( ads ? '1' : '0' );
		var secure = 'https:' === location.protocol ? ';Secure' : '';
		document.cookie = NAME + '=' + encodeURIComponent( val ) +
			';path=/;max-age=15552000;SameSite=Lax' + secure;
	}
	function apply( analytics, ads ) {
		gtag( 'consent', 'update', {
			ad_storage: ads ? 'granted' : 'denied',
			ad_user_data: ads ? 'granted' : 'denied',
			ad_personalization: ads ? 'granted' : 'denied',
			analytics_storage: analytics ? 'granted' : 'denied'
		} );
		gtag( 'set', 'ads_data_redaction', ! ads );
	}
	var stored = read();
	if ( stored ) { apply( stored.analytics, stored.ads ); }
	return {
		read: read,
		save: function ( analytics, ads ) { write( analytics, ads ); apply( analytics, ads ); },
		decided: function () { return null !== read(); }
	};
}() );
</script>
		<?php
	}

	/**
	 * Consent banner. Always rendered, hidden until JavaScript decides otherwise.
	 */
	public function banner(): void {
		$policy_url = '';
		$policy     = get_page_by_path( 'cookie-policy' );
		if ( $policy instanceof \WP_Post ) {
			$policy_url = (string) get_permalink( $policy );
		}
		?>
<div class="gxpt-cc" id="gxpt-cc" hidden role="region" aria-label="<?php esc_attr_e( 'Cookie consent', 'guruexpertpowertools' ); ?>">
	<div class="gxpt-cc__inner">
		<div class="gxpt-cc__copy">
			<strong><?php esc_html_e( 'We use cookies', 'guruexpertpowertools' ); ?></strong>
			<p>
				<?php esc_html_e( 'We use cookies to run this shop, and with your permission to measure traffic and show you relevant adverts. You can accept, reject, or choose which you allow.', 'guruexpertpowertools' ); ?>
				<?php if ( '' !== $policy_url ) : ?>
					<a href="<?php echo esc_url( $policy_url ); ?>"><?php esc_html_e( 'Cookie Policy', 'guruexpertpowertools' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<div class="gxpt-cc__opts" id="gxpt-cc-opts" hidden>
			<label><input type="checkbox" checked disabled> <?php esc_html_e( 'Essential (always on)', 'guruexpertpowertools' ); ?></label>
			<label><input type="checkbox" id="gxpt-cc-analytics"> <?php esc_html_e( 'Analytics', 'guruexpertpowertools' ); ?></label>
			<label><input type="checkbox" id="gxpt-cc-ads"> <?php esc_html_e( 'Advertising', 'guruexpertpowertools' ); ?></label>
		</div>
		<div class="gxpt-cc__btns">
			<button type="button" class="gxpt-cc__btn gxpt-cc__btn--ghost" data-gxpt-cc="reject"><?php esc_html_e( 'Reject all', 'guruexpertpowertools' ); ?></button>
			<button type="button" class="gxpt-cc__btn gxpt-cc__btn--ghost" data-gxpt-cc="choose"><?php esc_html_e( 'Choose', 'guruexpertpowertools' ); ?></button>
			<button type="button" class="gxpt-cc__btn gxpt-cc__btn--ghost" data-gxpt-cc="save" hidden><?php esc_html_e( 'Save choices', 'guruexpertpowertools' ); ?></button>
			<button type="button" class="gxpt-cc__btn gxpt-cc__btn--solid" data-gxpt-cc="accept"><?php esc_html_e( 'Accept all', 'guruexpertpowertools' ); ?></button>
		</div>
	</div>
</div>
<style id="gxpt-cc-css">
.gxpt-cc{position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#0E2A1C;color:#fff;
	box-shadow:0 -6px 24px rgba(0,0,0,.28);font-size:14px;line-height:1.5}
.gxpt-cc[hidden]{display:none}
.gxpt-cc__inner{max-width:1200px;margin:0 auto;padding:16px 20px;display:flex;gap:18px;
	align-items:center;flex-wrap:wrap}
.gxpt-cc__copy{flex:1 1 320px;min-width:260px}
.gxpt-cc__copy strong{display:block;margin-bottom:4px;font-size:15px}
.gxpt-cc__copy p{margin:0;color:#d8e6dd}
.gxpt-cc__copy a{color:#fff;text-decoration:underline}
.gxpt-cc__opts{display:flex;gap:16px;flex-wrap:wrap;flex:1 1 100%;order:3}
.gxpt-cc__opts[hidden]{display:none}
.gxpt-cc__opts label{display:flex;align-items:center;gap:7px;color:#d8e6dd}
.gxpt-cc__btns{display:flex;gap:10px;flex-wrap:wrap}
.gxpt-cc__btn{cursor:pointer;border-radius:6px;padding:10px 16px;font:inherit;font-weight:600;
	border:1px solid rgba(255,255,255,.45);background:transparent;color:#fff}
.gxpt-cc__btn[hidden]{display:none}
.gxpt-cc__btn--solid{background:#208050;border-color:#208050}
.gxpt-cc__btn:focus-visible{outline:3px solid #7ee2a8;outline-offset:2px}
@media (max-width:640px){.gxpt-cc__btns{width:100%}.gxpt-cc__btn{flex:1 1 auto}}
</style>
<script id="gxpt-cc-js">
( function () {
	var bar = document.getElementById( 'gxpt-cc' );
	if ( ! bar || ! window.gxptConsent ) { return; }
	var opts = document.getElementById( 'gxpt-cc-opts' );
	var cbA  = document.getElementById( 'gxpt-cc-analytics' );
	var cbD  = document.getElementById( 'gxpt-cc-ads' );
	var btnSave   = bar.querySelector( '[data-gxpt-cc="save"]' );
	var btnChoose = bar.querySelector( '[data-gxpt-cc="choose"]' );

	function show() { bar.hidden = false; }
	function hide() { bar.hidden = true; }

	if ( ! window.gxptConsent.decided() ) { show(); }

	bar.addEventListener( 'click', function ( e ) {
		var t = e.target.closest ? e.target.closest( '[data-gxpt-cc]' ) : null;
		if ( ! t ) { return; }
		var act = t.getAttribute( 'data-gxpt-cc' );
		if ( 'accept' === act ) { window.gxptConsent.save( true, true ); hide(); return; }
		if ( 'reject' === act ) { window.gxptConsent.save( false, false ); hide(); return; }
		if ( 'choose' === act ) {
			var cur = window.gxptConsent.read();
			cbA.checked = !! ( cur && cur.analytics );
			cbD.checked = !! ( cur && cur.ads );
			opts.hidden = false;
			btnSave.hidden = false;
			btnChoose.hidden = true;
			return;
		}
		if ( 'save' === act ) {
			window.gxptConsent.save( cbA.checked, cbD.checked );
			hide();
		}
	} );

	// Any element with data-gxpt-cc-open reopens the banner, e.g. the footer link.
	document.addEventListener( 'click', function ( e ) {
		var o = e.target.closest ? e.target.closest( '[data-gxpt-cc-open]' ) : null;
		if ( ! o ) { return; }
		e.preventDefault();
		show();
		btnChoose.click();
	} );
}() );
</script>
		<?php
	}
}
