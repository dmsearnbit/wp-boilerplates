<?php

class CloseRoomsRepository {
	const TABLE_NAME = 'rise_closed_rooms';

	/**
	 * <p><b>Returns table name with prefix included</b></p>
	 *
	 * @return string
	 */
	private static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}


	/**
	 * <p><b>Returns an array of dates on which the given room is closed</b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getClosedDatesInDateRange( $roomID, $startDate, $endDate ) {
		global $wpdb;
		$table = self::getTableName();

		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND (
                              (start_date <= %s AND end_date >= %s) 
                                  OR 
                              (start_date <= %s AND end_date >= %s) 
                                  OR 
                              (start_date >= %s AND end_date <= %s)
                          	)", $roomID, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate );
		$result = $wpdb->get_results( $sql, ARRAY_A );

		$allDates = array();
		foreach ( $result as $row ) {
			$currentStartDate = $row['start_date'];
			$currentEndDate   = $row['end_date'];

			// removing time from date, as createArrayOfDates already appends times at the end of each date
			$currentStartDate = explode( ' ', $currentStartDate )[0];
			$currentEndDate   = explode( ' ', $currentEndDate )[0];

			$dates    = PricingPlansController::createArrayOfDates( $currentStartDate, $currentEndDate, false );
			$allDates = array_merge( $allDates, $dates );
		}

		return $allDates;
	}


	/**
	 * <p><b>Returns an array of rows from closed rooms table that belongs to the given room ID</b></p>
	 *
	 * @param $roomID
	 *
	 * @return array|object|null
	 */
	public static function getClosedDates( $roomID ) {
		global $wpdb;
		$table = self::getTableName();

		$sql = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d", $roomID );

		return $wpdb->get_results( $sql, ARRAY_A );
	}


	/**
	 * <p><b>
	 * Inserts a row into the closed rooms table with given room ID, start date and end date. Returns true on success,
	 * otherwise returns false.
	 * </b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return bool
	 */
	public static function addClosedDate( $roomID, $startDate, $endDate ) {
		global $wpdb;
		$table = self::getTableName();

		$result = $wpdb->insert(
			$table,
			array(
				'room_id'    => $roomID,
				'start_date' => $startDate->format( 'Y-m-d H:i:s' ),
				'end_date'   => $endDate->format( 'Y-m-d H:i:s' )
			)
		);


		$startDate  = $startDate->format( 'Y-m-d' );
		$endDate    = $endDate->format( 'Y-m-d' );
		$logType    = __( 'Room closed', 'rise-hotel-booking' );
		$logDetails = __( 'Room ID', 'rise-hotel-booking' ) . ": $roomID, $startDate - $endDate";
		ActivityLogRepository::addLog( $logType, $logDetails );

		return (bool) $result;
	}


	/**
	 * <p><b>Deletes the row that has the given ID from the table. Returns true on success, otherwise returns false</b></p>
	 *
	 * @param $dateID
	 *
	 * @return bool
	 */
	public static function deleteClosedDate( $dateID ) {
		global $wpdb;
		$table = self::getTableName();

		$result = $wpdb->delete(
			$table,
			array(
				'id' => $dateID
			)
		);

		return (bool) $result;
	}


	/**
	 * <p><b>Updates the row that has the given ID with the given start and end dates.
	 * Returns true on success, otherwise returns false</b></p>
	 *
	 * @param $dateID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return bool
	 */
	public static function updateClosedDate( $dateID, $startDate, $endDate ) {
		global $wpdb;
		$table = self::getTableName();

		$result = $wpdb->update(
			$table,
			array(
				'start_date' => $startDate->format( 'Y-m-d H:i:s' ),
				'end_date'   => $endDate->format( 'Y-m-d H:i:s' )
			),
			array(
				'id' => $dateID
			)
		);

		return (bool) $result;

	}


	/**
	 * <p><b>Returns true if the given room is closed between given dates, otherwise returns false.</b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function isRoomClosedBetweenDates( $roomID, $startDate, $endDate ) {
		$closedDates = self::getClosedDatesInDateRange( $roomID, $startDate, $endDate );

		return ! empty( $closedDates );
	}
}