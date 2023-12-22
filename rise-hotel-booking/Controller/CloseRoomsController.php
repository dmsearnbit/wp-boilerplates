<?php

include_once( RISE_LOCATION . '/Repository/CloseRoomsRepository.php' );

class CloseRoomsController {
	public function __construct() {
		// add close rooms page under rise_room post type
		add_action( 'admin_menu', array( $this, 'addPage' ) );
	}


	/**
	 * <p><b>Adds close rooms page under rise_room post type</b></p>
	 */
	public function addPage() {
		add_submenu_page(
			'edit.php?post_type=rise_room',
			__( 'Close Rooms', 'rise-hotel-booking' ),
			__( 'Close Rooms', 'rise-hotel-booking' ),
			'manage_options',
			'rise_close_rooms',
			array( $this, 'closeRoomsHTML' )
		);
	}


	/**
	 * <p><b>Includes close rooms view</b></p>
	 */
	public function closeRoomsHTML() {
		$rooms = get_posts( [
			'post_type'   => 'rise_room',
			'post_status' => 'publish',
			'numberposts' => - 1
		] );

		$page_title = get_admin_page_title();

		include( RISE_LOCATION . '/View/AdminPanel/CloseRooms.php' );
	}


	/**
	 * Handles form input, adds, deletes or updates the dates. Returns nothing.
	 *
	 * @param $roomID
	 * @param $closedDates
	 * @param $closedDatesAction
	 * @param $dateIDs
	 */
	public function handleForm( $roomID, $closedDates, $closedDatesAction, $dateIDs ) {
		$roomID = intval( $roomID );
		for ( $i = 0; $i < count( $closedDates ); $i ++ ) {
			$currentDates  = explode( ' - ', $closedDates[ $i ] );
			$currentAction = sanitize_text_field( $closedDatesAction[ $i ] );
			$currentDateID = intval( $dateIDs[ $i ] );

			$startDate = DateTime::createFromFormat( 'd/m/Y H:i:s', sanitize_text_field( $currentDates[0] ) . ' 00:00:00' );
			$endDate   = DateTime::createFromFormat( 'd/m/Y H:i:s', sanitize_text_field( $currentDates[1] ) . ' 23:59:59' );

			switch ( $currentAction ) {
				case 'add':
					CloseRoomsRepository::addClosedDate( $roomID, $startDate, $endDate );
					break;
				case 'delete':
					CloseRoomsRepository::deleteClosedDate( $currentDateID );
					break;
				case 'update':
					CloseRoomsRepository::updateClosedDate( $currentDateID, $startDate, $endDate );
					break;
			}
		}
	}


	/**
	 * <p><b>Converts start date and end date to the format DateRangePicker needs</b></p>
	 *
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return string
	 */
	public function convertDateType( $startDate, $endDate ) {
		$startDate = DateTime::createFromFormat( 'Y-m-d H:i:s', $startDate )->format( 'd/m/Y' );
		$endDate   = DateTime::createFromFormat( 'Y-m-d H:i:s', $endDate )->format( 'd/m/Y' );

		return $startDate . ' - ' . $endDate;
	}


	/**
	 * <p><b>Returns an array of rows from closed rooms table that belongs to the given room ID</b></p>
	 *
	 * @param $roomID
	 *
	 * @return array|object|null
	 */
	public function getClosedDates( $roomID ) {
		return CloseRoomsRepository::getClosedDates( $roomID );
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1/get-closed-dates',
			'(?P<roomID>\d+)/(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getClosedDatesAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}


	/**
	 * <p><b>API endpoint for getClosedDatesInDateRange function in CloseRoomsRepository.</b></p>
	 *
	 * @param $request
	 *
	 * @return array|false
	 * @throws Exception
	 */
	public function getClosedDatesAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID    = intval( $request['roomID'] );
		$startDate = sanitize_text_field( $request['startDate'] ); // YYYY-MM-DD
		$endDate   = sanitize_text_field( $request['endDate'] ); // YYYY-MM-DD

		return CloseRoomsRepository::getClosedDatesInDateRange( $roomID, $startDate, $endDate );
	}
}