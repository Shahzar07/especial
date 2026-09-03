<?php
/**
 * Template part: product tile.
 *
 * Image, title, category, price — nothing else. No wishlist, no quick-view, no
 * rating, no badge row. The image sits directly on the page rather than inside
 * a card, which is why there is no border, radius or shadow anywhere here.
 *
 * Hover crossfades to the second image. The element never moves.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array  $product Normalised product from Catalog::product().
 *     @type string $sizes   Sizes attribute for the tile image.
 *     @type bool   $eager   Skip lazy loading, for tiles above the fold.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'product' => null,
		'sizes'   => '(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw',
		'eager'   => false,
	)
);

$product = $args['product'];

if ( ! $product || empty( $product['images'][0] ) ) {
	return;
}

$primary = $product['images'][0];
// Only fade out when there is something to fade TO. When every product had a
// single photograph, fading on hover simply blanked the tile.
$secondary = isset( $product['images'][1] ) ? $product['images'][1] : null;
?>

<article class="eg-card" data-has-hover="<?php echo $secondary ? 'true' : 'false'; ?>">
	<a href="<?php echo esc_url( $product['permalink'] ); ?>" class="eg-card__link">
		<div class="eg-card__media">
			<?php
			eg_the_image(
				$primary,
				array(
					'class' => 'eg-card__img eg-card__img--primary',
					'sizes' => $args['sizes'],
					'eager' => $args['eager'],
				)
			);

			if ( $secondary ) {
				eg_the_image(
					$secondary,
					array(
						'class' => 'eg-card__img eg-card__img--hover',
						'sizes' => $args['sizes'],
						'alt'   => '',
					)
				);
			}
			?>

			<?php if ( $product['sold_out'] ) : ?>
				<div class="eg-sold-out"><span><?php esc_html_e( 'Sold out', 'especial-gallery' ); ?></span></div>
			<?php endif; ?>
		</div>

		<div class="eg-card__body">
			<h3 class="eg-card__title"><?php echo esc_html( $product['title'] ); ?></h3>
			<?php if ( $product['category'] ) : ?>
				<p class="eg-card__meta"><?php echo esc_html( $product['category'] ); ?></p>
			<?php endif; ?>
			<?php eg_the_price( $product ); ?>
		</div>
	</a>
</article>
