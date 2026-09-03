<?php
/**
 * Template part: a release, as a wide hairline box.
 *
 * Image on the left, the facts on the right. A short release run through the
 * tall portrait grid meant two half-page images and a great deal of air; laid
 * on its side the same information takes about a fifth of the height and the
 * row reads as a deliberate pair rather than as a grid that failed to fill.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array $product Normalised product.
 * }
 */

defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) ? $args['product'] : null;

if ( ! $product || empty( $product['images'][0] ) ) {
	return;
}
?>

<a href="<?php echo esc_url( $product['permalink'] ); ?>" class="eg-release">
	<div class="eg-release__media">
		<?php
		eg_the_image(
			$product['images'][0],
			array(
				'sizes' => '(min-width: 1024px) 20vw, 40vw',
			)
		);
		?>
		<?php if ( $product['sold_out'] ) : ?>
			<div class="eg-sold-out"><span><?php esc_html_e( 'Sold out', 'especial-gallery' ); ?></span></div>
		<?php endif; ?>
	</div>

	<div class="eg-release__body">
		<h3><span class="eg-link"><?php echo esc_html( $product['title'] ); ?></span></h3>
		<?php if ( $product['category'] ) : ?>
			<p class="eg-card__meta"><?php echo esc_html( $product['category'] ); ?></p>
		<?php endif; ?>
		<?php eg_the_price( $product ); ?>
	</div>
</a>
