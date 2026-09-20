<?php
/**
 * Google Tag Manager container.
 *
 * Emits the GTM-TRK2HTXB container snippet and its <noscript> fallback.
 *
 * ORDERING -- DELIBERATE DEVIATION FROM GOOGLE'S BOILERPLATE
 * ---------------------------------------------------------
 * Google's install screen says to paste the container "as high in the <head> as
 * possible". Followed literally on this site that would be actively harmful.
 *
 * Cookie_Consent writes the Consent Mode v2 denied-by-default signals at wp_head
 * priority 1. Consent Mode only governs a tag that loads AFTER those defaults are
 * on the dataLayer; a container that loads first starts firing with consent
 * effectively granted, which is exactly the leak the consent banner exists to
 * prevent and a data-protection exposure under Kenya's Data Protection Act 2019.
 *
 * So this module hooks priority 3 -- after Cookie_Consent (1) and Analytics (2),
 * still inside <head>, still before any body content. "As high as possible"
 * is honoured as "as high as is correct".
 *
 * DOUBLE-COUNTING -- READ BEFORE ADDING TAGS IN THE GTM UI
 * -------------------------------------------------------
 * This theme already fires three Google tags server-side:
 *   - GA4 config        G-J98C50VRME            (class-analytics.php)
 *   - WhatsApp click    AW-18454223190/AwzyCMmJ7f0cENay1N9E (class-whatsapp-tracking.php)
 *   - Purchase          via Google for WooCommerce
 *
 * Re-creating ANY of those as a tag inside container GTM-TRK2HTXB will double
 * every hit -- inflating sessions, conversions and ROAS while corrupting the
 * Performance Max bidding signal. GTM cannot detect the duplication for you.
 * Either keep those three in the theme (current design) or move them into GTM
 * and disable the theme modules via their filters -- never both.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Google Tag Manager container.
 */
final class Tag_Manager {

	/**
	 * Container ID issued by Google Tag Manager.
	 *
	 * @var string
	 */
	const CONTAINER_ID = 'GTM-TRK2HTXB';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'container' ), 3 );
		add_action( 'wp_body_open', array( $this, 'noscript' ), 1 );
	}

	/**
	 * Resolve the container ID, allowing themes or plugins to override it.
	 *
	 * Returning an empty string disables the container entirely, which is the
	 * supported way to switch tagging off without editing this file.
	 *
	 * @return string Container ID, or '' when disabled.
	 */
	private function container_id(): string {
		/**
		 * Filters the Google Tag Manager container ID.
		 *
		 * @param string $id Container ID. Return '' to disable GTM.
		 */
		$id = (string) apply_filters( 'guruexpertpowertools_gtm_container_id', self::CONTAINER_ID );

		return trim( $id );
	}

	/**
	 * Whether the container should render for the current request.
	 *
	 * Skipped in the admin, during AJAX/REST/cron, on feeds, on login screens and
	 * in the customiser preview, none of which are shopper pageviews and all of
	 * which would otherwise pollute the container's data.
	 *
	 * @return bool
	 */
	private function should_render(): bool {
		if ( '' === $this->container_id() ) {
			return false;
		}

		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}

		/**
		 * Filters whether the GTM container renders for this request.
		 *
		 * @param bool $render Whether to render.
		 */
		return (bool) apply_filters( 'guruexpertpowertools_gtm_enabled', true );
	}

	/**
	 * Output the container snippet in <head>.
	 *
	 * @return void
	 */
	public function container(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		$id = $this->container_id();
		?>
<!-- gxpt-gtm: Google Tag Manager container, after Consent Mode defaults -->
<script id="gxpt-gtm">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $id ); ?>');</script>
		<?php
	}

	/**
	 * Output the <noscript> iframe immediately after <body>.
	 *
	 * Fires on wp_body_open, which header.php already calls on the line after the
	 * opening <body> tag -- the exact placement Google specifies.
	 *
	 * @return void
	 */
	public function noscript(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		printf(
			'<noscript id="gxpt-gtm-ns"><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n",
			esc_attr( rawurlencode( $this->container_id() ) )
		);
	}
}
