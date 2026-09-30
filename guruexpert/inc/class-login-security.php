<?php
/**
 * Login security: brute-force throttling, bot honeypot, generic errors,
 * lost-password throttling, session and password hardening.
 *
 * Covers every sign-in path that goes through wp_signon()/wp_authenticate():
 * wp-login.php, the WooCommerce My Account login, the checkout login toggle and
 * XML-RPC (already disabled by Security). No extra plugin is required.
 *
 * Proxy note: the client IP is read from REMOTE_ADDR only, because forwarded
 * headers can be forged by an attacker to dodge the lockout. If the site is ever
 * put behind Cloudflare or another reverse proxy, define GXPT_TRUSTED_PROXY_HEADER
 * in wp-config.php (e.g. 'HTTP_CF_CONNECTING_IP') so every visitor is not counted
 * as one shared IP.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Hardens authentication for customers and staff.
 */
final class Login_Security {

	/** Failed attempts allowed per username+IP before a lockout. */
	const MAX_PER_USER_IP = 5;

	/** Failed attempts allowed per IP (any username) before a lockout. */
	const MAX_PER_IP = 20;

	/** Counting window and lockout length, in seconds. */
	const WINDOW = 15 * MINUTE_IN_SECONDS;

	/** Password-reset requests allowed per IP per window. */
	const MAX_RESETS = 5;

	/** Honeypot field name. Real users never see or fill it. */
	const HONEYPOT = 'gxpt_website';

	public function hooks(): void {
		// Lockout check runs before core validates the password (core is priority 20).
		add_filter( 'authenticate', array( $this, 'maybe_block' ), 5, 3 );
		// Replace user-enumerating errors with one generic message (after core, priority 99).
		add_filter( 'authenticate', array( $this, 'generic_errors' ), 99, 3 );
		add_action( 'wp_login_failed', array( $this, 'record_failure' ), 10, 1 );
		add_action( 'wp_login', array( $this, 'clear_failures' ), 10, 1 );

		// Honeypot on every login and registration form.
		add_action( 'login_form', array( $this, 'honeypot_field' ) );
		add_action( 'register_form', array( $this, 'honeypot_field' ) );
		add_action( 'woocommerce_login_form', array( $this, 'honeypot_field' ) );
		add_action( 'woocommerce_register_form', array( $this, 'honeypot_field' ) );
		add_filter( 'woocommerce_process_registration_errors', array( $this, 'check_registration_honeypot' ), 10, 1 );
		add_filter( 'registration_errors', array( $this, 'check_registration_honeypot' ), 10, 1 );

		// Lost-password throttling (wp-login.php and WooCommerce both fire lostpassword_post).
		add_action( 'lostpassword_post', array( $this, 'throttle_reset' ), 10, 1 );

		// Session hardening: "Remember me" lasts 14 days, not WordPress's default of 14+ days with no cap.
		add_filter( 'auth_cookie_expiration', array( $this, 'cookie_lifetime' ), 10, 3 );

		// Require strong customer passwords (WooCommerce strength meter: 3 = strong).
		add_filter( 'woocommerce_min_password_strength', static fn() => 3 );

		// Application passwords only for administrators (a common credential-stuffing target).
		add_filter( 'wp_is_application_passwords_available_for_user', array( $this, 'app_passwords_for_admins' ), 10, 2 );

		// Login screens must never be cached or framed.
		add_action( 'login_init', array( $this, 'login_headers' ) );
		add_action( 'template_redirect', array( $this, 'account_headers' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	private function ip(): string {
		$ip = '';
		if ( defined( 'GXPT_TRUSTED_PROXY_HEADER' ) && ! empty( $_SERVER[ GXPT_TRUSTED_PROXY_HEADER ] ) ) {
			$raw = (string) wp_unslash( $_SERVER[ GXPT_TRUSTED_PROXY_HEADER ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ip  = trim( explode( ',', $raw )[0] );
		}
		if ( '' === $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	private function key_user_ip( string $username ): string {
		return 'gxpt_lf_u_' . md5( strtolower( trim( $username ) ) . '|' . $this->ip() );
	}

	private function key_ip(): string {
		return 'gxpt_lf_i_' . md5( $this->ip() );
	}

	private function count( string $key ): int {
		return (int) get_transient( $key );
	}

	private function bump( string $key ): void {
		set_transient( $key, $this->count( $key ) + 1, self::WINDOW );
	}

	private function locked_error(): \WP_Error {
		return new \WP_Error(
			'gxpt_locked',
			sprintf(
				/* translators: %d: minutes */
				esc_html__( 'Too many failed sign-in attempts. For your security, please wait %d minutes and try again, or reset your password.', 'guruexpertpowertools' ),
				(int) ( self::WINDOW / MINUTE_IN_SECONDS )
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Authentication                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Refuse to check credentials while locked out, or when the honeypot is filled.
	 *
	 * @param mixed  $user     Null, WP_User or WP_Error.
	 * @param string $username Submitted username/email.
	 * @param string $password Submitted password.
	 */
	public function maybe_block( $user, $username, $password ) {
		if ( '' === (string) $username && '' === (string) $password ) {
			return $user; // Not a login attempt (e.g. cookie auth).
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- honeypot presence check only.
		if ( ! empty( $_POST[ self::HONEYPOT ] ) ) {
			return new \WP_Error( 'gxpt_bot', esc_html__( 'Invalid login details.', 'guruexpertpowertools' ) );
		}
		if ( $this->count( $this->key_user_ip( (string) $username ) ) >= self::MAX_PER_USER_IP
			|| $this->count( $this->key_ip() ) >= self::MAX_PER_IP ) {
			return $this->locked_error();
		}
		return $user;
	}

	/**
	 * Collapse "unknown user" / "wrong password" into one message so attackers
	 * cannot discover which emails have accounts.
	 *
	 * @param mixed $user Result so far.
	 */
	public function generic_errors( $user, $username = '', $password = '' ) {
		if ( ! is_wp_error( $user ) ) {
			return $user;
		}
		$leaky = array( 'invalid_username', 'invalid_email', 'incorrect_password', 'invalidcombo' );
		if ( array_intersect( $leaky, $user->get_error_codes() ) ) {
			$lost = wp_lostpassword_url();
			if ( function_exists( 'wc_lostpassword_url' ) ) {
				$lost = wc_lostpassword_url();
			}
			return new \WP_Error(
				'gxpt_invalid',
				sprintf(
					/* translators: %s: lost password URL */
					wp_kses( __( 'Invalid login details. <a href="%s">Forgot your password?</a>', 'guruexpertpowertools' ), array( 'a' => array( 'href' => array() ) ) ),
					esc_url( $lost )
				)
			);
		}
		return $user;
	}

	/**
	 * @param string $username Username that failed.
	 */
	public function record_failure( $username ): void {
		$this->bump( $this->key_user_ip( (string) $username ) );
		$this->bump( $this->key_ip() );
	}

	/**
	 * @param string $username Username that signed in.
	 */
	public function clear_failures( $username ): void {
		delete_transient( $this->key_user_ip( (string) $username ) );
	}

	/* ------------------------------------------------------------------ */
	/* Honeypot                                                             */
	/* ------------------------------------------------------------------ */

	public function honeypot_field(): void {
		printf(
			'<p class="gxpt-hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden"><label for="%1$s">%2$s</label><input type="text" name="%1$s" id="%1$s" value="" tabindex="-1" autocomplete="off"></p>',
			esc_attr( self::HONEYPOT ),
			esc_html__( 'Leave this field empty', 'guruexpertpowertools' )
		);
	}

	/**
	 * @param \WP_Error $errors Registration errors.
	 */
	public function check_registration_honeypot( $errors ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- honeypot presence check only; Woo/core verify their own nonces.
		if ( ! empty( $_POST[ self::HONEYPOT ] ) && is_wp_error( $errors ) ) {
			$errors->add( 'gxpt_bot', esc_html__( 'Registration could not be completed. Please try again.', 'guruexpertpowertools' ) );
		}
		return $errors;
	}

	/* ------------------------------------------------------------------ */
	/* Password reset                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * @param \WP_Error $errors Lost-password errors.
	 */
	public function throttle_reset( $errors ): void {
		$key = 'gxpt_lp_' . md5( $this->ip() );
		if ( $this->count( $key ) >= self::MAX_RESETS ) {
			if ( is_wp_error( $errors ) ) {
				$errors->add( 'gxpt_reset_locked', esc_html__( 'Too many password reset requests. Please wait 15 minutes and try again.', 'guruexpertpowertools' ) );
			}
			return;
		}
		$this->bump( $key );
	}

	/* ------------------------------------------------------------------ */
	/* Session / headers                                                    */
	/* ------------------------------------------------------------------ */

	/**
	 * @param int  $length   Default lifetime.
	 * @param int  $user_id  User ID.
	 * @param bool $remember Remember me ticked.
	 */
	public function cookie_lifetime( $length, $user_id = 0, $remember = false ): int {
		return $remember ? 14 * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;
	}

	/**
	 * @param bool     $available Default.
	 * @param \WP_User $user      User.
	 */
	public function app_passwords_for_admins( $available, $user = null ): bool {
		return (bool) $available && $user instanceof \WP_User && user_can( $user, 'manage_options' );
	}

	private function send_private_headers(): void {
		if ( headers_sent() ) {
			return;
		}
		nocache_headers();
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000' );
		}
	}

	public function login_headers(): void {
		$this->send_private_headers();
	}

	public function account_headers(): void {
		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			$this->send_private_headers();
		}
	}
}
