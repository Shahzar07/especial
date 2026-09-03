<?php
/**
 * Navigation walker.
 *
 * The header nav is a flat row of text links with the underline-on-hover
 * vocabulary and an accent-coloured active state. Core's walker emits a stack
 * of classes this design has no use for, so this trims it to what the system
 * actually styles.
 *
 * @package Especial_Gallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * A minimal single-level nav walker.
 */
class EG_Walker_Nav extends Walker_Nav_Menu {

	/**
	 * Opens a menu item.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Menu arguments.
	 * @param int      $id     Menu item ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		unset( $depth, $args, $id );

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$active  = in_array( 'current-menu-item', $classes, true )
			|| in_array( 'current_page_item', $classes, true )
			|| in_array( 'current-menu-ancestor', $classes, true );

		$output .= '<li>';
		$output .= sprintf(
			'<a href="%1$s" class="eg-link"%2$s%3$s>%4$s</a>',
			esc_url( $item->url ),
			$active ? ' data-active="true"' : '',
			$active ? ' aria-current="page"' : '',
			esc_html( $item->title )
		);
	}

	/**
	 * Closes a menu item.
	 *
	 * @param string  $output Accumulated markup, by reference.
	 * @param WP_Post $item   Menu item.
	 * @param int     $depth  Depth.
	 * @param mixed   $args   Menu arguments.
	 * @return void
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		unset( $item, $depth, $args );
		$output .= '</li>';
	}

	/**
	 * Sub-menus are not part of this design. Dropping them here rather than
	 * hiding them in CSS keeps the markup honest about what is reachable.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Menu arguments.
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		unset( $output, $depth, $args );
	}

	/**
	 * Closes a sub-menu. See start_lvl().
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Menu arguments.
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		unset( $output, $depth, $args );
	}
}
