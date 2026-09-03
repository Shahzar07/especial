<?php
/**
 * Template Name: Mailing-list gate
 *
 * The gate. It renders outside the store chrome — no header, no footer, no bag —
 * so it is a clean full viewport, which is the whole visual argument.
 *
 * One orchestrated moment on load: each element rises in turn, and every step
 * of it is neutralised under prefers-reduced-motion.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of where the visitor was headed.
$eg_next  = isset( $_GET['eg_next'] ) ? eg_safe_next( sanitize_text_field( wp_unslash( $_GET['eg_next'] ) ) ) : '/';
$eg_error = isset( $_GET['eg_error'] ) ? sanitize_text_field( wp_unslash( $_GET['eg_error'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

/*
 * The three strongest product shots carry the entire visual argument here, so
 * they are eager-loaded and given explicit dimensions — layout shift on the one
 * page every visitor sees first would be the worst place to have it.
 */
$eg_showcase = Especial_Gallery\Catalog::featured( 3 );
if ( ! $eg_showcase ) {
	$eg_showcase = Especial_Gallery\Catalog::newest( 3 );
}
?>

<div class="eg-gate">
	<div class="eg-gate__inner">

		<p class="eg-reveal eg-gate__wordmark" style="--eg-step:0">
			<?php echo esc_html( eg_wordmark() ); ?>
		</p>

		<?php if ( $eg_showcase ) : ?>
			<div class="eg-reveal eg-gate__images" style="--eg-step:1">
				<?php foreach ( $eg_showcase as $eg_product ) : ?>
					<?php if ( empty( $eg_product['images'][0] ) ) { continue; } ?>
					<figure class="eg-gate__figure">
						<?php
						eg_the_image(
							$eg_product['images'][0],
							array(
								'sizes' => '(min-width: 640px) 33vw, 100vw',
								'eager' => true,
							)
						);
						?>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h1 class="eg-reveal eg-display eg-gate__headline" style="--eg-step:2">
			<?php echo esc_html( get_theme_mod( 'eg_gate_headline', __( 'Sign up for our list', 'especial-gallery' ) ) ); ?>
		</h1>

		<div class="eg-reveal" style="--eg-step:3">
			<?php
			eg_part(
				'components/newsletter',
				array(
					'next'  => $eg_next,
					'gate'  => true,
					'error' => $eg_error,
				)
			);
			?>
		</div>

		<?php if ( has_nav_menu( 'gate' ) ) : ?>
			<nav class="eg-reveal" style="--eg-step:4" aria-label="<?php esc_attr_e( 'Gate', 'especial-gallery' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'gate',
						'container'      => false,
						'depth'          => 1,
						'walker'         => new EG_Walker_Nav(),
						'items_wrap'     => '<ul class="eg-gate__links">%3$s</ul>',
					)
				);
				?>
			</nav>
		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
