<?php
if ( isset( $allFieldsCompleted ) ) {
	$date = 'value="' . $arrivalDate . ' - ' . $departureDate . '"';
} else {
	$today    = DateTime::createFromFormat( 'd/m/Y', date( 'd/m/Y' ) );
	$tomorrow = DateTime::createFromFormat( 'd/m/Y', date( 'd/m/Y', strtotime( '+1 day' ) ) );

	$today    = $today->format( 'd/m/Y' );
	$tomorrow = $tomorrow->format( 'd/m/Y' );

	$date = 'value="' . $today . ' - ' . $tomorrow . '"';
}
?>

<div class="rise_container my-3">
    <div class="rise_container_content">
        <form action="<?php echo esc_url( $submitURL ) ?>" method="POST">
            <div class="rise_search_container">
                <div class="rise_search_col rise_search_col_main">
                    <div class="rise_input">
                        <label for="rise_dates"><?php _e( 'Arrival & Departure Date', 'rise-hotel-booking' ) ?></label>
                        <input type="text" id="rise_dates"
                               placeholder=""<?php _e( 'Check-in & Check-out Date', 'rise-hotel-booking' ) ?>
                               name="rise_dates"
							<?php echo wp_kses( $date, array() ) ?>
                               required>
                    </div>
                </div>
                <div class="rise_search_col">
                    <div class="rise_input">
                        <label for="rise_number_of_people"><?php _e( 'Number of People', 'rise-hotel-booking' ) ?></label>
                        <input type="number" id="rise_number_of_people" name="rise_number_of_people"
                               placeholder="<?php _e( 'Number of People', 'rise-hotel-booking' ) ?>"
                               value="<?php echo @$allFieldsCompleted ? esc_attr( $numberOfPeople ) : 2 ?>" min="1" required>
                    </div>
                </div>
                <div class="rise_search_col">
                    <div class="rise_input">
                        <label for="rise_quantity"><?php _e( 'Rooms', 'rise-hotel-booking' ) ?></label>
                        <input type="number" id="rise_quantity" name="rise_quantity"
                               placeholder="<?php _e( 'Rooms', 'rise-hotel-booking' ) ?>"
                               value="<?php echo @$allFieldsCompleted ? esc_attr( $quantity ) : 1 ?>" min="1" required>
                    </div>
                </div>
            </div>
            <div class="rise_input">
                <button id="rise_search_btn"
                        class="rise_search_btn"><?php echo __( 'Check Availability', 'rise-hotel-booking' ) ?></button>
            </div>
        </form>
    </div>
</div>
