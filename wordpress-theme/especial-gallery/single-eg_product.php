<?php
/**
 * A single product.
 *
 * Images on the left and scrolling; the facts on the right and sticky, so the
 * price and the add-to-bag control stay reachable however tall the photography
 * runs.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$eg_product = Especial_Gallery\Catalog::product( get_the_ID() );

	if ( ! $eg_product ) {
		continue;
	}

	$eg_related = Especial_Gallery\Catalog::related( get_the_ID(), 4 );
	$eg_single  = count( $eg_product['images'] ) === 1;
	?>

	<div class="eg-container eg-page">
		<div class="eg-pdp">

			<div class="eg-pdp__media">
				<div class="eg-pdp__gallery<?php echo $eg_single ? ' eg-pdp__gallery--single' : ''; ?>">
					<?php foreach ( $eg_product['images'] as $eg_index => $eg_image ) : ?>
						<figure class="eg-pdp__figure">
							<?php
							eg_the_image(
								$eg_image,
								array(
									'sizes' => '(min-width: 1024px) 40vw, (min-width: 640px) 50vw, 100vw',
									'eager' => 0 === $eg_index,
								)
							);
							?>
						</figure>
					<?php endforeach; ?>

					<?php if ( ! $eg_product['images'] ) : ?>
						<figure class="eg-pdp__figure"></figure>
					<?php endif; ?>
				</div>
			</div>

			<div class="eg-pdp__detail">
				<div class="eg-pdp__sticky">
					<?php if ( $eg_product['category'] ) : ?>
						<p class="eg-pdp__category">
							<?php if ( $eg_product['category_link'] && ! is_wp_error( $eg_product['category_link'] ) ) : ?>
								<a href="<?php echo esc_url( $eg_product['category_link'] ); ?>" class="eg-link">
									<?php echo esc_html( $eg_product['category'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $eg_product['category'] ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<h1 class="eg-pdp__title"><?php the_title(); ?></h1>

					<?php eg_part( 'components/pdp-buy', array( 'product' => $eg_product ) ); ?>

					<?php if ( $eg_product['description'] ) : ?>
						<p class="eg-pdp__description"><?php echo esc_html( $eg_product['description'] ); ?></p>
					<?php endif; ?>

					<div class="eg-pdp__accordions">
						<?php
						if ( $eg_product['details'] ) {
							eg_part(
								'components/accordion',
								array(
									'title' => __( 'Details', 'especial-gallery' ),
									'items' => $eg_product['details'],
									'open'  => true,
								)
							);
						}

						eg_part(
							'components/accordion',
							array(
								'title'   => __( 'Shipping', 'especial-gallery' ),
								'content' => '<p>' . esc_html__( 'Orders ship within two working days, tracked. Shipping is calculated at checkout.', 'especial-gallery' ) . '</p>',
							)
						);

						eg_part(
							'components/accordion',
							array(
								'title'   => __( 'Returns', 'especial-gallery' ),
								'content' => '<p>' . esc_html__( 'Unopened items can be returned within thirty days of delivery. Opened collectibles can be returned if they arrived damaged.', 'especial-gallery' ) . '</p>',
							)
						);
						?>
					</div>
				</div>
			</div>

		</div>

		<?php
		// The body content is only printed when the editor has written more than
		// the short description, so a product entered with just an excerpt does
		// not show the same paragraph twice.
		$eg_content = trim( wp_strip_all_tags( get_the_content() ) );
		$eg_excerpt = trim( wp_strip_all_tags( $eg_product['description'] ) );
		?>
		<?php if ( $eg_content && $eg_content !== $eg_excerpt ) : ?>
			<section class="eg-section">
				<div class="eg-editorial eg-prose">
					<?php the_content(); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $eg_related ) : ?>
			<section class="eg-section">
				<?php eg_section_heading( __( 'Related', 'especial-gallery' ) ); ?>
				<div class="eg-section-body">
					<?php eg_part( 'components/product-grid', array( 'products' => $eg_related ) ); ?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php
endwhile;

get_footer();
