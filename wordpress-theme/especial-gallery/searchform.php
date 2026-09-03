<?php
/**
 * The search form.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

$eg_search_id = 'eg-search-' . wp_unique_id();
?>
<form role="search" method="get" class="eg-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $eg_search_id ); ?>" class="eg-sr-only">
		<?php esc_html_e( 'Search', 'especial-gallery' ); ?>
	</label>
	<input type="search"
		id="<?php echo esc_attr( $eg_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search', 'especial-gallery' ); ?>">
	<button type="submit" class="eg-link eg-text-sm">
		<?php esc_html_e( 'Search', 'especial-gallery' ); ?>
	</button>
</form>
