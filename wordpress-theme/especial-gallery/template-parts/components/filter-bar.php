<?php
/**
 * Template part: the category filter bar.
 *
 * A single row of text toggles, never dropdowns. The active one takes the
 * accent colour and keeps its underline drawn.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type int    $count   Number of products in the current view.
 *     @type string $current Slug of the active category, if any.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'count'   => 0,
		'current' => '',
	)
);

$eg_categories = Especial_Gallery\Catalog::categories();
$eg_shop       = get_post_type_archive_link( Especial_Gallery\Post_Types::PRODUCT );
?>

<nav class="eg-filters" aria-label="<?php esc_attr_e( 'Filter by category', 'especial-gallery' ); ?>">
	<?php if ( $eg_shop ) : ?>
		<a href="<?php echo esc_url( $eg_shop ); ?>"
			class="eg-link"
			data-active="<?php echo '' === $args['current'] ? 'true' : 'false'; ?>">
			<?php esc_html_e( 'All', 'especial-gallery' ); ?>
		</a>
	<?php endif; ?>

	<?php
	foreach ( $eg_categories as $eg_term ) :
		$eg_link = get_term_link( $eg_term );
		if ( is_wp_error( $eg_link ) ) {
			continue;
		}
		$eg_active = $eg_term->slug === $args['current'];
		?>
		<a href="<?php echo esc_url( $eg_link ); ?>"
			class="eg-link"
			data-active="<?php echo $eg_active ? 'true' : 'false'; ?>"
			<?php echo $eg_active ? 'aria-current="page"' : ''; ?>>
			<?php echo esc_html( $eg_term->name ); ?>
		</a>
	<?php endforeach; ?>

	<span class="eg-tabular eg-filters__count">
		<?php
		printf(
			/* translators: %d: number of products. */
			esc_html( _n( '%d item', '%d items', (int) $args['count'], 'especial-gallery' ) ),
			(int) $args['count']
		);
		?>
	</span>
</nav>
