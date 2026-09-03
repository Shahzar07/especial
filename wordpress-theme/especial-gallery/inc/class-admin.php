<?php
/**
 * The theme's admin screens.
 *
 * Shop settings and the demo importer. Anything that is design goes to the
 * Customizer, where a person can see the effect; anything that is a credential
 * or a number goes here, where they cannot.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page, importer, and the setup notice.
 */
class Admin {

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_post_eg_import_demo', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'setup_notice' ) );
		add_action( 'after_switch_theme', array( $this, 'on_activate' ) );
	}

	/**
	 * Adds the settings and setup pages under Appearance.
	 *
	 * @return void
	 */
	public function menu() {
		add_theme_page(
			__( 'Especial Gallery settings', 'especial-gallery' ),
			__( 'Shop settings', 'especial-gallery' ),
			'manage_options',
			'eg-settings',
			array( $this, 'render_settings' )
		);

		add_theme_page(
			__( 'Especial Gallery setup', 'especial-gallery' ),
			__( 'Theme setup', 'especial-gallery' ),
			'manage_options',
			'eg-demo',
			array( $this, 'render_setup' )
		);
	}

	/**
	 * Records the activation time so the setup notice can be shown once.
	 *
	 * @return void
	 */
	public function on_activate() {
		if ( ! Demo_Content::imported() ) {
			set_transient( 'eg_show_setup_notice', 1, DAY_IN_SECONDS );
		}
	}

	/**
	 * Points a new shop owner at the importer.
	 *
	 * @return void
	 */
	public function setup_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'eg_show_setup_notice' ) ) {
			return;
		}

		if ( Demo_Content::imported() ) {
			delete_transient( 'eg_show_setup_notice' );
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Especial Gallery is active.', 'especial-gallery' ); ?></strong>
				<?php esc_html_e( 'Import the demo catalogue to get a working shop you can click through, then edit it into your own.', 'especial-gallery' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=eg-demo' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Open theme setup', 'especial-gallery' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Registers the settings, their sections and their sanitisers.
	 *
	 * @return void
	 */
	public function settings() {
		register_setting(
			'eg_settings',
			'eg_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitises the whole settings array.
	 *
	 * Every field is coerced to its type here rather than trusted from the
	 * form, because a settings page is a form like any other.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		$currencies = array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY' );
		$providers  = array( 'none', 'mailchimp', 'klaviyo', 'resend' );

		$currency = isset( $input['currency'] ) ? strtoupper( sanitize_text_field( $input['currency'] ) ) : 'USD';
		$provider = isset( $input['esp_provider'] ) ? sanitize_key( $input['esp_provider'] ) : 'none';

		$out = array(
			'currency'                 => in_array( $currency, $currencies, true ) ? $currency : 'USD',
			'shipping_cents'           => isset( $input['shipping_cents'] ) ? absint( round( (float) $input['shipping_cents'] * 100 ) ) : 0,
			'free_shipping_over_cents' => isset( $input['free_shipping_over_cents'] ) ? absint( round( (float) $input['free_shipping_over_cents'] * 100 ) ) : 0,
			'order_email'              => isset( $input['order_email'] ) ? sanitize_email( $input['order_email'] ) : '',
			'esp_provider'             => in_array( $provider, $providers, true ) ? $provider : 'none',
			'esp_api_key'              => isset( $input['esp_api_key'] ) ? sanitize_text_field( $input['esp_api_key'] ) : '',
			'esp_list_id'              => isset( $input['esp_list_id'] ) ? sanitize_text_field( $input['esp_list_id'] ) : '',
			'stripe_secret_key'        => isset( $input['stripe_secret_key'] ) ? sanitize_text_field( $input['stripe_secret_key'] ) : '',
			'product_slug'             => isset( $input['product_slug'] ) ? sanitize_title( $input['product_slug'] ) : 'product',
			'category_base'            => isset( $input['category_base'] ) ? sanitize_title( $input['category_base'] ) : '',
		);

		if ( ! $out['product_slug'] ) {
			$out['product_slug'] = 'product';
		}

		// The permalink base moved, so the rules have to be rebuilt or every
		// product URL 404s until someone saves the permalink screen by hand.
		if ( eg_option( 'product_slug', 'product' ) !== $out['product_slug']
			|| eg_option( 'category_base', '' ) !== $out['category_base'] ) {
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}

		return $out;
	}

	/**
	 * The settings screen.
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'especial-gallery' ) );
		}

		$shipping = (int) eg_option( 'shipping_cents', 600 );
		$free     = (int) eg_option( 'free_shipping_over_cents', 7500 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Shop settings', 'especial-gallery' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'eg_settings' ); ?>

				<h2><?php esc_html_e( 'Pricing and shipping', 'especial-gallery' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="eg_currency"><?php esc_html_e( 'Currency', 'especial-gallery' ); ?></label></th>
						<td>
							<select id="eg_currency" name="eg_options[currency]">
								<?php foreach ( array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY' ) as $code ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( eg_option( 'currency', 'USD' ), $code ); ?>>
										<?php echo esc_html( $code ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_shipping"><?php esc_html_e( 'Flat shipping', 'especial-gallery' ); ?></label></th>
						<td>
							<input type="number" step="0.01" min="0" id="eg_shipping" name="eg_options[shipping_cents]"
								value="<?php echo esc_attr( number_format( $shipping / 100, 2, '.', '' ) ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Charged on every order below the free-shipping threshold.', 'especial-gallery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_free"><?php esc_html_e( 'Free shipping over', 'especial-gallery' ); ?></label></th>
						<td>
							<input type="number" step="0.01" min="0" id="eg_free" name="eg_options[free_shipping_over_cents]"
								value="<?php echo esc_attr( number_format( $free / 100, 2, '.', '' ) ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Set to 0 to charge shipping on every order.', 'especial-gallery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_order_email"><?php esc_html_e( 'Order notifications', 'especial-gallery' ); ?></label></th>
						<td>
							<input type="email" id="eg_order_email" name="eg_options[order_email]"
								value="<?php echo esc_attr( eg_option( 'order_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_product_slug"><?php esc_html_e( 'Product URL base', 'especial-gallery' ); ?></label></th>
						<td>
							<code><?php echo esc_html( home_url( '/' ) ); ?></code>
							<input type="text" id="eg_product_slug" name="eg_options[product_slug]"
								value="<?php echo esc_attr( eg_option( 'product_slug', 'product' ) ); ?>" class="small-text">
							<code>/your-product/</code>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_category_base"><?php esc_html_e( 'Category URL base', 'especial-gallery' ); ?></label></th>
						<td>
							<code><?php echo esc_html( home_url( '/' ) ); ?></code>
							<input type="text" id="eg_category_base" name="eg_options[category_base]"
								value="<?php echo esc_attr( eg_option( 'category_base', '' ) ); ?>" class="small-text">
							<code>/keychains/</code>
							<p class="description">
								<?php esc_html_e( 'Leave empty to put categories at the root of the site — /keychains rather than /collection/keychains. Give a base if a category slug would collide with a page of the same name.', 'especial-gallery' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Payments', 'especial-gallery' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="eg_stripe"><?php esc_html_e( 'Stripe secret key', 'especial-gallery' ); ?></label></th>
						<td>
							<?php if ( defined( 'EG_STRIPE_SECRET_KEY' ) && EG_STRIPE_SECRET_KEY ) : ?>
								<p><em><?php esc_html_e( 'Set in wp-config.php as EG_STRIPE_SECRET_KEY, which takes precedence over this field.', 'especial-gallery' ); ?></em></p>
							<?php else : ?>
								<input type="password" id="eg_stripe" name="eg_options[stripe_secret_key]" autocomplete="off"
									value="<?php echo esc_attr( eg_option( 'stripe_secret_key', '' ) ); ?>" class="regular-text">
							<?php endif; ?>
							<p class="description">
								<?php esc_html_e( 'With a key set, checkout creates a real Stripe Checkout Session and the customer pays on Stripe\'s own page. With no key the order is recorded and acknowledged instead, so the whole flow is testable before an account exists. Card details never touch this site either way.', 'especial-gallery' ); ?>
							</p>
							<p class="description">
								<strong><?php esc_html_e( 'Better still:', 'especial-gallery' ); ?></strong>
								<?php esc_html_e( 'put the key in wp-config.php as EG_STRIPE_SECRET_KEY. It then stays out of the database and out of a database export.', 'especial-gallery' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Mailing list', 'especial-gallery' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="eg_esp"><?php esc_html_e( 'Provider', 'especial-gallery' ); ?></label></th>
						<td>
							<select id="eg_esp" name="eg_options[esp_provider]">
								<?php
								$providers = array(
									'none'      => __( 'None — keep addresses in WordPress only', 'especial-gallery' ),
									'mailchimp' => __( 'Mailchimp', 'especial-gallery' ),
									'klaviyo'   => __( 'Klaviyo', 'especial-gallery' ),
									'resend'    => __( 'Resend', 'especial-gallery' ),
								);
								foreach ( $providers as $value => $label ) :
									?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( eg_option( 'esp_provider', 'none' ), $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Addresses are always stored in WordPress as well, so a misconfigured key never loses a signup. A failed provider write is logged and the visitor is admitted anyway — a broken webhook must not trap a customer at the door.', 'especial-gallery' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_esp_key"><?php esc_html_e( 'API key', 'especial-gallery' ); ?></label></th>
						<td>
							<input type="password" id="eg_esp_key" name="eg_options[esp_api_key]" autocomplete="off"
								value="<?php echo esc_attr( eg_option( 'esp_api_key', '' ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="eg_esp_list"><?php esc_html_e( 'List or audience ID', 'especial-gallery' ); ?></label></th>
						<td>
							<input type="text" id="eg_esp_list" name="eg_options[esp_list_id]"
								value="<?php echo esc_attr( eg_option( 'esp_list_id', '' ) ); ?>" class="regular-text">
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Subscribers', 'especial-gallery' ); ?></h2>
			<?php $this->render_subscribers(); ?>
		</div>
		<?php
	}

	/**
	 * The stored mailing list, newest first.
	 *
	 * @return void
	 */
	private function render_subscribers() {
		$list = get_option( 'eg_subscribers', array() );
		$list = is_array( $list ) ? array_reverse( $list ) : array();

		if ( ! $list ) {
			echo '<p>' . esc_html__( 'Nobody has signed up yet.', 'especial-gallery' ) . '</p>';
			return;
		}

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: %d: number of subscribers. */
					_n( '%d address on the list.', '%d addresses on the list.', count( $list ), 'especial-gallery' ),
					count( $list )
				)
			)
		);

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Email', 'especial-gallery' ) . '</th>';
		echo '<th>' . esc_html__( 'Joined', 'especial-gallery' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( array_slice( $list, 0, 100 ) as $row ) {
			printf(
				'<tr><td>%1$s</td><td>%2$s</td></tr>',
				esc_html( isset( $row['email'] ) ? $row['email'] : '' ),
				esc_html( isset( $row['date'] ) ? $row['date'] : '' )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * The setup screen.
	 *
	 * @return void
	 */
	public function render_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'especial-gallery' ) );
		}

		$imported = Demo_Content::imported();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Theme setup', 'especial-gallery' ); ?></h1>

			<?php if ( $imported ) : ?>
				<div class="notice notice-success inline">
					<p>
						<?php
						printf(
							/* translators: %s: date and time. */
							esc_html__( 'Demo content was imported on %s. Running it again fills in anything missing and leaves everything that already exists alone.', 'especial-gallery' ),
							esc_html( (string) get_option( 'eg_demo_imported' ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Import the demo shop', 'especial-gallery' ); ?></h2>
			<p><?php esc_html_e( 'This creates:', 'especial-gallery' ); ?></p>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Nine products in two categories, with their photography, prices and specifications.', 'especial-gallery' ); ?></li>
				<li><?php esc_html_e( 'The mailing-list gate, checkout and confirmation pages the bag depends on.', 'especial-gallery' ); ?></li>
				<li><?php esc_html_e( 'Contact, returns, terms and privacy pages, as starting points to rewrite.', 'especial-gallery' ); ?></li>
				<li><?php esc_html_e( 'The header and footer menus, the hero artwork, and pretty permalinks.', 'especial-gallery' ); ?></li>
			</ul>
			<p>
				<?php esc_html_e( 'It is safe to run more than once: every item is matched before it is created, so nothing is duplicated and nothing you have edited is overwritten.', 'especial-gallery' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="eg_import_demo">
				<?php wp_nonce_field( 'eg_import_demo' ); ?>
				<?php
				submit_button(
					$imported
						? __( 'Run the import again', 'especial-gallery' )
						: __( 'Import demo content', 'especial-gallery' ),
					'primary',
					'submit',
					false
				);
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Then', 'especial-gallery' ); ?></h2>
			<ol>
				<li>
					<?php
					printf(
						/* translators: %s: link to the Customizer. */
						wp_kses( __( 'Set your brand, hero artwork and colours in the <a href="%s">Customizer</a>.', 'especial-gallery' ), array( 'a' => array( 'href' => array() ) ) ),
						esc_url( admin_url( 'customize.php' ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link to the settings page. */
						wp_kses( __( 'Set shipping, currency and your payment key under <a href="%s">Shop settings</a>.', 'especial-gallery' ), array( 'a' => array( 'href' => array() ) ) ),
						esc_url( admin_url( 'themes.php?page=eg-settings' ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link to the product list. */
						wp_kses( __( 'Replace the demo catalogue under <a href="%s">Products</a>.', 'especial-gallery' ), array( 'a' => array( 'href' => array() ) ) ),
						esc_url( admin_url( 'edit.php?post_type=' . Post_Types::PRODUCT ) )
					);
					?>
				</li>
				<li><?php esc_html_e( 'Rewrite the terms and privacy pages for your own shop and jurisdiction.', 'especial-gallery' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'The gate', 'especial-gallery' ); ?></h2>
			<p>
				<?php esc_html_e( 'The mailing-list gate is on by default and can be switched off in the Customizer. It is a mailing-list capture, not access control: search engines and social unfurlers are let through on a User-Agent match, which is deliberately spoofable, so that shared links still rank and still preview. Never put anything sensitive behind it.', 'especial-gallery' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'You are seeing the shop without the gate because you are logged in as an editor. Use a private window, or set a preview token in the Customizer, to see what a visitor sees.', 'especial-gallery' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Runs the importer from the setup screen.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'especial-gallery' ) );
		}

		check_admin_referer( 'eg_import_demo' );

		$result = Demo_Content::import();

		delete_transient( 'eg_show_setup_notice' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'eg-demo',
					'eg_imported' => absint( $result['products'] ),
				),
				admin_url( 'themes.php' )
			)
		);
		exit;
	}
}
