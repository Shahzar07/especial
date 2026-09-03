<?php
/**
 * Template part: accordion.
 *
 * Built on a real button so the open state is announced, and animated with the
 * grid-rows trick so the height transitions without a measured pixel value and
 * without moving anything else on the page.
 *
 * The panel is rendered open and closed by script on load, so its content is in
 * the document for a search engine and for a browser with JavaScript disabled.
 *
 * @package Especial_Gallery
 *
 * @var array $args {
 *     @type string $title   Trigger label.
 *     @type string $content Escaped HTML content.
 *     @type array  $items   Alternative to content: a list of strings.
 *     @type bool   $open    Whether it starts open.
 * }
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'title'   => '',
		'content' => '',
		'items'   => array(),
		'open'    => false,
	)
);

if ( ! $args['title'] || ( ! $args['content'] && ! $args['items'] ) ) {
	return;
}

$eg_id = 'eg-acc-' . wp_unique_id();
?>

<div class="eg-accordion" data-eg-accordion data-open="<?php echo $args['open'] ? 'true' : 'false'; ?>">
	<h3>
		<button type="button"
			class="eg-accordion__trigger"
			aria-expanded="<?php echo $args['open'] ? 'true' : 'false'; ?>"
			aria-controls="<?php echo esc_attr( $eg_id ); ?>">
			<?php echo esc_html( $args['title'] ); ?>
			<span class="eg-accordion__sign" aria-hidden="true"><?php echo $args['open'] ? '&minus;' : '+'; ?></span>
		</button>
	</h3>
	<div id="<?php echo esc_attr( $eg_id ); ?>" class="eg-accordion__panel" role="region">
		<div class="eg-accordion__inner">
			<div class="eg-accordion__content">
				<?php if ( $args['items'] ) : ?>
					<ul>
						<?php foreach ( $args['items'] as $eg_item ) : ?>
							<li><?php echo esc_html( $eg_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php echo wp_kses_post( $args['content'] ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
