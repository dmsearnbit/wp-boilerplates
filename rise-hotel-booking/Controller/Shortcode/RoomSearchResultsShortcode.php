<?php

class RoomSearchResultsShortcode {
	public function __construct() {
		// register shortcodes
		add_action( 'init', array( $this, 'registerShortcodes' ) );
	}


	/**
	 * <p><b>Registers shortcodes</b></p>
	 */
	public function registerShortcodes() {
		add_shortcode( 'rise_room_search_results', array( $this, 'roomSearchResultsShortcode' ) );
	}


	/**
	 * <p><b>Shortcode content for room search results page</b></p>
	 *
	 * @return false|string
	 */
	public function roomSearchResultsShortcode() {
		// ob should be used to avoid "Updating failed. The response is not a valid JSON response." error
		ob_start();

		$submitURL = get_page_link( SettingsRepository::getSetting( 'search-result-page' ) );
		include( RISE_LOCATION . '/View/FrontEnd/RoomSearchResults.php' );

		return ob_get_clean();
	}


	/**
	 * <p><b>returns checkout page url</b></p>
	 * @return string
	 */
	public function getCheckoutURL() {
		return get_page_link( SettingsRepository::getSetting( 'room-checkout-page' ) );
	}


	/**
	 * <p><b>returns search results page url</b></p>
	 * @return string
	 */
	public function getSearchResultsURL() {
		return get_page_link( SettingsRepository::getSetting( 'search-result-page' ) );
	}


	/**
	 * @param $roomID
	 * @param $checkinDate
	 * @param $checkoutDate
	 * @param $quantity
	 *
	 * @return bool
	 */
	public function isRoomAvailable( $roomID, $checkinDate, $checkoutDate, $quantity ) {
		return BookingRepository::getAvailableRoomAmount( $roomID, $checkinDate, $checkoutDate ) >= $quantity;
	}
}