<?php
include_once( RISE_LOCATION . '/Repository/RoomRepository.php' );
include_once( RISE_LOCATION . '/Repository/PricingPlansRepository.php' );

class RoomSearchShortcode {
	public function __construct() {
		// register shortcodes
		add_action( 'init', array( $this, 'registerShortcodes' ) );
	}


	/**
	 * <p><b>Registers shortcodes</b></p>
	 */
	public function registerShortcodes() {
		add_shortcode( 'rise_room_search', array( $this, 'roomSearchShortcode' ) );
	}


	/**
	 * <p><b>Returns an array of available rooms in a date range</b></p>
	 *
	 * @param $arrivalDate 2021-11-30 00:00:00
	 * @param $departureDate 2021-12-07 23:59:59
	 * @param $numberOfPeople 2
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getAvailableRooms( $arrivalDate, $departureDate, $numberOfPeople, $quantity ) {
		return RoomRepository::getAvailableRooms( $arrivalDate, $departureDate, $numberOfPeople, $quantity, true );
	}


	/**
	 * <p><b>Sorts given $rooms array of Room items by their price, ascending or descending.</b></p>
	 *
	 * @param Room[] $rooms
	 * @param $order => (string) 'ASC' or 'DESC'
	 *
	 * @return Room[]
	 */
	public static function sortRoomsByPrice( $rooms, $order = 'ASC' ) {
		usort( $rooms, function ( $a, $b ) use ( $order ) {
			if ( strtoupper( $order ) == 'ASC' ) {
				return $a->getTotalPrice() >= $b->getTotalPrice();
			} else {
				return $a->getTotalPrice() < $b->getTotalPrice();
			}
		} );

		return $rooms;
	}


	/**
	 * <p><b>Shortcode content for room search page</b></p>
	 *
	 * @return false|string
	 */
	public function roomSearchShortcode() {
		// ob should be used to avoid "Updating failed. The response is not a valid JSON response." error
		ob_start();

		$submitURL = get_page_link( SettingsRepository::getSetting( 'search-result-page' ) );
		include( RISE_LOCATION . '/View/FrontEnd/RoomSearch.php' );

		return ob_get_clean();
	}


}