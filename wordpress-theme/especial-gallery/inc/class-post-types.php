<?php
/**
 * Custom post types and taxonomies.
 *
 * The source application held its catalogue in a static TypeScript module with
 * a `Product` type and a set of accessors. The equivalent here is a real custom
 * post type with typed meta, so the catalogue becomes editable by a person
 * rather than a developer — which is the whole point of moving to WordPress.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers eg_product, eg_product_cat and eg_order.
 */
class Post_Types {

	const PRODUCT  = 'eg_product';
	const CATEGORY = 'eg_product_cat';
	const ORDER    = 'eg_order';

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'init', array( $this, 'category_rewrites' ), 11 );
		add_action( 'after_switch_theme', array( $this, 'flush' ) );

		// The root-level category rules are built from a cached list of slugs,
		// so they have to be rebuilt whenever that list can have changed.
		add_filter( 'term_link', array( $this, 'category_link' ), 10, 3 );
		add_action( 'created_' . self::CATEGORY, array( $this, 'refresh_category_slugs' ) );
		add_action( 'edited_' . self::CATEGORY, array( $this, 'refresh_category_slugs' ) );
		add_action( 'delete_' . self::CATEGORY, array( $this, 'refresh_category_slugs' ) );
		add_filter( 'manage_' . self::PRODUCT . '_posts_columns', array( $this, 'product_columns' ) );
		add_action( 'manage_' . self::PRODUCT . '_posts_custom_column', array( $this, 'product_column' ), 10, 2 );
		add_filter( 'manage_' . self::ORDER . '_posts_columns', array( $this, 'order_columns' ) );
		add_action( 'manage_' . self::ORDER . '_posts_custom_column', array( $this, 'order_column' ), 10, 2 );
	}

	/**
	 * Registers the post types and taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$product_slug  = eg_option( 'product_slug', 'product' );
		$category_base = eg_option( 'category_base', '' );

		register_post_type(
			self::PRODUCT,
			array(
				'labels'             => array(
					'name'               => __( 'Products', 'especial-gallery' ),
					'singular_name'      => __( 'Product', 'especial-gallery' ),
					'add_new'            => __( 'Add product', 'especial-gallery' ),
					'add_new_item'       => __( 'Add product', 'especial-gallery' ),
					'edit_item'          => __( 'Edit product', 'especial-gallery' ),
					'new_item'           => __( 'New product', 'especial-gallery' ),
					'view_item'          => __( 'View product', 'especial-gallery' ),
					'search_items'       => __( 'Search products', 'especial-gallery' ),
					'not_found'          => __( 'No products yet.', 'especial-gallery' ),
					'not_found_in_trash' => __( 'No products in the bin.', 'especial-gallery' ),
					'all_items'          => __( 'All products', 'especial-gallery' ),
					'menu_name'          => __( 'Products', 'especial-gallery' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_position'      => 20,
				'menu_icon'          => 'dashicons-screenoptions',
				'has_archive'        => 'shop',
				'rewrite'            => array(
					'slug'       => sanitize_title( $product_slug ),
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
				'taxonomies'         => array( self::CATEGORY ),
			)
		);

		register_taxonomy(
			self::CATEGORY,
			array( self::PRODUCT ),
			array(
				'labels'            => array(
					'name'          => __( 'Product categories', 'especial-gallery' ),
					'singular_name' => __( 'Product category', 'especial-gallery' ),
					'add_new_item'  => __( 'Add category', 'especial-gallery' ),
					'edit_item'     => __( 'Edit category', 'especial-gallery' ),
					'search_items'  => __( 'Search categories', 'especial-gallery' ),
					'menu_name'     => __( 'Categories', 'especial-gallery' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				/*
				 * The base defaults to empty, which puts categories at the root
				 * of the site — /keychains rather than /category/keychains, as
				 * the original had them. WordPress will not accept an empty
				 * rewrite slug, so it gets a placeholder here and the real
				 * root-level rules are added by category_rewrites() below.
				 */
				'rewrite'           => array(
					'slug'       => $category_base ? $category_base : 'collection',
					'with_front' => false,
				),
			)
		);

		/*
		 * Orders are private, uneditable-by-default records. They are a post type
		 * rather than a custom table because the volume a shop of this size sees
		 * fits the posts table comfortably, and it inherits search, capabilities,
		 * revisions and the list table for free.
		 */
		register_post_type(
			self::ORDER,
			array(
				'labels'          => array(
					'name'          => __( 'Orders', 'especial-gallery' ),
					'singular_name' => __( 'Order', 'especial-gallery' ),
					'edit_item'     => __( 'Order', 'especial-gallery' ),
					'search_items'  => __( 'Search orders', 'especial-gallery' ),
					'not_found'     => __( 'No orders yet.', 'especial-gallery' ),
					'menu_name'     => __( 'Orders', 'especial-gallery' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-list-view',
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);

		$this->register_meta();
	}

	/**
	 * Registers product meta with types and sanitisers.
	 *
	 * Registering rather than writing raw post meta means the REST API, the
	 * block editor and anything else reading these fields gets the right type
	 * and a sanitiser, instead of a bare string.
	 *
	 * @return void
	 */
	private function register_meta() {
		$fields = array(
			'_eg_price_cents'      => 'integer',
			'_eg_compare_at_cents' => 'integer',
			'_eg_featured'         => 'boolean',
			'_eg_sold_out'         => 'boolean',
			'_eg_details'          => 'string',
			'_eg_variants'         => 'string',
			'_eg_gallery'          => 'string',
			'_eg_released_at'      => 'string',
		);

		foreach ( $fields as $key => $type ) {
			register_post_meta(
				self::PRODUCT,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'integer' === $type
						? 'absint'
						: ( 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_textarea_field' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Rewrite rules are registered on `init`, which has already run by the time
	 * a theme is switched. Flushing once here is what makes product permalinks
	 * work immediately on activation rather than after a manual permalink save.
	 *
	 * @return void
	 */
	public function flush() {
		$this->register();
		$this->refresh_category_slugs();
		$this->category_rewrites();
		flush_rewrite_rules();
	}

	/**
	 * Puts product categories at the root of the site.
	 *
	 * The original had /keychains and /pins, not /category/keychains, and losing
	 * that would mean a redirect for every category URL a search engine already
	 * knows. WordPress cannot express it through a rewrite slug, so one explicit
	 * rule per category is registered instead.
	 *
	 * One rule per term rather than a greedy /([^/]+)/ catch-all: a catch-all at
	 * the root would swallow every page URL on the site and hand it to the
	 * taxonomy, which 404s everything that is not a category.
	 *
	 * Runs only when no category base is set; give one on the settings screen
	 * and WordPress's own rules take over.
	 *
	 * @return void
	 */
	public function category_rewrites() {
		if ( eg_option( 'category_base', '' ) ) {
			return;
		}

		foreach ( $this->category_slugs() as $slug ) {
			// No leading caret: WordPress matches these with "#^$match#" and
			// would otherwise compile ^^slug, which matches nothing.
			add_rewrite_rule(
				preg_quote( $slug, '/' ) . '/?$',
				'index.php?' . self::CATEGORY . '=' . $slug,
				'top'
			);
			add_rewrite_rule(
				preg_quote( $slug, '/' ) . '/page/([0-9]{1,})/?$',
				'index.php?' . self::CATEGORY . '=' . $slug . '&paged=$matches[1]',
				'top'
			);
		}
	}

	/**
	 * Points category links at the root-level URL.
	 *
	 * The taxonomy is registered with a placeholder base because WordPress will
	 * not take an empty one, so without this every link on the site would point
	 * at /collection/keychains while only /keychains resolved.
	 *
	 * @param string   $link     Generated URL.
	 * @param \WP_Term $term     Term.
	 * @param string   $taxonomy Taxonomy name.
	 * @return string
	 */
	public function category_link( $link, $term, $taxonomy ) {
		if ( self::CATEGORY !== $taxonomy || eg_option( 'category_base', '' ) ) {
			return $link;
		}

		if ( ! in_array( $term->slug, $this->category_slugs(), true ) ) {
			return $link;
		}

		return home_url( user_trailingslashit( $term->slug, 'category' ) );
	}

	/**
	 * The category slugs, cached.
	 *
	 * Read on every `init` to build the rules above, so it must not be a term
	 * query — that would be one query on every request for a list that changes
	 * about twice a year.
	 *
	 * @return array
	 */
	private function category_slugs() {
		$slugs = get_option( 'eg_category_slugs', false );

		if ( false === $slugs ) {
			$slugs = $this->refresh_category_slugs();
		}

		return is_array( $slugs ) ? $slugs : array();
	}

	/**
	 * Rebuilds the cached slug list and the rewrite rules that depend on it.
	 *
	 * @return array The refreshed slugs.
	 */
	public function refresh_category_slugs() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::CATEGORY,
				'hide_empty' => false,
				'fields'     => 'slugs',
			)
		);

		$slugs = ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();

		// Reserved words that would collide with a core route or with one of
		// the theme's own pages. A category called "shop" must not take the
		// product archive's URL.
		$reserved = array( 'shop', 'checkout', 'cart', 'page', 'feed', 'wp-admin', 'wp-json', 'product' );
		$slugs    = array_values( array_diff( $slugs, $reserved ) );

		$previous = get_option( 'eg_category_slugs', array() );
		update_option( 'eg_category_slugs', $slugs, false );

		// Only flush when the set actually changed — flushing is expensive and
		// this hook fires on every term save, including ones that touch nothing
		// the rules depend on.
		if ( $previous !== $slugs ) {
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}

		return $slugs;
	}

	/**
	 * Adds price and stock columns to the product list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function product_columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['eg_price'] = __( 'Price', 'especial-gallery' );
				$out['eg_stock'] = __( 'Stock', 'especial-gallery' );
			}
		}
		return $out;
	}

	/**
	 * Renders a product list-table column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Product ID.
	 * @return void
	 */
	public function product_column( $column, $post_id ) {
		if ( 'eg_price' === $column ) {
			echo esc_html( eg_format_price( (int) get_post_meta( $post_id, '_eg_price_cents', true ) ) );
			return;
		}

		if ( 'eg_stock' === $column ) {
			$product = Catalog::product( $post_id );
			echo $product && $product['sold_out']
				? esc_html__( 'Sold out', 'especial-gallery' )
				: esc_html__( 'In stock', 'especial-gallery' );
		}
	}

	/**
	 * Adds total and customer columns to the order list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function order_columns( $columns ) {
		return array(
			'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'       => __( 'Reference', 'especial-gallery' ),
			'eg_customer' => __( 'Customer', 'especial-gallery' ),
			'eg_total'    => __( 'Total', 'especial-gallery' ),
			'eg_status'   => __( 'Status', 'especial-gallery' ),
			'date'        => __( 'Placed', 'especial-gallery' ),
		);
	}

	/**
	 * Renders an order list-table column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Order ID.
	 * @return void
	 */
	public function order_column( $column, $post_id ) {
		switch ( $column ) {
			case 'eg_customer':
				echo esc_html( (string) get_post_meta( $post_id, '_eg_email', true ) );
				break;
			case 'eg_total':
				echo esc_html( eg_format_price( (int) get_post_meta( $post_id, '_eg_total_cents', true ) ) );
				break;
			case 'eg_status':
				$status = (string) get_post_meta( $post_id, '_eg_status', true );
				echo esc_html( $status ? $status : __( 'recorded', 'especial-gallery' ) );
				break;
		}
	}
}
