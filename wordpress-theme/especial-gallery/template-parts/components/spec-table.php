<?php
/**
 * Template part: the specification table.
 *
 * The same hairline logic as the feature grid, but rows rather than cells,
 * because a spec sheet is read down and not across.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type array $rows List of [term, value] pairs.
 * }
 */

defined( 'ABSPATH' ) || exit;

$eg_rows = isset( $args['rows'] ) ? (array) $args['rows'] : array();

if ( ! $eg_rows ) {
	return;
}
?>

<dl class="eg-specs">
	<?php foreach ( $eg_rows as $eg_row ) : ?>
		<div class="eg-specs__row">
			<dt><?php echo esc_html( $eg_row[0] ); ?></dt>
			<dd><?php echo esc_html( $eg_row[1] ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
