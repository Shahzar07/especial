<?php
/**
 * Archive pages — categories, tags, dates and authors.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="eg-container eg-page">
	<h1 class="eg-display eg-page__title"><?php the_archive_title(); ?></h1>

	<?php if ( get_the_archive_description() ) : ?>
		<div class="eg-page__meta"><?php the_archive_description(); ?></div>
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
