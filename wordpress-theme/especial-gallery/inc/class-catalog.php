<?php
/**
 * The catalogue — the only surface the rest of the theme reads.
 *
 * This is the direct descendant of the source project's data/products.ts: a set
 * of static accessors over the product data, with every template reading through
 * them and none reading post meta directly. Moving the catalogue to WooCommerce,
 * or to a remote API, means rewriting this one class.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Product queries and normalisation.
 */
class Catalog {

	/**
	 * Normalises a product post into the array shape every template expects.
	 *
	 * Mirrors the `Product` type from lib/types.ts. Returning one predictable
	 * array rather than a WP_Post plus a handful of get_post_meta() calls is
	 * what keeps the N+1 problem out of the templates.
	 *
	 * @param int|\WP_Post $post Product post or ID.
	 * @return array|null Null when the post is not a published product.
	 */
	public static function product( $post ) {
		$post = get_post( $post );

		if ( ! $post || Post_Types::PRODUCT !== $post->post_type ) {
			return null;
		}

		$variants = self::variants( $post->ID );
		$images   = self::images( $post->ID );

		$price_cents   = (int) get_post_meta( $post->ID, '_eg_price_cents', true );
		$compare_cents = (int) get_post_meta( $post->ID, '_eg_compare_at_cents', true );

		// A compare-at price below the asking price is not a discount; ignoring
		// it is kinder than rendering a strikethrough that reads as a price rise.
		if ( $compare_cents <= $price_cents ) {
			$compare_cents = 0;
		}

		$terms    = get_the_terms( $post->ID, Post_Types::CATEGORY );
		$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;

		return array(
			'id'             => $post->ID,
			'slug'           => $post->post_name,
			'title'          => get_the_title( $post ),
			'permalink'      => get_permalink( $post ),
			'price_cents'    => $price_cents,
			'compare_cents'  => $compare_cents,
			'currency'       => eg_option( 'currency', 'USD' ),
			'images'         => $images,
			'variants'       => $variants,
			'description'    => self::description( $post ),
			'details'        => self::details( $post->ID ),
			'featured'       => (bool) get_post_meta( $post->ID, '_eg_featured', true ),
			'sold_out'       => self::is_sold_out( $post->ID, $variants ),
			'category'       => $category ? $category->name : '',
			'category_slug'  => $category ? $category->slug : '',
			'category_link'  => $category ? get_term_link( $category ) : '',
			'released_at'    => get_post_meta( $post->ID, '_eg_released_at', true ),
		);
	}

	/**
	 * The product's short description.
	 *
	 * The excerpt is the intended field — "what it is, what it's made of", no
	 * storytelling. It falls back to the body so a product entered without one
	 * is not silently blank on the tile.
	 *
	 * @param \WP_Post $post Product.
	 * @return string
	 */
	private static function description( $post ) {
		if ( $post->post_excerpt ) {
			return $post->post_excerpt;
		}
		return wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '…' );
	}

	/**
	 * The product's images.
	 *
	 * Index 0 is the resting state and index 1 is the hover crossfade. A
	 * single-image product simply never crossfades — which is deliberate: when
	 * every product had one photograph, fading out on hover blanked the tile.
	 *
	 * @param int $post_id Product ID.
	 * @return array List of image arrays with id, url, srcset, sizes, alt, w, h.
	 */
	public static function images( $post_id ) {
		$ids = array();

		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$ids[] = (int) $thumb;
		}

		$gallery = get_post_meta( $post_id, '_eg_gallery', true );
		if ( $gallery ) {
			foreach ( explode( ',', (string) $gallery ) as $id ) {
				$id = absint( $id );
				if ( $id && ! in_array( $id, $ids, true ) ) {
					$ids[] = $id;
				}
			}
		}

		$images = array();
		foreach ( $ids as $id ) {
			$src = wp_get_attachment_image_src( $id, 'eg-tile' );
			if ( ! $src ) {
				continue;
			}
			$images[] = array(
				'id'     => $id,
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
				'srcset' => (string) wp_get_attachment_image_srcset( $id, 'eg-tile' ),
				'sizes'  => (string) wp_get_attachment_image_sizes( $id, 'eg-tile' ),
				'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * The product's variants.
	 *
	 * Stored as one newline-delimited meta value — `id|Label|1` per line —
	 * rather than as a repeater of separate meta rows. A product has two or
	 * three of these; a row each would triple the meta table for nothing.
	 *
	 * A product with no variants declared gets a single implicit "Standard"
	 * one, so the add-to-bag path has something to reference and every product
	 * behaves the same way downstream.
	 *
	 * @param int $post_id Product ID.
	 * @return array List of variant arrays.
	 */
	public static function variants( $post_id ) {
		$raw   = (string) get_post_meta( $post_id, '_eg_variants', true );
		$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );

		$variants = array();
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( '' === $parts[0] ) {
				continue;
			}
			$variants[] = array(
				'id'        => sanitize_key( $parts[0] ),
				'label'     => isset( $parts[1] ) && '' !== $parts[1] ? $parts[1] : $parts[0],
				'available' => ! isset( $parts[2] ) || '0' !== $parts[2],
			);
		}

		if ( ! $variants ) {
			$sold_out = (bool) get_post_meta( $post_id, '_eg_sold_out', true );
			$variants = array(
				array(
					'id'        => 'standard',
					'label'     => __( 'Standard', 'especial-gallery' ),
					'available' => ! $sold_out,
				),
			);
		}

		return $variants;
	}

	/**
	 * Whether every variant is unavailable.
	 *
	 * @param int   $post_id  Product ID.
	 * @param array $variants Pre-resolved variants, to avoid a second lookup.
	 * @return bool
	 */
	public static function is_sold_out( $post_id, $variants = null ) {
		if ( get_post_meta( $post_id, '_eg_sold_out', true ) ) {
			return true;
		}

		$variants = null === $variants ? self::variants( $post_id ) : $variants;

		foreach ( $variants as $variant ) {
			if ( $variant['available'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The bulleted specification list.
	 *
	 * @param int $post_id Product ID.
	 * @return array List of strings.
	 */
	public static function details( $post_id ) {
		$raw = (string) get_post_meta( $post_id, '_eg_details', true );
		return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
	}

	/**
	 * Finds a single variant on a product.
	 *
	 * @param int    $post_id    Product ID.
	 * @param string $variant_id Variant key.
	 * @return array|null
	 */
	public static function variant( $post_id, $variant_id ) {
		foreach ( self::variants( $post_id ) as $variant ) {
			if ( $variant['id'] === $variant_id ) {
				return $variant;
			}
		}
		return null;
	}

	/**
	 * Resolves a product by slug.
	 *
	 * @param string $slug Post slug.
	 * @return array|null
	 */
	public static function by_slug( $slug ) {
		$post = get_page_by_path( sanitize_title( $slug ), OBJECT, Post_Types::PRODUCT );
		return $post ? self::product( $post ) : null;
	}

	/**
	 * Runs a product query and returns normalised products.
	 *
	 * `update_post_term_cache` stays on because every tile prints its category;
	 * turning it off would trade one query for one per product. The meta cache
	 * is primed for the same reason.
	 *
	 * @param array $args WP_Query arguments to merge over the defaults.
	 * @return array List of normalised products.
	 */
	public static function query( array $args = array() ) {
		$defaults = array(
			'post_type'              => Post_Types::PRODUCT,
			'post_status'            => 'publish',
			'posts_per_page'         => 12,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
		);

		$query = new \WP_Query( wp_parse_args( $args, $defaults ) );

		$products = array();
		foreach ( $query->posts as $post ) {
			$product = self::product( $post );
			if ( $product ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Featured products, newest first.
	 *
	 * @param int $limit Maximum returned.
	 * @return array
	 */
	public static function featured( $limit = 4 ) {
		$featured = self::query(
			array(
				'posts_per_page' => $limit,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_eg_featured',
						'value' => '1',
					),
				),
			)
		);

		// A shop that has not marked anything featured should still show a
		// release row rather than an empty section.
		if ( ! $featured ) {
			$featured = self::newest( $limit );
		}

		return $featured;
	}

	/**
	 * Newest products.
	 *
	 * @param int $limit Maximum returned.
	 * @return array
	 */
	public static function newest( $limit = 8 ) {
		return self::query( array( 'posts_per_page' => $limit ) );
	}

	/**
	 * Products in a category.
	 *
	 * @param string $slug  Category slug.
	 * @param int    $limit Maximum returned.
	 * @return array
	 */
	public static function by_category( $slug, $limit = 24 ) {
		return self::query(
			array(
				'posts_per_page' => $limit,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => Post_Types::CATEGORY,
						'field'    => 'slug',
						'terms'    => sanitize_title( $slug ),
					),
				),
			)
		);
	}

	/**
	 * Related products: same category first, then newest, never the product
	 * itself.
	 *
	 * @param int $post_id Current product.
	 * @param int $limit   Maximum returned.
	 * @return array
	 */
	public static function related( $post_id, $limit = 4 ) {
		$terms = wp_get_post_terms( $post_id, Post_Types::CATEGORY, array( 'fields' => 'ids' ) );

		$same = array();
		if ( $terms && ! is_wp_error( $terms ) ) {
			$same = self::query(
				array(
					'posts_per_page' => $limit,
					'post__not_in'   => array( $post_id ),
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => Post_Types::CATEGORY,
							'field'    => 'term_id',
							'terms'    => $terms,
						),
					),
				)
			);
		}

		if ( count( $same ) >= $limit ) {
			return array_slice( $same, 0, $limit );
		}

		$seen = wp_list_pluck( $same, 'id' );
		$rest = self::query(
			array(
				'posts_per_page' => $limit,
				'post__not_in'   => array_merge( array( $post_id ), $seen ),
			)
		);

		return array_slice( array_merge( $same, $rest ), 0, $limit );
	}

	/**
	 * All product categories that actually hold something, with counts.
	 *
	 * @return array List of term objects.
	 */
	public static function categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => Post_Types::CATEGORY,
				'hide_empty' => true,
			)
		);

		return ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
	}

	/**
	 * The image used as a category cell's artwork: the category's own thumbnail
	 * when one is set, otherwise the newest product in it.
	 *
	 * @param \WP_Term $term Category.
	 * @return int Attachment ID, or 0.
	 */
	public static function category_image( $term ) {
		$id = absint( get_term_meta( $term->term_id, '_eg_image_id', true ) );
		if ( $id ) {
			return $id;
		}

		$products = self::by_category( $term->slug, 1 );
		if ( $products && ! empty( $products[0]['images'][0]['id'] ) ) {
			return (int) $products[0]['images'][0]['id'];
		}

		return 0;
	}
}
