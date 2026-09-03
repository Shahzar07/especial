<?php
/**
 * Search results.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="eg-container eg-page">
	<h1 class="eg-display eg-page__title">
		<?php
		printf(
			/* translators: %s: search term. */
			esc_html__( 'Results for “%s”', 'especial-gallery' ),
			esc_html( get_search_query() )
		);
		?>
	</h1>

	<div class="eg-mt-6" style="max-width:32rem">
		<?php get_search_form(); ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="eg-posts">
			<?php
			while ( have_posts() ) :
				the_post();
				eg_part( 'content/post-card' );
			endwhile;
			?>
		</div>

		<?php eg_pagination(); ?>
	<?php else : ?>
		<p class="eg-empty"><?php esc_html_e( 'Nothing matched that search.', 'especial-gallery' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
