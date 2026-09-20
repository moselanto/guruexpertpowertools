<?php
/**
 * Instantiate theme modules on load (each guarded).
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require GURUEXPERTPOWERTOOLS_DIR . 'inc/helpers.php';

$guruexpertpowertools_modules = array(
	'GuruExpertPowerTools\\Setup',
	'GuruExpertPowerTools\\Assets',
	'GuruExpertPowerTools\\Security',
	'GuruExpertPowerTools\\WooCommerce_Support',
	'GuruExpertPowerTools\\Ajax',
	'GuruExpertPowerTools\\Customizer',
	'GuruExpertPowerTools\\Schema',
	/*
	 * Cookie_Consent must be registered so its wp_head priority 1 hook runs before the
	 * Google for WooCommerce consent tag. The autoloader maps Cookie_Consent to
	 * inc/class-cookie-consent.php.
	 */
	'GuruExpertPowerTools\\Cookie_Consent',
	/*
	 * Content_Installer and Demo_Import are not yet implemented -- inc/class-content-installer.php
	 * and inc/class-demo-import.php are empty placeholders. The class_exists() guard below meant
	 * they failed silently while README.md advertised them as working features. Re-add them here
	 * once the classes actually exist. See ROADMAP.md phase 5.
	 */
	'GuruExpertPowerTools\\Single_Product',
		'GuruExpertPowerTools\\Merchant_Inspector',
	/*
	 * Google Customer Reviews. Two integrations in one module: the opt-in survey,
	 * which the programme requires and which renders only on the order-received
	 * page, and the optional seller-rating badge on the storefront. The autoloader
	 * maps Google_Customer_Reviews to inc/class-google-customer-reviews.php.
	 */
	'GuruExpertPowerTools\\Google_Customer_Reviews',
	/*
	 * WhatsApp order-click conversion tracking. Reports WhatsApp taps to Google Ads as
	 * the SECONDARY "WhatsApp Order Click" action, so chat-placed orders are visible in
	 * reporting without steering Performance Max bidding. The autoloader maps
	 * Whatsapp_Tracking to inc/class-whatsapp-tracking.php.
	 */
	'GuruExpertPowerTools\\Whatsapp_Tracking',
	/*
	 * Google Analytics 4. The property (G-J98C50VRME) existed but the site was never
	 * tagged, so GA4 reported "No data received" and the Ads remarketing audience sat
	 * at zero. Hooks wp_head at priority 2, after Cookie_Consent's Consent Mode denied
	 * defaults at priority 1. Stands itself down if Site Kit ever connects Analytics,
	 * to avoid double-counting. The autoloader maps Analytics to inc/class-analytics.php.
	 */
	'GuruExpertPowerTools\\Analytics',
	/*
	 * Google Tag Manager container GTM-TRK2HTXB. Hooks wp_head priority 3 -- after
	 * Cookie_Consent's Consent Mode defaults (1) and Analytics (2) -- NOT "as high in
	 * the head as possible" as Google's install screen instructs, because a container
	 * that loads before the denied-by-default consent signals starts firing as though
	 * consent were granted. The noscript iframe hooks wp_body_open, which header.php
	 * calls immediately after <body>. The autoloader maps Tag_Manager to
	 * inc/class-tag-manager.php.
	 *
	 * WARNING: GA4 (G-J98C50VRME), the WhatsApp click conversion and the WooCommerce
	 * purchase conversion are already fired by this theme. Do not also create them as
	 * tags inside GTM -- that doubles every hit and corrupts Performance Max bidding.
	 */
	'GuruExpertPowerTools\\Tag_Manager',
);

foreach ( $guruexpertpowertools_modules as $guruexpertpowertools_class ) {
	try {
		if ( class_exists( $guruexpertpowertools_class ) ) {
			( new $guruexpertpowertools_class() )->hooks();
		}
	} catch ( \Throwable $e ) {
		error_log( 'Guru Expert Power Tools module ' . $guruexpertpowertools_class . ' failed: ' . $e->getMessage() );
	}
}

require GURUEXPERTPOWERTOOLS_DIR . 'inc/required-plugins.php';
