<?php
/**
 * Guru Expert Power Tools theme bootstrap.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

define( 'GURUEXPERTPOWERTOOLS_VERSION', '1.24.2' );
define( 'GURUEXPERTPOWERTOOLS_DIR', trailingslashit( get_template_directory() ) );
define( 'GURUEXPERTPOWERTOOLS_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * PSR-4-style autoloader for the GuruExpertPowerTools\ namespace (inc/ directory).
 */
spl_autoload_register(
	static function ( $class ) {
		if ( ! is_string( $class ) ) {
			return;
		}
		$prefix = 'GuruExpertPowerTools\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$relative = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $relative ) );
		$file     = GURUEXPERTPOWERTOOLS_DIR . 'inc/class-' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Boot the theme. Any failure is logged rather than fatally white-screening
 * the entire site, and (in the admin) surfaced as a dismissible notice.
 */
try {
	require GURUEXPERTPOWERTOOLS_DIR . 'inc/bootstrap.php';
} catch ( \Throwable $e ) {
	error_log( 'Guru Expert Power Tools bootstrap error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
	if ( is_admin() ) {
		add_action(
			'admin_notices',
			static function () use ( $e ) {
				printf(
					'<div class="notice notice-error"><p><strong>Guru Expert Power Tools:</strong> %s</p></div>',
					esc_html( $e->getMessage() )
				);
			}
		);
	}
}
