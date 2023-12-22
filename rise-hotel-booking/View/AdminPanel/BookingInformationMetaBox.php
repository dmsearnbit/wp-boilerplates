<?php
if ( ! $editing ) {
	?>
    <style>
        #rise_details_show, #rise_notes_show {
            display: none;
        }

        #rise_details_edit, #rise_notes_edit {
            display: flex;
        }

        button.rise_field_edit {
            display: none;
        }
    </style>
	<?php
}
?>

<div class="rise_meta_box_container">
    <div class="rise_book_id"><?php _e( 'Book ID', 'rise-hotel-booking' ) ?> #<?php echo intval( $ID ) ?></div>
    <div class="rise_book_id_alt"><?php echo sprintf( __( 'Booked on %s', 'rise-hotel-booking' ),  wp_kses( $bookDate, array() ) ) ?></div>
    <div class="rise_details_fields">
        <div class="rise_details_field">
            <div class="d-flex justify-content-between">
                <b><?php _e( 'General', 'rise-hotel-booking' ) ?></b>
            </div>
            <div class="rise_field_group mt-3">
                <label for="rise_customer_payment_method"><?php _e( 'Payment Method:', 'rise-hotel-booking' ) ?></label>
                <select name="rise_customer_payment_method" id="rise_customer_payment_method">
					<?php
					foreach ( PaymentMethod::$methods as $key => $value ) {
						$selected = '';
						if ( $editing ) {
							if ( $metaData['rise_customer_payment_method'] == $key ) {
								$selected = ' selected';
							}
						}
						echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . wp_kses( $value, array() ) . '</option>';
					}
					?>
                </select>
            </div>
            <div class="rise_field_group mt-2">
                <label for="rise_customer_booking_status"><?php _e( 'Booking Status:', 'rise-hotel-booking' ) ?></label>
                <select name="rise_customer_booking_status" id="rise_customer_booking_status">
					<?php
					foreach ( BookingStatus::$status as $key => $value ) {
						$selected = '';
						if ( $editing ) {
							if ( $metaData['rise_customer_booking_status'] == $key ) {
								$selected = ' selected';
							}
						}
						echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . wp_kses( $value, array() ) . '</option>';
					}
					?>
                </select>
            </div>
            <div class="rise_field_group mt-4">
				<?php
				if ( $editing && ! empty( $metaData['rise_customer_coupon'] ) ) {
					?>
                    <div class="rise_coupon_info">
						<?php echo sprintf( __( 'Coupon code <b>%s</b> is used in this booking.', 'rise-hotel-booking' ), wp_kses( $metaData['rise_customer_coupon'], array() ) ) ?>
                    </div>
					<?php
				}
				?>
            </div>
        </div>
        <div class="rise_details_field">
            <div class="d-flex justify-content-between">
                <b><?php _e( "Customer's Details", "rise-hotel-booking" ) ?></b>
                <button class="rise_field_edit" data-action="toggle-details" data-status="show">
                    <span class="dashicons dashicons-edit"></span>
                </button>
            </div>
            <div class="rise_field_group mt-3" id="rise_details_show">
                <div class="rise_address_field">
                    <div class="rise_address_field_name"><?php _e( 'Name', 'rise-hotel-booking' ) ?></div>
                    <div class="rise_address_field_value"><?php echo $editing ? wp_kses( $name, array() ) : '' ?></div>
                </div>
                <div class="rise_address_field">
                    <div class="rise_address_field_name"><?php _e( 'Address', 'rise-hotel-booking' ) ?></div>
                    <div class="rise_address_field_value"><?php echo $editing ? wp_kses( $address, array() ) : '' ?></div>
                </div>
                <div class="rise_address_field">
                    <div class="rise_address_field_name"><?php _e( 'Email', 'rise-hotel-booking' ) ?></div>
                    <div class="rise_address_field_value"><?php echo $editing ? wp_kses( $email, array() ) : '' ?></div>
                </div>
                <div class="rise_address_field">
                    <div class="rise_address_field_name"><?php _e( 'Phone', 'rise-hotel-booking' ) ?></div>
                    <div class="rise_address_field_value"><?php echo $editing ? wp_kses( $phone, array() ) : '' ?></div>
                </div>
            </div>
            <div class="rise_field_group mt-3" id="rise_details_edit">
                <div class="rise_edit_field">
                    <select name="rise_customer_name_prefix" id="rise_customer_name_prefix">
                        <option value="" selected disabled><?php _e( 'Name Prefix', 'rise-hotel-booking' ) ?></option>
						<?php
						foreach ( NamePrefix::$prefixes as $key => $value ) {
							$selected = '';
							if ( $editing ) {
								if ( $metaData['rise_customer_name_prefix'] == $key ) {
									$selected = ' selected';
								}
							}
							echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . wp_kses( $value, array() ) . '</option>';
						}
						?>
                    </select>
                    <input type="text" name="rise_customer_first_name" id="rise_customer_first_name"
                           placeholder="<?php _e( 'First name', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_first_name'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                    <input type="text" name="rise_customer_last_name" id="rise_customer_last_name"
                           placeholder="<?php _e( 'Last name', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_last_name'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                    <textarea name="rise_customer_address" id="rise_customer_address" cols="30" rows="3"
                              placeholder="<?php _e( 'Address', 'rise-hotel-booking' ) ?>" autocomplete="nope"
                              required><?php echo $editing ? esc_attr( $metaData['rise_customer_address'] ) : '' ?></textarea>
                    <input type="text" name="rise_customer_city" id="rise_customer_city" placeholder="<?php _e( 'City', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_city'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                </div>
                <div class="rise_edit_field">
                    <input type="text" name="rise_customer_state" id="rise_customer_state"
                           placeholder="<?php _e( 'State / Province', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_state'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                    <input type="number" name="rise_customer_postal_code" id="rise_customer_postal_code"
                           placeholder="<?php _e( 'Postal code', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_postal_code'] ) : '' ?>"
                           autocomplete="nope">
                    <input type="email" name="rise_customer_email" id="rise_customer_email" placeholder="<?php _e( 'Email address', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_email'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                    <input type="tel" name="rise_customer_phone" id="rise_customer_phone" placeholder="<?php _e( 'Phone', 'rise-hotel-booking' ) ?>"
                           value="<?php echo $editing ? esc_attr( $metaData['rise_customer_phone'] ) : '' ?>"
                           autocomplete="nope"
                           required>
                    <select name="rise_customer_country" id="rise_customer_country" required>
                        <option value="" selected disabled><?php _e( 'Country', 'rise-hotel-booking' ) ?></option>
						<?php
						foreach ( Country::$countries as $key => $value ) {
							$selected = '';
							if ( $editing ) {
								if ( $metaData['rise_customer_country'] == $key ) {
									$selected = ' selected';
								}
							}
							echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . wp_kses( $value, array() ) . '</option>';
						}
						?>
                    </select>

                </div>
            </div>
        </div>
        <div class="rise_details_field">
            <div class="d-flex justify-content-between">
                <b><?php _e( "Customer's Notes", "rise-hotel-booking" ) ?></b>
                <button class="rise_field_edit" data-action="toggle-notes" data-status="show">
                    <span class="dashicons dashicons-edit"></span>
                </button>
            </div>
            <div class="rise_field_group mt-3" id="rise_notes_show">
                <p><?php echo $editing ? wp_kses( $metaData['rise_customer_notes'], array() ) : '' ?></p>
            </div>
            <div class="rise_field_group mt-3" id="rise_notes_edit">
                <textarea name="rise_customer_notes" id="rise_customer_notes" cols="30" rows="5"
                          placeholder="<?php _e( "Customer's Notes", "rise-hotel-booking" ) ?>"
                          autocomplete="nope"><?php echo $editing ? esc_textarea( $metaData['rise_customer_notes'] ) : '' ?></textarea>
            </div>
        </div>
    </div>

</div>