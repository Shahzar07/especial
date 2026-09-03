<?php
/**
 * A single blog post.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="eg-container eg-container--reading eg-page">
		<h1 class="eg-display eg-page__title"><?php the_title(); ?></h1>

		<p class="eg-page__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</p>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="eg-mt-7">
				<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
			</figure>
		<?php endif; ?>

		<div class="eg-page__body eg-prose">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<nav class="eg-pagination">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</article>
	<?php
endwhile;

get_footer();
