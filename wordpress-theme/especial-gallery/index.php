<?php
/**
 * The fallback template.
 *
 * WordPress falls back here for anything with no more specific template, so it
 * has to render a loop of any post type sensibly.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="eg-container eg-page">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<h1 class="eg-display eg-page__title"><?php single_post_title(); ?></h1>
	<?php endif; ?>

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
		<?php eg_part( 'content/none' ); ?>
	<?php endif; ?>
</div>

<?php
get_footer();
