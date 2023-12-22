<?php
$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] );

if ( $method == 'POST' ) {
    //TODO: degisken tiplerini kontrol et ona göre sanitization ver. 
	$this->handleForm(
		intval( $_POST['rise_room_id'] ),
		floatval( $_POST['rise-plan-regular-price'] ),
		map_deep( @$_POST['rise-plan-dates'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-id'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-price'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-rates'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-delete'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-priority'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-rates-regular'], 'sanitize_text_field' ),
		map_deep( @$_POST['rise-plan-no-date'], 'intval' )
	);
}

$currency       = SettingsRepository::getSetting( 'currency' );
$currencySymbol = Currency::$currencies[ $currency ];
$rates          = CustomRatesController::getPricingRates();
?>

<!-- Pricing plan template -->
<div class="rise-plan d-none" id="rise-plan-to-copy">
    <div class="rise-plan-buttons">
        <div class="rise-plan-buttons-left">
            <button class="btn btn-danger" data-action="rise-plan-delete">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <div class="rise-plan-buttons-right">
            <button class="btn btn-primary" data-action="rise-plan-move-down">
                <span class="dashicons dashicons-arrow-down-alt"></span>
            </button>
            <button class="btn btn-primary" data-action="rise-plan-move-up">
                <span class="dashicons dashicons-arrow-up-alt"></span>
            </button>
        </div>
    </div>
    <div class="rise-plan-header">
        <div class="rise-plan-title">
            <h4><?php echo __( 'Dates: ', 'rise-hotel-booking' ) ?></h4>
        </div>
        <div class="rise-plan-input">
            <label for="" data-rise-no-date><?php _e( 'No date', 'rise-hotel-booking' ) ?></label>
            <input name="rise-plan-no-date[]" type="checkbox" data-rise-no-date>
        </div>
        <div class="rise-plan-input">
            <input name="rise-plan-dates[]" class="rise-plan-dates" readonly required>
        </div>
    </div>
    <div class="rise-plan-line"></div>
    <div class="rise-plan-content">
        <div class="rise-plan-currency"><?php echo wp_kses( $currencySymbol, array() ) ?></div>
        <input type="number" name="rise-plan-price[]"
               placeholder="<?php echo __( 'Nightly price', 'rise-hotel-booking' ) ?>"
               min="1" required>
    </div>
    <div class="rise-plan-rates">
		<?php
		foreach ( $rates as $rate ) {
			$currentRateID    = $rate->ID;
			$currentRateTitle = $rate->post_title;
			?>
            <div class="rise-plan-rate">
                <input type="checkbox"
                       name="rise-plan-rates[]"
                       data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>"
                       id=""
                       value="">
                <label for=""
                       data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>">
					<?php echo wp_kses( $currentRateTitle, array() ) ?>
                </label>
            </div>
			<?php
		}
		?>
    </div>
    <input type="hidden" name="rise-plan-id[]" value="null">
    <input type="hidden" name="rise-plan-priority[]" data-rise-priority value="0">
</div>

<input type="hidden" name="rise_room_pricing_plans" value="<?php echo esc_attr( @$_GET['rise_room_id'] ) ?>">
<input type="hidden" name="rise_rest" value="<?php echo get_rest_url( null, 'rise-hotel-booking/v1/get-prices' ) ?>">
<input type="hidden" name="rise_currency_symbol" value="<?php echo esc_attr( $currencySymbol ) ?>">
<input type="hidden" name="rise_php_max_int" value="<?php echo intval( PHP_INT_MAX ) ?>">

<div class="wrap">
    <div class="row">
        <div class="col-12 mb-3">
            <h1 class="wp-heading-inline">
				<?php echo wp_kses( $page_title, array() ) ?>
            </h1>
        </div>

        <div class="col-md-5">
            <form method="POST" name="rise_pricing_plans">
                <div class="form-group w-100">
                    <select name="rise_room_id" id="rise_rooms">
                        <option value="" selected disabled><?php _e( 'Select a room', 'rise-hotel-booking' ) ?></option>
						<?php
						foreach ( $rooms as $room ) {
							$selected = '';
							if ( @$_GET['rise_room_id'] == $room->ID ) {
								$selected = 'selected';
							}
							echo '<option value="' . intval( $room->ID ) . '" ' . wp_kses( $selected, array() ) . '>' . wp_kses( $room->post_title, array() ) . '</option>';
						}
						?>
                    </select>
                </div>
				<?php
				// check if user selected a room
				if ( isset( $_GET['rise_room_id'] ) ) {
					$rise_room_id = intval( $_GET['rise_room_id'] );
					// check if the room user selected exists
					if ( get_post_status( $rise_room_id ) ) {
						?>
                        <!-- rise plan div -->
                        <div class="rise-plan">
                            <div class="rise-plan-header">
                                <div class="rise-plan-title">
                                    <h4><?php echo __( 'Regular Price', 'rise-hotel-booking' ) ?></h4>
                                    <span class="rise-more-info"
                                          title="<?php _e( 'This price will be valid for the dates when no other plans are set.', 'rise-hotel-booking' ); ?>">
                                        ?
                                    </span>
                                </div>
                            </div>
                            <div class="rise-plan-line"></div>
                            <div class="rise-plan-content">
								<?php
								// converting regular price to html value
								$regularPrice = $this->convertPriceToHTMLValue( 'regular', $rise_room_id );
								?>
                                <div class="rise-plan-currency"><?php echo wp_kses( $currencySymbol, array() ) ?></div>
                                <input type="number" name="rise-plan-regular-price"
                                       placeholder="<?php echo __( 'Nightly price', 'rise-hotel-booking' ) ?>"
                                       min="1" <?php echo wp_kses( $regularPrice, array() ) ?> required>
                            </div>
                            <div class="rise-plan-rates">
								<?php
								$plan = $this->getRegularPlanByRoomID( $rise_room_id );
								if ( $plan ) {
									$regularRates = $this->getRatesByPlanID( $plan['plan_id'] );
									foreach ( $rates as $rate ) {
										$currentRateID    = $rate->ID;
										$currentRateTitle = $rate->post_title;

										?>
                                        <div class="rise-plan-rate">
                                            <input type="checkbox"
                                                   name="rise-plan-rates-regular[]"
                                                   data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>"
                                                   id="rise-rate-regular-<?php echo esc_attr( $currentRateID ) ?>"
                                                   value="<?php echo esc_attr( $currentRateID ) ?>"
												<?php echo in_array( intval( $currentRateID ), $regularRates ) ? 'checked' : '' ?>>
                                            <label for="rise-rate-regular-<?php echo esc_attr( $currentRateID ) ?>"
                                                   data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>">
												<?php echo wp_kses( $currentRateTitle, array() ) ?>
                                            </label>
                                        </div>
										<?php
									}
								}
								?>
                            </div>
                        </div>

                        <!-- other plans title and buttons -->
                        <div class="rise-other-plans-title">
                            <div class="rise-other-plans-title-container">
                                <h4><?php echo __( 'Other plans', 'rise-hotel-booking' ) ?></h4>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Each plan has its own dates and price. The price is valid for the dates set on the plan.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <div class="rise-other-plans-buttons">
                                <button class="btn btn-primary" id="rise-add-new-plan">
                                    <b>+</b>
                                </button>
                            </div>
                        </div>

                        <div class="rise-other-plans">
							<?php
							$plans = $this->getPlans( $rise_room_id );
							if ( $plans ) {
								foreach ( $plans as $planArr ) {
									$plan   = $planArr['plan'];
									$noDate = $plan->getPlanType() == 'other-no-date';
									// converting start and end time to daterangepicker format
									$planDate = $this->convertDateType( $plan->getStartTime(), $plan->getEndTime() );

									// converting plan price to html value, so we can add it easily
									$planPrice = $this->convertPriceToHTMLValue( 'other', null, $plan->getPrice() );
									?>
                                    <div class="rise-plan" id="rise-plan-existing"
                                         data-rise-plan-id="<?php echo esc_attr( $plan->getPlanID() ) ?>">
                                        <div class="rise-plan-buttons">
                                            <div class="rise-plan-buttons-left">
                                                <button class="btn btn-danger" data-action="rise-plan-delete">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                            <div class="rise-plan-buttons-right">
                                                <button class="btn btn-primary" data-action="rise-plan-move-down">
                                                    <span class="dashicons dashicons-arrow-down-alt"></span>
                                                </button>
                                                <button class="btn btn-primary" data-action="rise-plan-move-up">
                                                    <span class="dashicons dashicons-arrow-up-alt"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="rise-plan-header">
                                            <div class="rise-plan-title">
                                                <h4><?php echo __( 'Dates: ', 'rise-hotel-booking' ) ?></h4>
                                            </div>
                                            <div class="rise-plan-input">
                                                <label for="" data-rise-no-date>No date</label>
                                                <input name="rise-plan-no-date[]" type="checkbox" data-rise-no-date
													<?php echo $noDate ? 'checked' : '' ?>>
                                            </div>
                                            <div class="rise-plan-input">
                                                <input name="rise-plan-dates[]" class="rise-plan-dates"
                                                       value="<?php echo esc_attr( $planDate ) ?>" readonly
                                                       required <?php echo $noDate ? 'style="display: none;"' : '' ?>>
                                            </div>
                                        </div>
                                        <div class="rise-plan-line"></div>
                                        <div class="rise-plan-content">
                                            <div class="rise-plan-currency"><?php echo wp_kses( $currencySymbol, array() ) ?></div>
                                            <input type="number" name="rise-plan-price[]"
                                                   placeholder="<?php echo __( 'Nightly price', 'rise-hotel-booking' ) ?>"
                                                   min="1" <?php echo wp_kses( $planPrice, array() ) ?> required>
                                        </div>
                                        <div class="rise-plan-rates">
											<?php
											foreach ( $rates as $rate ) {
												$currentRateID    = $rate->ID;
												$currentRateTitle = $rate->post_title;

												?>
                                                <div class="rise-plan-rate">
                                                    <input type="checkbox"
                                                           name="rise-plan-rates[]"
                                                           data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>"
                                                           id="rise-rate-<?php echo esc_attr( $plan->getPlanID() ) . '-' . esc_attr( $currentRateID ) ?>"
                                                           value="<?php echo esc_attr( $plan->getPlanID() ) . '-' . esc_attr( $currentRateID ) ?>"
														<?php echo in_array( wp_kses( $currentRateID, array() ), $planArr['rates'] ) ? 'checked' : '' ?>>
                                                    <label for="rise-rate-<?php echo esc_attr( $plan->getPlanID() ) . '-' . esc_attr( $currentRateID ) ?>"
                                                           data-rise-rate-id="<?php echo esc_attr( $currentRateID ) ?>">
														<?php echo wp_kses( $currentRateTitle, array() ) ?>
                                                    </label>
                                                </div>
												<?php
											}
											?>
                                        </div>
                                        <input type="hidden" name="rise-plan-id[]"
                                               value="<?php echo esc_attr( $plan->getPlanID() ) ?>">
                                        <input type="hidden" name="rise-plan-priority[]" data-rise-priority value="0">
                                    </div>
									<?php
								}
							}
							?>
                        </div>

                        <div id="rise-plans-to-delete" class="rise-plans-to-delete"></div>

                        <div class="rise-plans-submit">
                            <input type="submit" value="<?php echo __( 'Apply Changes', 'rise-hotel-booking' ) ?>"
                                   class="btn btn-success" id="rise-btn-submit">
                        </div>

						<?php
					} else {
						echo '<h2>' . __( 'Room not found', 'rise-hotel-booking' ) . '</h2>';
					}
				}
				?>
            </form>
        </div>

        <div class="col-md-7">
            <div id="rise-calendar-pricing-plans"></div>
        </div>

    </div>
</div>