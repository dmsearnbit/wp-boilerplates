<div class="rise_container my-3">
    <div class="rise_container_content">
		<?php
		$pageWasRefreshed = isset( $_SERVER['HTTP_CACHE_CONTROL'] ) && sanitize_text_field( $_SERVER['HTTP_CACHE_CONTROL'] ) === 'max-age=0';

        if ($book->getPaymentMethod() == 'iyzico') {
            $IDNumberValid = strlen($book->getIDNumber()) >= 5;
        } else {
            $IDNumberValid = true;
        }

		// rise_result_temporary_id not being set means booking was already completed and rooms in the session are unset.
		if ( $pageWasRefreshed && ! isset( $_SESSION['rise_result_temporary_id'] ) ) {
			return;
		}
		// if user didn't agree with terms and conditions
		if ( ! $termsAndConditions ) {
			?>
            <div class="rise_error">
				<?php echo __( 'You must agree with terms and conditions to continue booking.', 'rise-hotel-booking' ) ?>
            </div>
			<?php
		} // if user didn't fill all the required fields
		else if ( empty( $book->getFirstName() ) ||
		          empty( $book->getLastName() ) ||
		          empty( $book->getAddress() ) ||
		          empty( $book->getCity() ) ||
		          empty( $book->getState() ) ||
		          empty( $book->getPostalCode() ) ||
		          empty( $book->getCountry() ) ||
		          empty( $book->getPhone() ) ||
		          empty( $book->getEmail() ) ||
                  !$IDNumberValid
		) {
			?>
            <div class="rise_error">
				<?php echo __( 'You must fill all the required fields to continue booking', 'rise-hotel-booking' ) ?>
            </div>
			<?php
		} // if the entered email address isn't valid
		else if ( ! filter_var( $book->getEmail(), FILTER_VALIDATE_EMAIL ) ) {
			?>
            <div class="rise_error">
				<?php echo __( 'Invalid e-mail.', 'rise-hotel-booking' ) ?>
            </div>
			<?php
		} // if room data didn't come through
		else if ( empty( $rooms ) ) {
			?>
            <div class="rise_error">
				<?php echo __( 'An unexpected error occurred.', 'rise-hotel-booking' ) ?>
            </div>
			<?php
		} //if everything was successful
		else {
			// check if any of the rooms were closed
			foreach ( $rooms as $room ) {
				if ( CloseRoomsRepository::isRoomClosedBetweenDates(
					$room->getRoomID(),
					$room->getCheckInDate(),
					$room->getCheckOutDate()
				) ) {
					?>
                    <div class="rise_error mb-2">
						<?php _e( 'One of the rooms in the booking was not available between your check-in and check-out dates. Booking unsuccessful.', 'rise-hotel-booking' ); ?>
                    </div>
					<?php
					return;
				}
			}

			include_once( RISE_LOCATION . '/Controller/PaymentController.php' );
			$_SESSION['rise_booking'] = $book;
			$_SESSION['rise_rooms']   = $rooms;

			$advancePaymentRate = SettingsRepository::getSetting( 'advance-payment' );
			$advancePaymentRate = (float) $advancePaymentRate;
			if ( $advancePaymentRate == 0 || $book->getPaymentMethod() == 'offline' || $book->getPaymentMethod() == 'arrival' ) {
				$bookingAdded = BookingController::addBooking( $book, $rooms );

				if ( $bookingAdded ) {
					$subTotal       = BookingController::calculateTotalRoomPrice( $rooms );
					$tax            = BookingController::calculateTotalTax( $rooms );
					$grandTotal     = $subTotal + $tax;
					$advancePayment = BookingController::calculateAdvancePayment( $grandTotal );

					$booking  = $book;
					$_SESSION = [];
					include( RISE_LOCATION . '/View/FrontEnd/BookingConfirmation.php' );
				} else {
					?>
                    <div class="rise_error">
						<?php echo __( 'An unexpected error occurred.', 'rise-hotel-booking' ) ?>
                    </div>
					<?php
				}

				return;
			}

			switch ( $book->getPaymentMethod() ) {
				case 'stripe':
					$_SESSION['rise_method'] = 'stripe';
					PaymentController::stripePayment( $rooms, $book->getEmail() );
					break;
				case 'iyzico':
					$_SESSION['rise_method'] = 'iyzico';
					PaymentController::iyzicoPayment( $rooms, $book );
					break;
			}
		}
		?>
    </div>
</div>