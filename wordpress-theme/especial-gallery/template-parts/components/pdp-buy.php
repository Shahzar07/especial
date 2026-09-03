<?php
/**
 * Template part: price, options and add to bag.
 *
 * Option swatches are square with a 1px ink border when selected — no fill, no
 * radius, and no accent colour, because accent is reserved for focus, active
 * navigation and inline links.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array $product Normalised product.
 * }
 */

defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) ? $args['product'] : null;

if ( ! $product ) {
	return;
}

$eg_first_available = '';
foreach ( $product['variants'] as $eg_variant ) {
	if ( $eg_variant['available'] ) {
		$eg_first_available = $eg_variant['id'];
		break;
	}
}

$eg_can_buy = ! $product['sold_out'] && $eg_first_available;
?>

<div class="eg-buy"
	data-eg-buy
	data-eg-slug="<?php echo esc_attr( $product['slug'] ); ?>"
	data-eg-title="<?php echo esc_attr( $product['title'] ); ?>"
	data-eg-image="<?php echo esc_attr( isset( $product['images'][0]['url'] ) ? $product['images'][0]['url'] : '' ); ?>"
	<?php /* Carried so the drawer can show a running subtotal. The server never
	         reads it — it prices every order from the catalogue — so a browser
	         editing this attribute changes nothing but its own display. */ ?>
	data-eg-price="<?php echo esc_attr( $product['price_cents'] ); ?>">

	<?php eg_the_price( $product, 'eg-pdp__price' ); ?>

	<?php if ( count( $product['variants'] ) > 1 ) : ?>
		<fieldset class="eg-variants">
			<legend><?php esc_html_e( 'Option', 'especial-gallery' ); ?></legend>
			<div class="eg-variants__options">
				<?php foreach ( $product['variants'] as $eg_variant ) : ?>
					<button type="button"
						class="eg-variant"
						data-eg-variant="<?php echo esc_attr( $eg_variant['id'] ); ?>"
						data-eg-label="<?php echo esc_attr( $eg_variant['label'] ); ?>"
						aria-pressed="<?php echo $eg_variant['id'] === $eg_first_available ? 'true' : 'false'; ?>"
						<?php disabled( ! $eg_variant['available'] ); ?>>
						<?php echo esc_html( $eg_variant['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</fieldset>
	<?php else : ?>
		<?php $eg_only = $product['variants'][0]; ?>
		<input type="hidden" data-eg-variant-single
			value="<?php echo esc_attr( $eg_only['id'] ); ?>"
			data-eg-label="<?php echo esc_attr( $eg_only['label'] ); ?>">
	<?php endif; ?>

	<button type="button"
		class="eg-button eg-add-to-bag"
		data-eg-add
		<?php disabled( ! $eg_can_buy ); ?>>
		<?php
		echo $eg_can_buy
			? esc_html__( 'Add to bag', 'especial-gallery' )
			: esc_html__( 'Sold out', 'especial-gallery' );
		?>
	</button>

	<p class="eg-form-message" role="status" aria-live="polite" data-eg-buy-message></p>
</div>
