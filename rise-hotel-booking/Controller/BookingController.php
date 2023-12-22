<?php

include_once( RISE_LOCATION . '/Model/Country.php' );
include_once( RISE_LOCATION . '/Model/PaymentMethod.php' );
include_once( RISE_LOCATION . '/Model/BookingStatus.php' );
include_once( RISE_LOCATION . '/Model/NamePrefix.php' );
include_once( RISE_LOCATION . '/Model/Booking.php' );
include_once( RISE_LOCATION . '/Model/RoomItem.php' );
include_once( RISE_LOCATION . '/Repository/BookingRepository.php' );
include_once( RISE_LOCATION . '/Repository/PricingPlansRepository.php' );
include_once( RISE_LOCATION . '/Repository/SettingsRepository.php' );

class BookingController {
	public function __construct() {
		// register custom post type booking
		add_action( 'init', array( $this, 'createPostType' ) );

		// register meta boxes for booking
		add_action( 'add_meta_boxes', array( $this, 'createMetaBoxes' ) );

		// save meta data when post is saved
		add_action( 'save_post', array( $this, 'saveMetaData' ) );

		// create custom columns for booking
		add_filter( 'manage_rise_booking_posts_columns', array( $this, 'setCustomColumns' ) );

		// add data to custom columns
		add_action( 'manage_rise_booking_posts_custom_column', array( $this, 'addDataToCustomColumns' ), 10, 2 );

		// delete post
		add_action( 'delete_post', array( $this, 'deletePost' ) );
	}


	/**
	 * <p><b>create custom post type for bookings</b></p>
	 */
	public function createPostType() {
		$postTypeData = array(
			'public'              => true,
			'has_archive'         => true,
			'supports'            => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'labels'              => array(
				'name'          => __( 'Bookings', 'rise-hotel-booking' ),
				'singular_name' => __( 'Booking', 'rise-hotel-booking' )
			),
			'rewrite'             => array(
				'slug' => 'hotel-bookings'
			),
			'menu_position'       => 1,
			'show_in_menu'        => 'edit.php?post_type=rise_room'
		);

		register_post_type( 'rise_booking', $postTypeData );
	}


	/**
	 * <p><b>creates custom columns</b></p>
	 */
	public function setCustomColumns( $columns ) {
		unset( $columns['date'] );

		$columns['checkin_date']   = __( 'Check-in Date', 'rise-hotel-booking' );
		$columns['checkout_date']  = __( 'Check-out Date', 'rise-hotel-booking' );
		$columns['booking_status'] = __( 'Booking Status', 'rise-hotel-booking' );
		$columns['grand_total']    = __( 'Grand Total', 'rise-hotel-booking' );
		$columns['date']           = __( 'Date', 'rise-hotel-booking' );

		return $columns;
	}


	/**
	 * <p><b>adds check-in, check-out, booking status and grand total to custom columns</b></p>
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function addDataToCustomColumns( $column, $post_id ) {
		$roomItemsAmount = BookingRepository::getAmountOfRoomItems( $post_id );
		$roomItems       = BookingRepository::getRoomItems( $post_id );

		switch ( $column ) {
			case 'checkin_date':
				if ( $roomItemsAmount <= 0 ) {
					_e( 'No rooms in booking', 'rise-hotel-booking' );
					break;
				}

				if ( $roomItemsAmount > 1 ) {
					_e( 'Multiple Rooms', 'rise-hotel-booking' );
					break;
				}
				$date = DateTime::createFromFormat( 'Y-m-d H:i:s', wp_kses( $roomItems[0]->getCheckInDate(), array() ) )->format( 'Y-m-d' );
				echo wp_kses( $date, array() );
				break;
			case 'checkout_date':
				if ( $roomItemsAmount <= 0 ) {
					_e( 'No rooms in booking', 'rise-hotel-booking' );
					break;
				}

				if ( $roomItemsAmount > 1 ) {
					_e( 'Multiple Rooms', 'rise-hotel-booking' );
					break;
				}
				$date = DateTime::createFromFormat( 'Y-m-d H:i:s', wp_kses( $roomItems[0]->getCheckOutDate(), array() ) )->format( 'Y-m-d' );
				echo wp_kses( $date, array() );
				break;
			case 'booking_status':
				$bookingStatus = get_post_meta( intval( $post_id ), 'rise_customer_booking_status', true );
				echo wp_kses( BookingStatus::$status[ $bookingStatus ], array() );
				break;
			case 'grand_total':
			
				if ($roomItemsAmount <= 0) {
					_e('No rooms in booking', 'rise-hotel-booking');
					break;
				}
				$currency = wp_kses( Currency::$currencies[ SettingsRepository::getSetting( 'currency' ) ], array() );

				$subTotal   = BookingController::calculateTotalRoomPrice( $roomItems );
				$grandTotal = $subTotal + BookingController::calculateTax( floatval( $subTotal ) );
				$grandTotal = $currency . number_format( floatval( $grandTotal ), 2 );
				echo $grandTotal;
				break;
		}
	}


	/**
	 * <p><b>create meta boxes for bookings</b></p>
	 */
	public function createMetaBoxes() {
		add_meta_box(
			'rise_booking_information',
			__( 'Booking Information', 'rise-hotel-booking' ),
			array( $this, 'bookingInformationHTML' ),
			'rise_booking',
			'normal',
			'high'
		);

		add_meta_box(
			'rise_booking_items',
			__( 'Booking Items', 'rise-hotel-booking' ),
			array( $this, 'bookingItemsHTML' ),
			'rise_booking',
			'normal',
			'low'
		);
	}


	/**
	 * <p><b>Booking Information meta box content</b></p>
	 *
	 * @param $post
	 */
	public function bookingInformationHTML( $post ) {
		$ID       = get_the_ID();
		$bookDate = get_the_date( 'F j, Y, H:i:s' );
		$editing  = false;

		if ( $post->filter == 'edit' ) {
			$editing  = true;
			$metaData = array();
			foreach ( BookingRepository::$metaDataKeys as $key ) {
				$metaData[ $key ] = get_post_meta( $ID, $key, true );
			}

			$namePrefix = @NamePrefix::$prefixes[ sanitize_text_field( $metaData['rise_customer_name_prefix'] ) ];
			$firstName  = sanitize_text_field( $metaData['rise_customer_first_name'] );
			$lastName   = sanitize_text_field( $metaData['rise_customer_last_name'] );
			$name       = $namePrefix . ' ' . $firstName . ' ' . $lastName;

			$address    = sanitize_text_field( $metaData['rise_customer_address'] );
			$city       = sanitize_text_field( $metaData['rise_customer_city'] );
			$state      = sanitize_text_field( $metaData['rise_customer_state'] );
			$postalCode = sanitize_text_field( $metaData['rise_customer_postal_code'] );
			$country    = Country::$countries[ sanitize_text_field( $metaData['rise_customer_country'] ) ];
			$address    .= ' ' . $city . ' ' . $state . ' ' . $postalCode . ' ' . $country;

			$email = sanitize_email( $metaData['rise_customer_email'] );
			$phone = filter_var( $metaData['rise_customer_phone'], FILTER_SANITIZE_NUMBER_INT );
		}

		include( RISE_LOCATION . '/View/AdminPanel/BookingInformationMetaBox.php' );
	}


	/**
	 * <p><b>Booking Items meta box content</b></p>
	 *
	 * @param $post
	 */
	public function bookingItemsHTML( $post ) {
		// get all the rooms
		$rooms = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => 'rise_room'
		) );

		// get necessary settings
		$tax             = SettingsRepository::getSetting( 'tax' );
		$advance_payment = SettingsRepository::getSetting( 'advance-payment' );
		$currency        = SettingsRepository::getSetting( 'currency' );
		$currency        = Currency::$currencies[ $currency ];

		$editing      = false;
		$allowEditing = false;
		if ( SettingsRepository::getSetting( 'allow-editing-bookings' ) == 'true' ) {
			$allowEditing = true;
		}

		if ( $post->filter == 'edit' ) {
			$editing = true;
			$items   = BookingRepository::getRoomItems( $post->ID );
		}

		include( RISE_LOCATION . '/View/AdminPanel/BookingItemsMetaBox.php' );
	}


	/**
	 * <p><b>save the data in meta box</b></p>
	 *
	 * @param $postID
	 */
	public function saveMetaData( $postID ) {
		// checking if 'get_current_screen' function exists, because it's not available sometimes.
		// checking if current post type is rise_booking as well.
		if ( function_exists( 'get_current_screen' ) ) {
			if ( ! isset( get_current_screen()->post_type ) ) {
				return;
			}

			if ( get_current_screen()->post_type != 'rise_booking' ) {
				return;
			}

			if ( get_post_status( $postID ) == 'trash' ) {
				return;
			}

			$metaData = array();
			// meta data keys are already defined in booking repository, so we just loop through all of them
			// and check if the key exists in $_POST. if it exists, we append the $metaData array with the meta data.
			foreach ( BookingRepository::$metaDataKeys as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					if ( $key == 'rise_customer_email' ) {
						$metaData[ $key ] = filter_var( $_POST[ $key ], FILTER_SANITIZE_EMAIL );
					} elseif ( $key == 'rise_customer_phone' ) {
						$metaData[ $key ] = filter_var( $_POST[ $key ], FILTER_SANITIZE_NUMBER_INT );
					} else {
						$metaData[ $key ] = sanitize_text_field( $_POST[ $key ] );
					}
				}
			}

			$prefix             = @NamePrefix::$prefixes[ sanitize_text_field( $metaData['rise_customer_name_prefix'] ) ];
			$fullName           = sanitize_text_field( @$metaData['rise_customer_first_name'] ) . ' ' . sanitize_text_field( @$metaData['rise_customer_last_name'] );
			$fullNameWithPrefix = $prefix . ' ' . $fullName;

			// creating an array that contains booking data
			$data = array(
				'ID'         => $postID,
				'post_title' => __( 'Booking', 'rise-hotel-booking' ) . ' #' . $postID . ' - ' . $fullNameWithPrefix,
				'meta_input' => $metaData
			);

			// unhook this function from save_post hook to prevent infinite loop
			remove_action( 'save_post', array( $this, 'saveMetaData' ) );

			wp_update_post( $data );

			// hook it again after updating the post
			add_action( 'save_post', array( $this, 'saveMetaData' ) );

			// if there was any room added
			if ( isset( $_POST['rise_room_id'] ) ) {
				// loop through each one of them, $i being the index
				foreach ( $_POST['rise_room_id'] as $i => $roomID ) {
					// create a room item object
					$room = new RoomItem(
						intval( $roomID ),
						sanitize_text_field( $_POST['rise_checkin_date'][ $i ] ),
						sanitize_text_field( $_POST['rise_checkout_date'][ $i ] ),
						intval( $_POST['rise_quantity'][ $i ] ),
						str_replace( ',', '', sanitize_text_field( $_POST['rise_total_price'][ $i ] ) ),
						intval( $_POST['rise_number_of_people'][ $i ] ),
						null,
						null,
						$postID,
						intval( $_POST['rise_plan_id'][ $i ] )
					);

					// check the action and do what's needed
					switch ( sanitize_text_field( $_POST['rise_action'][ $i ] ) ) {
						case 'add':
							BookingRepository::addRoomItem( $room );
							break;
						case 'update':
							if ( SettingsRepository::getSetting( 'allow-editing-bookings' ) == 'true' ) {
								$room->setItemID( intval( $_POST['rise_item_id'][ $i ] ) );
								BookingRepository::updateRoomItem( $room );
							}
							break;
						case 'delete':
							BookingRepository::deleteRoomItem( intval( $_POST['rise_item_id'][ $i ] ) );
							break;
					}
				}
			}
		}
	}


	/**
	 * <p><b>deletes all room items when the booking is deleted</b></p>
	 *
	 * @param $postID
	 *
	 * @return bool
	 */
	public function deletePost( $postID ) {
		$post = get_post( $postID );
		if ( $post->post_type != 'rise_booking' ) {
			return false;
		}

		return BookingRepository::deleteAllRoomItems( $postID );
	}


	/**
	 * <p><b>Converts given check-in and check-out dates to be shown in front-end booking page.</b></p>
	 *
	 * @param $checkIn
	 * @param $checkOut
	 *
	 * @return string
	 * @throws Exception
	 */
	public function convertDatesToTableType( $checkIn, $checkOut ) {
		$checkIn  = new DateTime( $checkIn );
		$checkOut = new DateTime( $checkOut );

		return $checkIn->format( 'F d, Y' ) . ' - ' . $checkOut->format( 'F d, Y' );
	}


	/**
	 * <p><b>Returns the amount of nights between two dates.</b></p>
	 *
	 * @param $checkIn
	 * @param $checkOut
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getNumberOfNights( $checkIn, $checkOut ) {
		$checkIn  = new DateTime( $checkIn );
		$checkOut = new DateTime( $checkOut );

		return $checkOut->diff( $checkIn )->format( "%a" );
	}


	/**
	 * <p><b>Returns array containing error message if</b></p>
	 * * Coupon was not found
	 * * Current date is not between utilization dates of coupon
	 * * Usage limit of the coupon has been reached
	 *
	 * <p><b>Returns false if</b></p>
	 * * Check-in and check-out dates are not between reservation dates of coupon
	 *
	 * <p><b>Returns discount percentage if everything's OK</b></p>
	 *
	 * @param $couponCode
	 * @param $checkInDate
	 * @param $checkOutDate
	 * @param $roomID
	 * @param bool $setSession
	 *
	 * @return array|false|float|int
	 * @throws Exception
	 */
	public static function checkCouponAvailability( $couponCode, $checkInDate, $checkOutDate, $roomID, $planID, $setSession = true ) {
		// TODO: pass plan ID and calculate discounted price by the given plan
		// coupon post
		@$coupon = get_posts( array(
			'numberposts' => 1,
			'post_type'   => 'rise_coupon',
			'meta_key'    => 'rise_coupon_code',
			'meta_value'  => $couponCode
		) )[0];


		// if nothing returned from the first get_posts call in function, that means coupon was not found.
		if ( empty( $coupon ) ) {
			return array(
				'message' => __( 'Coupon not found.', 'rise-hotel-booking' ),
				'type'    => 'invalid',
				'status'  => 400
			);
		}

		// get remaining amount of coupon
		$remainingAmount = get_post_meta( $coupon->ID, 'rise_coupon_remaining_amount', true );

		// get reservation dates of the coupon
		$reservationDateStart = get_post_meta( $coupon->ID, 'rise_coupon_reservation_dates_start', true );
		$reservationDateEnd   = get_post_meta( $coupon->ID, 'rise_coupon_reservation_dates_end', true );

		// get utilization dates of the coupon
		$utilizationDateStart = get_post_meta( $coupon->ID, 'rise_coupon_utilization_dates_start', true );
		$utilizationDateEnd   = get_post_meta( $coupon->ID, 'rise_coupon_utilization_dates_end', true );

		// get coupon quantity and discount percentage of the coupon
		$quantity           = get_post_meta( $coupon->ID, 'rise_coupon_quantity', true );
		$discountPercentage = (float) get_post_meta( $coupon->ID, 'rise_coupon_percentage', true );

		// create datetime objects from utilization date as we need objects to compare the dates
		$utilizationDateStart = DateTime::createFromFormat( 'Y-m-d H:i:s', $utilizationDateStart );
		$utilizationDateEnd   = DateTime::createFromFormat( 'Y-m-d H:i:s', $utilizationDateEnd );

		// create datetime objects from reservation dates
		$reservationDateStart = DateTime::createFromFormat( 'Y-m-d H:i:s', $reservationDateStart );
		$reservationDateEnd   = DateTime::createFromFormat( 'Y-m-d H:i:s', $reservationDateEnd );

		// create datetime object from current date and time of the same format as reservation and utilization dates
		$currentDate = DateTime::createFromFormat( 'Y-m-d H:i:s', date( 'Y-m-d H:i:s' ) );

		// check if current date is between utilization dates
		if ( ! ( ( $utilizationDateStart <= $currentDate ) && ( $currentDate <= $utilizationDateEnd ) ) ) {
			return array(
				'message' => __( 'Coupon is expired or not activated yet.', 'rise-hotel-booking' ),
				'type'    => 'not_available',
				'status'  => 400
			);
		}

		// check if coupon limit is not -1 (unlimited), and count of bookings used this coupon exceeds
		// usage limit of the coupon
		if ( $quantity != - 1 && $remainingAmount <= 0 ) {
			return array(
				'message' => __( 'Coupon usage limit has been reached.', 'rise-hotel-booking' ),
				'type'    => 'usage_limit_reached',
				'status'  => 400
			);
		}

		// total price of room
		$roomPrices             = PricingPlansRepository::getPrices( $roomID, $checkInDate->format( 'Y-m-d' ), $checkOutDate->format( 'Y-m-d' ), $planID );
		$discountedTotalPrice   = 0;
		$amountOfDaysDiscounted = 0;

		// removing last element because we don't include check-out when calculating total room price
		array_pop( $roomPrices );

		// array of dates between check-in and check-out dates
		$dates = PricingPlansController::createArrayOfDates( $checkInDate->format( 'Y-m-d' ), $checkOutDate->format( 'Y-m-d' ) );


		// loop through all the dates between check-in and check-out
		foreach ( $dates as $date ) {
			$currentPrice = (float) $roomPrices[ $date ];

			// current date as an object
			$currentDate = DateTime::createFromFormat( 'Y-m-d H:i:s', $date . ' 00:00:00' );

			// if current date is between reservation date start and end, add the discounted price to the total price.
			if ( ( $reservationDateStart <= $currentDate ) && ( $currentDate <= $reservationDateEnd ) ) {
				$discountedCurrentPrice = $currentPrice - ( ( $currentPrice / 100 ) * $discountPercentage );
				$discountedTotalPrice   += $discountedCurrentPrice;
				$amountOfDaysDiscounted ++;
			} else {
				$discountedTotalPrice += $currentPrice;
			}
		}

		// checking if check-in and check-out dates are between coupon's reservation dates
		if ( $amountOfDaysDiscounted !== 0 ) {
			if ( $setSession ) {
				$_SESSION['rise_result_coupon'] = $couponCode;
			}

			return $discountedTotalPrice;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1',
			'/check-availability/(?P<roomID>\d+)/(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'checkAvailabilityAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1',
			'/get-room-information/(?P<roomID>\d+)/(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)/(?P<quantity>\d+)/(?P<planID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRoomInformationAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1',
			'/get-customer-details-by-email/(?P<email>\S+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getCustomerDetailsByEmailAPI' ),
				'permission_callback' => function () {
					return true;
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1',
			'/check-coupon-availability/(?P<couponCode>\S+)/(?P<checkInDate>[a-zA-Z0-9-]+)/(?P<checkOutDate>[a-zA-Z0-9-]+)/(?P<roomID>\d+)/(?P<planID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'checkCouponAvailabilityAPI' ),
				'permission_callback' => function () {
					return true;
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1',
			'/remove-coupon',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'removeCouponAPI' ),
				'permission_callback' => function () {
					return true;
				}
			)
		);
	}


	/**
	 * <p><b>API endpoint for getAvailableRoomAmount function in BookingRepository</b></p>
	 *
	 * @param $request
	 *
	 * @return mixed
	 */
	public function checkAvailabilityAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID    = intval( $request['roomID'] );
		$startDate = sanitize_text_field( $request['startDate'] );
		$endDate   = sanitize_text_field( $request['endDate'] );

		return BookingRepository::getAvailableRoomAmount( $roomID, $startDate, $endDate );
	}


	/**
	 * <p><b>API endpoint for getCustomerDetailsByEmail function in BookingRepository</b></p>
	 *
	 * @param $request
	 *
	 * @return array|false|WP_REST_Response
	 */
	public function getCustomerDetailsByEmailAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$email = sanitize_email( $request['email'] );

		return BookingRepository::getCustomerDetailsByEmail( $email );
	}


	/**
	 * <p><b>Returns room information as an array</b></p>
	 *
	 * @param $request
	 *
	 * @return array|false
	 * @throws Exception
	 */
	public function getRoomInformationAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID    = intval( $request['roomID'] );
		$startDate = sanitize_text_field( $request['startDate'] );
		$endDate   = sanitize_text_field( $request['endDate'] );
		$quantity  = intval( $request['quantity'] );
		$planID    = intval( $request['planID'] );

		// get rates by plan id
		$rates = PricingPlansRepository::getRatesByPlanID( $planID, true );
		$ratesHTML = '';
		foreach ($rates as $rate) {
			$ratesHTML .= '<span>'.$rate["name"].'</span>';
		}
		if ($ratesHTML == '') {
			$ratesHTML = '<span>' . __('Regular', 'rise-hotel-booking') . '</span>';
		}

		$room    = get_post( $roomID );
		$roomURL = get_permalink( $roomID );

		return array(
			'item'     => '<a href="' . $roomURL . '">' . $room->post_title . '</a>',
			'dates'    => $this->convertDatesToTableType( $startDate, $endDate ),
			'night'    => $this->getNumberOfNights( $startDate, $endDate ),
			'total'    => number_format( PricingPlansController::getTotalPrice( $roomID, $startDate, $endDate, $planID ) * $quantity, 2 ),
			'currency' => Currency::$currencies[ SettingsRepository::getSetting( 'currency' ) ],
			'rates'    => $ratesHTML,
		);
	}


	/**
	 * <p><b>Returns coupon discount percentage if coupon is available, error if not.</b></p>
	 *
	 * @param $request
	 *
	 * @return array|false|WP_REST_Response
	 * @throws Exception
	 */
	public function checkCouponAvailabilityAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$couponCode   = sanitize_text_field( $request['couponCode'] );
		$checkInDate  = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $request['checkInDate'] ) . ' 00:00:00' );
		$checkOutDate = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $request['checkOutDate'] ) . ' 23:59:59' );
		$roomID       = intval( $request['roomID'] );
		$planID       = intval( $request['planID'] );

		$couponAvailability = self::checkCouponAvailability( $couponCode, $checkInDate, $checkOutDate, $roomID, $planID );

		// if it returns an array, that means an error occurred, and it returns the array in rest response format.
		if ( is_array( $couponAvailability ) ) {
			return new WP_REST_Response( $couponAvailability, $couponAvailability['status'] );
		}

		return $couponAvailability;
	}


	/**
	 * <p><b>Removes coupon code from session, returns true. If nonce verification was unsuccessful, returns false.</b></p>
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function removeCouponAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		unset( $_SESSION['rise_result_coupon'] );

		return true;
	}


	/**
	 * @param $booking
	 * @param $rooms
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function addBooking( $booking, $rooms ) {
		$subTotal       = BookingController::calculateTotalRoomPrice( $rooms );
		$tax            = BookingController::calculateTotalTax( $rooms );
		$grandTotal     = $subTotal + $tax;
		$advancePayment = BookingController::calculateAdvancePayment( $grandTotal );

		$prices = array(
			'sub-total'       => $subTotal,
			'tax'             => $tax,
			'grand-total'     => $grandTotal,
			'advance-payment' => $advancePayment
		);

		unset( $_SESSION['rise_result_temporary_id'] );
		unset( $_SESSION['rise_result_room_id'] );
		unset( $_SESSION['rise_result_arrival_date'] );
		unset( $_SESSION['rise_result_departure_date'] );
		unset( $_SESSION['rise_result_number_of_people'] );
		unset( $_SESSION['rise_result_quantity'] );
		unset( $_SESSION['rise_result_coupon'] );
		unset( $_SESSION['rise_booking_status'] );
		unset( $_SESSION['rise_method'] );
		unset( $_SESSION['rise_booking'] );
		unset( $_SESSION['rise_rooms'] );


		$mailEnabled = SettingsRepository::getSetting( 'mail-enabled' );

		if ( $mailEnabled ) {
			try {
				$allMailsSent = MailController::sendBookingConfirmation( $booking, $rooms, $prices );
				if ( ! $allMailsSent ) {
					throw new Exception( __( 'Booking was completed, but some or all of the e-mails were unsuccessful to be sent.', 'rise-hotel-booking' ) );
				}
			} catch ( Exception $e ) {
				ActivityLogRepository::addLog( __( 'Mail Unsuccessful', 'rise-hotel-booking' ), $e->getMessage() );
				error_log( $e->getMessage() );
			}
		}

		ActivityLogRepository::addLog( __( 'Booking Completed', 'rise-hotel-booking' ), __( 'Booking was completed successfully.', 'rise-hotel-booking' ) );

		$bookingAdded = BookingRepository::addBooking( $booking, $rooms );
		if ( ! $bookingAdded ) {
			$logType    = __( 'Booking Failed', 'rise-hotel-booking' );
			$logMessage = __( 'A booking was not completed successfully.', 'rise-hotel-booking' );
			ActivityLogRepository::addLog( $logType, $logMessage );

			return false;
		}

		return true;
	}


	/**
	 * <p><b>Calculates and returns tax based on the given price and current tax rate.</b></p>
	 *
	 * @param $basePrice
	 *
	 * @return float|int
	 */
	public static function calculateTax( $basePrice ) {
		$taxRate = SettingsRepository::getSetting( 'tax' );

		return ( $basePrice / 100 ) * $taxRate;
	}


	/**
	 * <p><b>Calculates and returns advance payment based on the given price and current advance payment rate.</b></p></b></p>
	 *
	 * @param $basePrice
	 *
	 * @return float|int
	 */
	public static function calculateAdvancePayment( $basePrice ) {
		$advancePaymentRate = intval( SettingsRepository::getSetting( 'advance-payment' ) );

		return ( $basePrice / 100 ) * $advancePaymentRate;
	}


	/**
	 * <p><b>Calculates and returns total room price.</b></p>
	 *
	 * @param $rooms
	 *
	 * @return int|mixed
	 */
	public static function calculateTotalRoomPrice( $rooms ) {
		$subTotal = 0;
		foreach ( $rooms as $room ) {
			$subTotal += $room->getTotalPrice();
		}

		return $subTotal;
	}


	/**
	 * <p><b>Calculates and returns total tax.</b></p>
	 *
	 * @param $rooms
	 *
	 * @return float|int
	 */
	public static function calculateTotalTax( $rooms ) {
		$totalPrice = 0.00;
		foreach ( $rooms as $room ) {
			$totalPrice += $room->getTotalPrice();
		}

		return BookingController::calculateTax( $totalPrice );
	}


	public static function sanitizeBookingObject( $booking ) {
		$booking->setCoupon( sanitize_text_field( $booking->getCoupon() ) );
		$booking->setTitle( sanitize_text_field( $booking->getTitle() ) );
		$booking->setFirstName( sanitize_text_field( $booking->getFirstName() ) );
		$booking->setLastName( sanitize_text_field( $booking->getLastName() ) );
		$booking->setAddress( sanitize_textarea_field( $booking->getAddress() ) );
		$booking->setCity( sanitize_text_field( $booking->getCity() ) );
		$booking->setState( sanitize_text_field( $booking->getState() ) );
		$booking->setPostalCode( sanitize_text_field( $booking->getPostalCode() ) );
		$booking->setCountry( sanitize_text_field( $booking->getCountry() ) );
		$booking->setPhone( filter_var( $booking->getPhone(), FILTER_SANITIZE_NUMBER_INT ) );
		$booking->setEmail( sanitize_email( $booking->getEmail() ) );
		$booking->setPaymentMethod( sanitize_text_field( $booking->getPaymentMethod() ) );
		$booking->setAdditionalInformation( sanitize_textarea_field( $booking->getAdditionalInformation() ) );
		if ( $booking->getBookID() ) {
			$booking->setBookID( intval( $booking->getBookID() ) );
		}

		return $booking;
	}
}