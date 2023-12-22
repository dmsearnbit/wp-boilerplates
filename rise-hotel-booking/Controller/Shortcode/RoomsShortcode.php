<?php

class RoomsShortcode {
	public function __construct() {
		// register shortcodes
		add_action( 'init', array( $this, 'registerShortcodes' ) );
	}


	/**
	 * <p><b>Registers shortcodes</b></p>
	 */
	public function registerShortcodes() {
		add_shortcode( 'rise_rooms', array( $this, 'roomsShortcode' ) );
	}


	/**
	 * <p><b>Shortcode content for room page</b></p>
	 *
	 * @param $atts
	 *
	 * @return false|string
	 */
	public function roomsShortcode( $atts ) {
		// ob should be used to avoid "Updating failed. The response is not a valid JSON response." error
		ob_start();

		$args = shortcode_atts( array(
			'type' => null,
			'max'  => - 1,
		), $atts );

		// if user didn't specify which type of rooms they want
		if ( ! isset( $args['type'] ) ) {
			$posts = get_posts( array(
					'post_type'   => 'rise_room',
					'numberposts' => $args['max'],
				)
			);

		} else {
			// get term object, so we can search posts by taxonomy term
			$term = get_term_by( 'slug', $args['type'], 'rise_room_type' );

			// get posts
			$posts = get_posts( array(
				'post_type'   => 'rise_room',
				'numberposts' => $args['max'],
				'tax_query'   => array(
					array(
						'taxonomy'         => 'rise_room_type',
						'field'            => 'term_id',
						'terms'            => $term->term_id, // term id
						'include_children' => false
					)
				)
			) );
		}
		include( RISE_LOCATION . '/View/FrontEnd/Rooms.php' );

		return ob_get_clean();
	}
}