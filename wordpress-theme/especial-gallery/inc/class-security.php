<?php
/**
 * Security hardening.
 *
 * The source project set three response headers in next.config.ts. WordPress
 * has no equivalent config file, so they are sent from PHP here, along with a
 * small number of defaults that a storefront wants and core leaves open.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Response headers and sensible defaults.
 */
class Security {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_filter( 'wp_headers', array( $this, 'headers' ) );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'login_errors', array( $this, 'login_errors' ) );
		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ) );
		remove_action( 'wp_head', 'wp_generator' );
	}

	/**
	 * The three headers the original set, plus a permissions policy.
	 *
	 * A Content-Security-Policy is deliberately not set here. A theme cannot
	 * know what plugins a site will run, and a policy that breaks the payment
	 * provider's script is worse than no policy at all — that belongs in the
	 * server configuration, where the whole stack is visible.
	 *
	 * @param array $headers Existing headers.
	 * @return array
	 */
	public function headers( $headers ) {
		$headers['X-Content-Type-Options'] = 'nosniff';
		$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
		$headers['X-Frame-Options']        = 'SAMEORIGIN';
		$headers['Permissions-Policy']     = 'geolocation=(), microphone=(), camera=(), interest-cohort=()';

		return $headers;
	}

	/**
	 * A failed login says only that it failed.
	 *
	 * The default distinguishes a wrong password from an unknown username,
	 * which confirms for an attacker that an account exists.
	 *
	 * @param string $error Existing message.
	 * @return string
	 */
	public function login_errors( $error ) {
		unset( $error );
		return __( 'Those details were not recognised.', 'especial-gallery' );
	}

	/**
	 * SVG stays disallowed.
	 *
	 * An SVG is an executable document. Several themes enable uploads for the
	 * convenience of a logo; this one inlines its own icons instead, so the
	 * convenience buys nothing and the risk is real.
	 *
	 * @param array $mimes Allowed types.
	 * @return array
	 */
	public function upload_mimes( $mimes ) {
		unset( $mimes['svg'], $mimes['svgz'] );
		return $mimes;
	}
}
