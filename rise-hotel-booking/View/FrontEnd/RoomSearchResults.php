<?php
$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] );

if ( $method != 'POST' ) {
	include( RISE_LOCATION . '/View/FrontEnd/RoomSearch.php' );

	return;
}
?>

<div class="rise_container my-3">
    <div class="rise_container_content">
		<?php
		$dates = explode( ' - ', sanitize_text_field( $_POST['rise_dates'] ) );

		$arrivalDate    = sanitize_text_field( $dates[0] );
		$departureDate  = sanitize_text_field( $dates[1] );
		$numberOfPeople = intval( $_POST['rise_number_of_people'] );
		$quantity       = intval( $_POST['rise_quantity'] );

		$arrivalDateExists    = $arrivalDate != '';
		$departureDateExists  = $departureDate != '';
		$numberOfPeopleExists = $numberOfPeople != 0;
		$quantityExists       = $quantity != 0;
		$allFieldsCompleted   = $arrivalDateExists && $departureDateExists && $numberOfPeopleExists && $quantityExists;

		if ( ! $allFieldsCompleted ) {
			?>
            <div class="rise_container mb-3">
                <div class="rise_error">
					<?php echo __( 'Please fill all fields before searching a room.', 'rise-hotel-booking' ) ?>
                </div>
            </div>
			<?php
			$submitURL = $this->getSearchResultsURL();
			include( RISE_LOCATION . '/View/FrontEnd/RoomSearch.php' );

			return;
		}

		$submitURL = $this->getSearchResultsURL();
		include( RISE_LOCATION . '/View/FrontEnd/RoomSearch.php' );

		$rooms    = RoomSearchShortcode::getAvailableRooms( $arrivalDate, $departureDate, $numberOfPeople, $quantity );
		$rooms    = RoomSearchShortcode::sortRoomsByPrice( $rooms, 'ASC' );
		$currency = SettingsRepository::getSetting( 'currency' );
		$currency = Currency::$currencies[ $currency ];
		?>

        <form action="<?php echo esc_url( $this->getCheckoutURL() ) ?>" method="POST">
            <div class="row align-items-baseline">
                <input type="hidden" name="rise_result_arrival_date" value="<?php echo esc_attr( $arrivalDate ) ?>">
                <input type="hidden" name="rise_result_departure_date" value="<?php echo esc_attr( $departureDate ) ?>">
                <input type="hidden" name="rise_result_number_of_people"
                       value="<?php echo esc_attr( $numberOfPeople ) ?>">
                <input type="hidden" name="rise_result_room_id" value="">
                <input type="hidden" name="rise_result_plan_id" value="">
                <input type="hidden" name="rise_result_quantity" value="<?php echo esc_attr( $quantity ) ?>">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="rise-search-rooms">
                            <thead>
                            <tr>
                                <th scope="col">
									<?php echo __( 'Room', 'rise-hotel-booking' ) ?>
                                </th>
                                <th scope="col">
									<?php echo __( 'Price', 'rise-hotel-booking' ) ?>
                                </th>
                                <th scope="col">
									<?php echo __( 'Your Plan', 'rise-hotel-booking' ) ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							$noRoomsAvailable = true;
							foreach ( $rooms as $room ) {
								$arrivalDateFormatted   = DateTime::createFromFormat( 'd/m/Y', wp_kses( $arrivalDate, array() ) )->format( 'Y-m-d' );
								$departureDateFormatted = DateTime::createFromFormat( 'd/m/Y', wp_kses( $departureDate, array() ) )->format( 'Y-m-d' );

								$isRoomPublished = $room->getStatus() == 'publish';

								$isRoomClosed = CloseRoomsRepository::isRoomClosedBetweenDates(
									$room->getID(),
									$arrivalDateFormatted,
									$departureDateFormatted
								);

								$isRoomAvailable = $this->isRoomAvailable(
									$room->getID(),
									$arrivalDateFormatted,
									$departureDateFormatted,
									$quantity
								);

								if ( $isRoomPublished && ! $isRoomClosed && $isRoomAvailable ) {
									$noRoomsAvailable = false;
									$rates            = PricingPlansRepository::getRatesForDates( $arrivalDateFormatted, $departureDateFormatted, $room->getID() );
									$availableAmount  = BookingRepository::getAvailableRoomAmount( $room->getID(), $arrivalDateFormatted, $departureDateFormatted );
									if ( ( count( $rates ) ) == 1 ) {
										?>
                                        <tr data-room-id="<?php echo esc_attr( $room->getID() ) ?>">
                                            <td rowspan="1" class="rise-room">
                                                <a href="<?php echo get_permalink( $room->getID() ) ?>" target="_blank"
                                                   class="text-center">
													<?php echo $room->getTitle() ?>
                                                </a>
												<?php
												$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $room->getID() ), 'thumbnail' );
												echo '<p class="rise-remaining-room">' . __( sprintf( 'Only %d rooms left!', $availableAmount ), 'rise-hotel-booking' ) . '</p>';
												echo '<img src="' . $thumbnail[0] . '" class="img-fluid" alt="' . $room->getTitle() . '">';
												echo '<p>' . $room->getContent() . '</p>';
												?>

                                            </td>
                                            <td data-rise-price>
												<?php echo $currency . number_format( $rates[0]['price'] * $quantity, 2 ) ?>
                                            </td>
                                            <td>
												<?php
												$rates = $rates[0];
												if ( empty( $rates['rates'] ) ) {
													echo '<p>' . __( 'Regular', 'rise-hotel-booking' ) . '</p>';
												} else {
													foreach ( $rates['rates'] as $rateID ) {
														echo '<p>' . CustomRatesController::getRateNameByID( $rateID ) . '</p>';
													}
												}
												?>
                                                <button class="btn btn-primary"
                                                        data-action="rise_book"
                                                        data-plan-id="<?php echo esc_attr( $rates['plan_id'] ) ?>"><?php echo __( 'Book this room', 'rise-hotel-booking' ) ?></button>
                                            </td>
                                        </tr>
										<?php
									} else {
										?>
                                        <tr data-room-id="<?php echo esc_attr( $room->getID() ) ?>">
                                            <td rowspan="<?php echo count( $rates ) + 1 ?>" class="rise-room">
                                                <a href="<?php echo get_permalink( $room->getID() ) ?>" target="_blank"
                                                   class="text-center">
													<?php echo wp_kses( $room->getTitle(), array() ) ?>
                                                </a>
												<?php
												$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $room->getID() ), 'thumbnail' );
												echo '<p class="rise-remaining-room">' . __( sprintf( 'Only %d rooms left!', $availableAmount ), 'rise-hotel-booking' ) . '</p>';
												echo '<img src="' . $thumbnail[0] . '" class="img-fluid" alt="' . $room->getTitle() . '">';
												echo '<p>' . wp_kses( $room->getContent(), array() ) . '</p>';
												?>

                                            </td>
                                        </tr>
										<?php
										foreach ( $rates as $rate ) {
											?>
                                            <tr data-room-id="<?php echo $room->getID() ?>">
                                                <td data-rise-price>
													<?php echo wp_kses( $currency, array() ) . number_format( $rate['price'] * $quantity, 2 ) ?>
                                                </td>
                                                <td>
													<?php
													if ( empty( $rate['rates'] ) ) {
														echo '<p>' . __( 'Regular', 'rise-hotel-booking' ) . '</p>';
													} else {
														foreach ( $rate['rates'] as $rateID ) {
															echo '<p>' . wp_kses( CustomRatesController::getRateNameByID( $rateID ), array() ) . '</p>';
														}
													}
													?>
                                                    <button class="btn btn-primary"
                                                            data-action="rise_book"
                                                            data-plan-id="<?php echo intval( $rate['plan_id'] ) ?>"><?php echo __( 'Book this room', 'rise-hotel-booking' ) ?></button>
                                                </td>
                                            </tr>
											<?php
										}
									}

								}
							}
							?>
                            </tbody>
                        </table>
                    </div>
                </div>
				<?php

				if ( $noRoomsAvailable ) {
					?>
                    <div class="col-12">
                        <div class="rise_error mt-3">
							<?php _e( 'There are no rooms available for the selected dates, number of people and quantity.', 'rise-hotel-booking' ); ?>
                        </div>
                    </div>
					<?php
				}
				?>
            </div>
        </form>
    </div>
</div>
