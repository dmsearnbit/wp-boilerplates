<?php
$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] );

if ( $method == 'POST' ) {
	$this->handleForm(
		intval( $_POST['rise_room_id'] ),
		array_map( 'sanitize_text_field', @$_POST['rise-closed-dates'] ),
		array_map( 'sanitize_text_field', @$_POST['rise-closed-dates-action'] ),
		array_map( 'intval', @$_POST['rise-closed-dates-id'] )
	);
}
?>

<input type="hidden" name="rise_room_close_rooms" value="<?php echo esc_attr( @$_GET['rise_room_id'] ) ?>">

<!-- closed date template -->
<div class="rise-closed-date d-none" id="rise-closed-date-to-copy">
    <div class="d-flex mb-2">
        <button class="btn btn-danger" data-action="rise-closed-date-delete">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
    <div class="d-flex">
        <input name="rise-closed-dates[]" class="rise-closed-dates" readonly required>
        <input type="hidden" name="rise-closed-dates-action[]" value="add">
        <input type="hidden" name="rise-closed-dates-id[]" value="null">
    </div>
</div>

<div class="wrap">
    <div class="row">
        <div class="col-12 mb-3">
            <h1 class="wp-heading-inline">
				<?php echo wp_kses( $page_title, array() ) ?>
            </h1>
        </div>

        <div class="col-md-5">
            <form method="POST" name="rise_close_rooms">
                <div class="form-group w-100">
                    <select name="rise_room_id" id="rise_rooms">
                        <option value="" selected disabled><?php _e( 'Select a room', 'rise-hotel-booking' ) ?></option>
						<?php
						foreach ( $rooms as $room ) {
							$selected = '';
							if ( @$_GET['rise_room_id'] == $room->ID ) {
								$selected = 'selected';
							}
							echo '<option value="' . esc_attr( $room->ID ) . '" ' . $selected . '>' . wp_kses( $room->post_title, array() ) . '</option>';
						}
						?>
                    </select>
                </div>
				<?php
				if ( isset( $_GET['rise_room_id'] ) ) {
					$roomID = intval( $_GET['rise_room_id'] );
					?>
                    <div class="d-flex justify-content-end mt-2">
                        <button class="btn btn-primary" id="rise-add-new-closed-date">
                            <b><?php _e( 'Add new date', 'rise-hotel-booking' ) ?></b>
                        </button>
                    </div>
                    <div id="rise-closed-dates" class="rise-closed-dates">
						<?php
						$dates = $this->getClosedDates( $roomID );
						if ( empty( $dates ) ) {
							echo '<h4 class="my-4 rise-dates-no-date" id="rise-dates-no-date">' . __( 'No dates added.', 'rise-hotel-booking' ) . '</h4>';
						}
						foreach ( $dates as $date ) {
							?>
                            <div class="rise-closed-date" id="rise-closed-date-existing">
                                <div class="d-flex mb-2">
                                    <button class="btn btn-danger" data-action="rise-closed-date-delete">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <div class="d-flex">
                                    <input name="rise-closed-dates[]" class="rise-closed-dates" readonly required
                                           value="<?php echo esc_attr( $this->convertDateType( $date['start_date'], $date['end_date'] ) ) ?>"
                                    >
                                    <input type="hidden" name="rise-closed-dates-action[]" value="update">
                                    <input type="hidden" name="rise-closed-dates-id[]"
                                           value="<?php echo esc_attr( $date['id'] ) ?>">
                                </div>
                            </div>
							<?php
						}
						?>
                    </div>
                    <div class="rise-closed-dates-to-delete"></div>
                    <div class="rise-closed-dates-submit">
                        <input type="submit" value="<?php _e( 'Apply Changes', 'rise-hotel-booking' ) ?>"
                               class="btn btn-success" id="rise-btn-submit">
                    </div>
					<?php
				}
				?>


            </form>
        </div>

        <div class="col-md-7">
            <div id="rise-calendar-close-rooms"></div>
        </div>

    </div>
</div>
