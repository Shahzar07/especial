<?php
/**
 * Mailing-list subscription — the one implementation.
 *
 * The gate form and the footer form both call process(), so the two paths
 * cannot drift. A port of lib/subscribe.ts, lib/esp.ts and lib/rate-limit.ts.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Validates, rate limits, stores and forwards a subscription.
 */
class Subscribe {

	const RATE_WINDOW = 600; // Ten minutes.
	const RATE_MAX    = 5;   // Attempts per window, per IP.

	/**
	 * The customer-facing message for an unusable address.
	 *
	 * @return string
	 */
	public static function email_error() {
		return __( 'Enter a valid email address.', 'especial-gallery' );
	}

	/**
	 * The customer-facing message for anything we cannot explain.
	 *
	 * @return string
	 */
	public static function generic_error() {
		return __( 'Something went wrong on our end. Try again.', 'especial-gallery' );
	}

	/**
	 * Processes a submission.
	 *
	 * @param string $email Raw email address.
	 * @param string $next  Where to send them afterwards.
	 * @return array {
	 *     @type bool   $ok       Whether the visitor is admitted.
	 *     @type int    $status   HTTP status to answer with.
	 *     @type string $error    Customer-facing message when not ok.
	 *     @type string $redirect Where to go when ok.
	 * }
	 */
	public static function process( $email, $next = '/' ) {
		if ( ! self::rate_limit_ok() ) {
			return array(
				'ok'     => false,
				'status' => 429,
				'error'  => __( 'Too many attempts. Try again shortly.', 'especial-gallery' ),
			);
		}

		$email = sanitize_email( trim( (string) $email ) );

		// Server-side validation is the gate; the client check is UX only.
		if ( ! $email || ! is_email( $email ) ) {
			return array(
				'ok'     => false,
				'status' => 400,
				'error'  => self::email_error(),
			);
		}

		self::store( $email );

		$forwarded = self::forward( $email );

		// A failed provider write must never trap a paying customer at the door.
		// The address is already in the database, so nothing is lost either.
		if ( is_wp_error( $forwarded ) ) {
			self::log( 'ESP write failed, admitting anyway: ' . $forwarded->get_error_message() );
		}

		Gate::issue_pass();

		return array(
			'ok'       => true,
			'status'   => 200,
			'redirect' => eg_safe_next( $next ),
		);
	}

	/**
	 * Stores the address locally.
	 *
	 * Kept whatever the provider does, so a shop never loses a signup to a
	 * misconfigured API key and can always export the list.
	 *
	 * @param string $email Validated address.
	 * @return void
	 */
	private static function store( $email ) {
		$list = get_option( 'eg_subscribers', array() );
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		$key = md5( strtolower( $email ) );

		if ( ! isset( $list[ $key ] ) ) {
			$list[ $key ] = array(
				'email' => $email,
				'date'  => current_time( 'mysql' ),
			);

			// The option is not autoloaded, and a list this shape stays small.
			// Past a few thousand addresses a shop should be exporting to a real
			// ESP anyway, which is exactly what forward() is for.
			update_option( 'eg_subscribers', $list, false );
		}

		/**
		 * Fires when an address joins the list.
		 *
		 * @param string $email Validated address.
		 */
		do_action( 'eg_subscriber_added', $email );
	}

	/**
	 * Forwards the address to whichever provider is configured.
	 *
	 * The provider was left open rather than guessed, so this is an adapter with
	 * three implementations selected by a setting. With `none` the gate works
	 * end to end and simply keeps the address locally, so nothing blocks on
	 * credentials.
	 *
	 * @param string $email Validated address.
	 * @return true|\WP_Error
	 */
	private static function forward( $email ) {
		$provider = eg_option( 'esp_provider', 'none' );
		$key      = eg_option( 'esp_api_key', '' );
		$list     = eg_option( 'esp_list_id', '' );

		if ( 'none' === $provider || ! $key ) {
			return true;
		}

		switch ( $provider ) {
			case 'mailchimp':
				return self::mailchimp( $email, $key, $list );
			case 'klaviyo':
				return self::klaviyo( $email, $key, $list );
			case 'resend':
				return self::resend( $email, $key, $list );
		}

		return true;
	}

	/**
	 * Mailchimp Marketing API.
	 *
	 * @param string $email Address.
	 * @param string $key   API key, which carries its datacentre after the dash.
	 * @param string $list  Audience ID.
	 * @return true|\WP_Error
	 */
	private static function mailchimp( $email, $key, $list ) {
		if ( ! $list ) {
			return new \WP_Error( 'eg_esp', 'Mailchimp audience ID is not set.' );
		}

		$parts = explode( '-', $key );
		$dc    = end( $parts );

		return self::post(
			"https://{$dc}.api.mailchimp.com/3.0/lists/" . rawurlencode( $list ) . '/members',
			array(
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/json',
			),
			array(
				'email_address' => $email,
				'status'        => 'subscribed',
			)
		);
	}

	/**
	 * Klaviyo subscription API.
	 *
	 * @param string $email Address.
	 * @param string $key   Private API key.
	 * @param string $list  List ID.
	 * @return true|\WP_Error
	 */
	private static function klaviyo( $email, $key, $list ) {
		if ( ! $list ) {
			return new \WP_Error( 'eg_esp', 'Klaviyo list ID is not set.' );
		}

		return self::post(
			'https://a.klaviyo.com/api/profile-subscription-bulk-create-jobs/',
			array(
				'Authorization' => 'Klaviyo-API-Key ' . $key,
				'Content-Type'  => 'application/json',
				'revision'      => '2024-10-15',
			),
			array(
				'data' => array(
					'type'          => 'profile-subscription-bulk-create-job',
					'attributes'    => array(
						'profiles' => array(
							'data' => array(
								array(
									'type'       => 'profile',
									'attributes' => array( 'email' => $email ),
								),
							),
						),
					),
					'relationships' => array(
						'list' => array(
							'data' => array(
								'type' => 'list',
								'id'   => $list,
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Resend audiences API.
	 *
	 * @param string $email Address.
	 * @param string $key   API key.
	 * @param string $list  Audience ID.
	 * @return true|\WP_Error
	 */
	private static function resend( $email, $key, $list ) {
		if ( ! $list ) {
			return new \WP_Error( 'eg_esp', 'Resend audience ID is not set.' );
		}

		return self::post(
			'https://api.resend.com/audiences/' . rawurlencode( $list ) . '/contacts',
			array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			array(
				'email'        => $email,
				'unsubscribed' => false,
			)
		);
	}

	/**
	 * Posts JSON to a provider.
	 *
	 * @param string $url     Endpoint.
	 * @param array  $headers Request headers.
	 * @param array  $body    Payload, JSON-encoded here.
	 * @return true|\WP_Error
	 */
	private static function post( $url, $headers, $body ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 12,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 409 from Mailchimp and friends means "already subscribed", which is a
		// success from the visitor's point of view.
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}
		if ( 409 === $code || 400 === $code ) {
			return true;
		}

		return new \WP_Error( 'eg_esp', 'Provider responded ' . $code );
	}

	/* ── rate limiting ───────────────────────────────────────────────────── */

	/**
	 * Five attempts per ten minutes per IP.
	 *
	 * Held in transients, which means the object cache when one is present and
	 * the options table when it is not. Either way it survives across requests,
	 * which an in-process counter would not on PHP.
	 *
	 * @return bool True when the request is within the limit.
	 */
	private static function rate_limit_ok() {
		$key   = 'eg_rl_' . md5( self::ip() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}

	/**
	 * The requesting IP.
	 *
	 * REMOTE_ADDR only. A forwarded-for header is attacker-controlled unless a
	 * known proxy sets it, so trusting it here would hand anyone an unlimited
	 * number of rate-limit buckets.
	 *
	 * @return string
	 */
	private static function ip() {
		return isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';
	}

	/**
	 * Logs a message when debugging is on.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[especial-gallery] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
