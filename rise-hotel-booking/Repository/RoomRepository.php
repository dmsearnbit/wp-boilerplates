<?php
include_once( RISE_LOCATION . '/Model/Room.php' );
include_once( RISE_LOCATION . '/Repository/PricingPlansRepository.php' );

class RoomRepository {
	/**
	 * <p><b>Returns an array of room objects that are available between arrival and departure dates</b></p>
	 *
	 * @param $arrivalDate 2021-11-30 00:00:00
	 * @param $departureDate 2021-12-07 23:59:59
	 * @param $numberOfPeople 2
	 * @param $quantity 1
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getAvailableRooms( $arrivalDate, $departureDate, $numberOfPeople, $quantity, $shortDesc = false ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT * FROM $wpdb->posts JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID 
					WHERE $wpdb->posts.post_type = %s 
					AND $wpdb->posts.post_status = %s
					AND $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.meta_value >= %d",
			'rise_room', 'publish', 'rise_room_numberOfAdults', $numberOfPeople
		);

		$roomRows = $wpdb->get_results( $sql );
		$rooms    = array();

		foreach ( $roomRows as $roomRow ) {
			$arrivalDate   = str_replace( '/', '-', $arrivalDate );
			$departureDate = str_replace( '/', '-', $departureDate );
			$price         = PricingPlansController::getTotalPrice( $roomRow->ID, $arrivalDate, $departureDate );
			if ( ! is_int( $price ) ) {
				//If it is not an integer, means the price could not be calculated, so log it and continue to the next room.
				error_log( "Price for the room id: $roomRow->ID 
							for the dates between $arrivalDate and $departureDate was requested but not found. " );
				ActivityLogRepository::addLog(
					__( "Error", "rise-hotel-booking" ),
					sprintf(
						__(
							"Price for the room id: %d for the dates between %s and %s was requested but not found.",
							"rise-hotel-booking"
						),
						$roomRow->ID,
						$arrivalDate,
						$departureDate
					)
				);
				continue;
			}

			if ( BookingRepository::getAvailableRoomAmount( $roomRow->ID, $arrivalDate, $departureDate ) < $quantity ) {
				continue;
			}

			$content = $shortDesc ? get_post_meta( $roomRow->ID, 'rise_room_shortDescription', true ) : $roomRow->post_content;

			$rooms[] = new Room(
				$roomRow->ID,
				$roomRow->post_title,
				$content,
				1,
				$roomRow->meta_value,
				$roomRow->post_status,
				$price
			);


		}

		return $rooms;
	}

	public static function getRoomMetaBoxDetails($roomID, $startDate, $endDate) {
		$availableAmount = BookingRepository::getAvailableRoomAmount( $roomID, $startDate, $endDate );
		$maxNumberOfAdults = get_post_meta( $roomID, 'rise_room_numberOfAdults', true );

		return [
			'availableAmount' => $availableAmount,
			'maxNumberOfAdults' => $maxNumberOfAdults
		];
	}
}