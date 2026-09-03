<?php
/**
 * Template part: the mailing-list field.
 *
 * The same bare-underline pattern as the gate, and it posts to the same
 * endpoint, so a visitor already past the gate simply has their cookie
 * refreshed.
 *
 * Both paths are live. With JavaScript the submit is intercepted and sent over
 * AJAX; without it the surrounding form posts for real to admin-post.php, where
 * the identical validation runs. That is the WordPress equivalent of the
 * server action the original fell back to before React had hydrated.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type string $next    Where to send a successful submission.
 *     @type bool   $gate    Whether this is the gate's own, larger field.
 *     @type string $error   A message to show on load, from a failed no-JS post.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'next'  => '/',
		'gate'  => false,
		'error' => '',
	)
);

$eg_id = $args['gate'] ? 'eg-gate-email' : 'eg-footer-email';
?>

<form class="eg-subscribe<?php echo $args['gate'] ? ' eg-gate__form' : ''; ?>"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	data-eg-subscribe
	novalidate>

	<input type="hidden" name="action" value="eg_subscribe_form">
	<input type="hidden" name="next" value="<?php echo esc_attr( $args['next'] ); ?>">
	<?php wp_nonce_field( 'eg_public', 'eg_nonce' ); ?>

	<div class="eg-field<?php echo $args['gate'] ? '' : ' eg-field--compact'; ?>">
		<label for="<?php echo esc_attr( $eg_id ); ?>" class="eg-sr-only">
			<?php esc_html_e( 'Email address', 'especial-gallery' ); ?>
		</label>
		<input type="email"
			id="<?php echo esc_attr( $eg_id ); ?>"
			name="email"
			inputmode="email"
			autocomplete="email"
			autocapitalize="off"
			spellcheck="false"
			placeholder="<?php esc_attr_e( 'Email', 'especial-gallery' ); ?>"
			required>
		<button type="submit" class="eg-link">
			<?php
			echo $args['gate']
				? esc_html__( 'Enter', 'especial-gallery' )
				: esc_html__( 'Sign up', 'especial-gallery' );
			?>
		</button>
	</div>

	<?php /* A reserved line, so an error appearing never shifts the layout. */ ?>
	<p class="eg-form-message"
		role="status"
		aria-live="polite"
		data-eg-message
		<?php echo $args['error'] ? 'data-error="true"' : ''; ?>>
		<?php echo esc_html( $args['error'] ); ?>
	</p>
</form>
