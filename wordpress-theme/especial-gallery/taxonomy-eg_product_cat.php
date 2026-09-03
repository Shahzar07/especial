<?php
/**
 * A product category.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

get_header();

$eg_term     = get_queried_object();
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
	<h1 class="eg-display eg-page__title"><?php echo esc_html( $eg_term ? $eg_term->name : '' ); ?></h1>

	<?php if ( $eg_term && $eg_term->description ) : ?>
		<p class="eg-page__meta"><?php echo esc_html( $eg_term->description ); ?></p>
	<?php endif; ?>

	<?php eg_part( 'components/filter-bar', array( 'count' => count( $eg_products ), 'current' => $eg_term ? $eg_term->slug : '' ) ); ?>

	<div class="eg-section-body">
		<?php
		if ( $eg_products ) {
			eg_part( 'components/product-grid', array( 'products' => $eg_products, 'eager_count' => 4 ) );
		} else {
			echo '<p class="eg-empty">' . esc_html__( 'Nothing in this category yet.', 'especial-gallery' ) . '</p>';
		}
		?>
	</div>

	<?php eg_pagination(); ?>
</div>

<?php
get_footer();
