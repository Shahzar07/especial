<?php
/**
 * Global helpers.
 *
 * Prices are stored in minor units (cents) so no float ever touches a price,
 * exactly as the source application did. They are formatted once, here.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats a price held in minor units.
 *
 * Whole-dollar prices read cleaner without ".00" in a gallery grid, so the
 * decimals are dropped when the amount divides evenly.
 *
 * @param int    $cents    Amount in minor units.
 * @param string $currency ISO 4217 code.
 * @return string Formatted price, safe to echo after escaping.
 */
function eg_format_price( $cents, $currency = '' ) {
	$cents    = (int) $cents;
	$currency = $currency ? $currency : eg_option( 'currency', 'USD' );

	$symbols = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'CAD' => 'CA$',
		'AUD' => 'A$',
		'JPY' => '¥',
	);
	$symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';

	$decimals = ( 0 === $cents % 100 ) ? 0 : 2;

	return $symbol . number_format_i18n( $cents / 100, $decimals );
}

/**
 * Reads a theme option from the single options array.
 *
 * One option row rather than a row per setting: fewer autoloaded queries, and
 * a rebrand is one update_option() call.
 *
 * @param string $key     Option key.
 * @param mixed  $default Returned when the key is unset or empty.
 * @return mixed
 */
function eg_option( $key, $default = '' ) {
	$options = get_option( 'eg_options', array() );

	if ( ! is_array( $options ) || ! isset( $options[ $key ] ) || '' === $options[ $key ] ) {
		return $default;
	}

	return $options[ $key ];
}

/**
 * Writes a theme option.
 *
 * @param string $key   Option key.
 * @param mixed  $value Value to store.
 * @return void
 */
function eg_update_option( $key, $value ) {
	$options = get_option( 'eg_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}
	$options[ $key ] = $value;
	update_option( 'eg_options', $options );
}

/**
 * The brand name. Falls back to the site title so the theme is never blank.
 *
 * @return string
 */
function eg_brand() {
	$brand = get_theme_mod( 'eg_brand', '' );
	return $brand ? $brand : get_bloginfo( 'name', 'display' );
}

/**
 * The letterspaced wordmark. The space between words is rendered as a gap by
 * the tracking, not as a character, so it is stored with real spaces.
 *
 * @return string
 */
function eg_wordmark() {
	$wordmark = get_theme_mod( 'eg_wordmark', '' );
	return $wordmark ? $wordmark : strtoupper( eg_brand() );
}

/**
 * Builds an HTML class attribute value from a map of class => condition.
 *
 * @param array $classes Map of class name to boolean.
 * @return string Space-separated class list.
 */
function eg_classes( array $classes ) {
	$out = array();
	foreach ( $classes as $name => $enabled ) {
		if ( $enabled ) {
			$out[] = sanitize_html_class( $name );
		}
	}
	return implode( ' ', $out );
}

/**
 * Renders a template part with arguments, on WordPress versions that support it.
 *
 * `get_template_part()` gained a third `$args` parameter in 5.5. This wrapper
 * keeps every call site identical and degrades to a global on anything older,
 * so the theme's stated 6.0 floor is a floor and not a hard dependency.
 *
 * @param string $slug Path under template-parts/, without the extension.
 * @param array  $args Arguments exposed to the part as $args.
 * @return void
 */
function eg_part( $slug, array $args = array() ) {
	get_template_part( 'template-parts/' . $slug, null, $args );
}

/**
 * Returns the URL of a bundled SVG icon, inlined.
 *
 * Icons are 1px-stroke line drawings on a 24 grid that inherit currentColor,
 * so they never introduce a second accent. They are inlined rather than served
 * as <img> precisely so they can inherit colour.
 *
 * @param string $name Icon slug.
 * @param int    $size Pixel size for width and height.
 * @return string SVG markup, already safe.
 */
function eg_icon( $name, $size = 28 ) {
	$file = EG_DIR . '/assets/svg/' . sanitize_file_name( $name ) . '.svg';

	if ( ! file_exists( $file ) ) {
		return '';
	}

	// Local, theme-authored file — not remote content, and never user input.
	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! $svg ) {
		return '';
	}

	$size = absint( $size );
	$svg  = str_replace(
		'<svg',
		'<svg width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" aria-hidden="true" focusable="false"',
		$svg
	);

	return $svg;
}

/**
 * Echoes an inline SVG icon.
 *
 * @param string $name Icon slug.
 * @param int    $size Pixel size.
 * @return void
 */
function eg_the_icon( $name, $size = 28 ) {
	echo wp_kses( eg_icon( $name, $size ), eg_svg_allowed_html() );
}

/**
 * The tag/attribute allowlist for inline SVG, for use with wp_kses().
 *
 * @return array
 */
function eg_svg_allowed_html() {
	return array(
		'svg'    => array(
			'xmlns'          => true,
			'viewbox'        => true,
			'width'          => true,
			'height'         => true,
			'fill'           => true,
			'stroke'         => true,
			'stroke-width'   => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
			'class'          => true,
			'aria-hidden'    => true,
			'focusable'      => true,
			'role'           => true,
		),
		'path'   => array( 'd' => true, 'fill' => true, 'stroke' => true ),
		'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
		'rect'   => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ),
		'line'   => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ),
		'g'      => array( 'fill' => true, 'stroke' => true ),
	);
}

/**
 * Only ever redirect to a path on this origin.
 *
 * Without this, /?eg_next= turns the storefront into an open redirect.
 * Protocol-relative "//evil.com" and "/\evil.com" are both rejected.
 *
 * @param mixed $next Candidate path.
 * @return string A safe same-origin path, defaulting to "/".
 */
function eg_safe_next( $next ) {
	if ( ! is_string( $next ) || '' === $next ) {
		return '/';
	}
	if ( '/' !== substr( $next, 0, 1 ) ) {
		return '/';
	}
	if ( '//' === substr( $next, 0, 2 ) || '/\\' === substr( $next, 0, 2 ) ) {
		return '/';
	}
	return $next;
}
