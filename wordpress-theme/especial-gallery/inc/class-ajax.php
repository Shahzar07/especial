<?php
/**
 * AJAX endpoints, plus the no-JavaScript fallbacks for the same actions.
 *
 * The source project's API routes map here: /api/subscribe, /api/checkout and
 * /api/checkout/quote. Each one has a nonce, sanitises everything it reads, and
 * always answers with a parseable JSON body whatever happens to it — a form
 * that gets HTML back where it expected JSON fails in a way nobody can debug.
 *
 * Every action is registered for both logged-in and logged-out visitors,
 * because a shop's customers are overwhelmingly the latter.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the theme's AJAX actions.
 */
class Ajax {

	/**
	 * Hooks.
	 */
	public function __construct() {
		$actions = array(
			'eg_subscribe'      => 'subscribe',
			'eg_checkout_quote' => 'quote',
			'eg_checkout'       => 'checkout',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
		}

		// The no-JS paths. Both post to admin-post.php, which is the WordPress
		// equivalent of the server actions the original used when React had not
		// hydrated yet.
		add_action( 'admin_post_eg_subscribe_form', array( $this, 'subscribe_form' ) );
		add_action( 'admin_post_nopriv_eg_subscribe_form', array( $this, 'subscribe_form' ) );
		add_action( 'admin_post_eg_checkout_form', array( $this, 'checkout_form' ) );
		add_action( 'admin_post_nopriv_eg_checkout_form', array( $this, 'checkout_form' ) );
	}

	/* ── subscribe ───────────────────────────────────────────────────────── */

	/**
	 * Handles a mailing-list submission over AJAX.
	 *
	 * @return void
	 */
	public function subscribe() {
		if ( ! check_ajax_referer( 'eg_public', 'nonce', false ) ) {
			wp_send_json_error( array( 'error' => Subscribe::generic_error() ), 403 );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$next  = isset( $_POST['next'] ) ? esc_url_raw( wp_unslash( $_POST['next'] ) ) : '/';

		$result = Subscribe::process( $email, $next );

		if ( ! $result['ok'] ) {
			wp_send_json_error( array( 'error' => $result['error'] ), $result['status'] );
		}

		wp_send_json_success( array( 'redirect' => $result['redirect'] ) );
	}

	/**
	 * Handles a mailing-list submission posted as a real form.
	 *
	 * @return void
	 */
	public function subscribe_form() {
		check_admin_referer( 'eg_public', 'eg_nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$next  = isset( $_POST['next'] ) ? esc_url_raw( wp_unslash( $_POST['next'] ) ) : '/';

		$result = Subscribe::process( $email, $next );

		// An invalid address still sets no cookie, and the visitor lands back on
		// the gate with the reason rather than on a white admin-post page.
		if ( ! $result['ok'] ) {
			$gate = eg_page_url( 'gate' );
			wp_safe_redirect(
				add_query_arg(
					array(
						'eg_error' => rawurlencode( $result['error'] ),
						'eg_next'  => rawurlencode( eg_safe_next( $next ) ),
					),
					$gate ? $gate : home_url( '/' )
				)
			);
			exit;
		}

		wp_safe_redirect( home_url( eg_safe_next( $result['redirect'] ) ) );
		exit;
	}

	/* ── checkout ────────────────────────────────────────────────────────── */

	/**
	 * Prices the bag so the summary panel can be filled in.
	 *
	 * This is the same function that prices the real order, so the figure a
	 * customer reads and the figure they are charged come from one place and
	 * cannot drift.
	 *
	 * @return void
	 */
	public function quote() {
		if ( ! check_ajax_referer( 'eg_public', 'nonce', false ) ) {
			wp_send_json_error( array( 'error' => Subscribe::generic_error() ), 403 );
		}

		$order = Orders::price( $this->read_lines() );

		if ( empty( $order['ok'] ) ) {
			wp_send_json_error( array( 'error' => $order['error'] ), 400 );
		}

		wp_send_json_success( $this->quote_payload( $order ) );
	}

	/**
	 * Places an order over AJAX.
	 *
	 * @return void
	 */
	public function checkout() {
		if ( ! check_ajax_referer( 'eg_public', 'nonce', false ) ) {
			wp_send_json_error( array( 'error' => Subscribe::generic_error() ), 403 );
		}

		$order = Orders::price( $this->read_lines() );

		if ( empty( $order['ok'] ) ) {
			wp_send_json_error( array( 'error' => $order['error'] ), 400 );
		}

		$customer = $this->read_customer();

		if ( is_wp_error( $customer ) ) {
			wp_send_json_error(
				array(
					'error'  => $customer->get_error_message(),
					'fields' => $customer->get_error_data(),
				),
				400
			);
		}

		$placed = Orders::place( $order, $customer );

		if ( is_wp_error( $placed ) ) {
			wp_send_json_error( array( 'error' => $placed->get_error_message() ), 502 );
		}

		wp_send_json_success( $placed );
	}

	/**
	 * Places an order posted as a real form, for a browser without JavaScript.
	 *
	 * @return void
	 */
	public function checkout_form() {
		check_admin_referer( 'eg_public', 'eg_nonce' );

		$checkout = eg_page_url( 'checkout' );
		$checkout = $checkout ? $checkout : home_url( '/' );

		$order = Orders::price( $this->read_lines() );

		if ( empty( $order['ok'] ) ) {
			wp_safe_redirect( add_query_arg( 'eg_error', rawurlencode( $order['error'] ), $checkout ) );
			exit;
		}

		$customer = $this->read_customer();

		if ( is_wp_error( $customer ) ) {
			wp_safe_redirect( add_query_arg( 'eg_error', rawurlencode( $customer->get_error_message() ), $checkout ) );
			exit;
		}

		$placed = Orders::place( $order, $customer );

		if ( is_wp_error( $placed ) ) {
			wp_safe_redirect( add_query_arg( 'eg_error', rawurlencode( $placed->get_error_message() ), $checkout ) );
			exit;
		}

		if ( 'redirect' === $placed['kind'] ) {
			// The payment provider's own hosted page, so wp_safe_redirect's
			// same-host restriction does not apply and would break the flow.
			wp_redirect( $placed['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		$confirmed = eg_page_url( 'confirmed' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'order' => rawurlencode( $placed['order_id'] ),
					'clear' => '1',
				),
				$confirmed ? $confirmed : home_url( '/' )
			)
		);
		exit;
	}

	/* ── request reading ─────────────────────────────────────────────────── */

	/**
	 * Reads and sanitises the bag lines from the request.
	 *
	 * Only slug, variant and quantity are read. Anything else the browser sends
	 * — a price above all — is dropped on the floor here rather than trusted.
	 *
	 * @return array
	 */
	private function read_lines() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify first.
		$raw = isset( $_POST['lines'] ) ? wp_unslash( $_POST['lines'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$lines = array();
		foreach ( $raw as $line ) {
			if ( ! is_array( $line ) ) {
				continue;
			}
			$lines[] = array(
				'slug'       => isset( $line['slug'] ) ? sanitize_title( $line['slug'] ) : '',
				'variant_id' => isset( $line['variant_id'] ) ? sanitize_key( $line['variant_id'] ) : 'standard',
				'quantity'   => isset( $line['quantity'] ) ? absint( $line['quantity'] ) : 0,
			);
		}

		return $lines;
	}

	/**
	 * Reads and validates the customer block.
	 *
	 * @return array|\WP_Error Sanitised fields, or an error naming the bad ones.
	 */
	private function read_customer() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify first.
		$get = function ( $key ) {
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		};

		$customer = array(
			'email'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'name'     => $get( 'name' ),
			'line1'    => $get( 'line1' ),
			'line2'    => $get( 'line2' ),
			'city'     => $get( 'city' ),
			'postcode' => $get( 'postcode' ),
			'country'  => $get( 'country' ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$required = array( 'email', 'name', 'line1', 'city', 'postcode', 'country' );
		$missing  = array();

		foreach ( $required as $field ) {
			if ( '' === trim( $customer[ $field ] ) ) {
				$missing[] = $field;
			}
		}

		if ( $missing ) {
			return new \WP_Error(
				'eg_checkout_fields',
				__( 'Fill in every required field.', 'especial-gallery' ),
				$missing
			);
		}

		if ( ! is_email( $customer['email'] ) ) {
			return new \WP_Error(
				'eg_checkout_email',
				__( 'Enter a valid email address.', 'especial-gallery' ),
				array( 'email' )
			);
		}

		// Length ceilings, so a crafted request cannot write a novel into meta.
		foreach ( $customer as $key => $value ) {
			$customer[ $key ] = mb_substr( $value, 0, 254 );
		}

		return $customer;
	}

	/**
	 * Shapes a priced order for the browser.
	 *
	 * Pre-formatted strings go with the figures so the client never re-implements
	 * currency formatting and the two can never disagree.
	 *
	 * @param array $order Priced order.
	 * @return array
	 */
	private function quote_payload( $order ) {
		return array(
			'subtotal_cents'           => $order['subtotal_cents'],
			'shipping_cents'           => $order['shipping_cents'],
			'total_cents'              => $order['total_cents'],
			'free_shipping_over_cents' => $order['free_shipping_over_cents'],
			'subtotal'                 => eg_format_price( $order['subtotal_cents'] ),
			'shipping'                 => 0 === $order['shipping_cents']
				? __( 'Free', 'especial-gallery' )
				: eg_format_price( $order['shipping_cents'] ),
			'total'                    => eg_format_price( $order['total_cents'] ),
			'free_shipping_over'       => eg_format_price( $order['free_shipping_over_cents'] ),
			'lines'                    => $order['lines'],
		);
	}
}
