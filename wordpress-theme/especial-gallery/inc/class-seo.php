<?php
/**
 * SEO parity.
 *
 * The Next build emitted its metadata through the App Router's `metadata`
 * export and a hand-written JSON-LD block on the product page. This reproduces
 * both, and stands aside entirely when a dedicated SEO plugin is active — two
 * sets of canonical and Open Graph tags on one page is worse than none.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Meta tags, Open Graph, Twitter cards and structured data.
 */
class Seo {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'meta' ), 1 );
		add_action( 'wp_head', array( $this, 'schema' ), 5 );
		add_filter( 'document_title_parts', array( $this, 'title_parts' ) );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	/**
	 * Whether an SEO plugin has already taken responsibility for this.
	 *
	 * @return bool
	 */
	private function plugin_active() {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( 'The_SEO_Framework\\Load' );
	}

	/**
	 * Description, canonical, Open Graph and Twitter tags.
	 *
	 * @return void
	 */
	public function meta() {
		if ( $this->plugin_active() ) {
			return;
		}

		$description = $this->description();
		$canonical   = $this->canonical();
		$image       = $this->image();
		$type        = is_singular( Post_Types::PRODUCT ) ? 'product' : ( is_singular( 'post' ) ? 'article' : 'website' );

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}

		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( eg_brand() ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $this->title() ) );
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );

		if ( $description ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		}

		if ( $canonical ) {
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
		}

		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		} else {
			echo '<meta name="twitter:card" content="summary">' . "\n";
		}

		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $this->title() ) );

		if ( $description ) {
			printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
		}
	}

	/**
	 * The page title without the site suffix.
	 *
	 * @return string
	 */
	private function title() {
		if ( is_front_page() ) {
			return eg_brand() . ' — ' . get_theme_mod( 'eg_tagline', get_bloginfo( 'description', 'display' ) );
		}
		if ( is_singular() ) {
			return get_the_title();
		}
		if ( is_tax() || is_category() || is_tag() ) {
			return wp_strip_all_tags( get_the_archive_title() );
		}
		if ( is_search() ) {
			/* translators: %s: search term. */
			return sprintf( __( 'Search results for “%s”', 'especial-gallery' ), get_search_query() );
		}
		return get_bloginfo( 'name', 'display' );
	}

	/**
	 * The meta description for the current view.
	 *
	 * @return string
	 */
	private function description() {
		if ( is_front_page() ) {
			$description = get_bloginfo( 'description', 'display' );
			return $description ? $description : get_theme_mod( 'eg_tagline', '' );
		}

		if ( is_singular() ) {
			$post = get_post();
			if ( $post && $post->post_excerpt ) {
				return wp_strip_all_tags( $post->post_excerpt );
			}
			return $post ? wp_trim_words( wp_strip_all_tags( wp_strip_all_tags( $post->post_content ) ), 30, '' ) : '';
		}

		if ( is_tax() || is_category() || is_tag() ) {
			return wp_strip_all_tags( (string) term_description() );
		}

		return '';
	}

	/**
	 * The canonical URL.
	 *
	 * @return string
	 */
	private function canonical() {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( is_post_type_archive() ) {
			return (string) get_post_type_archive_link( get_query_var( 'post_type' ) );
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );
				return is_wp_error( $link ) ? '' : $link;
			}
		}
		return '';
	}

	/**
	 * The share image.
	 *
	 * @return string
	 */
	private function image() {
		if ( is_singular() && has_post_thumbnail() ) {
			return (string) get_the_post_thumbnail_url( null, 'large' );
		}

		$hero = absint( get_theme_mod( 'eg_hero_image', 0 ) );
		if ( $hero ) {
			return (string) wp_get_attachment_image_url( $hero, 'large' );
		}

		$logo = absint( get_theme_mod( 'custom_logo', 0 ) );
		return $logo ? (string) wp_get_attachment_image_url( $logo, 'large' ) : '';
	}

	/**
	 * Structured data.
	 *
	 * This is what makes a product page eligible for a rich result, and the
	 * organisation block is what associates the site with the brand.
	 *
	 * @return void
	 */
	public function schema() {
		if ( $this->plugin_active() ) {
			return;
		}

		$graph = array();

		if ( is_front_page() ) {
			$logo = absint( get_theme_mod( 'custom_logo', 0 ) );

			$graph[] = array(
				'@context' => 'https://schema.org',
				'@type'    => 'Organization',
				'name'     => eg_brand(),
				'url'      => home_url( '/' ),
				'logo'     => $logo ? wp_get_attachment_image_url( $logo, 'full' ) : '',
			);
		}

		if ( is_singular( Post_Types::PRODUCT ) ) {
			$product = Catalog::product( get_the_ID() );

			if ( $product ) {
				$graph[] = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Product',
					'name'        => $product['title'],
					'description' => wp_strip_all_tags( $product['description'] ),
					'image'       => wp_list_pluck( $product['images'], 'url' ),
					'brand'       => array(
						'@type' => 'Brand',
						'name'  => eg_brand(),
					),
					'offers'      => array(
						'@type'         => 'Offer',
						'price'         => number_format( $product['price_cents'] / 100, 2, '.', '' ),
						'priceCurrency' => $product['currency'],
						'availability'  => $product['sold_out']
							? 'https://schema.org/OutOfStock'
							: 'https://schema.org/InStock',
						'url'           => $product['permalink'],
					),
				);
			}
		}

		if ( is_singular( 'post' ) ) {
			$graph[] = array(
				'@context'      => 'https://schema.org',
				'@type'         => 'Article',
				'headline'      => get_the_title(),
				'description'   => wp_strip_all_tags( get_the_excerpt() ),
				'image'         => (string) get_the_post_thumbnail_url( null, 'full' ),
				'author'        => array(
					'@type' => 'Person',
					'name'  => get_the_author(),
				),
				'datePublished' => get_the_date( 'c' ),
				'dateModified'  => get_the_modified_date( 'c' ),
			);
		}

		foreach ( $graph as $item ) {
			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode( $item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			);
		}
	}

	/**
	 * Appends the tagline to the front-page title.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public function title_parts( $parts ) {
		if ( is_front_page() ) {
			$tagline = get_theme_mod( 'eg_tagline', '' );
			if ( $tagline ) {
				$parts['tagline'] = $tagline;
			}
		}
		return $parts;
	}

	/**
	 * The gate page must never be indexed — it is a door, not a destination.
	 *
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public function robots( $robots ) {
		$gate_id     = (int) eg_page_id( 'gate' );
		$checkout_id = (int) eg_page_id( 'checkout' );
		$confirm_id  = (int) eg_page_id( 'confirmed' );

		$private = array_filter( array( $gate_id, $checkout_id, $confirm_id ) );

		if ( $private && is_page( $private ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['index'], $robots['follow'] );
		}

		return $robots;
	}
}
