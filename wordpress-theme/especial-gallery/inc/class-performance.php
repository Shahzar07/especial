<?php
/**
 * Performance.
 *
 * The original was a static export with a first-party image optimiser. A PHP
 * theme cannot match that for free, so this closes the gap where it is cheapest
 * to close: fewer requests, correctly sized images, and no work done for a page
 * that will not use the result.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Trims the head, sizes images and lazy-loads what is below the fold.
 */
class Performance {

	/**
	 * Hooks.
	 */
	public function __construct() {
		// Emoji support costs a stylesheet and a script on every page for a
		// feature every browser this theme targets has natively.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );

		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );

		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_block_library' ), 100 );
		add_filter( 'wp_lazy_loading_enabled', array( $this, 'lazy_loading' ), 10, 2 );
		add_action( 'save_post_' . Post_Types::PRODUCT, array( $this, 'flush_cache' ) );
		add_action( 'wp_head', array( $this, 'preconnect' ), 1 );
	}

	/**
	 * Drops the block library stylesheet on pages with no blocks.
	 *
	 * The storefront templates are hand-written markup, so on a product page
	 * this is a stylesheet nothing on the page uses.
	 *
	 * @return void
	 */
	public function dequeue_block_library() {
		if ( is_singular() && ! has_blocks( get_the_ID() ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'classic-theme-styles' );
		}
	}

	/**
	 * Keeps the first hero and product images eager.
	 *
	 * Lazy-loading the largest element above the fold delays the very paint
	 * Largest Contentful Paint measures, which is the opposite of the intent.
	 * WordPress's own heuristic misses these because they are printed by
	 * template parts rather than by the_content().
	 *
	 * @param bool   $default Whether to lazy-load.
	 * @param string $context Where the image is being rendered.
	 * @return bool
	 */
	public function lazy_loading( $default, $context ) {
		if ( 'eg_above_fold' === $context ) {
			return false;
		}
		return $default;
	}

	/**
	 * Clears the theme's own cached fragments when a product changes.
	 *
	 * @return void
	 */
	public function flush_cache() {
		delete_transient( 'eg_category_counts' );
	}

	/**
	 * Opens the connection to the payment provider early.
	 *
	 * Only on the checkout page, and only when a provider is actually
	 * configured — a preconnect to a host the page never contacts is a wasted
	 * socket, not an optimisation.
	 *
	 * @return void
	 */
	public function preconnect() {
		if ( ! eg_is_page( 'checkout' ) ) {
			return;
		}

		$has_stripe = ( defined( 'EG_STRIPE_SECRET_KEY' ) && EG_STRIPE_SECRET_KEY )
			|| eg_option( 'stripe_secret_key', '' );

		if ( $has_stripe ) {
			echo '<link rel="preconnect" href="https://checkout.stripe.com" crossorigin>' . "\n";
		}
	}
}
