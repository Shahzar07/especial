<?php
/**
 * Especial Gallery — theme bootstrap.
 *
 * This file stays a bootstrap. Every piece of behaviour lives in a class under
 * /inc and is required and constructed here, so there is exactly one place to
 * look to see what the theme does.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

define( 'EG_VERSION', '1.0.0' );
define( 'EG_DIR', get_template_directory() );
define( 'EG_URI', get_template_directory_uri() );

/**
 * Loads a class file from /inc.
 *
 * @param string $slug File slug without the `class-` prefix or `.php` suffix.
 * @return void
 */
function eg_require( $slug ) {
	$path = EG_DIR . '/inc/class-' . $slug . '.php';
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

require_once EG_DIR . '/inc/helpers.php';

eg_require( 'theme-setup' );
eg_require( 'assets' );
eg_require( 'catalog' );
eg_require( 'post-types' );
eg_require( 'meta-boxes' );
eg_require( 'customizer' );
eg_require( 'template-tags' );
eg_require( 'walker-nav' );
eg_require( 'gate' );
eg_require( 'subscribe' );
eg_require( 'orders' );
eg_require( 'ajax' );
eg_require( 'seo' );
eg_require( 'security' );
eg_require( 'performance' );
eg_require( 'admin' );
eg_require( 'demo-content' );

new Especial_Gallery\Theme_Setup();
new Especial_Gallery\Assets();
new Especial_Gallery\Post_Types();
new Especial_Gallery\Meta_Boxes();
new Especial_Gallery\Customizer();
new Especial_Gallery\Gate();
new Especial_Gallery\Ajax();
new Especial_Gallery\Seo();
new Especial_Gallery\Security();
new Especial_Gallery\Performance();
new Especial_Gallery\Admin();

/*
 * WooCommerce is optional. The theme ships its own catalogue, bag and checkout
 * so it is a complete store on activation with no plugin at all; this bridge
 * only runs when WooCommerce is present, and its job is to make Woo's own pages
 * inherit the same visual system rather than to replace the native store.
 */
if ( class_exists( 'WooCommerce' ) ) {
	eg_require( 'woocommerce' );
	new Especial_Gallery\Woo();
}
