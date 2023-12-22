<?php

class MailController {
	private static $adminMailTemplate;

	private static $customerMailTemplate;

	public function __construct() {
		self::$adminMailTemplate    = SettingsRepository::getSetting( 'admin-mail-template' );
		self::$customerMailTemplate = SettingsRepository::getSetting( 'customer-mail-template' );
	}


	/**
	 * @param $booking
	 * @param $rooms
	 * @param $prices
	 * @param $format
	 *
	 * @return array|string|string[]
	 */
	// I don't really know how this function could return an array or a string array, but PHPStorm generated the PHPDocs like this.
	public static function generateContentFromTemplate( $booking, $rooms, $prices, $format ) {
		switch ( $format ) {
			case 'admin':
				$content = SettingsRepository::getSetting( 'admin-mail-template' );
				break;
			default:
				$content = SettingsRepository::getSetting( 'customer-mail-template' );
				break;
		}

		$namePrefix = @NamePrefix::$prefixes[ $booking->getTitle() ];
		$address    = $booking->getAddress() . ' ' . $booking->getCity() . ' ' . $booking->getState() . ' ' .
		              $booking->getPostalCode() . ' ' . Country::$countries[ $booking->getCountry() ];

		$fullName = $namePrefix . ' ' . $booking->getFirstName() . ' ' . $booking->getLastName();

		$hotelName    = SettingsRepository::getSetting( 'hotel-name' );
		$hotelAddress = SettingsRepository::getSetting( 'hotel-address' );

		$hotelPhone = SettingsRepository::getSetting( 'phone' );
		$hotelEmail = SettingsRepository::getSetting( 'email' );

		$hotelCity    = SettingsRepository::getSetting( 'city' );
		$hotelState   = SettingsRepository::getSetting( 'state' );
		$hotelZip     = SettingsRepository::getSetting( 'zip' );
		$hotelCountry = SettingsRepository::getSetting( 'country' );

		$hotelAddress .= ' ' . $hotelCity . ' ' . $hotelState . ' ' . $hotelZip . ' ' . $hotelCountry;

		$paymentInstructions = SettingsRepository::getSetting( $booking->getPaymentMethod() . '-payment-instructions' );
		if ( $paymentInstructions ) {
			$paymentInstructions = '<strong>' . __( 'Payment Instructions: ', 'rise-hotel-booking' ) . '</strong>' . $paymentInstructions;
		}

		// assign values of customer details
		$content = str_replace( '{CUSTOMER_FULL_NAME}', $fullName, $content );
		$content = str_replace( '{CUSTOMER_ADDRESS}', $address, $content );
		$content = str_replace( '{CUSTOMER_PHONE_NUMBER}', $booking->getPhone(), $content );
		$content = str_replace( '{CUSTOMER_EMAIL_ADDRESS}', $booking->getEmail(), $content );
		$content = str_replace( '{CUSTOMER_PAYMENT_METHOD}', PaymentMethod::$methods[ $booking->getPaymentMethod() ], $content );
		$content = str_replace( '{HOTEL_NAME}', $hotelName, $content );
		$content = str_replace( '{HOTEL_ADDRESS}', $hotelAddress, $content );
		$content = str_replace( '{HOTEL_PHONE_NUMBER}', $hotelPhone, $content );
		$content = str_replace( '{HOTEL_EMAIL_ADDRESS}', $hotelEmail, $content );

		// get the template user entered between ALL_ROOMS tags
		$roomDetailsTemplate = strstr( $content, '<ALL_ROOMS>' );
		$roomDetailsTemplate = str_replace( '<ALL_ROOMS>', '', $roomDetailsTemplate );
		$roomDetailsTemplate = strstr( $roomDetailsTemplate, '</ALL_ROOMS>', true );

		// generate room details from the template user entered
		// TODO: for some reason, empty lines are printed between each room. example:
		// how it should be:
		/*
		 * Room Double, from 2022-02-18 to 2022-02-25
		 * Room Double, from 2022-02-18 to 2022-02-25
		 * Room Double, from 2022-02-18 to 2022-02-25
		 */
		// how is it right now:
		/*
		 * Room Double, from 2022-02-18 to 2022-02-25
		 *
		 * Room Double, from 2022-02-18 to 2022-02-25
		 *
		 * Room Double, from 2022-02-18 to 2022-02-25
		 */
		$roomDetails = '';
		foreach ( $rooms as $room ) {
			$currentRoomFormatted = str_replace( '{ROOM_TITLE}', get_the_title( $room->getRoomID() ), $roomDetailsTemplate );
			$currentRoomFormatted = str_replace( '{ROOM_CHECK_IN_DATE}', $room->getCheckInDate(), $currentRoomFormatted );
			$currentRoomFormatted = str_replace( '{ROOM_CHECK_OUT_DATE}', $room->getCheckOutDate(), $currentRoomFormatted );
			$currentRoomFormatted = str_replace( '{ROOM_NUMBER_OF_PEOPLE}', $room->getNumberOfPeople(), $currentRoomFormatted );
			$currentRoomFormatted = str_replace( '{ROOM_QUANTITY}', $room->getQuantity(), $currentRoomFormatted );
			$currentRoomFormatted = str_replace( '{ROOM_TOTAL_PRICE}', number_format( $room->getTotalPrice(), 2 ), $currentRoomFormatted );
			$roomDetails          .= $currentRoomFormatted;
		}
		$roomDetails = trim( $roomDetails );

		// put room details between ALL_ROOMS tags
		$allRoomsRegex  = '/(<ALL_ROOMS>)[\s\S]+?(<\/ALL_ROOMS>)/';
		$content        = preg_replace( $allRoomsRegex, $roomDetails, $content );
		$currency       = SettingsRepository::getSetting( 'currency' );
		$currencySymbol = Currency::$currencies[ $currency ];

		// format prices
		$subTotal       = number_format( $prices['sub-total'], 2 );
		$tax            = number_format( $prices['tax'], 2 );
		$grandTotal     = number_format( $prices['grand-total'], 2 );
		$advancePayment = number_format( $prices['advance-payment'], 2 );

		// assign values of pricing details
		$content = str_replace( '{BOOKING_SUB_TOTAL}', $currencySymbol . $subTotal, $content );
		$content = str_replace( '{BOOKING_TAX}', $currencySymbol . $tax, $content );
		$content = str_replace( '{BOOKING_GRAND_TOTAL}', $currencySymbol . $grandTotal, $content );
		$content = str_replace( '{BOOKING_ADVANCE_PAYMENT}', $currencySymbol . $advancePayment, $content );

		if ( $booking->getCoupon() ) {
			$couponText = sprintf( __( 'Coupon code %s is included in this booking.', 'rise-hotel-booking' ), $booking->getCoupon() );
		} else {
			$couponText = '';
		}

		$content = str_replace( '{COUPON_CODE}', $couponText, $content );
		$content = str_replace( '{PAYMENT_INSTRUCTIONS}', $paymentInstructions, $content );


		return $content;
	}


	/**
	 * @param $booking
	 * @param $rooms
	 * @param $prices
	 *
	 * @return bool
	 */
	public static function sendBookingConfirmation( $booking, $rooms, $prices ) {
		$adminMails = SettingsRepository::getSetting( 'notification-mail-addresses' );
		$adminMails = explode( PHP_EOL, $adminMails );

		$customerMail = $booking->getEmail();

		$adminMailContent    = self::generateContentFromTemplate( $booking, $rooms, $prices, 'admin' );
		$customerMailContent = self::generateContentFromTemplate( $booking, $rooms, $prices, 'customer' );

		$adminSuccess = true;

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// send admin e-mails
		foreach ( $adminMails as $adminMail ) {
			$adminSuccess = $adminSuccess && wp_mail( $adminMail, __( 'You Received a Booking', 'rise-hotel-booking' ), $adminMailContent, $headers );
		}

		// send customer e-mail
		$customerSuccess = wp_mail( $customerMail, __( 'Booking Successful', 'rise-hotel-booking' ), $customerMailContent, $headers );

		return $adminSuccess && $customerSuccess;
	}
}