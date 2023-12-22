<?php
include_once( RISE_LOCATION . '/Controller/PaymentController.php' );
include_once( RISE_LOCATION . '/Controller/MailController.php' );

class RoomCheckoutShortcode {
	public function __construct() {
		// register shortcodes
		add_action( 'init', array( $this, 'registerShortcodes' ) );
	}


	/**
	 * <p><b>Registers shortcodes</b></p>
	 */
	public function registerShortcodes() {
		add_shortcode( 'rise_checkout', array( $this, 'roomCheckoutShortcode' ) );
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
	 * <p><b>Shortcode content for room search page</b></p>
	 *
	 * @return false|string
	 * @throws Exception
	 */
	public function roomCheckoutShortcode() {
		$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] );

		// ob should be used to avoid "Updating failed. The response is not a valid JSON response." error
		ob_start();

		$currency           = Currency::$currencies[ $this->getCurrency() ];
		$taxRate            = $this->getTax();
		$advancePaymentRate = $this->getAdvancePayment();

		if ( $method == 'POST' && ! isset( $_POST['rise-action'] ) ) {
			// if a new room is being added
			if ( isset( $_POST['rise_result_room_id'] ) ) {
				$roomID         = intval( $_POST['rise_result_room_id'] );
				$planID         = intval( $_POST['rise_result_plan_id'] );
				$arrivalDate    = sanitize_text_field( $_POST['rise_result_arrival_date'] );
				$departureDate  = sanitize_text_field( $_POST['rise_result_departure_date'] );
				$numberOfPeople = intval( $_POST['rise_result_number_of_people'] );
				$quantity       = intval( $_POST['rise_result_quantity'] );

				$planID = $planID ?: null;

				// if rise_result_room_id key exists in session variable, that means user already added a room,
				// so we should append the new room to the existing array
				if ( isset( $_SESSION['rise_result_room_id'] ) ) {
					$temporaryID = intval( end( $_SESSION['rise_result_temporary_id'] ) ) + 1;

					array_push( $_SESSION['rise_result_temporary_id'], $temporaryID );
					array_push( $_SESSION['rise_result_room_id'], $roomID );
					array_push( $_SESSION['rise_result_plan_id'], $planID );
					array_push( $_SESSION['rise_result_arrival_date'], $arrivalDate );
					array_push( $_SESSION['rise_result_departure_date'], $departureDate );
					array_push( $_SESSION['rise_result_number_of_people'], $numberOfPeople );
					array_push( $_SESSION['rise_result_quantity'], $quantity );
				} else {
					$_SESSION['rise_result_temporary_id']     = [ 0 ];
					$_SESSION['rise_result_room_id']          = [ $roomID ];
					$_SESSION['rise_result_plan_id']          = [ $planID ];
					$_SESSION['rise_result_arrival_date']     = [ $arrivalDate ];
					$_SESSION['rise_result_departure_date']   = [ $departureDate ];
					$_SESSION['rise_result_number_of_people'] = [ $numberOfPeople ];
					$_SESSION['rise_result_quantity']         = [ $quantity ];
				}

				PaymentController::cancelPayment();
			}
		}

		if ( isset( $_SESSION['rise_result_room_id'] ) && ! isset( $_POST['rise-action'] ) ) {
			$roomData = array();

			// loop over all rooms and add additional data for them (total price, title, url)
			for ( $i = 0; $i < count( $_SESSION['rise_result_room_id'] ); $i ++ ) {
				$totalPrice = $this->getTotalPrice(
					$_SESSION['rise_result_room_id'][ $i ],
					$_SESSION['rise_result_arrival_date'][ $i ],
					$_SESSION['rise_result_departure_date'][ $i ],
					$_SESSION['rise_result_plan_id'][ $i ]
				);

				array_push( $roomData, array(
					'total_price' => $totalPrice * (int) $_SESSION['rise_result_quantity'][ $i ],
					'room_title'  => get_the_title( $_SESSION['rise_result_room_id'][ $i ] ),
					'room_url'    => get_permalink( $_SESSION['rise_result_room_id'][ $i ] )
				) );
			}

			// calculate tax and subtotal
			$subTotal = 0;
			foreach ( $roomData as $data ) {
				$subTotal += $data['total_price'];
			}

			$grandTotal     = $subTotal + BookingController::calculateTax( $subTotal );
			$advancePayment = BookingController::calculateAdvancePayment( $grandTotal );

			$termsAndConditionsURL = $this->getTermsAndConditionsURL();

			// $submitURL = get_page_link( SettingsRepository::getSetting( 'room-checkout-page' ) );
			include( RISE_LOCATION . '/View/FrontEnd/RoomCheckout.php' );
		} elseif ( isset( $_POST['rise-action'] ) ) {
			// if rise-action exists in post data, that means we already processed the room data in checkout page,
			// so we can just include RoomCheckout.php without worrying about room and booking data.
			include( RISE_LOCATION . '/View/FrontEnd/RoomCheckout.php' );
		} else {
			_e( 'Please search a room first.', 'rise-hotel-booking' );
		}

		return ob_get_clean();
	}


	/**
	 * <p><b>Returns payment instructions as html if payment method is offline</b></p>
	 *
	 * @return mixed|null
	 */
	public function paymentInstructions( $method ) {
		return SettingsRepository::getSetting( $method . '-payment-instructions' );
	}


	/**
	 * <p><b>Returns current currency</b></p>
	 *
	 * @return mixed|null
	 */
	public function getCurrency() {
		return SettingsRepository::getSetting( 'currency' );
	}


	/**
	 * <p><b>Returns current tax rate</b></p>
	 *
	 * @return mixed|null
	 */
	public function getTax() {
		return SettingsRepository::getSetting( 'tax' );
	}


	/**
	 * <p><b>Returns current advance payment rate</b></p>
	 *
	 * @return mixed|null
	 */
	public function getAdvancePayment() {
		return SettingsRepository::getSetting( 'advance-payment' );
	}


	/**
	 * <p><b>Returns terms and conditions URL</b></p>
	 *
	 * @return false|string|WP_Error
	 */
	public function getTermsAndConditionsURL() {
		return get_permalink( SettingsRepository::getSetting( 'terms-and-conditions-page' ) );
	}


	/**
	 * <p><b>Returns true if offline payments are enabled, false otherwise.</b></p>
	 *
	 * @return bool
	 */
	public function isPaymentMethodEnabled( $method ) {

		// if the secret key is not set, even though if the method is enabled, the method won't be shown to the user at
		// the checkout page. because secret key is mandatory.
		if ( $method == 'stripe' ) {
			$methodEnabled = SettingsRepository::getSetting( 'enable-' . $method . '-payments' ) == 'true';
			$secretKey     = SettingsRepository::getSetting( 'stripe-secret-key' );

			$secretKeyExists = false;

			if ( $secretKey ) {
				$secretKeyExists = true;
			}

			return $methodEnabled && $secretKeyExists;
		}

		return SettingsRepository::getSetting( 'enable-' . $method . '-payments' ) == 'true';
	}


	/**
	 * @param $roomID
	 * @param $arrivalDate
	 * @param $departureDate
	 *
	 * @return int|mixed|string
	 * @throws Exception
	 */
	public function getTotalPrice( $roomID, $arrivalDate, $departureDate, $planID = null ) {
		return PricingPlansController::getTotalPrice(
			$roomID,
			$arrivalDate,
			$departureDate,
			$planID
		);
	}


	/**
	 * <p><b>Returns all rates for the given plan ID</b></p>
	 *
	 * @param $planID
	 *
	 * @return array
	 */
	public function getRatesByPlanID( $planID ) {
		return PricingPlansRepository::getRatesByPlanID( $planID );
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1/delete-room-from-session',
			'(?P<temporaryID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'deleteRoomAPI' ),
				'permission_callback' => function () {
					return true;
				}
			)
		);
	}


	/**
	 * <p><b>API endpoint to delete room from session</b></p>
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function deleteRoomAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomDeleted = false;

		$roomID = intval( $request['temporaryID'] );
		foreach ( $_SESSION['rise_result_temporary_id'] as $key => $currentRoomID ) {
			if ( $currentRoomID == $roomID ) {
				array_splice( $_SESSION['rise_result_temporary_id'], $key, 1 );
				array_splice( $_SESSION['rise_result_room_id'], $key, 1 );
				array_splice( $_SESSION['rise_result_plan_id'], $key, 1 );
				array_splice( $_SESSION['rise_result_arrival_date'], $key, 1 );
				array_splice( $_SESSION['rise_result_departure_date'], $key, 1 );
				array_splice( $_SESSION['rise_result_number_of_people'], $key, 1 );
				array_splice( $_SESSION['rise_result_quantity'], $key, 1 );

				$roomDeleted = true;
			}
		}

		// if room id in session is empty that means no rooms left in the session, so we are going to unset
		// all the arrays related to booking
		if ( empty( $_SESSION['rise_result_room_id'] ) ) {
			unset( $_SESSION['rise_result_temporary_id'] );
			unset( $_SESSION['rise_result_room_id'] );
			unset( $_SESSION['rise_result_arrival_date'] );
			unset( $_SESSION['rise_result_departure_date'] );
			unset( $_SESSION['rise_result_number_of_people'] );
			unset( $_SESSION['rise_result_quantity'] );
		}

		PaymentController::cancelPayment();

		return $roomDeleted;
	}
}