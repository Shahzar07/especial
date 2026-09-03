<?php
/**
 * Template tags.
 *
 * The functions templates call. Keeping them here rather than inline in the
 * templates is what lets the templates stay markup, which is the whole reason
 * a designer can open one and understand it.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ID of one of the theme's special pages.
 *
 * The pages are located by a stored ID rather than by slug, so renaming the
 * checkout page in the admin does not silently break the bag.
 *
 * @param string $key One of gate, checkout, confirmed, shop.
 * @return int Page ID, or 0.
 */
function eg_page_id( $key ) {
	$pages = get_option( 'eg_pages', array() );

	if ( ! is_array( $pages ) || empty( $pages[ $key ] ) ) {
		return 0;
	}

	$id = absint( $pages[ $key ] );

	// A page that has been deleted or binned should read as absent, not as a
	// broken link that 404s in the middle of a checkout.
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return 0;
	}

	return $id;
}

/**
 * The URL of one of the theme's special pages.
 *
 * @param string $key One of gate, checkout, confirmed, shop.
 * @return string URL, or an empty string when the page does not exist.
 */
function eg_page_url( $key ) {
	$id = eg_page_id( $key );
	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Whether the current request is one of the theme's special pages.
 *
 * @param string $key One of gate, checkout, confirmed, shop.
 * @return bool
 */
function eg_is_page( $key ) {
	$id = eg_page_id( $key );
	return $id && is_page( $id );
}

/**
 * Renders a price, with a struck-through compare-at figure when there is one.
 *
 * @param array $product Normalised product.
 * @param string $class  Extra class for the wrapper.
 * @return void
 */
function eg_the_price( $product, $class = '' ) {
	$currency = $product['currency'];

	echo '<p class="' . esc_attr( trim( 'eg-card__price ' . $class ) ) . '">';

	if ( ! empty( $product['compare_cents'] ) ) {
		printf(
			'<span class="eg-tabular eg-price--sale">%1$s</span> <span class="eg-tabular eg-price--compare">%2$s</span>',
			esc_html( eg_format_price( $product['price_cents'], $currency ) ),
			esc_html( eg_format_price( $product['compare_cents'], $currency ) )
		);
	} else {
		printf(
			'<span class="eg-tabular">%s</span>',
			esc_html( eg_format_price( $product['price_cents'], $currency ) )
		);
	}

	echo '</p>';
}

/**
 * Renders one of a product's images.
 *
 * Prints width, height, srcset and sizes so the browser reserves the right box
 * before the file arrives — which is what keeps Cumulative Layout Shift at zero
 * on a page that is mostly photography.
 *
 * @param array $image  One image from Catalog::images().
 * @param array $args   {
 *     @type string $class  Class attribute.
 *     @type string $sizes  Sizes attribute, overriding the generated one.
 *     @type bool   $eager  Skip lazy loading, for anything above the fold.
 *     @type string $alt    Alt text override. Pass '' for a decorative image.
 * }
 * @return void
 */
function eg_the_image( $image, array $args = array() ) {
	if ( empty( $image['url'] ) ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'class' => '',
			'sizes' => '',
			'eager' => false,
			'alt'   => null,
		)
	);

	$alt = null === $args['alt'] ? $image['alt'] : $args['alt'];

	$attributes = array(
		'src'    => esc_url( $image['url'] ),
		'width'  => absint( $image['width'] ),
		'height' => absint( $image['height'] ),
		'alt'    => esc_attr( $alt ),
	);

	if ( $args['class'] ) {
		$attributes['class'] = esc_attr( $args['class'] );
	}

	if ( ! empty( $image['srcset'] ) ) {
		$attributes['srcset'] = esc_attr( $image['srcset'] );
		$attributes['sizes']  = esc_attr( $args['sizes'] ? $args['sizes'] : $image['sizes'] );
	}

	// An image with no alt text is decorative, and a screen reader should skip
	// it rather than read a filename aloud.
	if ( '' === $alt ) {
		$attributes['aria-hidden'] = 'true';
	}

	if ( $args['eager'] ) {
		$attributes['loading']  = 'eager';
		$attributes['decoding'] = 'sync';
		$attributes['fetchpriority'] = 'high';
	} else {
		$attributes['loading']  = 'lazy';
		$attributes['decoding'] = 'async';
	}

	$html = '<img';
	foreach ( $attributes as $name => $value ) {
		$html .= ' ' . $name . '="' . $value . '"';
	}
	$html .= '>';

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every attribute escaped above.
}

/**
 * Renders a section heading: display serif with a hairline rule beneath.
 *
 * @param string $title  Heading text.
 * @param string $url    Optional action link.
 * @param string $action Optional action label.
 * @return void
 */
function eg_section_heading( $title, $url = '', $action = '' ) {
	?>
	<div class="eg-section-heading">
		<h2 class="eg-display"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $url && $action ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" class="eg-link eg-section-heading__action">
				<?php echo esc_html( $action ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The data attribute payload a product tile needs so the bag can add it
 * without a round trip.
 *
 * Only slug, variant and title travel. The price is deliberately absent: the
 * server prices every order from the catalogue, so a price in the DOM would be
 * a number nobody reads and an invitation to trust it later.
 *
 * @param array $product Normalised product.
 * @return string Escaped attribute string.
 */
function eg_product_attributes( $product ) {
	$variant = '';
	foreach ( $product['variants'] as $candidate ) {
		if ( $candidate['available'] ) {
			$variant = $candidate['id'];
			break;
		}
	}

	return sprintf(
		'data-eg-slug="%1$s" data-eg-variant="%2$s" data-eg-title="%3$s" data-eg-image="%4$s"',
		esc_attr( $product['slug'] ),
		esc_attr( $variant ),
		esc_attr( $product['title'] ),
		esc_attr( isset( $product['images'][0]['url'] ) ? $product['images'][0]['url'] : '' )
	);
}

/**
 * Prints the bag toggle's live item count.
 *
 * Rendered empty and filled in by the client from localStorage, because the bag
 * lives in the browser and a server-rendered count would be wrong for every
 * visitor served a cached page.
 *
 * @return void
 */
function eg_bag_count() {
	echo '<span class="eg-tabular eg-bag-count" data-eg-count aria-hidden="true"></span>';
}

/**
 * Renders the theme's pagination.
 *
 * @return void
 */
function eg_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'prev_text' => __( 'Previous', 'especial-gallery' ),
			'next_text' => __( 'Next', 'especial-gallery' ),
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="eg-pagination" aria-label="' . esc_attr__( 'Pagination', 'especial-gallery' ) . '">';
	echo wp_kses_post( $links );
	echo '</nav>';
}

/**
 * The list of facts shown in the hero, from the Customizer.
 *
 * @return array
 */
function eg_hero_facts() {
	$raw = (string) get_theme_mod( 'eg_hero_facts', '' );
	return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
}
