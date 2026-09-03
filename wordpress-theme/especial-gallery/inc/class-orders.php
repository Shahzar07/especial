<?php
/**
 * Order pricing and placement.
 *
 * The catalogue prices the order, never the browser. The bag sends only slugs,
 * variant ids and quantities; every price, title and total is looked up and
 * recomputed here, and availability is re-checked because a variant can sell
 * out between going into a bag and being paid for. A line arriving with its own
 * price is ignored outright — accepting one would let anyone set their own.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Prices, validates and records orders.
 */
class Orders {

	/**
	 * Flat shipping rate in minor units, until a carrier is wired in.
	 *
	 * @return int
	 */
	public static function shipping_cents() {
		return (int) eg_option( 'shipping_cents', 600 );
	}

	/**
	 * Subtotal at or above which shipping is free, in minor units.
	 *
	 * @return int
	 */
	public static function free_shipping_over_cents() {
		return (int) eg_option( 'free_shipping_over_cents', 7500 );
	}

	/**
	 * Rebuilds an order from the catalogue.
	 *
	 * @param array $lines Raw lines: slug, variant_id, quantity.
	 * @return array {
	 *     Either a priced order or a failure.
	 *
	 *     @type bool   $ok             False on failure.
	 *     @type string $error          Customer-facing message on failure.
	 *     @type array  $lines          Priced lines on success.
	 *     @type int    $subtotal_cents Subtotal on success.
	 *     @type int    $shipping_cents Shipping on success.
	 *     @type int    $total_cents    Total on success.
	 * }
	 */
	public static function price( $lines ) {
		if ( ! is_array( $lines ) || ! $lines ) {
			return array(
				'ok'    => false,
				'error' => __( 'Your bag is empty.', 'especial-gallery' ),
			);
		}

		if ( count( $lines ) > 40 ) {
			return array(
				'ok'    => false,
				'error' => __( 'That is more items than we can process at once.', 'especial-gallery' ),
			);
		}

		$priced = array();

		foreach ( $lines as $line ) {
			$slug       = isset( $line['slug'] ) ? sanitize_title( $line['slug'] ) : '';
			$variant_id = isset( $line['variant_id'] ) ? sanitize_key( $line['variant_id'] ) : '';
			$quantity   = isset( $line['quantity'] ) ? absint( $line['quantity'] ) : 0;

			if ( ! $slug || $quantity < 1 ) {
				return array(
					'ok'    => false,
					'error' => __( 'Something in your bag is no longer available.', 'especial-gallery' ),
				);
			}

			// A single line cannot exceed 20 units. Without a ceiling, a crafted
			// request can ask for two billion of something and overflow the total.
			if ( $quantity > 20 ) {
				$quantity = 20;
			}

			$product = Catalog::by_slug( $slug );
			if ( ! $product ) {
				return array(
					'ok'    => false,
					'error' => __( 'Something in your bag is no longer available.', 'especial-gallery' ),
				);
			}

			$variant = Catalog::variant( $product['id'], $variant_id );
			if ( ! $variant ) {
				return array(
					'ok'    => false,
					/* translators: %s: product name. */
					'error' => sprintf( __( '%s is no longer offered in that option.', 'especial-gallery' ), $product['title'] ),
				);
			}

			if ( ! $variant['available'] || $product['sold_out'] ) {
				return array(
					'ok'    => false,
					/* translators: 1: product name, 2: variant label. */
					'error' => sprintf( __( '%1$s (%2$s) has sold out.', 'especial-gallery' ), $product['title'], $variant['label'] ),
				);
			}

			$priced[] = array(
				'slug'          => $product['slug'],
				'variant_id'    => $variant['id'],
				'title'         => $product['title'],
				'variant_label' => $variant['label'],
				'quantity'      => $quantity,
				'unit_cents'    => $product['price_cents'],
				'total_cents'   => $product['price_cents'] * $quantity,
				'image'         => isset( $product['images'][0]['url'] ) ? $product['images'][0]['url'] : '',
			);
		}

		$subtotal = 0;
		foreach ( $priced as $line ) {
			$subtotal += $line['total_cents'];
		}

		$free_over = self::free_shipping_over_cents();
		$shipping  = ( $free_over > 0 && $subtotal >= $free_over ) ? 0 : self::shipping_cents();

		return array(
			'ok'                       => true,
			'lines'                    => $priced,
			'subtotal_cents'           => $subtotal,
			'shipping_cents'           => $shipping,
			'total_cents'              => $subtotal + $shipping,
			'free_shipping_over_cents' => $free_over,
			'currency'                 => eg_option( 'currency', 'USD' ),
		);
	}

	/**
	 * Generates a short, unambiguous order reference.
	 *
	 * The alphabet omits look-alike characters, because these are read aloud
	 * down a telephone and typed back into a support form.
	 *
	 * @return string
	 */
	public static function reference() {
		$alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$out      = '';

		for ( $i = 0; $i < 8; $i++ ) {
			$out .= $alphabet[ wp_rand( 0, strlen( $alphabet ) - 1 ) ];
		}

		return 'EG-' . $out;
	}

	/**
	 * Records the order and hands it to a payment provider if one is configured.
	 *
	 * With a Stripe key set this creates a real Checkout Session and returns its
	 * URL. With no key the order is recorded and acknowledged instead, so
	 * validation, pricing, stock and confirmation are all exercisable before an
	 * account exists. Card details never touch this site in either case — that
	 * is the provider's job, on their own page.
	 *
	 * @param array $order    A priced order from self::price().
	 * @param array $customer Sanitised customer details.
	 * @return array|\WP_Error {
	 *     @type string $kind     'redirect' or 'recorded'.
	 *     @type string $url      Payment page, when kind is 'redirect'.
	 *     @type string $order_id Reference, when kind is 'recorded'.
	 * }
	 */
	public static function place( $order, $customer ) {
		$reference = self::reference();
		$post_id   = self::record( $reference, $order, $customer );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$key = self::stripe_key();

		if ( ! $key ) {
			return array(
				'kind'     => 'recorded',
				'order_id' => $reference,
			);
		}

		$session = self::stripe_session( $key, $order, $customer, $reference );

		if ( is_wp_error( $session ) ) {
			// The order is already recorded, so nothing is lost; the shop owner
			// sees it in the list table and the customer sees an honest error
			// rather than a blank page.
			update_post_meta( $post_id, '_eg_status', 'payment-failed' );
			return $session;
		}

		update_post_meta( $post_id, '_eg_status', 'awaiting-payment' );

		return array(
			'kind' => 'redirect',
			'url'  => $session,
		);
	}

	/**
	 * The Stripe secret key, from wp-config or the settings screen.
	 *
	 * A constant in wp-config.php wins, because that is the right place for a
	 * secret: it stays out of the database and out of a database export.
	 *
	 * @return string
	 */
	private static function stripe_key() {
		if ( defined( 'EG_STRIPE_SECRET_KEY' ) && EG_STRIPE_SECRET_KEY ) {
			return (string) EG_STRIPE_SECRET_KEY;
		}
		return (string) eg_option( 'stripe_secret_key', '' );
	}

	/**
	 * Writes the order to the database.
	 *
	 * @param string $reference Order reference.
	 * @param array  $order     Priced order.
	 * @param array  $customer  Customer details.
	 * @return int|\WP_Error Post ID or error.
	 */
	private static function record( $reference, $order, $customer ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => Post_Types::ORDER,
				'post_title'  => $reference,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_eg_reference', $reference );
		update_post_meta( $post_id, '_eg_email', $customer['email'] );
		update_post_meta( $post_id, '_eg_name', $customer['name'] );
		update_post_meta( $post_id, '_eg_address', wp_json_encode( $customer ) );
		update_post_meta( $post_id, '_eg_lines', wp_json_encode( $order['lines'] ) );
		update_post_meta( $post_id, '_eg_subtotal_cents', (int) $order['subtotal_cents'] );
		update_post_meta( $post_id, '_eg_shipping_cents', (int) $order['shipping_cents'] );
		update_post_meta( $post_id, '_eg_total_cents', (int) $order['total_cents'] );
		update_post_meta( $post_id, '_eg_status', 'recorded' );

		self::notify( $reference, $order, $customer );

		/**
		 * Fires once an order has been written.
		 *
		 * The hook a fulfilment integration should use: it runs whether or not
		 * a payment provider is configured, and it carries the priced order
		 * rather than anything the browser sent.
		 *
		 * @param int    $post_id  Order post ID.
		 * @param array  $order    Priced order.
		 * @param array  $customer Customer details.
		 */
		do_action( 'eg_order_recorded', $post_id, $order, $customer );

		return $post_id;
	}

	/**
	 * Emails the shop and the customer.
	 *
	 * A failure here must never fail the order — the customer has already
	 * committed, and the order is already in the database.
	 *
	 * @param string $reference Order reference.
	 * @param array  $order     Priced order.
	 * @param array  $customer  Customer details.
	 * @return void
	 */
	private static function notify( $reference, $order, $customer ) {
		$lines = array();
		foreach ( $order['lines'] as $line ) {
			$lines[] = sprintf(
				'%1$d x %2$s (%3$s) — %4$s',
				$line['quantity'],
				$line['title'],
				$line['variant_label'],
				eg_format_price( $line['total_cents'] )
			);
		}

		$body = implode(
			"\n",
			array_merge(
				array(
					/* translators: %s: order reference. */
					sprintf( __( 'Order %s', 'especial-gallery' ), $reference ),
					'',
				),
				$lines,
				array(
					'',
					__( 'Subtotal', 'especial-gallery' ) . ': ' . eg_format_price( $order['subtotal_cents'] ),
					__( 'Shipping', 'especial-gallery' ) . ': ' . eg_format_price( $order['shipping_cents'] ),
					__( 'Total', 'especial-gallery' ) . ': ' . eg_format_price( $order['total_cents'] ),
					'',
					$customer['name'],
					$customer['line1'],
					$customer['line2'],
					$customer['city'] . ' ' . $customer['postcode'],
					$customer['country'],
				)
			)
		);

		$admin_email = eg_option( 'order_email', get_option( 'admin_email' ) );

		/* translators: %s: order reference. */
		wp_mail( $admin_email, sprintf( __( 'New order %s', 'especial-gallery' ), $reference ), $body );

		/* translators: 1: brand, 2: order reference. */
		$subject = sprintf( __( '%1$s — order %2$s', 'especial-gallery' ), eg_brand(), $reference );
		wp_mail( $customer['email'], $subject, $body );
	}

	/**
	 * Creates a Stripe Checkout Session.
	 *
	 * Uses the HTTP API rather than curl, so it honours the site's proxy, TLS
	 * and timeout configuration like every other outbound request WordPress
	 * makes.
	 *
	 * @param string $key       Stripe secret key.
	 * @param array  $order     Priced order.
	 * @param array  $customer  Customer details.
	 * @param string $reference Order reference.
	 * @return string|\WP_Error Session URL or error.
	 */
	private static function stripe_session( $key, $order, $customer, $reference ) {
		$confirmed = eg_page_url( 'confirmed' );
		$checkout  = eg_page_url( 'checkout' );

		$body = array(
			'mode'                     => 'payment',
			'success_url'              => add_query_arg( 'order', rawurlencode( $reference ), $confirmed ),
			'cancel_url'               => $checkout,
			'customer_email'           => $customer['email'],
			'client_reference_id'      => $reference,
			'metadata[eg_reference]'   => $reference,
		);

		$i = 0;
		foreach ( $order['lines'] as $line ) {
			$body[ "line_items[{$i}][quantity]" ]                            = $line['quantity'];
			$body[ "line_items[{$i}][price_data][currency]" ]                = strtolower( $order['currency'] );
			$body[ "line_items[{$i}][price_data][unit_amount]" ]             = $line['unit_cents'];
			$body[ "line_items[{$i}][price_data][product_data][name]" ]      = $line['title'] . ' — ' . $line['variant_label'];
			$i++;
		}

		if ( $order['shipping_cents'] > 0 ) {
			$body[ "line_items[{$i}][quantity]" ]                       = 1;
			$body[ "line_items[{$i}][price_data][currency]" ]           = strtolower( $order['currency'] );
			$body[ "line_items[{$i}][price_data][unit_amount]" ]        = $order['shipping_cents'];
			$body[ "line_items[{$i}][price_data][product_data][name]" ] = __( 'Shipping', 'especial-gallery' );
		}

		$response = wp_remote_post(
			'https://api.stripe.com/v1/checkout/sessions',
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $data['url'] ) ) {
			return new \WP_Error(
				'eg_stripe',
				__( 'The payment provider refused the order. Try again.', 'especial-gallery' ),
				array( 'status' => $code )
			);
		}

		return (string) $data['url'];
	}
}
