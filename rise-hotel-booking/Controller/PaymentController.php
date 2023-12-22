<?php
include_once( RISE_LOCATION . '/Repository/SettingsRepository.php' );

include_once( RISE_LOCATION . '/PaymentGateways/iyzipay/IyzipayBootstrap.php' );

class PaymentController {
	/**
	 * <p><b>creates a stripe session, and redirects the user to the stripe payment page.</b></p>
	 *
	 * @param $rooms
	 * @param $email
	 */
	public static function stripePayment( $rooms, $email ) {
		include_once( RISE_LOCATION . '/PaymentGateways/stripe/init.php' );

		$stripeSecret = SettingsRepository::getSetting( 'stripe-secret-key' );

		\Stripe\Stripe::setApiKey( $stripeSecret );

		$currency  = strtolower( SettingsRepository::getSetting( 'currency' ) );
		$returnURL = get_permalink( SettingsRepository::getSetting( 'room-checkout-page' ) );

		$lineItems = array();

		foreach ( $rooms as $room ) {
			$grandTotal = $room->getTotalPrice() + BookingController::calculateTax( $room->getTotalPrice() );
			$totalPrice = BookingController::calculateAdvancePayment( $grandTotal );

			// multiplying price by 100 because stripe wants the amount as cents
			$unitAmount = round( ( round( $totalPrice / $room->getQuantity(), 2 ) ) * 100, 2 );

			array_push( $lineItems, array(
				'price_data' => [
					'currency'     => $currency,
					'product_data' => [
						'name'        => get_the_title( $room->getRoomID() ),
						'description' => __( sprintf( 'Dates between %s and %s', $room->getCheckInDate(), $room->getCheckOutDate() ), 'rise-hotel-booking' )
					],
					'unit_amount'  => $unitAmount,
				],
				'quantity'   => $room->getQuantity(),
			) );
		}

		try {
			// creating stripe session
			$session = \Stripe\Checkout\Session::create( [
				'line_items'     => [
					$lineItems
				],
				'mode'           => 'payment',
				'success_url'    => $returnURL . '?session_id={CHECKOUT_SESSION_ID}',
				'cancel_url'     => $returnURL . '?session_id={CHECKOUT_SESSION_ID}',
				'customer_email' => $email
			] );

			$_SESSION['rise_stripe_session'] = $session->id;

			// redirect the user to the payment page provided by stripe
			echo "<script> window.location.href = '" . esc_url( $session->url ) . "' </script>";
		} catch ( \Stripe\Exception\ApiErrorException $ex ) {
			?>
            <div class="rise_error mb-2">
				<?php _e( 'Payment unsuccessful, please choose another payment method.', 'rise-hotel-booking' ); ?>
            </div>
			<?php
		}

	}


	/**
	 * <p><b>check if payment was done.</b></p>
	 *
	 * @param $sessionID
	 *
	 * @return bool
	 */
	public static function stripeVerifyPayment( $sessionID ) {
		include_once( RISE_LOCATION . '/PaymentGateways/stripe/init.php' );

		$stripe = new \Stripe\StripeClient(
			SettingsRepository::getSetting( 'stripe-secret-key' )
		);

		try {
			$session = $stripe->checkout->sessions->retrieve(
				$sessionID,
				[]
			);

			return $session->payment_status == 'paid';
		} catch ( Stripe\Exception\ApiErrorException $ex ) {
			return false;
		}
	}


	/**
	 * <p><b>expires given session. returns true if successful, returns false otherwise.</b></p>
	 *
	 * @param $sessionID
	 *
	 * @return bool
	 */
	public static function stripeExpireSession( $sessionID ) {
		include_once( RISE_LOCATION . '/PaymentGateways/stripe/init.php' );

		$stripe = new \Stripe\StripeClient(
			SettingsRepository::getSetting( 'stripe-secret-key' )
		);

		try {
			$stripe->checkout->sessions->expire(
				$sessionID,
				[]
			);

			return true;
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			return false;
		}
	}


	/**
	 * <p><b>builds an iyzipay options object and returns it</b></p>
	 * @return \Iyzipay\Options
	 */
	public static function getIyzicoOptions() {
		$options = new \Iyzipay\Options();
		$options->setApiKey( SettingsRepository::getSetting( 'iyzico-api-key' ) );
		$options->setSecretKey( SettingsRepository::getSetting( 'iyzico-secret-key' ) );
		$testMode = SettingsRepository::getSetting( 'iyzico-test-mode' ) == 'true';

		if ( $testMode ) {
			$options->setBaseUrl( 'https://sandbox-api.iyzipay.com' );
		} else {
			$options->setBaseUrl( 'https://api.iyzipay.com' );
		}

		return $options;
	}


	/**
	 * <p><b>returns iyzico currency if current currency is supported in iyzico. if it's not supported, returns false.</b></p>
	 * @return false|string
	 */
	public static function getIyzicoCurrency() {
		$currency = SettingsRepository::getSetting( 'currency' );
		switch ( $currency ) {
			case 'TRY':
				return \Iyzipay\Model\Currency::TL;
			case 'USD':
				return \Iyzipay\Model\Currency::USD;
			case 'EUR':
				return \Iyzipay\Model\Currency::EUR;
			case 'GBP':
				return \Iyzipay\Model\Currency::GBP;
			case 'IRR':
				return \Iyzipay\Model\Currency::IRR;
			case 'NOK':
				return \Iyzipay\Model\Currency::NOK;
			case 'RUB':
				return \Iyzipay\Model\Currency::RUB;
			case 'CHF':
				return \Iyzipay\Model\Currency::CHF;
			default:
				return false;
		}
	}


	/**
	 * <p><b>returns an array of currencies supported by iyzico.</b></p>
	 * @return array
	 */
	public static function getIyzicoSupportedCurrencies() {
		return [
			'TRY',
			'USD',
			'EUR',
			'GBP',
			'IRR',
			'NOK',
			'RUB',
			'CHF',
		];
	}


	/**
	 * <p><b>creates iyzico payment request, displays the payment form.</b></p>
	 *
	 * @param $rooms
	 * @param $booking
	 */
	public static function iyzicoPayment( $rooms, $booking ) {
		IyzipayBootstrap::init();
		$totalPrice = BookingController::calculateTotalRoomPrice( $rooms );
		$grandTotal = $totalPrice + BookingController::calculateTax( $totalPrice );
		$paidPrice  = BookingController::calculateAdvancePayment( $grandTotal );
		$fullName   = $booking->getFirstName() . " " . $booking->getLastName();
		$returnURL  = get_permalink( SettingsRepository::getSetting( 'room-checkout-page' ) );

		$locale = get_locale() == 'tr_TR' ? \Iyzipay\Model\Locale::TR : \Iyzipay\Model\Locale::EN;

		// https://dev.iyzipay.com/tr/odeme-formu/odeme-formu-baslatma
		$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
		$request->setLocale( $locale );
		$request->setPrice( (string) $totalPrice );
		$request->setPaidPrice( (string) $paidPrice );
		$request->setCurrency( self::getIyzicoCurrency() );
		$request->setPaymentGroup( \Iyzipay\Model\PaymentGroup::PRODUCT );
		$request->setCallbackUrl( $returnURL );
		$request->setEnabledInstallments( array( 1 ) );

		$buyer = new \Iyzipay\Model\Buyer();
		$buyer->setId( "0" );
		$buyer->setName( $booking->getFirstName() );
		$buyer->setSurname( $booking->getLastName() );
		$buyer->setGsmNumber( (string) $booking->getPhone() );
		$buyer->setEmail( $booking->getEmail() );
		$buyer->setIdentityNumber( wp_kses($booking->getIDNumber(), array()) );
		$buyer->setRegistrationAddress( $booking->getAddress() );
		$buyer->setIp( filter_var( wp_kses( $_SERVER['REMOTE_ADDR'], array() ), FILTER_VALIDATE_IP ) );
		$buyer->setCity( $booking->getState() );
		$buyer->setCountry( $booking->getCountry() );
		$buyer->setZipCode( (string) $booking->getPostalCode() );

		$request->setBuyer( $buyer );
		$shippingAddress = new \Iyzipay\Model\Address();
		$shippingAddress->setContactName( $fullName );
		$shippingAddress->setCity( $booking->getState() );
		$shippingAddress->setCountry( $booking->getCountry() );
		$shippingAddress->setAddress( $booking->getAddress() );
		$shippingAddress->setZipCode( (string) $booking->getPostalCode() );
		$request->setShippingAddress( $shippingAddress );

		$request->setBillingAddress( $shippingAddress );

		$basketItems = array();

		foreach ( $rooms as $room ) {
			$roomTitle  = get_the_title( $room->getRoomID() );
			$basketItem = new \Iyzipay\Model\BasketItem();
			$basketItem->setId( $room->getRoomID() );
			$basketItem->setName( $roomTitle );
			$basketItem->setCategory1( __( 'Hotel Rooms', 'rise-hotel-booking' ) );
			// TODO: this should be physical or virtual, I couldn't decide which one is right.
			$basketItem->setItemType( \Iyzipay\Model\BasketItemType::PHYSICAL );
			$basketItem->setPrice( (string) $room->getTotalPrice() );
			$basketItems[] = $basketItem;
		}

		$request->setBasketItems( $basketItems );

		$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create( $request, self::getIyzicoOptions() );
		?>
        <div id="iyzipay-checkout-form" class="responsive">
			<?php echo wp_kses( $checkoutFormInitialize->getCheckoutFormContent(),
				array(
					'script' => array(
						'type' => 'text/javascript'
					)
				) ) ?>
        </div>
		<?php
	}


	/**
	 * <p><b>returns true if payment was successful, otherwise returns false.</b></p>
	 *
	 * @param $token
	 *
	 * @return bool
	 */
	public static function iyzicoVerifyPayment( $token ) {
		IyzipayBootstrap::init();

		$request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
		$request->setLocale( \Iyzipay\Model\Locale::EN );
		$request->setToken( $token );

		$checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve( $request, self::getIyzicoOptions() );

		return $checkoutForm->getPaymentStatus() == 'SUCCESS';
	}


	/**
	 * <p><b>cancels any ongoing payments.</b></p>
	 */
	public static function cancelPayment() {
		if ( isset( $_SESSION['rise_stripe_session'] ) ) {
			self::stripeExpireSession( $_SESSION['rise_stripe_session'] );
		}

		unset( $_SESSION['rise_method'] );
		unset( $_SESSION['rise_stripe_session'] );
		unset( $_SESSION['rise_booking_status'] );
		unset( $_SESSION['rise_rooms'] );
		unset( $_SESSION['rise_booking'] );
	}
}