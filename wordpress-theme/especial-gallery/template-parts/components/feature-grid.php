<?php
/**
 * Template part: the ruled feature grid.
 *
 * The dividing lines are the 1px gaps between cells, letting the container's
 * rule-coloured ground show through — so the block reads as a ruled table
 * rather than as a row of panels, and needs no radius, no shadow and no fill to
 * hold together. That is what keeps it inside a system that forbids all three.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array $features List of icon/title/body arrays.
 *     @type int   $columns  2, 3 or 4.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'features' => array(),
		'columns'  => 4,
	)
);

if ( ! $args['features'] ) {
	return;
}

$eg_modifier = in_array( (int) $args['columns'], array( 2, 3 ), true )
	? ' eg-features--' . (int) $args['columns']
	: '';
?>

<div class="eg-features<?php echo esc_attr( $eg_modifier ); ?>">
	<?php foreach ( $args['features'] as $eg_feature ) : ?>
		<div class="eg-feature">
			<?php if ( ! empty( $eg_feature['icon'] ) ) : ?>
				<span class="eg-feature__icon"><?php eg_the_icon( $eg_feature['icon'], 28 ); ?></span>
			<?php endif; ?>
			<h3><?php echo esc_html( $eg_feature['title'] ); ?></h3>
			<p><?php echo esc_html( $eg_feature['body'] ); ?></p>
		</div>
	<?php endforeach; ?>
</div>
