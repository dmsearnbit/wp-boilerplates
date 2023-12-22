<div class="rise_roomSettings">
    <div class="rise_roomSettings_row">
        <div class="rise_roomSettings_label">
            <label for="rise_room_quantity"><?php _e( 'Quantity', 'rise-hotel-booking' ) ?></label>
        </div>
        <div class="rise_roomSettings_input">
            <input type="number" name="rise_room_quantity" id="rise_room_quantity"
                   value="<?php echo esc_attr( $rise_room_quantity ) ?>">
        </div>
    </div>
    <div class="rise_roomSettings_row">
        <div class="rise_roomSettings_label">
            <label for="rise_room_numberOfAdults"><?php _e( 'Number of adults', 'rise-hotel-booking' ) ?></label>
        </div>
        <div class="rise_roomSettings_input">
            <input type="number" name="rise_room_numberOfAdults" id="rise_room_numberOfAdults"
                   value="<?php echo esc_attr( $rise_room_numberOfAdults ) ?>">
        </div>
    </div>
    <div class="rise_roomSettings_row">
        <div class="rise_roomSettings_label">
            <label for="rise_room_shortDescription"><?php _e( 'Short Description', 'rise-hotel-booking' ) ?></label>
        </div>
        <div class="rise_roomSettings_input">
			<?php wp_editor( wp_kses( $rise_room_shortDescription, array() ), 'rise_room_shortDescription', array(
				'media_buttons' => false,
			) ); ?>
        </div>
    </div>
</div>