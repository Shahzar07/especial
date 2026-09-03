<?php
/**
 * Template part: the bag drawer.
 *
 * The bag lives in the browser's localStorage, so the panel is rendered empty
 * and filled in by script. That is deliberate rather than a shortcut: a
 * server-rendered bag would be wrong for every visitor served a cached page,
 * and page caching is the single biggest thing a WordPress storefront has that
 * the original static build got for free.
 *
 * The scrim sits below the panel in the stacking order. With it above, it
 * intercepted every click inside the panel.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

$eg_checkout = eg_page_url( 'checkout' );
$eg_shop     = get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT );
?>

<div class="eg-scrim" data-eg-bag-close aria-hidden="true"></div>

<div id="eg-drawer"
	class="eg-drawer"
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Bag', 'especial-gallery' ); ?>"
	data-eg-drawer
	aria-hidden="true">

	<div class="eg-drawer__head">
		<h2><?php esc_html_e( 'Bag', 'especial-gallery' ); ?></h2>
		<button type="button" class="eg-link eg-drawer__close" data-eg-bag-close>
			<?php esc_html_e( 'Close', 'especial-gallery' ); ?>
		</button>
	</div>

	<div class="eg-drawer__empty" data-eg-bag-empty>
		<p><?php esc_html_e( 'Your bag is empty.', 'especial-gallery' ); ?></p>
		<?php if ( $eg_shop ) : ?>
			<a href="<?php echo esc_url( $eg_shop ); ?>" class="eg-link eg-text-sm eg-accent">
				<?php esc_html_e( 'See the newest collection', 'especial-gallery' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<ul class="eg-drawer__lines eg-is-hidden" data-eg-bag-lines></ul>

	<div class="eg-drawer__foot eg-is-hidden" data-eg-bag-foot>
		<div class="eg-drawer__subtotal">
			<span><?php esc_html_e( 'Subtotal', 'especial-gallery' ); ?></span>
			<span class="eg-tabular" data-eg-bag-subtotal></span>
		</div>
		<p class="eg-drawer__note">
			<?php esc_html_e( 'Shipping and taxes calculated at checkout.', 'especial-gallery' ); ?>
		</p>
		<?php if ( $eg_checkout ) : ?>
			<a href="<?php echo esc_url( $eg_checkout ); ?>" class="eg-button">
				<?php esc_html_e( 'Continue to checkout', 'especial-gallery' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
