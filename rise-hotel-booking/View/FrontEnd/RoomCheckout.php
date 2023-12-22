<?php
if ( @$_POST['rise-action'] == 'complete-checkout' ) {
	// this is the part that will run when user fills all the info on checkout page, agrees with terms and conditions
	// and clicks "Check out" button. redirect the user to payment provider and complete the booking here.
	$book = new Booking(
		sanitize_text_field( @$_SESSION['rise_result_coupon'] ),
		sanitize_text_field( @$_POST['rise-checkout-title'] ),
		sanitize_text_field( @$_POST['rise-checkout-first-name'] ),
		sanitize_text_field( @$_POST['rise-checkout-last-name'] ),
		sanitize_textarea_field( @$_POST['rise-checkout-address'] ),
		sanitize_text_field( @$_POST['rise-checkout-city'] ),
		sanitize_text_field( @$_POST['rise-checkout-state'] ),
		sanitize_text_field( @$_POST['rise-checkout-postal-code'] ),
		sanitize_text_field( @$_POST['rise-checkout-country'] ),
		filter_var( @$_POST['rise-checkout-phone'], FILTER_SANITIZE_NUMBER_INT ),
		sanitize_email( @$_POST['rise-checkout-email'] ),
		sanitize_text_field( @$_POST['rise-checkout-payment-method'] ),
		sanitize_textarea_field( @$_POST['rise-checkout-additional-information'] ),
        sanitize_text_field( @$_POST['rise-checkout-passport-id'] )
	);

	$termsAndConditions = @$_POST['rise-checkout-terms-and-conditions'] == 'on';

	$rooms = array();

	// loop through all the rooms and create a room item object for each one of rooms, and then append the object to $rooms array
	for ( $i = 0; $i < count( $_POST['rise_result_room_id'] ); $i ++ ) {
		$totalPrice = $this->getTotalPrice(
				intval( @$_POST['rise_result_room_id'][ $i ] ),
				sanitize_text_field( @$_POST['rise_result_arrival_date'][ $i ] ),
				sanitize_text_field( @$_POST['rise_result_departure_date'][ $i ] ),
				intval( @$_POST['rise_result_plan_id'][ $i ] )
			) * intval( @$_POST['rise_result_quantity'][ $i ] );

		if ( isset( $_SESSION['rise_result_coupon'] ) && ! empty( $_SESSION['rise_result_coupon'] ) ) {
			if ( isset( $_POST['rise_result_arrival_date'][ $i ] ) ) {
				$checkIn = DateTime::createFromFormat( 'Y-m-d', sanitize_text_field( @$_POST['rise_result_arrival_date'][ $i ] ) );
			}

			if ( isset( $_POST['rise_result_departure_date'][ $i ] ) ) {
				$checkOut = DateTime::createFromFormat( 'Y-m-d', sanitize_text_field( @$_POST['rise_result_departure_date'][ $i ] ) );
			}

			$total = false;

			if ( isset( $checkIn ) && isset( $checkOut ) ) {
				$total = BookingController::checkCouponAvailability(
					sanitize_text_field( $_SESSION['rise_result_coupon'] ),
					$checkIn,
					$checkOut,
					intval( @$_POST['rise_result_room_id'][ $i ] ),
                    intval( @$_POST['rise_result_plan_id'][ $i ] ),
				);
			}

			if ( $total ) {
				$totalPrice = $total * intval( @$_POST['rise_result_quantity'][ $i ] );
			}
		}

		$rooms[] = new RoomItem(
			intval( @$_POST['rise_result_room_id'][ $i ] ),
			sanitize_text_field( @$_POST['rise_result_arrival_date'][ $i ] ),
			sanitize_text_field( @$_POST['rise_result_departure_date'][ $i ] ),
			intval( @$_POST['rise_result_quantity'][ $i ] ),
			$totalPrice,
			intval( @$_POST['rise_result_number_of_people'][ $i ] ),
			null,
			null,
			null,
			intval( @$_POST['rise_result_plan_id'][$i] )
		);
	}

	$_SESSION['rise_booking_status'] = 'payment-will-be-done';
	include_once( RISE_LOCATION . '/View/FrontEnd/RoomCompleteCheckout.php' );

	return;
}

if ( isset( $_SESSION['rise_booking_status'] ) ) {
	$paymentDone = false;
	$booking     = BookingController::sanitizeBookingObject( $_SESSION['rise_booking'] );
	$rooms       = RoomController::sanitizeRoomItems( $_SESSION['rise_rooms'] );

	if ( ! isset( $noPaymentRequired ) ) {
		include_once( RISE_LOCATION . '/Controller/PaymentController.php' );

		switch ( $_SESSION['rise_method'] ) {
			case 'stripe':
				if ( isset( $_GET['session_id'] ) ) {
					$paymentDone = PaymentController::stripeVerifyPayment( sanitize_text_field( $_GET['session_id'] ) );
				}
				break;
			case 'iyzico':
				if ( isset( $_POST['token'] ) ) {
					$paymentDone = PaymentController::iyzicoVerifyPayment( sanitize_text_field( $_POST['token'] ) );
				}
				break;
		}
	}

	if ( ( $paymentDone ) || ( @$noPaymentRequired ) ) {
		$bookingAdded = BookingController::addBooking( $booking, $rooms );

		if ( $bookingAdded ) {
			$subTotal       = BookingController::calculateTotalRoomPrice( $rooms );
			$tax            = BookingController::calculateTotalTax( $rooms );
			$grandTotal     = $subTotal + $tax;
			$advancePayment = BookingController::calculateAdvancePayment( $grandTotal );

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
	} else {
		?>
        <div class="rise_error">
			<?php echo __( 'Payment is not completed.', 'rise-hotel-booking' ) ?>
        </div>
		<?php
	}
	unset( $_SESSION['rise_booking_status'] );
}
?>

<div class="rise_container my-3">
    <div class="rise_container_content">
        <input type="hidden" name="rise_currency" value="<?php echo esc_attr( $currency ) ?>">
        <input type="hidden" name="rise_advance_payment_rate" value="<?php echo esc_attr( $advancePaymentRate ) ?>">
        <input type="hidden" name="rise_tax_rate"
               value="<?php echo esc_attr( SettingsRepository::getSetting( 'tax' ) ) ?>">

        <form action="" method="POST">
            <input type="hidden" name="rise_result_coupon"
                   value="<?php echo esc_attr( @$_SESSION['rise_result_coupon'] ) ?>">
			<?php
			// creating hidden inputs for every room in session, so we can access them when checkout is completed.
			for ( $i = 0; $i < count( $_SESSION['rise_result_temporary_id'] ); $i ++ ) {
				?>
                <div class="rise_checkout_room">
                    <input type="hidden" name="rise_result_room_id[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_room_id'][ $i ] ) ?>">
                    <input type="hidden" name="rise_result_plan_id[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_plan_id'][ $i ] ) ?>">
                    <input type="hidden" name="rise_result_arrival_date[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_arrival_date'][ $i ] ) ?>">
                    <input type="hidden" name="rise_result_departure_date[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_departure_date'][ $i ] ) ?>">
                    <input type="hidden" name="rise_result_number_of_people[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_number_of_people'][ $i ] ) ?>">
                    <input type="hidden" name="rise_result_quantity[]"
                           value="<?php echo esc_attr( $_SESSION['rise_result_quantity'][ $i ] ) ?>">
                </div>
				<?php
			}
			?>

            <div class="rise-checkout-group">
                <div class="rise-checkout-group-title">
					<?php echo __( 'Book Room', 'rise-hotel-booking' ) ?>
                </div>
                <div class="table-responsive" id="rise-checkout-table">
                    <table class="table table-bordered text-center">
                        <thead>
                        <th class="text-center"><?php echo __( 'Room type', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Number of People', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Quantity', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Your Plan', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Check-in', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Check-out', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center rise-checkout-night"><?php echo __( 'Night', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Gross Total', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Actions', 'rise-hotel-booking' ) ?></th>
                        </thead>
                        <tbody>
						<?php
						$couponAvailability  = false;
						$subTotal            = 0;
						$discountedRoomCount = 0;
						for ( $i = 0; $i < count( $_SESSION['rise_result_room_id'] ); $i ++ ) {
							$discountedRoomPrice = false;

							// if a coupon was applied
							if ( isset( $_SESSION['rise_result_coupon'] ) ) {
								// creating datetime objects
								$checkInDate = DateTime::createFromFormat(
									'Y-m-d H:i:s',
									$_SESSION['rise_result_arrival_date'][ $i ] . ' 00:00:00'
								);

								$checkOutDate = DateTime::createFromFormat(
									'Y-m-d H:i:s',
									$_SESSION['rise_result_departure_date'][ $i ] . ' 23:59:59'
								);

								$couponAvailability = BookingController::checkCouponAvailability(
									$_SESSION['rise_result_coupon'],
									$checkInDate,
									$checkOutDate,
									$_SESSION['rise_result_room_id'][ $i ],
                                    $_SESSION['rise_result_plan_id'][ $i ]
								);

								// if coupon availability didn't return array, and it's not false,
								// that means there was no error. in this case we convert the response to float, and assign the value to $discountedRoomPrice variable.
								if ( ! is_array( $couponAvailability ) && $couponAvailability ) {
									$discountedRoomPrice = (float) $couponAvailability;
								}
							}

							// if $discountedRoomPrice value is not false, meaning the coupon was valid,
							// calculate the discounted room count.
							if ( $discountedRoomPrice ) {
								$discountedRoomCount ++;
							}
							$rates = $this->getRatesByPlanID( $_SESSION['rise_result_plan_id'][ $i ] );
							?>
                            <tr data-room-id="<?php echo esc_attr( $_SESSION['rise_result_room_id'][ $i ] ) ?>"
                                data-plan-id="<?php echo esc_attr( $_SESSION['rise_result_plan_id'][ $i ] ) ?>"
                                data-quantity="<?php echo $_SESSION['rise_result_quantity'][ $i ] ?>"
                                data-price="<?php echo $couponAvailability ? esc_attr( $discountedRoomPrice ) : esc_attr( $roomData[ $i ]['total_price'] ) ?>"
                                data-original-price="<?php echo $roomData[ $i ]['total_price'] ?>"
                                class="rise-checkout-row">
                                <td class="text-center align-middle">
                                    <a href="<?php echo esc_attr( $roomData[ $i ]['room_url'] ) ?>"
                                       target="_blank"><?php echo wp_kses( $roomData[ $i ]['room_title'], array() ) ?></a>
                                </td>
                                <td class="text-center align-middle">
									<?php echo intval( $_SESSION['rise_result_number_of_people'][ $i ] ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo intval( $_SESSION['rise_result_quantity'][ $i ] ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php
									if ( empty( $rates ) ) {
										echo __( 'Regular', 'rise-hotel-booking' );
									}
									for ( $j = 0; $j < count( $rates ); $j ++ ) {
										echo CustomRatesController::getRateNameByID( $rates[ $j ] );

										if ( $j < count( $rates ) - 1 ) {
											echo '<br><br>';
										}
									}
									?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo wp_kses( $_SESSION['rise_result_arrival_date'][ $i ], array() ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo wp_kses( $_SESSION['rise_result_departure_date'][ $i ], array() ) ?>
                                </td>
                                <td class="text-center align-middle rise-checkout-night">
									<?php echo intval( $this->getNumberOfNights(
										$_SESSION['rise_result_arrival_date'][ $i ],
										$_SESSION['rise_result_departure_date'][ $i ]
									) ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php
									// if coupon was valid for this room
									if ( $couponAvailability ) {
										?>
                                        <div class="rise-regular-price rise-old-price" data-rise-regular-price>
											<?php echo sanitize_text_field( $currency ) . number_format( floatval( $roomData[ $i ]['total_price'] ), 2 ) ?>
                                        </div>
                                        <div class="rise-discounted-price" data-rise-discounted-price>
											<?php echo sanitize_text_field( $currency ) . number_format( floatval( $discountedRoomPrice ), 2 ); ?>
                                        </div>
										<?php
									} else {
										?>
                                        <div class="rise-regular-price" data-rise-regular-price>
											<?php echo sanitize_text_field( $currency ) . number_format( floatval( $roomData[ $i ]['total_price'] ), 2 ) ?>
                                        </div>
                                        <div class="rise-discounted-price" data-rise-discounted-price>

                                        </div>
										<?php
									}
									?>
                                </td>
                                <td class="align-middle">
                                    <button class="rise_checkout_delete_item" data-rise-checkout-delete
                                            data-room-id="<?php echo intval( $_SESSION['rise_result_room_id'][ $i ] ) ?>"
                                            data-temporary-id="<?php echo intval( $_SESSION['rise_result_temporary_id'][ $i ] ) ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
							<?php
							if ( $couponAvailability ) {
								$subTotal += $discountedRoomPrice;
							} else {
								$subTotal += $roomData[ $i ]['total_price'];
							}
						}

						// if coupon was not applied to any room, just unset the coupon from session
						if ( $discountedRoomCount == 0 ) {
							unset( $_SESSION['rise_result_coupon'] );
						}
						?>
                        <tr>
                            <td colspan="9" class="text-right rise-colspan-full">
                                <div id="rise-checkout-coupon-alert" class="rise-checkout-coupon-alert d-none">
                                </div>
                                <div class="rise-checkout-applied-coupon-field <?php echo $discountedRoomCount == 0 ? 'd-none' : '' ?>"
                                     id="rise-checkout-applied-coupon-field">
									<?php echo sprintf( __( 'Coupon <b class="rise-checkout-applied-coupon">%s</b> has been applied.', 'rise-hotel-booking' ), sanitize_text_field( @$_SESSION['rise_result_coupon'] ) ) ?>
                                    <a href="#"
                                       id="rise-checkout-remove-coupon"><?php _e( 'Remove coupon?', 'rise-hotel-booking' ) ?></a>
                                </div>
                                <div class="rise-checkout-coupon-field">
                                    <div class="rise-checkout-coupon-field-left">
                                        <input type="text" placeholder="<?php _e( 'Coupon', 'rise-hotel-booking' ) ?>"
                                               name="rise-checkout-coupon-user-input"
                                               value="<?php echo sanitize_text_field( @$_SESSION['rise_result_coupon'] ) ?>">
                                    </div>
                                    <div class="rise-checkout-coupon-field-right">
                                        <button class="btn btn-success rise-checkout-button"
                                                data-action="rise-checkout-apply-coupon"
                                                data-applied="<?php echo sanitize_text_field( @$_SESSION['rise_result_coupon'] ) ?>">
											<?php echo __( 'Apply Coupon', 'rise-hotel-booking' ) ?>
                                            <span class="rise-spinner d-none"></span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
						<?php
						$tax = BookingController::calculateTax( $subTotal );
						?>
                        <tr>
                            <td colspan="8" class="text-center rise-colspan-1">
								<?php echo __( 'Sub Total', 'rise-hotel-booking' ) ?>
                            </td>
                            <td>
                                <b id="rise-checkout-sub-total"
                                   class="rise-checkout-sub-total"><?php echo sanitize_text_field( $currency ) . number_format( floatval( $subTotal ), 2 ) ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-center rise-colspan-1">
								<?php echo __( 'Tax', 'rise-hotel-booking' ) ?>
                            </td>
                            <td id="rise-checkout-tax"
                                class="rise-checkout-tax"><?php echo sanitize_text_field( $currency ) . number_format( floatval( $tax ), 2 ) ?></td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-center rise-colspan-1">
								<?php echo __( 'Grand Total', 'rise-hotel-booking' ) ?>
                            </td>
                            <td>
                                <b id="rise-checkout-grand-total"
                                   class="rise-checkout-grand-total"><?php echo sanitize_text_field( $currency ) . number_format( floatval( $subTotal ) + floatval( $tax ), 2 ) ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-center rise-colspan-1">
								<?php
								echo sprintf(
									__( 'Advance Payment (%s of Grand Total)', 'rise-hotel-booking' ),
									'%' . intval( $advancePaymentRate )
								)
								?>
                            </td>
                            <td id="rise-checkout-advance-payment"
                                class="rise-checkout-advance-payment"><?php echo sanitize_text_field( $currency ) . number_format( BookingController::calculateAdvancePayment( floatval( $subTotal ) + floatval( $tax ) ), 2 ) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
				<?php
				$showInFooterAndCheckout = SettingsRepository::getSetting( 'show-in-footer-and-checkout' ) == 'true';
				if ( $showInFooterAndCheckout ) {
					?>
                    <div class="d-flex justify-content-end py-3">
                        <a href="<?php echo RISE_PLUGIN_WEBSITE ?>" target="_blank">
							<?php _e( 'Booking engine powered by Rise', 'rise-hotel-booking' ); ?>
                        </a>
                    </div>
					<?php
				}
				?>
            </div>
            <div class="rise-checkout-group">
                <div class="rise-checkout-group-title"><?php // echo __( 'Billing Details', 'rise-hotel-booking' ) ?></div>
                <!--
                <div class="rise-checkout-field">
                    <div class="rise-checkout-field-title"><?php // echo __( 'Existing customer?', 'rise-hotel-booking' ) ?></div>
                    <div class="form-group my-3">
                        <label for="rise-existing-customer-email">
                            <b><?php // echo __( 'E-mail', 'rise-hotel-booking' ) ?></b>
                        </label>
                        <input type="email" id="rise-existing-customer-email"
                               class="form-control"
                               placeholder="<?php // echo __( 'Your e-mail here', 'rise-hotel-booking' ); ?>">
                    </div>
                    <button class="btn btn-success rise-checkout-button"
                            data-action="rise-apply-existing-email">
						<?php // echo __( 'Apply', 'rise-hotel-booking' ) ?>
                        <span class="rise-spinner d-none"></span>
                    </button>
                </div>
                <div class="text-center text-muted">
                    <span>-OR-</span>
                </div>
                -->
                <div class="rise-checkout-field">
                    <div class="rise-checkout-field-title"><?php echo __( 'New customer', 'rise-hotel-booking' ) ?></div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group my-3">
                                <label for="rise-checkout-title">
                                    <b><?php echo __( 'Title', 'rise-hotel-booking' ) ?></b>
                                </label>
                                <select class="form-control" id="rise-checkout-title" name="rise-checkout-title">
                                    <option value="" selected><?php _e( 'Select title', 'rise-hotel-booking' ) ?></option>
									<?php
									foreach ( NamePrefix::$prefixes as $key => $prefix ) {
										echo '<option value="' . esc_attr( $key ) . '">' . wp_kses( $prefix, array() ) . '</option>';
									}
									?>
                                </select>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-first-name">
                                    <b><?php echo __( 'First name', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-first-name"
                                       name="rise-checkout-first-name"
                                       placeholder="<?php echo __( 'First name', 'rise-hotel-booking' ) ?>" required>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-last-name">
                                    <b><?php echo __( 'Last name', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-last-name"
                                       name="rise-checkout-last-name"
                                       placeholder="<?php echo __( 'Last name', 'rise-hotel-booking' ) ?>" required>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-address">
                                    <b><?php echo __( 'Address', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <textarea name="rise-checkout-address" id="rise-checkout-address" class="form-control"
                                          cols="30"
                                          rows="8" placeholder="<?php echo __( 'Address', 'rise-hotel-booking' ) ?>"
                                          required></textarea>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-city">
                                    <b><?php echo __( 'City', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-city"
                                       name="rise-checkout-city"
                                       placeholder="<?php echo __( 'City', 'rise-hotel-booking' ) ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group my-3">
                                <label for="rise-checkout-state">
                                    <b><?php echo __( 'State / Province', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-state"
                                       name="rise-checkout-state"
                                       placeholder="<?php echo __( 'State / Province', 'rise-hotel-booking' ) ?>"
                                       required>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-postal-code">
                                    <b><?php echo __( 'Postal Code', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-postal-code"
                                       name="rise-checkout-postal-code"
                                       placeholder="<?php echo __( 'Postal code', 'rise-hotel-booking' ) ?>" required>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-country">
                                    <b><?php echo __( 'Country', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <select class="form-control" id="rise-checkout-country" name="rise-checkout-country"
                                        style="width: 100%" required>
                                    <option value="" selected disabled><?php _e( 'Select country', 'rise-hotel-booking' ) ?></option>
									<?php
									foreach ( Country::$countries as $key => $country ) {
										echo '<option value="' . esc_attr( $key ) . '">' . wp_kses( $country, array() ) . '</option>';
									}
									?>
                                </select>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-phone">
                                    <b><?php echo __( 'Phone', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="tel" class="form-control" id="rise-checkout-phone"
                                       name="rise-checkout-phone"
                                       placeholder="<?php echo __( 'Phone Number', 'rise-hotel-booking' ) ?>" required>
                            </div>
                            <div class="form-group my-3 d-none" data-rise-passport-id>
                                <label for="rise-checkout-passport-id">
                                    <b><?php echo __( 'Passport/ID Number', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="rise-checkout-passport-id"
                                       name="rise-checkout-passport-id"
                                       placeholder="<?php echo __( 'Passport/ID Number', 'rise-hotel-booking' ) ?>">
                                <div class="rise-checkout-form-info">
                                    <small><?php echo __( 'This information is not stored, and is only used for payment and billing purposes.', 'rise-hotel-booking' ) ?></small>
                                </div>
                            </div>
                            <div class="form-group my-3">
                                <label for="rise-checkout-email">
                                    <b><?php echo __( 'E-mail', 'rise-hotel-booking' ) ?></b>
                                    <span class="rise-mandatory-asterisk">*</span>
                                </label>
                                <input type="email" class="form-control" id="rise-checkout-email"
                                       name="rise-checkout-email"
                                       placeholder="<?php echo __( 'E-mail Address', 'rise-hotel-booking' ) ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rise-checkout-field my-3">
                <div class="rise-checkout-field-title">
					<?php echo __( 'Payment Method', 'rise-hotel-booking' ) ?>
                </div>
                <div class="rise-checkout-payment-methods">
					<?php
					// variable to check if at least one payment method is enabled
					$aMethodEnabled     = false;
					$advancePaymentRate = SettingsRepository::getSetting( 'advance-payment' );

					foreach ( PaymentMethod::$methods as $key => $method ) {
						// if advance payment rate is 0 don't show any other methods than offline payment and pay on arrival.
						if ( $advancePaymentRate == 0 && ( $key != 'offline' && $key != 'arrival' ) ) {
							continue;
						}

						// if current method is disabled, skip to next iteration
						if ( ! $this->isPaymentMethodEnabled( $key ) ) {
							continue;
						} elseif ( $key == 'iyzico' ) {
							// if current method is iyzico and current currency is not supported by iyzico, skip to next iteration
							$supportedCurrencies     = PaymentController::getIyzicoSupportedCurrencies();
							$iyzicoSupportedCurrency = in_array( SettingsRepository::getSetting( 'currency' ), $supportedCurrencies );
							if ( ! $iyzicoSupportedCurrency ) {
								continue;
							}
						}
						$aMethodEnabled = true;
						?>
                        <div class="rise-checkout-payment-method">
                            <input type="radio" name="rise-checkout-payment-method"
                                   id="rise-checkout-payment-method-<?php echo esc_attr( $key ) ?>"
                                   value="<?php echo esc_attr( $key ) ?>" required>
                            <label for="rise-checkout-payment-method-<?php echo esc_attr( $key ) ?>"><?php echo wp_kses( $method, array() ) ?></label>
                            <div class="rise-payment-instructions">
								<?php echo wp_kses( $this->paymentInstructions( $key ), array() ) ?>
                            </div>
                        </div>
						<?php
					}
					if ( ! $aMethodEnabled ) {
						_e( 'No payment methods available.', 'rise-hotel-booking' );
					}
					?>
                </div>
            </div>
            <div class="rise-checkout-field my-3">
                <div class="rise-checkout-field-title">
					<?php echo __( 'Additional Information', 'rise-hotel-booking' ) ?>
                </div>
                <textarea name="rise-checkout-additional-information" id="rise-checkout-additional-information"
                          cols="30"
                          rows="5"></textarea>
            </div>
            <div class="rise-checkout-group my-3">
                <div class="form-group">
                    <input type="checkbox" name="rise-checkout-terms-and-conditions"
                           id="rise-checkout-terms-and-conditions"
                           required>
                    <label for="rise-checkout-terms-and-conditions">
						<?php
						echo sprintf(
							__( 'I agree with <a href="%s" target="_blank">Terms and Conditions</a>', 'rise-hotel-booking' ),
							$termsAndConditionsURL
						);
						?>
                    </label>
                </div>
                <input type="hidden" name="rise-action" value="complete-checkout">
                <button class="btn btn-primary"
                        data-action="rise-checkout"><?php echo __( 'Check out', 'rise-hotel-booking' ) ?></button>
            </div>
            <div id="card-element"></div>
        </form>
    </div>
</div>