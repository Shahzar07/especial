<?php
/**
 * WooCommerce bridge.
 *
 * The theme ships a complete store of its own — catalogue, bag, server-priced
 * checkout — so WooCommerce is optional and this file only loads when it is
 * present. Its job is to make Woo's own pages inherit this visual system, not
 * to replace the native store: a site running both keeps eg_product for the
 * gallery catalogue and lets WooCommerce handle whatever it was installed for.
 *
 * Everything here is a hook. There are no template overrides in /woocommerce,
 * deliberately: an overridden template silently goes stale the next time
 * WooCommerce changes it, and a shop discovers that during a checkout.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Restyles WooCommerce to the gallery system.
 */
class Woo {

	/**
	 * Hooks.
	 */
	public function __construct() {
		// Woo's own stylesheets fight the design system at almost every point —
		// they set radii, shadows and their own type scale. The theme restyles
		// the markup instead, so the smallest change is to drop them.
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ), 20 );

		// Woo wraps its content in markup this theme's containers already
		// provide, so the wrappers are replaced rather than nested.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', array( $this, 'open_wrapper' ), 10 );
		add_action( 'woocommerce_after_main_content', array( $this, 'close_wrapper' ), 10 );

		// The sidebar and the breadcrumb are not part of this design.
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		// Ratings are decoration in a gallery grid; the design has no place for
		// a star row and the original had none.
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );

		add_filter( 'loop_shop_columns', array( $this, 'columns' ) );
		add_filter( 'woocommerce_product_thumbnails_columns', array( $this, 'columns' ) );
		add_filter( 'loop_shop_per_page', array( $this, 'per_page' ), 20 );
	}

	/**
	 * The bridging stylesheet, loaded only on WooCommerce pages.
	 *
	 * @return void
	 */
	public function styles() {
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'especial-gallery-woo',
			EG_URI . '/assets/css/woocommerce.css',
			array( 'especial-gallery' ),
			EG_VERSION
		);
	}

	/**
	 * Opens the theme's own container around Woo's content.
	 *
	 * @return void
	 */
	public function open_wrapper() {
		echo '<div class="eg-container eg-page eg-woo">';
	}

	/**
	 * Closes it.
	 *
	 * @return void
	 */
	public function close_wrapper() {
		echo '</div>';
	}

	/**
	 * Column count.
	 *
	 * The theme's own grid is driven by a minimum tile width rather than a
	 * count; Woo insists on a number, so this is the closest equivalent at a
	 * desktop width.
	 *
	 * @return int
	 */
	public function columns() {
		return 4;
	}

	/**
	 * Products per page, matching the native catalogue's setting.
	 *
	 * @return int
	 */
	public function per_page() {
		return (int) get_theme_mod( 'eg_products_per_page', 24 );
	}
}
