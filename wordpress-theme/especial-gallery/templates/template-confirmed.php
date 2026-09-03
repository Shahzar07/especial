<?php
/**
 * Template Name: Order confirmed
 *
 * Acknowledges the order with its reference. The bag is cleared by script only
 * once this page has actually rendered — in that order, so a failed navigation
 * never loses somebody's order.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a display-only reference, already validated server-side when the order was written.
$eg_reference = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';
$eg_shop      = get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT );
?>

<div class="eg-container eg-container--reading eg-page" data-eg-confirmed>
	<h1 class="eg-display eg-page__title"><?php esc_html_e( 'Order confirmed', 'especial-gallery' ); ?></h1>

	<div class="eg-prose eg-mt-6">
		<p class="eg-in-stock"><?php esc_html_e( 'Thank you. Your order is in.', 'especial-gallery' ); ?></p>

		<?php if ( $eg_reference ) : ?>
			<dl class="eg-confirmed__reference">
				<dt><?php esc_html_e( 'Reference', 'especial-gallery' ); ?></dt>
				<dd class="eg-tabular"><?php echo esc_html( $eg_reference ); ?></dd>
			</dl>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'A confirmation is on its way to the address you gave us. Orders ship within two working days, tracked, from our own studio.', 'especial-gallery' ); ?>
		</p>

		<p>
			<?php esc_html_e( 'Quote the reference above in any message about this order.', 'especial-gallery' ); ?>
		</p>

		<?php if ( $eg_shop ) : ?>
			<p>
				<a href="<?php echo esc_url( $eg_shop ); ?>" class="eg-link eg-accent">
					<?php esc_html_e( 'Back to the shop', 'especial-gallery' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
