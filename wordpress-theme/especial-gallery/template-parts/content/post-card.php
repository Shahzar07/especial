<?php
/**
 * Template part: a post in a list.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;
?>

<article <?php post_class( 'eg-post-card' ); ?>>
	<a href="<?php the_permalink(); ?>" class="eg-card__link">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="eg-post-card__media">
				<?php
				the_post_thumbnail(
					'eg-tile',
					array(
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(min-width: 768px) 33vw, 100vw',
					)
				);
				?>
			</div>
		<?php endif; ?>

		<h2><span class="eg-link"><?php the_title(); ?></span></h2>

		<p>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</p>

		<?php if ( has_excerpt() ) : ?>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
		<?php endif; ?>
	</a>
</article>
