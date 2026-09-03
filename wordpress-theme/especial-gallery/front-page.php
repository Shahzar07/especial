<?php
/**
 * The front page.
 *
 * The order of the sections is the order of the argument: the object, the
 * release, how it is made, what else there is, how to order it, what it is, and
 * finally the prose. The ruled blocks serve the reader who scans; the editorial
 * block at the foot serves the search engine, which is the whole point of
 * keeping long-form copy on a storefront at all.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

$eg_releases   = Especial_Gallery\Catalog::featured( 4 );
$eg_everything = Especial_Gallery\Catalog::newest( (int) get_theme_mod( 'eg_products_per_page', 24 ) );
$eg_shop       = get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT );

/*
 * These two blocks are the theme's own copy rather than Customizer fields.
 * They describe how the shop works, which is the same for every shop built on
 * this theme until the owner edits the file — and eight more Customizer
 * textareas would be a worse trade than one clearly-marked array.
 */
$eg_how_we_work = array(
	array(
		'icon'  => 'edition',
		'title' => __( 'Fixed runs', 'especial-gallery' ),
		'body'  => __( 'Every release is made to a set quantity and not reprinted. When a run is gone it stays gone.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'drop',
		'title' => __( 'One drop time', 'especial-gallery' ),
		'body'  => __( 'Releases go live for everyone at once. No queue, no raffle, no reserved allocation.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'direct',
		'title' => __( 'Sold direct', 'especial-gallery' ),
		'body'  => __( 'Straight from the studio to you. No resellers, no distributors, no marked-up middle.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'hand',
		'title' => __( 'Finished by hand', 'especial-gallery' ),
		'body'  => __( 'Moulded and struck in small batches, then checked and assembled one at a time.', 'especial-gallery' ),
	),
);

$eg_ordering = array(
	array(
		'icon'  => 'ship',
		'title' => __( 'Ships in two days', 'especial-gallery' ),
		'body'  => __( 'Tracked from our own studio. Shipping is calculated at checkout.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'return',
		'title' => __( 'Thirty-day returns', 'especial-gallery' ),
		'body'  => __( 'Unopened items come back for a full refund. Opened ones if they arrived damaged.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'material',
		'title' => __( 'Materials listed', 'especial-gallery' ),
		'body'  => __( 'Every product page states what it is made of and how it was made. Nothing else.', 'especial-gallery' ),
	),
	array(
		'icon'  => 'mail',
		'title' => __( 'List gets it first', 'especial-gallery' ),
		'body'  => __( 'Drops are announced to the mailing list before they appear anywhere else.', 'especial-gallery' ),
	),
);

$eg_specs = array(
	array( __( 'Keychain', 'especial-gallery' ), __( 'Soft moulded PVC, two-sided, raised outline, flat black reverse', 'especial-gallery' ) ),
	array( __( 'Keychain hardware', 'especial-gallery' ), __( 'Nickel-plated split ring and short chain', 'especial-gallery' ) ),
	array( __( 'Pin', 'especial-gallery' ), __( 'Hard enamel, polished flat, black metal plating', 'especial-gallery' ) ),
	array( __( 'Pin fixing', 'especial-gallery' ), __( 'Single post with a butterfly clutch', 'especial-gallery' ) ),
	array( __( 'Packing', 'especial-gallery' ), __( 'Sealed polybag; the pin on a printed backing card', 'especial-gallery' ) ),
);
?>

<?php eg_part( 'sections/hero' ); ?>

<div class="eg-container">

	<?php if ( $eg_releases ) : ?>
		<section class="eg-section eg-section--first">
			<?php eg_section_heading( get_theme_mod( 'eg_heading_release', __( 'Current release', 'especial-gallery' ) ) ); ?>
			<div class="eg-section-body eg-releases">
				<?php foreach ( $eg_releases as $eg_release ) : ?>
					<?php eg_part( 'components/release-card', array( 'product' => $eg_release ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="eg-section">
		<?php eg_section_heading( get_theme_mod( 'eg_heading_how', __( 'How we work', 'especial-gallery' ) ) ); ?>
		<div class="eg-section-body">
			<?php eg_part( 'components/feature-grid', array( 'features' => $eg_how_we_work ) ); ?>
		</div>
	</section>

	<?php if ( Especial_Gallery\Catalog::categories() ) : ?>
		<section class="eg-section">
			<?php eg_section_heading( get_theme_mod( 'eg_heading_categories', __( 'Categories', 'especial-gallery' ) ) ); ?>
			<div class="eg-section-body">
				<?php eg_part( 'components/category-grid' ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $eg_everything ) : ?>
		<section class="eg-section">
			<?php
			eg_section_heading(
				get_theme_mod( 'eg_heading_everything', __( 'Everything', 'especial-gallery' ) ),
				$eg_shop ? $eg_shop : '',
				$eg_shop ? __( 'Browse the shop', 'especial-gallery' ) : ''
			);
			?>
			<div class="eg-section-body">
				<?php eg_part( 'components/product-grid', array( 'products' => $eg_everything ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="eg-section">
		<?php eg_section_heading( get_theme_mod( 'eg_heading_ordering', __( 'Ordering', 'especial-gallery' ) ) ); ?>
		<div class="eg-section-body">
			<?php eg_part( 'components/feature-grid', array( 'features' => $eg_ordering ) ); ?>
		</div>
	</section>

	<section class="eg-section">
		<?php eg_section_heading( get_theme_mod( 'eg_heading_specs', __( 'What the objects are', 'especial-gallery' ) ) ); ?>
		<div class="eg-section-body">
			<?php eg_part( 'components/spec-table', array( 'rows' => $eg_specs ) ); ?>
		</div>
	</section>

	<?php
	/*
	 * The editorial block. This is where the organic traffic lands, so the
	 * long-form copy stays on the page — the ruled blocks above carry the
	 * scanning reader and this carries the search engine.
	 */
	$eg_editorial_id = absint( get_theme_mod( 'eg_editorial_page', 0 ) );
	$eg_editorial    = $eg_editorial_id ? get_post( $eg_editorial_id ) : null;
	?>
	<?php if ( $eg_editorial && 'publish' === $eg_editorial->post_status ) : ?>
		<section class="eg-section">
			<div class="eg-editorial eg-prose">
				<?php echo wp_kses_post( apply_filters( 'the_content', $eg_editorial->post_content ) ); ?>
			</div>
		</section>
	<?php endif; ?>

</div>

<?php
get_footer();
