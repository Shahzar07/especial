<?php
/**
 * Template part: category cells.
 *
 * Compact, and on the same column rhythm as the product grid, so the two read
 * as one page rather than as two unrelated layouts. Each cell is a hairline box
 * with a square image over a ruled caption — the same construction as the
 * feature blocks, which is what keeps the page's vocabulary consistent.
 *
 * With only a few categories the row is capped to their own width, because a
 * full-width auto-fill row leaves empty tracks trailing off to the right. The
 * cap lifts on its own once there are enough categories to fill the width.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

$eg_categories = Especial_Gallery\Catalog::categories();

if ( ! $eg_categories ) {
	return;
}

$eg_cap = count( $eg_categories ) <= 3
	? sprintf( 'calc(%1$d * 340px + %2$d * var(--eg-grid-gap))', count( $eg_categories ), count( $eg_categories ) - 1 )
	: '';
?>

<div class="eg-grid"<?php echo $eg_cap ? ' style="max-width:' . esc_attr( $eg_cap ) . '"' : ''; ?>>
	<?php
	foreach ( $eg_categories as $eg_term ) :
		$eg_link  = get_term_link( $eg_term );
		$eg_image = Especial_Gallery\Catalog::category_image( $eg_term );

		if ( is_wp_error( $eg_link ) ) {
			continue;
		}
		?>
		<a href="<?php echo esc_url( $eg_link ); ?>" class="eg-category">
			<div class="eg-category__media">
				<?php
				if ( $eg_image ) {
					echo wp_get_attachment_image(
						$eg_image,
						'eg-square',
						false,
						array(
							'alt'      => '',
							'aria-hidden' => 'true',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw',
						)
					);
				}
				?>
			</div>
			<div class="eg-category__caption">
				<span class="eg-link"><?php echo esc_html( $eg_term->name ); ?></span>
				<span class="eg-tabular eg-category__count">
					<?php
					printf(
						/* translators: %d: number of products. */
						esc_html( _n( '%d item', '%d items', (int) $eg_term->count, 'especial-gallery' ) ),
						(int) $eg_term->count
					);
					?>
				</span>
			</div>
		</a>
	<?php endforeach; ?>
</div>
