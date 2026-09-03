<?php
/**
 * Template Name: Checkout
 *
 * Contact and shipping details on the left, the bag and the totals on the right.
 *
 * The summary is filled in by the server through the quote endpoint, not by the
 * browser adding up what is in localStorage. That is the point: the same
 * function prices the quote and the real order, so the figure a customer reads
 * and the figure they are charged come from one place and cannot drift.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a message we set ourselves on redirect.
$eg_error = isset( $_GET['eg_error'] ) ? sanitize_text_field( wp_unslash( $_GET['eg_error'] ) ) : '';

$eg_fields = array(
	'email'    => array( __( 'Email', 'especial-gallery' ), 'email', 'email', true, true ),
	'name'     => array( __( 'Full name', 'especial-gallery' ), 'text', 'name', true, true ),
	'line1'    => array( __( 'Address', 'especial-gallery' ), 'text', 'address-line1', true, true ),
	'line2'    => array( __( 'Apartment, suite (optional)', 'especial-gallery' ), 'text', 'address-line2', false, true ),
	'city'     => array( __( 'City', 'especial-gallery' ), 'text', 'address-level2', true, false ),
	'postcode' => array( __( 'Postcode', 'especial-gallery' ), 'text', 'postal-code', true, false ),
	'country'  => array( __( 'Country', 'especial-gallery' ), 'text', 'country-name', true, true ),
);
$eg_shop = get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT );
?>

<div class="eg-container eg-page">
	<h1 class="eg-display eg-page__title"><?php the_title(); ?></h1>

	<?php /* Shown until script confirms the bag has something in it. */ ?>
	<div data-eg-checkout-empty>
		<div class="eg-empty">
			<p><?php esc_html_e( 'Your bag is empty.', 'especial-gallery' ); ?></p>
			<?php if ( $eg_shop ) : ?>
				<a href="<?php echo esc_url( $eg_shop ); ?>" class="eg-link eg-accent eg-text-sm eg-mt-4">
					<?php esc_html_e( 'See the newest collection', 'especial-gallery' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<form class="eg-checkout eg-is-hidden"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		data-eg-checkout
		novalidate>

		<input type="hidden" name="action" value="eg_checkout_form">
		<?php /* Filled by script from localStorage, so the no-JS post carries the same bag. */ ?>
		<input type="hidden" name="lines" value="" data-eg-checkout-lines>
		<?php wp_nonce_field( 'eg_public', 'eg_nonce' ); ?>

		<div class="eg-checkout__details">
			<div class="eg-checkout__group">
				<h2><?php esc_html_e( 'Contact', 'especial-gallery' ); ?></h2>
				<div class="eg-checkout__fields">
					<?php
					$eg_contact_fields = array_slice( $eg_fields, 0, 1, true );
					foreach ( $eg_contact_fields as $eg_name => $eg_field ) :
						?>
						<div class="eg-input eg-span-2">
							<label for="eg-co-<?php echo esc_attr( $eg_name ); ?>"><?php echo esc_html( $eg_field[0] ); ?></label>
							<input type="<?php echo esc_attr( $eg_field[1] ); ?>"
								id="eg-co-<?php echo esc_attr( $eg_name ); ?>"
								name="<?php echo esc_attr( $eg_name ); ?>"
								autocomplete="<?php echo esc_attr( $eg_field[2] ); ?>"
								<?php echo $eg_field[3] ? 'required' : ''; ?>>
							<p class="eg-input__error" data-eg-error="<?php echo esc_attr( $eg_name ); ?>"></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="eg-checkout__group">
				<h2><?php esc_html_e( 'Shipping address', 'especial-gallery' ); ?></h2>
				<div class="eg-checkout__fields">
					<?php
					$eg_address_fields = array_slice( $eg_fields, 1, null, true );
					foreach ( $eg_address_fields as $eg_name => $eg_field ) :
						?>
						<div class="eg-input<?php echo $eg_field[4] ? ' eg-span-2' : ''; ?>">
							<label for="eg-co-<?php echo esc_attr( $eg_name ); ?>"><?php echo esc_html( $eg_field[0] ); ?></label>
							<input type="<?php echo esc_attr( $eg_field[1] ); ?>"
								id="eg-co-<?php echo esc_attr( $eg_name ); ?>"
								name="<?php echo esc_attr( $eg_name ); ?>"
								autocomplete="<?php echo esc_attr( $eg_field[2] ); ?>"
								<?php echo $eg_field[3] ? 'required' : ''; ?>>
							<p class="eg-input__error" data-eg-error="<?php echo esc_attr( $eg_name ); ?>"></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="eg-checkout__summary">
			<div class="eg-summary">
				<h2><?php esc_html_e( 'Your bag', 'especial-gallery' ); ?></h2>

				<ul data-eg-summary-lines></ul>

				<dl class="eg-summary__totals">
					<div class="eg-summary__row">
						<dt><?php esc_html_e( 'Subtotal', 'especial-gallery' ); ?></dt>
						<dd class="eg-tabular" data-eg-summary-subtotal>&mdash;</dd>
					</div>
					<div class="eg-summary__row">
						<dt><?php esc_html_e( 'Shipping', 'especial-gallery' ); ?></dt>
						<dd class="eg-tabular" data-eg-summary-shipping>&mdash;</dd>
					</div>
					<div class="eg-summary__row eg-summary__row--total">
						<dt><?php esc_html_e( 'Total', 'especial-gallery' ); ?></dt>
						<dd class="eg-tabular" data-eg-summary-total>&mdash;</dd>
					</div>
				</dl>

				<p class="eg-summary__note eg-is-hidden" data-eg-summary-note></p>
			</div>

			<p class="eg-checkout__error" role="status" aria-live="polite" data-eg-checkout-error>
				<?php echo esc_html( $eg_error ); ?>
			</p>

			<button type="submit" class="eg-button" data-eg-checkout-submit>
				<?php esc_html_e( 'Place order', 'especial-gallery' ); ?>
			</button>

			<p class="eg-checkout__legal">
				<?php esc_html_e( 'Card details are entered on the payment provider’s own page. They never reach this site.', 'especial-gallery' ); ?>
			</p>
		</div>
	</form>
</div>

<?php
get_footer();
