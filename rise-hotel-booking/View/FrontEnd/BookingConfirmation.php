<?php
$hotelName    = SettingsRepository::getSetting( 'hotel-name' );
$hotelAddress = SettingsRepository::getSetting( 'hotel-address' );

$hotelPhone = SettingsRepository::getSetting( 'phone' );
$hotelEmail = SettingsRepository::getSetting( 'email' );

$hotelCity    = SettingsRepository::getSetting( 'city' );
$hotelState   = SettingsRepository::getSetting( 'state' );
$hotelZip     = SettingsRepository::getSetting( 'zip' );
$hotelCountry = SettingsRepository::getSetting( 'country' );

$hotelAddress .= ' ' . $hotelCity . ' ' . $hotelState . ' ' . $hotelZip . ' ' . $hotelCountry;
?>

<div class="rise_container my-3">
    <div class="rise_container_content">
        <div class="row">
            <div class="col-md-12">
                <h3 class="text-center"><?php _e( 'Booking Confirmation', 'rise-hotel-booking' ) ?></h3>
            </div>
            <div class="col-12">
                <div class="rise-book-information-title">
                    <h5><?php _e( 'Book Information:', 'rise-hotel-booking' ) ?></h5>
                </div>
                <div class="rise-book-information-field p-2">
                    <div class="rise-book-information-field-key"><?php _e( 'Full Name', 'rise-hotel-booking' ) ?>:</div>
                    <div class="rise-book-information-field-value">
						<?php
						echo @NamePrefix::$prefixes[ wp_kses( $booking->getTitle(), array() ) ] . ' ' . wp_kses( $booking->getFirstName(), array() ) . ' ' . wp_kses( $booking->getLastName(), array() );
						?>
                    </div>
                </div>
                <div class="rise-book-information-field p-2">
                    <div class="rise-book-information-field-key"><?php _e( 'Address', 'rise-hotel-booking' ) ?>:</div>
                    <div class="rise-book-information-field-value">
						<?php echo wp_kses( $booking->getAddress(), array() ) . ' ' . wp_kses( $booking->getCity(), array() ) . ' ' . wp_kses( $booking->getState(), array() ) . ' ' . wp_kses( $booking->getPostalCode(), array() ) . ' ' . Country::$countries[ wp_kses( $booking->getCountry(), array() ) ] ?>
                    </div>
                </div>
                <div class="rise-book-information-field p-2">
                    <div class="rise-book-information-field-key"><?php _e( 'Phone Number', 'rise-hotel-booking' ) ?>:
                    </div>
                    <div class="rise-book-information-field-value">
						<?php echo wp_kses( $booking->getPhone(), array() ); ?>
                    </div>
                </div>
                <div class="rise-book-information-field p-2">
                    <div class="rise-book-information-field-key"><?php _e( 'E-mail Address', 'rise-hotel-booking' ) ?>
                        :
                    </div>
                    <div class="rise-book-information-field-value">
						<?php echo wp_kses( $booking->getEmail(), array() ); ?>
                    </div>
                </div>
                <div class="rise-book-information-field p-2">
                    <div class="rise-book-information-field-key"><?php _e( 'Payment Method', 'rise-hotel-booking' ) ?>
                        :
                    </div>
                    <div class="rise-book-information-field-value">
						<?php echo PaymentMethod::$methods[ wp_kses( $booking->getPaymentMethod(), array() ) ]; ?>
                    </div>
                </div>
				<?php
				if ( $booking->getCoupon() ) {
					?>
                    <div class="rise-book-information-field p-2">
                        <b><?php echo sprintf( __( 'Coupon code %s is included in this booking.', 'rise-hotel-booking' ), wp_kses( $booking->getCoupon(), array() ) ) ?></b>
                    </div>
					<?php
				}
				?>
            </div>
            <div class="col-12 mt-5">
                <div class="rise-line"></div>
            </div>
            <div class="col-12 my-5">
                <div class="rise-hotel-information-title">
                    <h5><?php _e( 'Hotel Information', 'rise-hotel-booking' ); ?></h5>
                </div>
                <div class="rise-hotel-information-field p-2">
                    <div class="rise-hotel-information-field-key"><?php _e( 'Hotel Name', 'rise-hotel-booking' ) ?>:
                    </div>
                    <div class="rise-hotel-information-field-value">
						<?php
						echo wp_kses( $hotelName, array() );
						?>
                    </div>
                </div>
                <div class="rise-hotel-information-field p-2">
                    <div class="rise-hotel-information-field-key"><?php _e( 'Address', 'rise-hotel-booking' ) ?>:</div>
                    <div class="rise-hotel-information-field-value">
						<?php
						echo wp_kses( $hotelAddress, array() );
						?>
                    </div>
                </div>
                <div class="rise-hotel-information-field p-2">
                    <div class="rise-hotel-information-field-key"><?php _e( 'Phone', 'rise-hotel-booking' ) ?>:</div>
                    <div class="rise-hotel-information-field-value">
						<?php
						echo wp_kses( $hotelPhone, array() );
						?>
                    </div>
                </div>
                <div class="rise-hotel-information-field p-2">
                    <div class="rise-hotel-information-field-key"><?php _e( 'E-mail', 'rise-hotel-booking' ) ?>:</div>
                    <div class="rise-hotel-information-field-value">
						<?php
						echo wp_kses( $hotelEmail, array() );
						?>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="rise-line"></div>
            </div>
            <div class="col-12 my-5">
                <div class="rise-room-information-title">
                    <h5><?php _e( 'Booked Room(s)', 'rise-hotel-booking' ) ?></h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead>
                        <th class="text-center"><?php echo __( 'Room type', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Number of People', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Quantity', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Includes', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Check-in', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Check-out', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Night', 'rise-hotel-booking' ) ?></th>
                        <th class="text-center"><?php echo __( 'Gross Total', 'rise-hotel-booking' ) ?></th>
                        </thead>
                        <tbody>
						<?php
						foreach ( $rooms as $room ) {
							$rates = $this->getRatesByPlanID( $room->getPlanID() );
							?>
                            <tr class="rise-room-confirmation-row"
                                data-rise-quantity="<?php echo esc_attr( $room->getQuantity() ) ?>"
                                data-rise-number-of-people="<?php echo esc_attr( $room->getNumberOfPeople() ) ?>"
                                data-rise-check-in-date="<?php echo esc_attr( $room->getCheckInDate() ) ?>"
                                data-rise-check-out-date="<?php echo esc_attr( $room->getCheckOutDate() ) ?>"
                                data-rise-total-price="<?php echo esc_attr( $room->getTotalPrice() ) ?>">
                                <td class="text-center align-middle">
                                    <a href="<?php echo get_permalink( $room->getRoomID() ); ?>"
                                       target="_blank"><?php echo get_the_title( $room->getRoomID() ); ?></a>
                                </td>
                                <td class="text-center align-middle">
									<?php echo intval( $room->getNumberOfPeople() ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo intval( $room->getQuantity() ) ?>
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
									<?php echo wp_kses( $room->getCheckInDate(), array() ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo wp_kses( $room->getCheckOutDate(), array() ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo intval( $this->getNumberOfNights(
										wp_kses( $room->getCheckInDate(), array() ),
										wp_kses( $room->getCheckOutDate(), array() )
									) ) ?>
                                </td>
                                <td class="text-center align-middle">
									<?php echo $currency . number_format( floatval( $room->getTotalPrice() ), 2 ) ?>
                                </td>
                            </tr>
							<?php
						}
						?>
                        <tr>
                            <td colspan="7" class="text-center">
								<?php echo __( 'Sub Total', 'rise-hotel-booking' ) ?>
                            </td>
                            <td><b><?php echo $currency . number_format( floatval( $subTotal ), 2 ) ?></b></td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-center">
								<?php echo __( 'Tax', 'rise-hotel-booking' ) ?>
                            </td>
                            <td><?php echo $currency . number_format( floatval( $tax ), 2 ) ?></td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-center">
								<?php echo __( 'Grand Total', 'rise-hotel-booking' ) ?>
                            </td>
                            <td><b><?php echo $currency . number_format( floatval( $grandTotal ), 2 ) ?></b></td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-center">
								<?php
								echo sprintf(
									__( 'Advance Payment (%s of Grand Total)', 'rise-hotel-booking' ),
									'%' . intval( $advancePaymentRate )
								)
								?>
                            </td>
                            <td><?php echo $currency . number_format( $advancePayment, 2 ) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

			<?php
			$instructions = $this->paymentInstructions( $booking->getPaymentMethod() );
			if ( ! empty( $instructions ) ) {
				?>
                <div class="col-md-12 d-flex flex-column py-3 align-items-center">
                    <div class="rise-checkout-instructions-title font-weight-bold">
						<?php _e( 'Payment Instructions', 'rise-hotel-booking' ) ?>
                    </div>
                    <div class="rise-checkout-instructions-content">
						<?php echo wp_kses( $instructions, array() ); ?>
                    </div>
                </div>
				<?php
			}
			?>
        </div>
    </div>
</div>