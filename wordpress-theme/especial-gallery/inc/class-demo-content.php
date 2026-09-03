<?php
/**
 * One-click demo content.
 *
 * A storefront theme that activates into an empty site is not a storefront; the
 * owner sees a blank page and has no way to tell whether anything works. This
 * imports the nine products the design was built around, their categories, the
 * pages the bag and gate depend on, and the menus — so activation produces a
 * shop that can be clicked through end to end and then edited into a real one.
 *
 * It is idempotent: every object is matched on a marker before it is created,
 * so running it twice does not produce eighteen products.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Imports products, categories, pages, menus and settings.
 */
class Demo_Content {

	/**
	 * Runs the import.
	 *
	 * @return array {
	 *     @type int   $products Products created.
	 *     @type int   $images   Images added to the library.
	 *     @type array $notices  Human-readable messages.
	 * }
	 */
	public static function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'products' => 0,
				'images'   => 0,
				'notices'  => array( __( 'You do not have permission to import content.', 'especial-gallery' ) ),
			);
		}

		// Media sideloading lives in admin includes, which are not always loaded
		// on the request that runs this.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$notices = array();

		$pages = self::pages();
		$notices[] = sprintf(
			/* translators: %d: number of pages. */
			_n( '%d page created.', '%d pages created.', count( $pages ), 'especial-gallery' ),
			count( $pages )
		);

		$result = self::products();
		$notices[] = sprintf(
			/* translators: 1: products, 2: images. */
			__( '%1$d products imported with %2$d images.', 'especial-gallery' ),
			$result['products'],
			$result['images']
		);

		self::category_images();
		self::hero();
		self::menus();
		self::settings();

		flush_rewrite_rules();

		$notices[] = __( 'Menus, hero artwork and shop settings configured.', 'especial-gallery' );

		update_option( 'eg_demo_imported', current_time( 'mysql' ) );

		return array(
			'products' => $result['products'],
			'images'   => $result['images'],
			'notices'  => $notices,
		);
	}

	/**
	 * Whether the demo has already been run.
	 *
	 * @return bool
	 */
	public static function imported() {
		return (bool) get_option( 'eg_demo_imported', false );
	}

	/**
	 * Creates the pages the theme depends on and records their IDs.
	 *
	 * The gate, checkout and confirmation pages are found by stored ID rather
	 * than by slug, so renaming one in the admin later cannot break the bag.
	 *
	 * @return array Slugs of the pages created on this run.
	 */
	private static function pages() {
		$definitions = array(
			'gate'      => array(
				'title'    => __( 'Mailing list', 'especial-gallery' ),
				'template' => 'templates/template-gate.php',
				'content'  => '',
			),
			'checkout'  => array(
				'title'    => __( 'Checkout', 'especial-gallery' ),
				'template' => 'templates/template-checkout.php',
				'content'  => '',
			),
			'confirmed' => array(
				'title'    => __( 'Order confirmed', 'especial-gallery' ),
				'template' => 'templates/template-confirmed.php',
				'content'  => '',
			),
			'editorial' => array(
				'title'    => __( 'About the studio', 'especial-gallery' ),
				'template' => '',
				'content'  => self::editorial_copy(),
			),
			'contact'   => array(
				'title'    => __( 'Contact', 'especial-gallery' ),
				'template' => '',
				'content'  => self::legal_copy( 'contact' ),
			),
			'returns'   => array(
				'title'    => __( 'Returns', 'especial-gallery' ),
				'template' => '',
				'content'  => self::legal_copy( 'returns' ),
			),
			'terms'     => array(
				'title'    => __( 'Terms', 'especial-gallery' ),
				'template' => '',
				'content'  => self::legal_copy( 'terms' ),
			),
			'privacy'   => array(
				'title'    => __( 'Privacy', 'especial-gallery' ),
				'template' => '',
				'content'  => self::legal_copy( 'privacy' ),
			),
		);

		$stored  = get_option( 'eg_pages', array() );
		$stored  = is_array( $stored ) ? $stored : array();
		$created = array();

		foreach ( $definitions as $key => $definition ) {
			// Already recorded and still published: leave it alone.
			if ( ! empty( $stored[ $key ] ) && 'publish' === get_post_status( (int) $stored[ $key ] ) ) {
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $definition['title'],
					'post_content' => $definition['content'],
				)
			);

			if ( is_wp_error( $page_id ) || ! $page_id ) {
				continue;
			}

			if ( $definition['template'] ) {
				update_post_meta( $page_id, '_wp_page_template', $definition['template'] );
			}

			update_post_meta( $page_id, '_eg_demo', 1 );

			$stored[ $key ] = $page_id;
			$created[]      = $key;
		}

		update_option( 'eg_pages', $stored );

		if ( ! empty( $stored['privacy'] ) ) {
			update_option( 'wp_page_for_privacy_policy', (int) $stored['privacy'] );
		}

		if ( ! empty( $stored['editorial'] ) ) {
			set_theme_mod( 'eg_editorial_page', (int) $stored['editorial'] );
		}

		return $created;
	}

	/**
	 * Imports the catalogue from demo/products.json.
	 *
	 * @return array Counts of products and images created.
	 */
	private static function products() {
		$file = EG_DIR . '/demo/products.json';

		if ( ! file_exists( $file ) ) {
			return array(
				'products' => 0,
				'images'   => 0,
			);
		}

		// Theme-authored file shipped inside the theme, not remote content.
		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$rows = json_decode( (string) $json, true );

		if ( ! is_array( $rows ) ) {
			return array(
				'products' => 0,
				'images'   => 0,
			);
		}

		$products = 0;
		$images   = 0;

		foreach ( $rows as $row ) {
			$existing = get_page_by_path( $row['slug'], OBJECT, Post_Types::PRODUCT );
			if ( $existing ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => Post_Types::PRODUCT,
					'post_status'  => 'publish',
					'post_title'   => $row['title'],
					'post_name'    => $row['slug'],
					'post_excerpt' => $row['description'],
					'post_content' => wpautop( $row['description'] ),
					// Release order drives the grid, so the post date carries it.
					'post_date'    => $row['released_at'] . ' 12:00:00',
				)
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			$products++;

			update_post_meta( $post_id, '_eg_price_cents', absint( $row['price_cents'] ) );
			update_post_meta( $post_id, '_eg_compare_at_cents', absint( $row['compare_at_cents'] ) );
			update_post_meta( $post_id, '_eg_details', implode( "\n", (array) $row['details'] ) );
			update_post_meta( $post_id, '_eg_featured', ! empty( $row['featured'] ) ? 1 : 0 );
			update_post_meta( $post_id, '_eg_released_at', $row['released_at'] );
			update_post_meta( $post_id, '_eg_variants', '' );
			update_post_meta( $post_id, '_eg_demo', 1 );

			$term = self::term( $row['category'] );
			if ( $term ) {
				wp_set_object_terms( $post_id, array( (int) $term ), Post_Types::CATEGORY );
			}

			$attachment_ids = array();
			foreach ( (array) $row['images'] as $image ) {
				$id = self::attachment( $image['file'], $image['alt'] );
				if ( $id ) {
					$attachment_ids[] = $id;
					$images++;
				}
			}

			if ( $attachment_ids ) {
				set_post_thumbnail( $post_id, $attachment_ids[0] );
				$extra = array_slice( $attachment_ids, 1 );
				if ( $extra ) {
					update_post_meta( $post_id, '_eg_gallery', implode( ',', $extra ) );
				}
			}
		}

		return array(
			'products' => $products,
			'images'   => $images,
		);
	}

	/**
	 * Finds or creates a product category.
	 *
	 * @param string $slug Category slug.
	 * @return int|null Term ID.
	 */
	private static function term( $slug ) {
		$titles = array(
			'keychains' => __( 'Keychains', 'especial-gallery' ),
			'pins'      => __( 'Pins', 'especial-gallery' ),
		);

		$existing = get_term_by( 'slug', $slug, Post_Types::CATEGORY );
		if ( $existing ) {
			return (int) $existing->term_id;
		}

		$created = wp_insert_term(
			isset( $titles[ $slug ] ) ? $titles[ $slug ] : ucfirst( $slug ),
			Post_Types::CATEGORY,
			array( 'slug' => $slug )
		);

		return is_wp_error( $created ) ? null : (int) $created['term_id'];
	}

	/**
	 * Copies a bundled image into the media library.
	 *
	 * Matched on a meta marker rather than on filename, because WordPress
	 * renames a collision to foo-1.jpg and a second import would then add a
	 * third copy rather than finding the first.
	 *
	 * @param string $filename File under assets/images.
	 * @param string $alt      Alt text.
	 * @return int|null Attachment ID.
	 */
	private static function attachment( $filename, $alt ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_eg_demo_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $filename, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( $existing ) {
			return (int) $existing[0];
		}

		$source = EG_DIR . '/assets/images/' . $filename;
		if ( ! file_exists( $source ) ) {
			return null;
		}

		$upload = wp_upload_bits( $filename, null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! empty( $upload['error'] ) ) {
			return null;
		}

		$filetype = wp_check_filetype( $upload['file'], null );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return null;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
		);

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $attachment_id, '_eg_demo_file', $filename );
		update_post_meta( $attachment_id, '_eg_demo', 1 );

		return (int) $attachment_id;
	}

	/**
	 * Assigns the bundled category artwork.
	 *
	 * @return void
	 */
	private static function category_images() {
		$map = array(
			'keychains' => 'category-keychains.jpg',
			'pins'      => 'category-pins.jpg',
		);

		foreach ( $map as $slug => $file ) {
			$term = get_term_by( 'slug', $slug, Post_Types::CATEGORY );
			if ( ! $term || get_term_meta( $term->term_id, '_eg_image_id', true ) ) {
				continue;
			}

			$id = self::attachment( $file, '' );
			if ( $id ) {
				update_term_meta( $term->term_id, '_eg_image_id', $id );
			}
		}
	}

	/**
	 * Imports the hero artwork and its copy.
	 *
	 * @return void
	 */
	private static function hero() {
		if ( ! get_theme_mod( 'eg_hero_image' ) ) {
			$wide = self::attachment( 'hero.jpg', '' );
			if ( $wide ) {
				set_theme_mod( 'eg_hero_image', $wide );
			}

			$tall = self::attachment( 'hero-mobile.jpg', '' );
			if ( $tall ) {
				set_theme_mod( 'eg_hero_image_mobile', $tall );
			}
		}

		if ( ! get_theme_mod( 'eg_hero_title' ) ) {
			set_theme_mod( 'eg_hero_title', __( 'Skeleton Keychain', 'especial-gallery' ) );
		}

		if ( ! get_theme_mod( 'eg_hero_facts' ) ) {
			set_theme_mod(
				'eg_hero_facts',
				implode(
					"\n",
					array(
						__( 'Soft PVC', 'especial-gallery' ),
						__( 'Nickel hardware', 'especial-gallery' ),
						__( 'Sealed polybag', 'especial-gallery' ),
					)
				)
			);
		}

		$hero_product = get_page_by_path( 'skeleton-keychain-green', OBJECT, Post_Types::PRODUCT );
		if ( $hero_product && ! get_theme_mod( 'eg_hero_url' ) ) {
			set_theme_mod( 'eg_hero_url', get_permalink( $hero_product ) );
		}
	}

	/**
	 * Builds the header and footer menus.
	 *
	 * @return void
	 */
	private static function menus() {
		$pages     = get_option( 'eg_pages', array() );
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations = is_array( $locations ) ? $locations : array();

		$menus = array(
			'primary' => array(
				'name'  => __( 'Primary', 'especial-gallery' ),
				'items' => self::category_items(),
			),
			'shop'    => array(
				'name'  => __( 'Footer shop', 'especial-gallery' ),
				'items' => self::category_items(),
			),
			'help'    => array(
				'name'  => __( 'Footer help', 'especial-gallery' ),
				'items' => array(
					array( 'page', isset( $pages['contact'] ) ? $pages['contact'] : 0, __( 'Contact', 'especial-gallery' ) ),
					array( 'page', isset( $pages['returns'] ) ? $pages['returns'] : 0, __( 'Returns', 'especial-gallery' ) ),
				),
			),
			'legal'   => array(
				'name'  => __( 'Footer legal', 'especial-gallery' ),
				'items' => array(
					array( 'page', isset( $pages['terms'] ) ? $pages['terms'] : 0, __( 'Terms', 'especial-gallery' ) ),
					array( 'page', isset( $pages['privacy'] ) ? $pages['privacy'] : 0, __( 'Privacy', 'especial-gallery' ) ),
				),
			),
			'gate'    => array(
				'name'  => __( 'Gate links', 'especial-gallery' ),
				'items' => array(
					array( 'page', isset( $pages['terms'] ) ? $pages['terms'] : 0, __( 'Terms', 'especial-gallery' ) ),
					array( 'page', isset( $pages['contact'] ) ? $pages['contact'] : 0, __( 'Contact', 'especial-gallery' ) ),
					array( 'page', isset( $pages['returns'] ) ? $pages['returns'] : 0, __( 'Returns', 'especial-gallery' ) ),
				),
			),
		);

		foreach ( $menus as $location => $menu ) {
			if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
				continue;
			}

			$menu_id = wp_create_nav_menu( $menu['name'] );
			if ( is_wp_error( $menu_id ) ) {
				// A menu of that name already exists — reuse it rather than
				// failing, so a re-run repairs the assignment.
				$existing = wp_get_nav_menu_object( $menu['name'] );
				if ( ! $existing ) {
					continue;
				}
				$menu_id = $existing->term_id;
			}

			foreach ( $menu['items'] as $item ) {
				list( $type, $object_id, $title ) = $item;

				if ( ! $object_id ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $title,
						'menu-item-object'    => 'page' === $type ? 'page' : Post_Types::CATEGORY,
						'menu-item-object-id' => (int) $object_id,
						'menu-item-type'      => 'page' === $type ? 'post_type' : 'taxonomy',
						'menu-item-status'    => 'publish',
					)
				);
			}

			$locations[ $location ] = $menu_id;
		}

		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * The category menu items, as taxonomy references.
	 *
	 * @return array
	 */
	private static function category_items() {
		$items = array();

		foreach ( Catalog::categories() as $term ) {
			$items[] = array( 'taxonomy', $term->term_id, $term->name );
		}

		return $items;
	}

	/**
	 * Sets the reading and shop settings a storefront needs.
	 *
	 * @return void
	 */
	private static function settings() {
		// Pretty permalinks, without which product URLs fall back to query
		// strings and the whole rewrite layer looks broken.
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}

		if ( ! eg_option( 'shipping_cents' ) ) {
			eg_update_option( 'shipping_cents', 600 );
			eg_update_option( 'free_shipping_over_cents', 7500 );
			eg_update_option( 'currency', 'USD' );
			eg_update_option( 'esp_provider', 'none' );
		}

		if ( ! get_theme_mod( 'eg_brand' ) ) {
			set_theme_mod( 'eg_brand', get_bloginfo( 'name' ) );
		}
	}

	/**
	 * The editorial block's copy.
	 *
	 * This is where organic traffic lands, so it is real prose rather than
	 * lorem ipsum: it is the part of the page a search engine reads.
	 *
	 * @return string
	 */
	private static function editorial_copy() {
		$paragraphs = array(
			'<h2>' . esc_html__( 'Small runs, made properly', 'especial-gallery' ) . '</h2>',
			'<p>' . esc_html__( 'We produce collectible objects in short runs. Every release is made to a fixed quantity, sold direct, and not reprinted. When a run is gone it stays gone, and the next one is a different object rather than the same one again in another colour.', 'especial-gallery' ) . '</p>',
			'<h3>' . esc_html__( 'How the drops work', 'especial-gallery' ) . '</h3>',
			'<p>' . esc_html__( 'Releases are announced to the mailing list first and go live on the site at the same time for everyone. Items marked sold out are not restocked; if a run returns it is because the piece has been remade, and it is listed as a new release with its own run size.', 'especial-gallery' ) . '</p>',
			'<h3>' . esc_html__( 'Materials and making', 'especial-gallery' ) . '</h3>',
			'<p>' . esc_html__( 'Keychains are moulded in soft PVC from hand-built masters, printed on the face and left flat black on the reverse, then finished and assembled in small batches with nickel-plated hardware. Pins are struck in metal and filled with hard enamel, polished flat, and mounted on printed backing card. Each product page lists the material and the construction, and nothing else — the object should make its own argument.', 'especial-gallery' ) . '</p>',
			'<h3>' . esc_html__( 'Shipping and returns', 'especial-gallery' ) . '</h3>',
			'<p>' . esc_html__( 'Orders ship within two working days, tracked, from our own studio. Unopened items can be returned within thirty days of delivery for a full refund; opened collectibles can be returned if they arrived damaged.', 'especial-gallery' ) . '</p>',
		);

		return implode( "\n\n", $paragraphs );
	}

	/**
	 * Starter copy for a legal page.
	 *
	 * Deliberately short and marked as a starting point. A theme cannot write
	 * a shop's terms for it, and pretending otherwise would be worse than an
	 * empty page.
	 *
	 * @param string $which One of contact, returns, terms, privacy.
	 * @return string
	 */
	private static function legal_copy( $which ) {
		$copy = array(
			'contact' => array(
				__( 'Write to us and we will answer within two working days.', 'especial-gallery' ),
				__( 'For an order query, include the reference from your confirmation email. For a damaged delivery, attach photographs of the item and the outer packaging.', 'especial-gallery' ),
				__( 'Press and stockist enquiries go to the same address.', 'especial-gallery' ),
			),
			'returns' => array(
				__( 'Unopened items can be returned within thirty days of delivery for a full refund.', 'especial-gallery' ),
				__( 'Opened collectibles can be returned if they arrived damaged. Send photographs of the item and its packaging and we will arrange a replacement or a refund.', 'especial-gallery' ),
				__( 'Return postage is ours when the item was faulty or wrongly sent, and yours otherwise.', 'especial-gallery' ),
			),
			'terms'   => array(
				__( 'Ordering forms a contract on despatch, not on order confirmation. If an item turns out to be unavailable after you order, the order is cancelled and refunded in full.', 'especial-gallery' ),
				__( 'Products are sold in limited runs. Edition sizes stated on a product page are final and the run is not reprinted.', 'especial-gallery' ),
				__( 'Prices exclude shipping and any import duty, which is the responsibility of the recipient.', 'especial-gallery' ),
				__( 'Replace this page with terms written for your own shop before you take a real order.', 'especial-gallery' ),
			),
			'privacy' => array(
				__( 'We collect an email address when you join the mailing list, and a name and delivery address when you place an order. We use them to send you what you asked for and nothing else.', 'especial-gallery' ),
				__( 'Payment is taken on the payment provider’s own page. Card details never reach this site.', 'especial-gallery' ),
				__( 'You can ask us to remove your details at any time by writing to the contact address.', 'especial-gallery' ),
				__( 'Replace this page with a policy written for your own shop and jurisdiction before you launch.', 'especial-gallery' ),
			),
		);

		$lines = isset( $copy[ $which ] ) ? $copy[ $which ] : array();
		$html  = '';

		foreach ( $lines as $line ) {
			$html .= '<p>' . esc_html( $line ) . "</p>\n\n";
		}

		return trim( $html );
	}
}
