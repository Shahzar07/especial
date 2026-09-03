<?php
/**
 * The document head and the site header.
 *
 * The gate renders with no header, no footer and no bag — a clean full
 * viewport — so it is excluded here rather than hidden with CSS.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

$eg_on_gate = eg_is_page( 'gate' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( ! $eg_on_gate ) : ?>

	<a class="eg-sr-only eg-skip-link" href="#eg-main">
		<?php esc_html_e( 'Skip to content', 'especial-gallery' ); ?>
	</a>

	<header class="eg-header">
		<div class="eg-container eg-header__inner">
			<?php if ( has_custom_logo() ) : ?>
				<div class="eg-wordmark"><?php the_custom_logo(); ?></div>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="eg-link eg-wordmark">
					<?php echo esc_html( eg_wordmark() ); ?>
				</a>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<nav class="eg-nav" aria-label="<?php esc_attr_e( 'Categories', 'especial-gallery' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'depth'          => 1,
							'walker'         => new EG_Walker_Nav(),
							'items_wrap'     => '<ul>%3$s</ul>',
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<div class="eg-header__actions">
				<?php
				$eg_contact = eg_page_url( 'contact' );
				$eg_account = get_option( 'users_can_register' ) ? wp_login_url() : $eg_contact;
				?>
				<?php if ( $eg_account ) : ?>
					<?php /* There is no sign-in until registration is enabled, so this points at
					         the page that actually answers order questions rather than at a login
					         that does not exist. When accounts arrive it is one href. */ ?>
					<a href="<?php echo esc_url( $eg_account ); ?>"
						class="eg-header__action"
						title="<?php esc_attr_e( 'Account and orders', 'especial-gallery' ); ?>">
						<span class="eg-sr-only"><?php esc_html_e( 'Account and orders', 'especial-gallery' ); ?></span>
						<?php eg_the_icon( 'account', 20 ); ?>
					</a>
				<?php endif; ?>

				<button type="button"
					class="eg-header__action"
					data-eg-bag-open
					aria-controls="eg-drawer"
					aria-expanded="false">
					<span class="eg-sr-only" data-eg-bag-label><?php esc_html_e( 'Open bag', 'especial-gallery' ); ?></span>
					<?php eg_the_icon( 'bag', 20 ); ?>
					<?php eg_bag_count(); ?>
				</button>
			</div>
		</div>
	</header>

	<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="eg-mobile-nav" aria-label="<?php esc_attr_e( 'Categories', 'especial-gallery' ); ?>">
			<div class="eg-container">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 1,
						'walker'         => new EG_Walker_Nav(),
						'items_wrap'     => '<ul>%3$s</ul>',
					)
				);
				?>
			</div>
		</nav>
	<?php endif; ?>

<?php endif; ?>

<main id="eg-main">
