<?php
/**
 * Template part: the product grid.
 *
 * Columns come from a minimum tile width rather than a fixed count per
 * breakpoint, so the layout is driven by how wide a product should be rather
 * than by how many happen to exist. A fixed count breaks at both ends of a
 * small catalogue: two products in a two-column grid rendered each one half the
 * page wide and roughly 900px tall, and a category page holding a single item
 * showed one enormous tile.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array $products    Normalised products.
 *     @type int   $eager_count How many leading tiles skip lazy loading.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'products'    => array(),
		'eager_count' => 0,
	)
);

if ( ! $args['products'] ) {
	?>
	<p class="eg-empty"><?php esc_html_e( 'Nothing here yet.', 'especial-gallery' ); ?></p>
	<?php
	return;
}
?>

<div class="eg-grid">
	<?php foreach ( array_values( $args['products'] ) as $eg_index => $eg_product ) : ?>
		<?php
		eg_part(
			'components/product-card',
			array(
				'product' => $eg_product,
				'eager'   => $eg_index < (int) $args['eager_count'],
			)
		);
		?>
	<?php endforeach; ?>
</div>
