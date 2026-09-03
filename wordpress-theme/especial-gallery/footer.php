<?php
/**
 * The site footer and the bag drawer.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<?php if ( ! eg_is_page( 'gate' ) ) : ?>

	<footer class="eg-footer">
		<div class="eg-container eg-footer__inner">
			<div class="eg-footer__columns">
				<?php
				$eg_columns = array(
					'shop'  => __( 'Shop', 'especial-gallery' ),
					'help'  => __( 'Help', 'especial-gallery' ),
					'legal' => __( 'Legal', 'especial-gallery' ),
				);

				foreach ( $eg_columns as $eg_location => $eg_title ) :
					if ( ! has_nav_menu( $eg_location ) ) {
						continue;
					}
					?>
					<div class="eg-footer__col">
						<h2><?php echo esc_html( $eg_title ); ?></h2>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => $eg_location,
								'container'      => false,
								'depth'          => 1,
								'walker'         => new EG_Walker_Nav(),
								'items_wrap'     => '<ul>%3$s</ul>',
							)
						);
						?>
					</div>
				<?php endforeach; ?>

				<div class="eg-footer__col">
					<h2><?php esc_html_e( 'Mailing list', 'especial-gallery' ); ?></h2>
					<?php $eg_note = get_theme_mod( 'eg_newsletter_note', __( 'Drop announcements and restocks. No more than twice a month.', 'especial-gallery' ) ); ?>
					<?php if ( $eg_note ) : ?>
						<p class="eg-footer__note"><?php echo esc_html( $eg_note ); ?></p>
					<?php endif; ?>
					<div class="eg-mt-4">
						<?php eg_part( 'components/newsletter' ); ?>
					</div>
				</div>

				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<div class="eg-footer__col">
						<?php dynamic_sidebar( 'footer-1' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="eg-footer__bottom">
				<p class="eg-footer__wordmark"><?php echo esc_html( eg_wordmark() ); ?></p>

				<?php
				$eg_marks = array_filter( array_map( 'trim', explode( ',', (string) get_theme_mod( 'eg_payment_marks', 'Visa, Mastercard, Amex, PayPal, Apple Pay' ) ) ) );
				?>
				<?php if ( $eg_marks ) : ?>
					<?php /* Wordmarks rather than logos, so nothing is hotlinked from a
					         third party and no brand asset is redistributed with the theme. */ ?>
					<ul class="eg-payments" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'especial-gallery' ); ?>">
						<?php foreach ( $eg_marks as $eg_mark ) : ?>
							<li><?php echo esc_html( $eg_mark ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<p class="eg-copyright">
					<?php
					$eg_copyright = get_theme_mod( 'eg_copyright', '' );
					echo esc_html(
						$eg_copyright
							? $eg_copyright
							: sprintf( '© %1$s %2$s', gmdate( 'Y' ), eg_brand() )
					);
					?>
				</p>
			</div>
		</div>
	</footer>

	<?php eg_part( 'components/bag-drawer' ); ?>

<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
