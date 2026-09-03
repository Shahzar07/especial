<?php
/**
 * Asset enqueueing.
 *
 * Nothing in this theme prints a <script> or <link> tag in a template. Every
 * asset goes through the enqueue API so a caching or optimisation plugin can
 * see it, defer it, combine it or version it — which is the whole reason the
 * API exists.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers styles, scripts and the data the front end needs.
 */
class Assets {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'preload_fonts' ), 2 );
		add_action( 'wp_head', array( $this, 'custom_properties' ), 3 );
		add_filter( 'script_loader_tag', array( $this, 'defer' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
	}

	/**
	 * Front-end styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_style( 'especial-gallery', EG_URI . '/assets/css/main.css', array(), EG_VERSION );

		// style.css carries only the theme header, but WordPress and several
		// plugins expect a handle named after the stylesheet to exist.
		wp_register_style( 'especial-gallery-theme', get_stylesheet_uri(), array( 'especial-gallery' ), EG_VERSION );

		wp_enqueue_script( 'especial-gallery', EG_URI . '/assets/js/main.js', array(), EG_VERSION, true );

		wp_localize_script( 'especial-gallery', 'egData', $this->script_data() );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Everything the front-end JavaScript needs, in one object.
	 *
	 * Strings come from here rather than being written into the script so they
	 * go through translation like the rest of the theme.
	 *
	 * @return array
	 */
	private function script_data() {
		$checkout  = eg_page_url( 'checkout' );
		$confirmed = eg_page_url( 'confirmed' );

		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'eg_public' ),
			'checkout'  => $checkout ? $checkout : home_url( '/' ),
			'confirmed' => $confirmed ? $confirmed : home_url( '/' ),
			'currency'  => eg_option( 'currency', 'USD' ),
			'i18n'      => array(
				'bagEmpty'    => __( 'Your bag is empty.', 'especial-gallery' ),
				'item'        => __( 'item', 'especial-gallery' ),
				'items'       => __( 'items', 'especial-gallery' ),
				'openBag'     => __( 'Open bag', 'especial-gallery' ),
				'remove'      => __( 'Remove', 'especial-gallery' ),
				/* translators: %s: product name. */
				'increase'    => __( 'Increase quantity of %s', 'especial-gallery' ),
				/* translators: %s: product name. */
				'decrease'    => __( 'Decrease quantity of %s', 'especial-gallery' ),
				'added'       => __( 'Added to bag.', 'especial-gallery' ),
				'genericFail' => __( 'Something went wrong on our end. Try again.', 'especial-gallery' ),
				'emailError'  => __( 'Enter a valid email address.', 'especial-gallery' ),
				'subscribed'  => __( 'You are on the list.', 'especial-gallery' ),
				'placing'     => __( 'Placing order', 'especial-gallery' ),
				'placeOrder'  => __( 'Place order', 'especial-gallery' ),
				'entering'    => __( 'Entering', 'especial-gallery' ),
				'enter'       => __( 'Enter', 'especial-gallery' ),
				'adding'      => __( 'Adding', 'especial-gallery' ),
				'signUp'      => __( 'Sign up', 'especial-gallery' ),
				'required'    => __( 'Required.', 'especial-gallery' ),
				'free'        => __( 'Free', 'especial-gallery' ),
				/* translators: %s: formatted price threshold. */
				'freeShippingOver' => __( 'Free shipping over %s.', 'especial-gallery' ),
			),
		);
	}

	/**
	 * Preloads the two font files.
	 *
	 * Without this the browser only discovers them after parsing the stylesheet,
	 * which is late enough to show a flash of the fallback face on a cold load.
	 *
	 * @return void
	 */
	public function preload_fonts() {
		$fonts = array(
			'/assets/fonts/instrument-sans-var.woff2',
			'/assets/fonts/instrument-serif-400.woff2',
		);

		foreach ( $fonts as $font ) {
			if ( ! file_exists( EG_DIR . $font ) ) {
				continue;
			}
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( EG_URI . $font )
			);
		}
	}

	/**
	 * Emits the Customizer's colour overrides as custom properties.
	 *
	 * Overriding the properties rather than restating whole rules means a
	 * rebrand touches four declarations and every component follows, exactly as
	 * the design system intended.
	 *
	 * @return void
	 */
	public function custom_properties() {
		$map = array(
			'--eg-accent'    => get_theme_mod( 'eg_color_accent', '' ),
			'--eg-ink'       => get_theme_mod( 'eg_color_ink', '' ),
			'--eg-paper'     => get_theme_mod( 'eg_color_paper', '' ),
			'--eg-rule'      => get_theme_mod( 'eg_color_rule', '' ),
			'--eg-max-width' => get_theme_mod( 'eg_max_width', '' ),
		);

		$declarations = array();

		foreach ( $map as $property => $value ) {
			if ( ! $value ) {
				continue;
			}
			$value = ( '--eg-max-width' === $property )
				? preg_replace( '/[^0-9a-z.%]/i', '', $value )
				: sanitize_hex_color( $value );

			if ( $value ) {
				$declarations[] = $property . ':' . $value;
			}
		}

		if ( ! $declarations ) {
			return;
		}

		printf(
			'<style id="eg-tokens">:root{%s}</style>' . "\n",
			esc_html( implode( ';', $declarations ) )
		);
	}

	/**
	 * Defers the theme's own script.
	 *
	 * It only ever runs after DOMContentLoaded anyway, so blocking the parser
	 * for it buys nothing.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function defer( $tag, $handle ) {
		if ( 'especial-gallery' === $handle && false === strpos( $tag, ' defer' ) ) {
			$tag = str_replace( ' src=', ' defer src=', $tag );
		}
		return $tag;
	}

	/**
	 * Admin styles and the media picker used by the product meta box.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function admin_enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_product = $screen && Post_Types::PRODUCT === $screen->post_type;
		$is_theme   = in_array( $hook, array( 'appearance_page_eg-settings', 'appearance_page_eg-demo' ), true );
		$is_term    = $screen && Post_Types::CATEGORY === $screen->taxonomy;

		if ( ! $is_product && ! $is_theme && ! $is_term ) {
			return;
		}

		if ( $is_product || $is_term ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'especial-gallery-admin', EG_URI . '/assets/css/admin.css', array(), EG_VERSION );
		wp_enqueue_script( 'especial-gallery-admin', EG_URI . '/assets/js/admin.js', array( 'jquery' ), EG_VERSION, true );

		wp_localize_script(
			'especial-gallery-admin',
			'egAdmin',
			array(
				'chooseImages' => __( 'Choose images', 'especial-gallery' ),
				'useImages'    => __( 'Use these images', 'especial-gallery' ),
				'chooseImage'  => __( 'Choose image', 'especial-gallery' ),
				'useImage'     => __( 'Use this image', 'especial-gallery' ),
				'remove'       => __( 'Remove', 'especial-gallery' ),
			)
		);
	}
}
