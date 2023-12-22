<?php

include_once( RISE_LOCATION . '/Model/RoomItem.php' );

class BookingRepository {
	const TABLE_NAME = 'rise_booking_details';

	public static $metaDataKeys = array(
		'rise_customer_coupon',
		'rise_customer_payment_method',
		'rise_customer_booking_status',
		'rise_customer_name_prefix',
		'rise_customer_first_name',
		'rise_customer_last_name',
		'rise_customer_address',
		'rise_customer_city',
		'rise_customer_state',
		'rise_customer_postal_code',
		'rise_customer_email',
		'rise_customer_phone',
		'rise_customer_country',
		'rise_customer_notes'
	);


	/**
	 * <p><b>Returns table name with prefix included</b></p>
	 *
	 * @return string
	 */
	private static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . BookingRepository::TABLE_NAME;
	}


	/**
	 * <p><b>returns number of rooms that are available between given start and end dates.</b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return int|mixed
	 */
	public static function getAvailableRoomAmount( $roomID, $startDate, $endDate ) {
		global $wpdb;

		$roomQuantity = get_post_meta( $roomID, 'rise_room_quantity', true );

		$table   = self::getTableName();
		$sql     = $wpdb->prepare(
			"SELECT COUNT(*) as count FROM $table WHERE room_id = %d AND ( ( checkin_date >= %s AND checkin_date <= %s ) OR ( checkout_date >= %s AND checkout_date <= %s ) )"
			, $roomID, $startDate, $endDate, $startDate, $endDate );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$bookedCount = (int) $results[0]['count'];

		$isRoomClosedBetweenDates = CloseRoomsRepository::isRoomClosedBetweenDates(
			$roomID,
			$startDate,
			$endDate
		);

		return $isRoomClosedBetweenDates ? 0 : $roomQuantity - $bookedCount;
	}


	/**
	 * <p><b>Inserts booking room items into the table</b></p>
	 *
	 * @param $item
	 *
	 * @return bool|int
	 */
	public static function addRoomItem( $item ) {
		global $wpdb;

		$table = self::getTableName();

		// columns and their data to be inserted
		$insertData = array(
			'book_id'          => $item->getBookID(),
			'room_id'          => $item->getRoomID(),
			'plan_id'          => $item->getPlanID(),
			'checkin_date'     => $item->getCheckInDate(),
			'checkout_date'    => $item->getCheckOutDate(),
			'quantity'         => $item->getQuantity(),
			'number_of_people' => $item->getNumberOfPeople(),
			'total_price'      => $item->getTotalPrice(),
		);

		return $wpdb->insert( $table, $insertData );
	}


	/**
	 * <p><b>Returns an array of RoomItems that belong to a book</b></p>
	 *
	 * @param $bookID
	 *
	 * @return array
	 */
	public static function getRoomItems( $bookID ) {
		global $wpdb;

		$table = self::getTableName();

		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE book_id = %d ORDER BY item_id ASC", $bookID );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$items = array();
		foreach ( $results as $row ) {
			array_push( $items, new RoomItem(
				$row['room_id'],
				$row['checkin_date'],
				$row['checkout_date'],
				$row['quantity'],
				$row['total_price'],
				$row['number_of_people'],
				$row['insert_date'],
				$row['item_id'],
				$row['book_id'],
				$row['plan_id']
			) );
		}

		return $items;
	}


	/**
	 * <p><b>Deletes a room item by its ID</b></p>
	 *
	 * @param $itemID
	 *
	 * @return bool|int
	 */
	public static function deleteRoomItem( $itemID ) {
		global $wpdb;
		$table = self::getTableName();

		$where = array(
			'item_id' => $itemID
		);

		return $wpdb->delete( $table, $where );
	}


	/**
	 * <p><b>Updates a room item</b></p>
	 *
	 * @param $item
	 *
	 * @return bool|int
	 */
	public static function updateRoomItem( $item ) {
		global $wpdb;
		$table = self::getTableName();

		$updateData = array(
			'room_id'          => $item->getRoomID(),
			'checkin_date'     => $item->getCheckInDate(),
			'checkout_date'    => $item->getCheckOutDate(),
			'quantity'         => $item->getQuantity(),
			'number_of_people' => $item->getNumberOfPeople(),
			'total_price'      => $item->getTotalPrice(),
		);

		$where = array(
			'item_id' => $item->getItemID()
		);

		return $wpdb->update( $table, $updateData, $where );
	}


	/**
	 * <p><b>Takes an e-mail address as argument,
	 * returns a rise_booking post that has the given e-mail address as the value of rise_customer_email meta key.</b></p>
	 *
	 * @param $email
	 *
	 * @return array|WP_REST_Response
	 */
	public static function getCustomerDetailsByEmail( $email ) {
		$booking = get_posts( array(
			'post_type'   => 'rise_booking',
			'numberposts' => 1,
			'meta_key'    => 'rise_customer_email',
			'meta_value'  => $email
		) );

		if ( empty( $booking ) ) {
			return new WP_REST_Response( array( 'message' => 'Customer not found.', 'status' => 400 ), 400 );
		}

		$postID = $booking[0]->ID;

		$customerDetails = array();
		foreach ( BookingRepository::$metaDataKeys as $key ) {
			$customerDetails[ $key ] = get_post_meta( $postID, $key, true );
		}

		return $customerDetails;
	}


	/**
	 * <p><b>Adds booking and rooms into the database. Returns true if booking added successfully, else returns false.</b></p>
	 *
	 * @param $book
	 * @param $rooms
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function addBooking( $book, $rooms ) {
		global $wpdb;

		$paymentStatus = 'pending';
		switch ( $book->getPaymentMethod() ) {
			case 'iyzico':
			case 'stripe':
				$paymentStatus = 'completed';
				break;
		}

		$post = wp_insert_post( array(
			'post_title'     => __( 'Booking', 'rise-hotel-booking' ) . ' #',
			'post_type'      => 'rise_booking',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array(
				'rise_customer_coupon'         => '',
				'rise_customer_booking_status' => $paymentStatus,
				'rise_customer_name_prefix'    => $book->getTitle(),
				'rise_customer_first_name'     => $book->getFirstName(),
				'rise_customer_last_name'      => $book->getLastName(),
				'rise_customer_address'        => $book->getAddress(),
				'rise_customer_city'           => $book->getCity(),
				'rise_customer_state'          => $book->getState(),
				'rise_customer_postal_code'    => $book->getPostalCode(),
				'rise_customer_country'        => $book->getCountry(),
				'rise_customer_phone'          => $book->getPhone(),
				'rise_customer_email'          => $book->getEmail(),
				'rise_customer_payment_method' => $book->getPaymentMethod(),
				'rise_customer_notes'          => $book->getAdditionalInformation()
			)
		) );

		$addedRoomItems = 0;
		foreach ( $rooms as $room ) {
			if ( $book->getCoupon() ) {
				// creating datetime objects
				$checkInDate = DateTime::createFromFormat(
					'Y-m-d H:i:s',
					$room->getCheckInDate() . ' 00:00:00'
				);

				$checkOutDate = DateTime::createFromFormat(
					'Y-m-d H:i:s',
					$room->getCheckOutDate() . ' 23:59:59'
				);

				$discountedPrice = BookingController::checkCouponAvailability( $book->getCoupon(), $checkInDate, $checkOutDate, $room->getRoomID(), $room->getPlanID() );

				if ( ! is_array( $discountedPrice ) && $discountedPrice ) {
					$discountedPrice = (float) $discountedPrice;
				}

				if ( isset( $discountedPrice ) ) {
					$room->setTotalPrice( $discountedPrice );
				}
			}
			$bookingRoomDetail = array(
				'book_id'          => $post,
				'room_id'          => $room->getRoomID(),
				'plan_id'          => $room->getPlanID(),
				'checkin_date'     => $room->getCheckInDate(),
				'checkout_date'    => $room->getCheckOutDate(),
				'quantity'         => $room->getQuantity(),
				'number_of_people' => $room->getNumberOfPeople(),
				'total_price'      => $room->getTotalPrice()
			);

			$wpdb->insert( BookingRepository::getTableName(), $bookingRoomDetail );
			$addedRoomItems ++;
		}

		$prefix   = @NamePrefix::$prefixes[ $book->getTitle() ];
		$fullName = $prefix . ' ' . $book->getFirstName() . ' ' . $book->getLastName();

		$titleUpdate = wp_update_post( array(
			'ID'         => $post,
			'post_title' => __( 'Booking', 'rise-hotel-booking' ) . ' #' . $post . ' - ' . $fullName
		) );

		update_post_meta( $post, 'rise_customer_coupon', $book->getCoupon() );

		if ( $post && $addedRoomItems == count( $rooms ) && $titleUpdate ) {
			if ( $book->getCoupon() ) {
				@$coupon = get_posts( array(
					'numberposts' => 1,
					'post_type'   => 'rise_coupon',
					'meta_key'    => 'rise_coupon_code',
					'meta_value'  => $book->getCoupon()
				) )[0];

				if ( $coupon ) {
					$remainingAmount = get_post_meta( $coupon->ID, 'rise_coupon_remaining_amount', true );
					if ( (int) $remainingAmount !== - 1 ) {
						update_post_meta( $coupon->ID, 'rise_coupon_remaining_amount', (int) $remainingAmount - 1 );
					}
				}
			}

			return true;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>Returns amount of room items in a booking.</b></p>
	 *
	 * @param $bookingID
	 *
	 * @return string|null
	 */
	public static function getAmountOfRoomItems( $bookingID ) {
		global $wpdb;

		$table = self::getTableName();

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE book_id = %d", $bookingID );

		return $wpdb->get_var( $sql );
	}


	/**
	 * <p><b>deletes all room items that is tied to the given booking id</b></p>
	 *
	 * @param $bookingID
	 *
	 * @return bool
	 */
	public static function deleteAllRoomItems( $bookingID ) {
		global $wpdb;

		$table = self::getTableName();

		return (bool) $wpdb->delete( $table, array(
			'book_id' => $bookingID
		) );


	}
}