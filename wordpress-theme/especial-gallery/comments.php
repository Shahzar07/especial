<?php
/**
 * Comments.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

// A password-protected post must not leak its discussion.
if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="eg-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="eg-display eg-page__title">
			<?php
			printf(
				/* translators: %d: comment count. */
				esc_html( _n( '%d comment', '%d comments', (int) get_comments_number(), 'especial-gallery' ) ),
				(int) get_comments_number()
			);
			?>
		</h2>

		<ol class="eg-mt-6">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 0,
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => __( 'Older comments', 'especial-gallery' ),
				'next_text' => __( 'Newer comments', 'especial-gallery' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="eg-muted eg-text-sm eg-mt-6"><?php esc_html_e( 'Comments are closed.', 'especial-gallery' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => __( 'Leave a comment', 'especial-gallery' ),
			'title_reply_before' => '<h2 class="eg-display eg-page__title eg-mt-7">',
			'title_reply_after'  => '</h2>',
			'class_submit'       => 'submit',
		)
	);
	?>
</section>
