<?php
/**
 * Product meta boxes and the order detail panel.
 *
 * The source project's Product type became these fields. Every one is nonce
 * protected, capability checked and sanitised on save; nothing here trusts the
 * browser, including the browser of a logged-in shop owner.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Registers, renders and saves product and order meta.
 */
class Meta_Boxes {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . Post_Types::PRODUCT, array( $this, 'save' ), 10, 2 );

		// Category artwork, so a category cell can carry its own image rather
		// than borrowing the newest product's.
		add_action( Post_Types::CATEGORY . '_add_form_fields', array( $this, 'term_field_add' ) );
		add_action( Post_Types::CATEGORY . '_edit_form_fields', array( $this, 'term_field_edit' ) );
		add_action( 'created_' . Post_Types::CATEGORY, array( $this, 'save_term' ) );
		add_action( 'edited_' . Post_Types::CATEGORY, array( $this, 'save_term' ) );
	}

	/**
	 * Registers the boxes.
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			'eg_product_details',
			__( 'Product', 'especial-gallery' ),
			array( $this, 'render_product' ),
			Post_Types::PRODUCT,
			'normal',
			'high'
		);

		add_meta_box(
			'eg_order_details',
			__( 'Order', 'especial-gallery' ),
			array( $this, 'render_order' ),
			Post_Types::ORDER,
			'normal',
			'high'
		);
	}

	/**
	 * The product meta box.
	 *
	 * Prices are entered in whole currency units because that is what a person
	 * thinks in, and stored in minor units because that is what arithmetic needs.
	 *
	 * @param \WP_Post $post Product.
	 * @return void
	 */
	public function render_product( $post ) {
		wp_nonce_field( 'eg_save_product', 'eg_product_nonce' );

		$price    = (int) get_post_meta( $post->ID, '_eg_price_cents', true );
		$compare  = (int) get_post_meta( $post->ID, '_eg_compare_at_cents', true );
		$details  = (string) get_post_meta( $post->ID, '_eg_details', true );
		$variants = (string) get_post_meta( $post->ID, '_eg_variants', true );
		$gallery  = (string) get_post_meta( $post->ID, '_eg_gallery', true );
		$released = (string) get_post_meta( $post->ID, '_eg_released_at', true );
		$featured = (bool) get_post_meta( $post->ID, '_eg_featured', true );
		$sold_out = (bool) get_post_meta( $post->ID, '_eg_sold_out', true );
		?>
		<div class="eg-meta">
			<p class="eg-meta__row">
				<label for="eg_price"><strong><?php esc_html_e( 'Price', 'especial-gallery' ); ?></strong></label><br>
				<input type="number" step="0.01" min="0" id="eg_price" name="eg_price"
					value="<?php echo esc_attr( $price ? number_format( $price / 100, 2, '.', '' ) : '' ); ?>" class="small-text">
				<span class="description"><?php esc_html_e( 'In whole currency units, e.g. 18.00.', 'especial-gallery' ); ?></span>
			</p>

			<p class="eg-meta__row">
				<label for="eg_compare"><strong><?php esc_html_e( 'Compare-at price', 'especial-gallery' ); ?></strong></label><br>
				<input type="number" step="0.01" min="0" id="eg_compare" name="eg_compare"
					value="<?php echo esc_attr( $compare ? number_format( $compare / 100, 2, '.', '' ) : '' ); ?>" class="small-text">
				<span class="description"><?php esc_html_e( 'Optional. Shown struck through beside the price. Ignored unless it is higher than the price.', 'especial-gallery' ); ?></span>
			</p>

			<p class="eg-meta__row">
				<label for="eg_variants"><strong><?php esc_html_e( 'Options', 'especial-gallery' ); ?></strong></label><br>
				<textarea id="eg_variants" name="eg_variants" rows="4" class="large-text code"><?php echo esc_textarea( $variants ); ?></textarea>
				<span class="description">
					<?php esc_html_e( 'One per line, as id|Label|available. Availability is 1 or 0 and defaults to 1. Example: small|Small|1', 'especial-gallery' ); ?><br>
					<?php esc_html_e( 'Leave empty for a product with no options — it gets a single "Standard" option automatically.', 'especial-gallery' ); ?>
				</span>
			</p>

			<p class="eg-meta__row">
				<label for="eg_details"><strong><?php esc_html_e( 'Details', 'especial-gallery' ); ?></strong></label><br>
				<textarea id="eg_details" name="eg_details" rows="5" class="large-text"><?php echo esc_textarea( $details ); ?></textarea>
				<span class="description"><?php esc_html_e( 'One fact per line: material, construction, hardware, size. No storytelling — the object makes its own argument.', 'especial-gallery' ); ?></span>
			</p>

			<div class="eg-meta__row">
				<strong><?php esc_html_e( 'Extra images', 'especial-gallery' ); ?></strong>
				<p class="description">
					<?php esc_html_e( 'The featured image is the resting state. The first extra image is what a tile crossfades to on hover; the rest appear on the product page.', 'especial-gallery' ); ?>
				</p>
				<input type="hidden" id="eg_gallery" name="eg_gallery" value="<?php echo esc_attr( $gallery ); ?>">
				<div id="eg_gallery_preview" class="eg-gallery-preview">
					<?php
					foreach ( array_filter( array_map( 'absint', explode( ',', $gallery ) ) ) as $id ) {
						$thumb = wp_get_attachment_image( $id, 'thumbnail' );
						if ( $thumb ) {
							echo '<span class="eg-gallery-preview__item" data-id="' . esc_attr( $id ) . '">' . wp_kses_post( $thumb ) . '</span>';
						}
					}
					?>
				</div>
				<button type="button" class="button" id="eg_gallery_choose"><?php esc_html_e( 'Choose images', 'especial-gallery' ); ?></button>
				<button type="button" class="button" id="eg_gallery_clear"><?php esc_html_e( 'Clear', 'especial-gallery' ); ?></button>
			</div>

			<p class="eg-meta__row">
				<label for="eg_released_at"><strong><?php esc_html_e( 'Release date', 'especial-gallery' ); ?></strong></label><br>
				<input type="date" id="eg_released_at" name="eg_released_at" value="<?php echo esc_attr( $released ); ?>">
			</p>

			<p class="eg-meta__row">
				<label>
					<input type="checkbox" name="eg_featured" value="1" <?php checked( $featured ); ?>>
					<?php esc_html_e( 'Featured — show in the release row on the front page', 'especial-gallery' ); ?>
				</label>
			</p>

			<p class="eg-meta__row">
				<label>
					<input type="checkbox" name="eg_sold_out" value="1" <?php checked( $sold_out ); ?>>
					<?php esc_html_e( 'Sold out — overrides every option', 'especial-gallery' ); ?>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Saves the product meta box.
	 *
	 * @param int      $post_id Product ID.
	 * @param \WP_Post $post    Product.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		unset( $post );

		// An autosave carries no meta box fields, so writing here would wipe them.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['eg_product_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eg_product_nonce'] ) ), 'eg_save_product' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Money is read as a decimal string and converted with round(), not by
		// casting: (int) ( 18.10 * 100 ) is 1809 on a binary float.
		$price = isset( $_POST['eg_price'] ) ? (float) wp_unslash( $_POST['eg_price'] ) : 0;
		update_post_meta( $post_id, '_eg_price_cents', absint( round( $price * 100 ) ) );

		$compare = isset( $_POST['eg_compare'] ) ? (float) wp_unslash( $_POST['eg_compare'] ) : 0;
		update_post_meta( $post_id, '_eg_compare_at_cents', absint( round( $compare * 100 ) ) );

		update_post_meta(
			$post_id,
			'_eg_details',
			isset( $_POST['eg_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eg_details'] ) ) : ''
		);

		update_post_meta(
			$post_id,
			'_eg_variants',
			isset( $_POST['eg_variants'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eg_variants'] ) ) : ''
		);

		$gallery = isset( $_POST['eg_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['eg_gallery'] ) ) : '';
		$ids     = array_filter( array_map( 'absint', explode( ',', $gallery ) ) );
		update_post_meta( $post_id, '_eg_gallery', implode( ',', $ids ) );

		$released = isset( $_POST['eg_released_at'] ) ? sanitize_text_field( wp_unslash( $_POST['eg_released_at'] ) ) : '';
		update_post_meta( $post_id, '_eg_released_at', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $released ) ? $released : '' );

		update_post_meta( $post_id, '_eg_featured', isset( $_POST['eg_featured'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_eg_sold_out', isset( $_POST['eg_sold_out'] ) ? 1 : 0 );
	}

	/**
	 * The order detail panel — read only, because an order is a record of what
	 * happened rather than a document to revise.
	 *
	 * @param \WP_Post $post Order.
	 * @return void
	 */
	public function render_order( $post ) {
		$lines   = json_decode( (string) get_post_meta( $post->ID, '_eg_lines', true ), true );
		$address = json_decode( (string) get_post_meta( $post->ID, '_eg_address', true ), true );

		$lines   = is_array( $lines ) ? $lines : array();
		$address = is_array( $address ) ? $address : array();
		?>
		<table class="widefat striped">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Reference', 'especial-gallery' ); ?></th>
					<td><?php echo esc_html( (string) get_post_meta( $post->ID, '_eg_reference', true ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'especial-gallery' ); ?></th>
					<td><?php echo esc_html( (string) get_post_meta( $post->ID, '_eg_status', true ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Customer', 'especial-gallery' ); ?></th>
					<td>
						<?php echo esc_html( isset( $address['name'] ) ? $address['name'] : '' ); ?><br>
						<?php echo esc_html( isset( $address['email'] ) ? $address['email'] : '' ); ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Ship to', 'especial-gallery' ); ?></th>
					<td>
						<?php
						foreach ( array( 'line1', 'line2', 'city', 'postcode', 'country' ) as $part ) {
							if ( ! empty( $address[ $part ] ) ) {
								echo esc_html( $address[ $part ] ) . '<br>';
							}
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Items', 'especial-gallery' ); ?></th>
					<td>
						<?php foreach ( $lines as $line ) : ?>
							<?php
							printf(
								'%1$d &times; %2$s (%3$s) — %4$s<br>',
								absint( $line['quantity'] ),
								esc_html( $line['title'] ),
								esc_html( $line['variant_label'] ),
								esc_html( eg_format_price( (int) $line['total_cents'] ) )
							);
							?>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Total', 'especial-gallery' ); ?></th>
					<td>
						<?php esc_html_e( 'Subtotal', 'especial-gallery' ); ?>
						<?php echo esc_html( eg_format_price( (int) get_post_meta( $post->ID, '_eg_subtotal_cents', true ) ) ); ?><br>
						<?php esc_html_e( 'Shipping', 'especial-gallery' ); ?>
						<?php echo esc_html( eg_format_price( (int) get_post_meta( $post->ID, '_eg_shipping_cents', true ) ) ); ?><br>
						<strong>
							<?php esc_html_e( 'Total', 'especial-gallery' ); ?>
							<?php echo esc_html( eg_format_price( (int) get_post_meta( $post->ID, '_eg_total_cents', true ) ) ); ?>
						</strong>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/* ── category artwork ────────────────────────────────────────────────── */

	/**
	 * The image field on the add-category form.
	 *
	 * @return void
	 */
	public function term_field_add() {
		wp_nonce_field( 'eg_save_term', 'eg_term_nonce' );
		?>
		<div class="form-field">
			<label for="eg_term_image"><?php esc_html_e( 'Category image', 'especial-gallery' ); ?></label>
			<input type="hidden" id="eg_term_image" name="eg_term_image" value="">
			<div id="eg_term_image_preview"></div>
			<button type="button" class="button" id="eg_term_image_choose"><?php esc_html_e( 'Choose image', 'especial-gallery' ); ?></button>
			<p class="description"><?php esc_html_e( 'Optional. The newest product in the category is used when this is empty.', 'especial-gallery' ); ?></p>
		</div>
		<?php
	}

	/**
	 * The image field on the edit-category form.
	 *
	 * @param \WP_Term $term Category.
	 * @return void
	 */
	public function term_field_edit( $term ) {
		wp_nonce_field( 'eg_save_term', 'eg_term_nonce' );
		$id = absint( get_term_meta( $term->term_id, '_eg_image_id', true ) );
		?>
		<tr class="form-field">
			<th scope="row"><label for="eg_term_image"><?php esc_html_e( 'Category image', 'especial-gallery' ); ?></label></th>
			<td>
				<input type="hidden" id="eg_term_image" name="eg_term_image" value="<?php echo esc_attr( $id ); ?>">
				<div id="eg_term_image_preview">
					<?php
					if ( $id ) {
						echo wp_kses_post( wp_get_attachment_image( $id, 'thumbnail' ) );
					}
					?>
				</div>
				<button type="button" class="button" id="eg_term_image_choose"><?php esc_html_e( 'Choose image', 'especial-gallery' ); ?></button>
				<p class="description"><?php esc_html_e( 'Optional. The newest product in the category is used when this is empty.', 'especial-gallery' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the category image.
	 *
	 * @param int $term_id Category ID.
	 * @return void
	 */
	public function save_term( $term_id ) {
		if ( ! isset( $_POST['eg_term_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eg_term_nonce'] ) ), 'eg_save_term' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$id = isset( $_POST['eg_term_image'] ) ? absint( wp_unslash( $_POST['eg_term_image'] ) ) : 0;

		if ( $id ) {
			update_term_meta( $term_id, '_eg_image_id', $id );
		} else {
			delete_term_meta( $term_id, '_eg_image_id' );
		}
	}
}
