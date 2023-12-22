<?php
if ( ! $allowEditing ) {
	?>
    <style>
        button[data-action=rise-booking-item-edit] {
            display: none;
        }
    </style>
	<?php
}
?>

<div id="rise_room_item_modal" data-action="new" data-update-item-id="null">
    <div class="rise-modal-box">
        <div class="rise-modal-box-header">
            <div class="rise-modal-title">
				<?php echo __( 'Add new item', 'rise-hotel-booking' ) ?>
            </div>
            <div>
                <button id="rise_close_modal">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
        </div>
        <div class="rise-modal-box-body" id="rise-modal-box-body">
            <div class="rise-modal-box-content-field">
                <select name="rise-modal-room" id="rise-modal-room">
                    <option value="" selected disabled id="rise-select-default-option"><?php _e('Select room', 'rise-hotel-booking') ?></option>
					<?php
					foreach ( $rooms as $room ) {
						echo '<option value="' . intval( esc_attr( $room->ID ) ) . '">' . wp_kses( $room->post_title, array() ) . '</option>';
					}
					?>
                </select>
            </div>
            <div class="rise-modal-box-content-field">
                <input type="text" placeholder="<?php _e('Check-in & Check-out', 'rise-hotel-booking') ?>" name="rise-modal-dates" class="rise-modal-dates">
            </div>
            <style>
                .daterangepicker {
                    z-index: 9999 !important;
                }
            </style>
        </div>
        <div class="rise-modal-box-rates">

        </div>
        <div class="rise-modal-box-footer">
            <div class="flex-fill">
                <button class="btn btn-primary"
                        id="rise_check_modal"><?php echo __( 'Check Availability', 'rise-hotel-booking' ) ?></button>
            </div>
            <div>
                <button id="rise_add_modal" class="btn btn-success">
					<?php echo __( 'Add/Save', 'rise-hotel-booking' ) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="rise_tax" value="<?php echo esc_attr( $tax ) ?>">
<input type="hidden" name="rise_advance_payment" value="<?php echo esc_attr( $advance_payment ) ?>">
<input type="hidden" name="rise_currency" value="<?php echo esc_attr( $currency ) ?>">
<div id="rise_booking_items_input">
	<?php
	if ( $editing ) {
		$bookingItemID = 0;
		foreach ( $items as $item ) {
			?>
            <div class="rise-booking-item" data-item-id="<?php echo esc_attr( $bookingItemID ) ?>">
                <input type="hidden" name="rise_action[]" value="update">
                <input type="hidden" name="rise_plan_id[]" value="<?php echo esc_attr( $item->getPlanID() ) ?>">
                <input type="hidden" name="rise_item_id[]" value="<?php echo esc_attr( $item->getItemID() ) ?>">
                <input type="hidden" name="rise_room_id[]" value="<?php echo esc_attr( $item->getRoomID() ) ?>">
                <input type="hidden" name="rise_checkin_date[]"
                       value="<?php echo esc_attr( $item->getCheckInDate() ) ?>">
                <input type="hidden" name="rise_checkout_date[]"
                       value="<?php echo esc_attr( $item->getCheckOutDate() ) ?>">
                <input type="hidden" name="rise_quantity[]" value="<?php echo esc_attr( $item->getQuantity() ) ?>">
                <input type="hidden" name="rise_number_of_people[]"
                       value="<?php echo esc_attr( $item->getNumberOfPeople() ) ?>">
                <input type="hidden" name="rise_total_price[]" class="rise_total_price"
                       value="<?php echo esc_attr( $item->getTotalPrice() ) ?>">
            </div>
			<?php
			$bookingItemID ++;
		}
	}
	?>
</div>

<div class="rise_container">
    <table class="table">
        <thead>
        <tr>
            <th scope="col"><?php echo __( 'Item', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Check-in & Check-out', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Night', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Qty', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Includes', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Total', 'rise-hotel-booking' ) ?></th>
            <th scope="col" class="text-center"><?php echo __( 'Actions', 'rise-hotel-booking' ) ?></th>
        </tr>
        </thead>
        <tbody id="added_rooms">
		<?php
		if ( $editing ) {
			$bookingItemID = 0;
			foreach ( $items as $item ) {
				$planID = $item->getPlanID();
				$rates  = PricingPlansRepository::getRatesByPlanID( $planID );
				?>
                <tr>
                    <td><?php echo '<a href="' . get_permalink( $item->getRoomID() ) . '">' . get_the_title( $item->getRoomID() ) . '</a>' ?></td>
                    <td class="text-center"><?php echo $this->convertDatesToTableType( $item->getCheckInDate(), $item->getCheckOutDate() ) ?></td>
                    <td class="text-center"><?php echo $this->getNumberOfNights( $item->getCheckInDate(), $item->getCheckOutDate() ) ?></td>
                    <td class="text-center"><?php echo $item->getQuantity() ?></td>
                    <td class="text-center rise-booking-items-table-rates">
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
                    <td class="text-center"><?php echo $currency . number_format( $item->getTotalPrice(), 2 ) ?></td>
                    <td class="text-center">
                        <button class="btn btn-primary" data-action="rise-booking-item-edit"
                                data-item-id="<?php echo $bookingItemID ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="btn btn-danger" data-action="rise-booking-item-delete"
                                data-item-id="<?php echo $bookingItemID ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
				<?php
				$bookingItemID ++;
			}
		}
		?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="7">
                <div class="d-flex justify-content-end">
                    <button id="rise-add-room-item" class="btn btn-primary">
						<?php echo __( 'Add Room Item', 'rise-hotel-booking' ) ?>
                    </button>
                </div>
            </th>
        </tr>
        </tfoot>
    </table>
    <table class="table">
        <tbody>
        <tr class="d-flex">
            <td class="flex-fill text-center">
                <b><?php echo __( 'Sub Total', 'rise-hotel-booking' ); ?></b>
            </td>
            <td>
                <span><?php echo $currency ?><span id="rise_subtotal">0.00</span></span>
            </td>
        </tr>
        <tr class="d-flex">
            <td class="flex-fill text-center">
                <b><?php echo __( 'Tax', 'rise-hotel-booking' ); ?></b>
            </td>
            <td>
                <span><?php echo $currency ?><span id="rise_tax">0.00</span></span>
            </td>
        </tr>
        <tr class="d-flex">
            <td class="flex-fill text-center">
                <b><?php echo __( 'Grand Total', 'rise-hotel-booking' ); ?></b>
            </td>
            <td>
                <span><?php echo $currency ?><span id="rise_grand_total">0.00</span></span>
            </td>
        </tr>
        <tr class="d-flex">
            <td class="flex-fill text-center">
                <b><?php echo __( 'Advance Payment', 'rise-hotel-booking' ); ?></b>
            </td>
            <td>
                <span><?php echo $currency ?><span id="rise_advance_payment">0.00</span></span>
            </td>
        </tr>
        </tbody>
    </table>
</div>