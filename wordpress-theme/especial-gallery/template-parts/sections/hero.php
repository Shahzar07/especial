<?php
/**
 * Template part: the hero banner.
 *
 * The artwork is dark, so every mark set over it is paper rather than ink. It
 * is laid out from its own real dimensions rather than cropped into a fixed
 * container, so artwork of any shape drops in without touching a stylesheet and
 * nothing composed by hand gets silently cut to fit.
 *
 * Art-directed: on mobile the type sits on a continuous ink band BENEATH the
 * portrait crop, because overlaying it ran the headline straight across the
 * object. On desktop the wide crop keeps its left side quiet and the type sits
 * in it.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

$eg_wide   = absint( get_theme_mod( 'eg_hero_image', 0 ) );
$eg_tall   = absint( get_theme_mod( 'eg_hero_image_mobile', 0 ) );
$eg_title  = get_theme_mod( 'eg_hero_title', '' );
$eg_facts  = eg_hero_facts();
$eg_cta    = get_theme_mod( 'eg_hero_cta', __( 'See the object', 'especial-gallery' ) );
$eg_eyebrow = get_theme_mod( 'eg_hero_eyebrow', __( 'Current release', 'especial-gallery' ) );

// A hero with neither artwork nor a headline is not a hero; showing an empty
// black band would be worse than showing nothing.
if ( ! $eg_wide && ! $eg_title ) {
	return;
}

$eg_url = get_theme_mod( 'eg_hero_url', '' );

if ( ! $eg_url ) {
	$eg_newest = Especial_Gallery\Catalog::newest( 1 );
	$eg_url    = $eg_newest ? $eg_newest[0]['permalink'] : ( get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT ) ?: home_url( '/' ) );
}

// The eager/high-priority attributes matter here more than anywhere else on the
// site: this image is the Largest Contentful Paint on the front page.
$eg_image_attributes = array(
	'alt'           => '',
	'aria-hidden'   => 'true',
	'sizes'         => '100vw',
	'loading'       => 'eager',
	'decoding'      => 'sync',
	'fetchpriority' => 'high',
);

/*
 * Art direction through <picture>, not through two <img> elements toggled by
 * CSS. Hiding one with display:none does not reliably stop the browser
 * fetching it, so the CSS approach downloads both crops on every visit and
 * pays for the larger one twice over on the page whose LCP matters most.
 * A <source media> query resolves before the fetch, so exactly one file is
 * ever requested.
 */
$eg_wide_id = $eg_wide ? $eg_wide : $eg_tall;
$eg_tall_id = $eg_tall ? $eg_tall : $eg_wide;

$eg_tall_srcset = $eg_tall_id ? wp_get_attachment_image_srcset( $eg_tall_id, 'full' ) : '';
$eg_wide_srcset = $eg_wide_id ? wp_get_attachment_image_srcset( $eg_wide_id, 'full' ) : '';
?>

<section class="eg-hero">
	<a href="<?php echo esc_url( $eg_url ); ?>" class="eg-hero__link">

		<?php if ( $eg_wide_id ) : ?>
			<div class="eg-hero__media">
				<picture>
					<?php if ( $eg_tall_id && $eg_tall_srcset ) : ?>
						<source
							media="(max-width: 639px)"
							srcset="<?php echo esc_attr( $eg_tall_srcset ); ?>"
							sizes="100vw">
					<?php endif; ?>
					<?php if ( $eg_wide_srcset ) : ?>
						<source
							srcset="<?php echo esc_attr( $eg_wide_srcset ); ?>"
							sizes="100vw">
					<?php endif; ?>
					<?php echo wp_get_attachment_image( $eg_wide_id, 'full', false, $eg_image_attributes ); ?>
				</picture>
			</div>
		<?php endif; ?>

		<div class="eg-hero__overlay">
			<div class="eg-container eg-hero__body">
				<div class="eg-hero__col">
					<?php if ( $eg_eyebrow ) : ?>
						<p class="eg-hero__eyebrow"><?php echo esc_html( $eg_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $eg_title ) : ?>
						<h1 class="eg-display eg-hero__title"><?php echo esc_html( $eg_title ); ?></h1>
					<?php endif; ?>

					<div class="eg-hero__rule"></div>

					<?php if ( $eg_facts ) : ?>
						<ul class="eg-hero__facts">
							<?php foreach ( $eg_facts as $eg_fact ) : ?>
								<li><?php echo esc_html( $eg_fact ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $eg_cta ) : ?>
						<span class="eg-link eg-hero__cta"><?php echo esc_html( $eg_cta ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</a>
</section>
