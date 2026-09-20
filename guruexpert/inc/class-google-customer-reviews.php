<?php
/**
 * Google Customer Reviews: opt-in survey plus optional seller-rating badge.
 *
 * Why this exists: the Misrepresentation risk audit scored Reputation as an
 * outright FAIL. The shop carries zero reviews sitewide, was not enrolled in
 * Google Customer Reviews, and had product ratings switched off. Google weighs
 * seller reputation when reviewing a Merchant Center account, and the total
 * absence of any review signal is itself a risk factor for a young account.
 *
 * Two separate integrations, matching Merchant Center's two setup screens:
 *
 * 1. Opt-in survey (REQUIRED by the programme). Rendered on the WooCommerce
 *    order-received page only. Google offers the customer a survey opt-in; if
 *    they accept, Google emails the survey after the estimated delivery date
 *    and their responses accumulate into the seller rating.
 *
 * 2. Badge (OPTIONAL). Shows the seller rating on the storefront. Until enough
 *    responses accumulate Google renders the words "no rating available", so
 *    this is filterable and can be switched off without a redeploy.
 *
 * Merchant Center prerequisites, each verified against the live site on
 * 19 September 2026 before this file was written:
 *   - shopping cart and checkout hosted on the same domain  -- confirmed
 *   - confirmation page hosted on the shop's own domain     -- confirmed
 *   - <!DOCTYPE HTML> first on every page                   -- confirmed
 *
 * Note on the Merchant Center setup screen: the snippet it displays is not
 * copy-pasteable. It truncates the loader URL and prints the required field
 * "estimated_delivery_date" with a space rather than an underscore. The values
 * used below are Google's documented ones, not the ones shown on screen.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Google Customer Reviews opt-in and badge.
 */
final class Google_Customer_Reviews {

	/**
	 * Merchant Center account ID for Guru Expert Power Tools.
	 */
	const MERCHANT_ID = 5854305196;

	/**
	 * Working days allowed for delivery when estimating the survey date.
	 *
	 * The published Shipping & Delivery Policy promises 1 to 5 business days
	 * countrywide, so the upper bound is used deliberately. Google times the
	 * survey email from this date, and a survey that lands before the parcel
	 * does produces a poor rating for a delivery that was actually on time.
	 */
	const DELIVERY_BUSINESS_DAYS = 5;

	/**
	 * Badge corner. BOTTOM_LEFT is deliberate: the theme already pins a
	 * floating WhatsApp button and a back-to-top control to the bottom right,
	 * and the mobile bottom navigation bar occupies the lower edge.
	 */
	const BADGE_POSITION = 'BOTTOM_LEFT';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'woocommerce_thankyou', array( $this, 'opt_in' ), 20, 1 );
		add_action( 'wp_footer', array( $this, 'badge' ), 30 );
	}

	/**
	 * Render the opt-in module on the order confirmation page.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function opt_in( $order_id ): void {
		$order_id = absint( $order_id );
		if ( 0 === $order_id || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// A failed or cancelled order never ships, so it must not generate a survey.
		if ( $order->has_status( array( 'failed', 'cancelled' ) ) ) {
			return;
		}

		$email = sanitize_email( (string) $order->get_billing_email() );
		if ( '' === $email || ! is_email( $email ) ) {
			return;
		}

		$country = (string) $order->get_shipping_country();
		if ( '' === $country ) {
			$country = (string) $order->get_billing_country();
		}
		if ( '' === $country ) {
			$country = 'KE';
		}

		$config = array(
			'merchant_id'             => self::MERCHANT_ID,
			'order_id'                => (string) $order->get_order_number(),
			'email'                   => $email,
			'delivery_country'        => strtoupper( substr( $country, 0, 2 ) ),
			'estimated_delivery_date' => $this->estimated_delivery_date( $order ),
		);

		$gtins = $this->order_gtins( $order );
		if ( array() !== $gtins ) {
			$config['products'] = $gtins;
		}

		/**
		 * Filter the Google Customer Reviews opt-in payload.
		 *
		 * @param array     $config Opt-in configuration.
		 * @param \WC_Order $order  The order being confirmed.
		 */
		$config = (array) apply_filters( 'guruexpertpowertools_gcr_opt_in_config', $config, $order );

		$json = wp_json_encode( $config );
		if ( false === $json ) {
			return;
		}
		?>
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
<script id="gxpt-gcr-optin">
window.renderOptIn = function () {
	window.gapi.load( 'surveyoptin', function () {
		window.gapi.surveyoptin.render(
			<?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode output. ?>
		);
	} );
};
</script>
		<?php
	}

	/**
	 * Render the seller-rating badge.
	 */
	public function badge(): void {
		/**
		 * Filter whether the seller-rating badge is displayed.
		 *
		 * Returns false to hide it. Worth doing while the account has too few
		 * responses, because Google renders "no rating available" rather than
		 * rendering nothing at all.
		 *
		 * @param bool $enabled Whether to show the badge.
		 */
		if ( ! (bool) apply_filters( 'guruexpertpowertools_gcr_badge_enabled', true ) ) {
			return;
		}

		// The badge is a storefront trust signal; it has no place in checkout.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		$config = array(
			'merchant_id' => self::MERCHANT_ID,
			'position'    => self::BADGE_POSITION,
		);

		/**
		 * Filter the badge configuration.
		 *
		 * @param array $config Badge configuration.
		 */
		$config = (array) apply_filters( 'guruexpertpowertools_gcr_badge_config', $config );

		$json = wp_json_encode( $config );
		if ( false === $json ) {
			return;
		}
		?>
<script id="merchantWidgetScript" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
<script id="gxpt-gcr-badge">
document.getElementById( 'merchantWidgetScript' ).addEventListener( 'load', function () {
	merchantwidget.start(
		<?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode output. ?>
	);
} );
</script>
		<?php
	}

	/**
	 * Estimate the delivery date Google should time the survey from.
	 *
	 * Sunday is excluded because the shop trades Monday to Saturday, which is
	 * also the handling window configured on the Merchant Center shipping
	 * service. Saturday therefore counts as a working day here.
	 *
	 * @param \WC_Order $order Order being confirmed.
	 * @return string Date as YYYY-MM-DD.
	 */
	private function estimated_delivery_date( \WC_Order $order ): string {
		/**
		 * Filter the number of business days used for the delivery estimate.
		 *
		 * @param int       $days  Business days.
		 * @param \WC_Order $order Order being confirmed.
		 */
		$days = (int) apply_filters(
			'guruexpertpowertools_gcr_delivery_business_days',
			self::DELIVERY_BUSINESS_DAYS,
			$order
		);
		if ( $days < 1 ) {
			$days = 1;
		}

		$created = $order->get_date_created();
		try {
			$cursor = $created instanceof \WC_DateTime
				? new \DateTimeImmutable( $created->date( 'Y-m-d' ) )
				: new \DateTimeImmutable( 'today' );
		} catch ( \Exception $e ) {
			$cursor = new \DateTimeImmutable();
		}

		$added = 0;
		while ( $added < $days ) {
			$cursor = $cursor->modify( '+1 day' );
			if ( 7 !== (int) $cursor->format( 'N' ) ) {
				++$added;
			}
		}

		return $cursor->format( 'Y-m-d' );
	}

	/**
	 * Collect GTINs for the ordered products.
	 *
	 * Optional in Google's schema. Supplying them lets Google attribute product
	 * reviews as well as seller reviews. Products without a GTIN are skipped
	 * rather than sent empty, which Google rejects.
	 *
	 * @param \WC_Order $order Order being confirmed.
	 * @return array List of array( 'gtin' => string ).
	 */
	private function order_gtins( \WC_Order $order ): array {
		$seen = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$gtin = '';
			// Native GTIN field, WooCommerce 9.2+. This shop runs 11.1.1.
			if ( method_exists( $product, 'get_global_unique_id' ) ) {
				$gtin = (string) $product->get_global_unique_id();
			}
			$gtin = trim( $gtin );

			if ( '' !== $gtin && ! in_array( $gtin, $seen, true ) ) {
				$seen[] = $gtin;
			}
		}

		$out = array();
		foreach ( $seen as $gtin ) {
			$out[] = array( 'gtin' => $gtin );
		}

		return $out;
	}
}
