<?php
/**
 * The shop: every product.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

$eg_products = array();

while ( have_posts() ) {
	the_post();
	$eg_product = Especial_Gallery\Catalog::product( get_the_ID() );
	if ( $eg_product ) {
		$eg_products[] = $eg_product;
	}
}
?>

<div class="eg-container eg-page">
	<h1 class="eg-display eg-page__title"><?php post_type_archive_title(); ?></h1>

	<?php eg_part( 'components/filter-bar', array( 'count' => count( $eg_products ) ) ); ?>

	<div class="eg-section-body">
		<?php eg_part( 'components/product-grid', array( 'products' => $eg_products, 'eager_count' => 4 ) ); ?>
	</div>

	<?php eg_pagination(); ?>
</div>

<?php
get_footer();
