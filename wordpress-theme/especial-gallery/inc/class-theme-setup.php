<?php
/**
 * Theme supports, menus, widget areas, image sizes.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers everything WordPress needs to know about the theme itself.
 */
class Theme_Setup {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'widgets_init', array( $this, 'widgets' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'excerpt_more', array( $this, 'excerpt_more' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'nav_menu_defaults' ) );
	}

	/**
	 * Theme support and registration.
	 *
	 * @return void
	 */
	public function setup() {
		load_theme_textdomain( 'especial-gallery', EG_DIR . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'customize-selective-refresh-widgets' );

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style', 'navigation-widgets' )
		);

		add_theme_support(
			'custom-logo',
			array(
				'height'      => 64,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		/*
		 * The palette is the design system's, verbatim. Exposing it to the block
		 * editor is what stops an editor picking a colour the system does not
		 * contain — the alternative is a page that quietly breaks the rules.
		 */
		add_theme_support( 'editor-color-palette', $this->editor_palette() );
		add_theme_support( 'editor-font-sizes', $this->editor_font_sizes() );
		add_theme_support( 'disable-custom-gradients' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );

		// WooCommerce is optional, but if it is installed its pages should
		// inherit this theme rather than fall back to Woo's own markup.
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary (header)', 'especial-gallery' ),
				'shop'    => __( 'Footer — Shop', 'especial-gallery' ),
				'help'    => __( 'Footer — Help', 'especial-gallery' ),
				'legal'   => __( 'Footer — Legal', 'especial-gallery' ),
				'gate'    => __( 'Gate links', 'especial-gallery' ),
			)
		);

		/*
		 * Every product image is 4:5, no exceptions — layout.grid.imageRatio in
		 * the design system. Hard cropping guarantees it rather than hoping the
		 * uploaded file already has that shape.
		 */
		set_post_thumbnail_size( 800, 1000, true );
		add_image_size( 'eg-tile', 800, 1000, true );
		add_image_size( 'eg-tile-2x', 1400, 1750, true );
		add_image_size( 'eg-square', 900, 900, true );
		add_image_size( 'eg-thumb', 200, 250, true );
		add_image_size( 'eg-banner', 2400, 1200, false );
	}

	/**
	 * The design system's colour tokens, as an editor palette.
	 *
	 * @return array
	 */
	private function editor_palette() {
		return array(
			array(
				'name'  => __( 'Paper', 'especial-gallery' ),
				'slug'  => 'paper',
				'color' => '#FFFFFF',
			),
			array(
				'name'  => __( 'Ink', 'especial-gallery' ),
				'slug'  => 'ink',
				'color' => '#000000',
			),
			array(
				'name'  => __( 'Ink muted', 'especial-gallery' ),
				'slug'  => 'ink-muted',
				'color' => '#666666',
			),
			array(
				'name'  => __( 'Rule', 'especial-gallery' ),
				'slug'  => 'rule',
				'color' => '#E6E6E4',
			),
			array(
				'name'  => __( 'Wash', 'especial-gallery' ),
				'slug'  => 'wash',
				'color' => '#F5F5F3',
			),
			array(
				'name'  => __( 'Accent', 'especial-gallery' ),
				'slug'  => 'accent',
				'color' => '#1B34FF',
			),
		);
	}

	/**
	 * The type scale, as editor font sizes.
	 *
	 * @return array
	 */
	private function editor_font_sizes() {
		return array(
			array(
				'name' => __( 'Small', 'especial-gallery' ),
				'slug' => 'small',
				'size' => 14,
			),
			array(
				'name' => __( 'Body', 'especial-gallery' ),
				'slug' => 'normal',
				'size' => 16,
			),
			array(
				'name' => __( 'Lead', 'especial-gallery' ),
				'slug' => 'large',
				'size' => 20,
			),
			array(
				'name' => __( 'Section', 'especial-gallery' ),
				'slug' => 'huge',
				'size' => 39,
			),
		);
	}

	/**
	 * Widget areas.
	 *
	 * @return void
	 */
	public function widgets() {
		register_sidebar(
			array(
				'name'          => __( 'Sidebar', 'especial-gallery' ),
				'id'            => 'sidebar-1',
				'description'   => __( 'Shown beside blog posts and archives.', 'especial-gallery' ),
				'before_widget' => '<section id="%1$s" class="eg-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);

		register_sidebar(
			array(
				'name'          => __( 'Footer extra', 'especial-gallery' ),
				'id'            => 'footer-1',
				'description'   => __( 'An optional fifth column in the footer.', 'especial-gallery' ),
				'before_widget' => '<section id="%1$s" class="eg-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}

	/**
	 * Adds theme state to the body class list.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( ! is_active_sidebar( 'sidebar-1' ) ) {
			$classes[] = 'eg-no-sidebar';
		}
		if ( is_singular( 'eg_product' ) || is_post_type_archive( 'eg_product' ) || is_tax( 'eg_product_cat' ) ) {
			$classes[] = 'eg-store';
		}
		return $classes;
	}

	/**
	 * A hairline ellipsis rather than the default bracketed one.
	 *
	 * @param string $more Existing more string.
	 * @return string
	 */
	public function excerpt_more( $more ) {
		unset( $more );
		return '…';
	}

	/**
	 * Menus fall back to nothing rather than to a list of every page, which is
	 * what wp_page_menu() would otherwise dump into the header on a fresh site.
	 *
	 * @param array $args Menu arguments.
	 * @return array
	 */
	public function nav_menu_defaults( $args ) {
		if ( ! isset( $args['fallback_cb'] ) ) {
			$args['fallback_cb'] = '__return_empty_string';
		}
		return $args;
	}
}
