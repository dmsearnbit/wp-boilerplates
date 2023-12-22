<div class="rise_meta_box_container">
    <div class="rise_coupon_field">
        <div class="rise-settings-title-container">
            <label for="rise_coupon_code">
				<?php _e( 'Coupon code', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'The coupon code to be used at checkout.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
        <input type="text" name="rise_coupon_code" id="rise_coupon_code" placeholder="Coupon code"
               value="<?php echo esc_attr( $couponCode ) ?>" required>
    </div>
    <div class="rise_coupon_field">
        <div class="rise-settings-title-container">
            <label for="rise_coupon_percentage">
				<?php _e( 'Discount percentage (without "%" sign)', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'The discount percentage to be applied to the total price of the booking.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
        <input type="text" name="rise_coupon_percentage" id="rise_coupon_percentage" placeholder="Discount percentage"
               value="<?php echo esc_attr( $discountPercentage ) ?>" required>
    </div>
    <div class="rise_coupon_field">
        <div class="rise-settings-title-container">
            <label for="rise_coupon_utilization_dates">
				<?php _e( 'Utilization dates', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'The dates when the coupon can be used.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
        <input type="text" name="rise_coupon_utilization_dates" id="rise_coupon_utilization_dates"
               placeholder="<?php _e( 'Utilization dates', 'rise-hotel-booking' ) ?>"
               value="<?php echo esc_attr( @$utilizationDates ) ?>" required>
    </div>
    <div class="rise_coupon_field">
        <input type="checkbox" name="rise_coupon_utilization_same_as_reservation"
               id="rise_coupon_utilization_same_as_reservation"
               value="on" <?php echo $utilizationDatesSameAsReservation ? 'checked' : '' ?>>
        <div class="rise-settings-title-container">
            <label for="rise_coupon_utilization_same_as_reservation">
				<?php _e( 'Utilization dates are the same as reservation dates', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'If checked, the utilization dates will be set as the same as the reservation dates. Any change made to utilization dates will change reservation dates too, and any change made to reservation dates will change utilization dates too.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
    </div>
    <div class="rise_coupon_field">
        <div class="rise-settings-title-container">
            <label for="rise_coupon_reservation_dates">
				<?php _e( 'Reservation dates', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'The reservation dates between which the coupon will be valid.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
        <input type="text" name="rise_coupon_reservation_dates" id="rise_coupon_reservation_dates"
               placeholder="<?php _e( 'Reservation dates', 'rise-hotel-booking' ) ?>"
               value="<?php echo esc_attr( @$reservationDates ) ?>" required>
    </div>
    <div class="rise_coupon_field">
        <div class="rise-settings-title-container">
            <label for="rise_coupon_quantity">
				<?php _e( 'Quantity (-1 for unlimited)', 'rise-hotel-booking' ) ?>
            </label>
            <span class="rise-more-info"
                  title="<?php _e( 'The amount of bookings that the coupon is valid for.', 'rise-hotel-booking' ); ?>">
                ?
            </span>
        </div>
        <input type="number" name="rise_coupon_quantity" id="rise_coupon_quantity"
               placeholder="<?php _e( 'Quantity (-1 for unlimited)', 'rise-hotel-booking' ) ?>"
               value="<?php echo esc_attr( $quantity ) ?>" required>
    </div>
</div>