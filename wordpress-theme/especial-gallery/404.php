<?php
/**
 * The 404 page.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="eg-404">
	<p class="eg-gate__wordmark"><?php echo esc_html( eg_wordmark() ); ?></p>
	<h1 class="eg-display"><?php esc_html_e( 'Page not found', 'especial-gallery' ); ?></h1>
	<p><?php esc_html_e( 'This page has moved or never existed.', 'especial-gallery' ); ?></p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="eg-link">
		<?php esc_html_e( 'Back to the store', 'especial-gallery' ); ?>
	</a>
</div>

<?php
get_footer();
