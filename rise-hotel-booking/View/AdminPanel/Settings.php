<?php
$method = wp_kses( $_SERVER['REQUEST_METHOD'], array() );

if ( $method == 'POST' ) {
	$data = array(
		'currency'                     => sanitize_text_field( @$_POST['rise-currency'] ),
		'tax'                          => floatval( @$_POST['rise-tax'] ),
		'advance-payment'              => floatval( @$_POST['rise-advance-payment'] ),
		'allow-editing-bookings'       => sanitize_text_field( @$_POST['rise-allow-editing-bookings'] ),
		'hotel-name'                   => sanitize_text_field( @$_POST['rise-hotel-name'] ),
		'hotel-address'                => sanitize_text_field( @$_POST['rise-hotel-address'] ),
		'city'                         => sanitize_text_field( @$_POST['rise-city'] ),
		'state'                        => sanitize_text_field( @$_POST['rise-state'] ),
		'country'                      => sanitize_text_field( @$_POST['rise-country'] ),
		'zip'                          => sanitize_text_field( @$_POST['rise-zip'] ),
		'phone'                        => filter_var( @$_POST['rise-phone'], FILTER_SANITIZE_NUMBER_INT ),
		'email'                        => sanitize_email( @$_POST['rise-email'] ),
		'search-result-page'           => intval( @$_POST['rise-search-result-page'] ),
		'room-checkout-page'           => intval( @$_POST['rise-room-checkout-page'] ),
		'terms-and-conditions-page'    => intval( @$_POST['rise-terms-and-conditions-page'] ),
		'delete-data-when-removed'     => sanitize_text_field( @$_POST['rise-delete-data-when-removed'] ),
		'enable-offline-payments'      => sanitize_text_field( @$_POST['rise-enable-offline-payments'] ),
		'enable-arrival-payments'      => sanitize_text_field( @$_POST['rise-enable-arrival-payments'] ),
		'enable-paypal-payments'       => sanitize_text_field( @$_POST['rise-enable-paypal-payments'] ),
		'enable-stripe-payments'       => sanitize_text_field( @$_POST['rise-enable-stripe-payments'] ),
		'enable-iyzico-payments'       => sanitize_text_field( @$_POST['rise-enable-iyzico-payments'] ),
		'offline-payment-instructions' => sanitize_text_field( @$_POST['rise-offline-payment-instructions'] ),
		'arrival-payment-instructions' => sanitize_text_field( @$_POST['rise-arrival-payment-instructions'] ),
		'paypal-payment-instructions'  => sanitize_text_field( @$_POST['rise-paypal-payment-instructions'] ),
		'stripe-payment-instructions'  => sanitize_text_field( @$_POST['rise-stripe-payment-instructions'] ),
		'iyzico-payment-instructions'  => sanitize_text_field( @$_POST['rise-iyzico-payment-instructions'] ),
		'show-in-footer-and-checkout'  => sanitize_text_field( @$_POST['rise-show-in-footer-and-checkout'] ),
		'stripe-secret-key'            => sanitize_text_field( @$_POST['rise-stripe-secret-key'] ),
		'iyzico-api-key'               => sanitize_text_field( @$_POST['rise-iyzico-api-key'] ),
		'iyzico-secret-key'            => sanitize_text_field( @$_POST['rise-iyzico-secret-key'] ),
		'iyzico-test-mode'             => sanitize_text_field( @$_POST['rise-iyzico-test-mode'] ),
		'mail-enabled'                 => sanitize_text_field( @$_POST['rise-mail-enabled'] ),
		'notification-mail-addresses'  => sanitize_textarea_field( @$_POST['rise-notification-mail-addresses'] ),
	);

	$this->handleForm( $data );
}

$settings = $this->getSettingsValues();

$tutorialURL = RISE_PLUGIN_WEBSITE . '/tutorials';
$tutorial    = __(
	sprintf(
		'For tutorials, visit the <a href="%s" target="_blank">official website.</a>',
		$tutorialURL
	),
	'rise-hotel-booking'
);

?>

<div class="wrap">
    <div class="row">
        <div class="col-12 mb-3">
            <h1 class="wp-heading-inline">
				<?php echo wp_kses( $page_title, array() ) ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="rise-settings-tabs">
                <button class="btn btn-primary"
                        id="rise-tab-general"><?php _e( 'General', 'rise-hotel-booking' ) ?></button>
                <button class="btn btn-primary"
                        id="rise-tab-hotel-info"><?php _e( 'Hotel Info', 'rise-hotel-booking' ) ?></button>
                <button class="btn btn-primary"
                        id="rise-tab-payments"><?php _e( 'Payments', 'rise-hotel-booking' ) ?></button>
                <button class="btn btn-primary" id="rise-tab-mail"><?php _e( 'Mail', 'rise-hotel-booking' ) ?></button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-6 mt-3">
            <form method="POST" name="rise_settings">
                <!-- general tab -->
                <div class="rise-setting" id="rise-settings-general" style="display: none;">
                    <div class="rise-tutorial">
                        <p>
							<?php echo wp_kses( $tutorial, array(
								'a' => array(
									'href'   => array(),
									'target' => array()
								)
							) ) ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-currency"><?php _e( 'Currency', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Website currency. All the payments will be made in the currency you select here.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <select name="rise-currency" id="rise-currency">
							<?php
							foreach ( Currency::$currencies as $currency => $symbol ) {
								if ( $settings['currency'] == $currency ) {
									echo '<option value="' . esc_attr( $currency ) . '" selected>' . wp_kses( $currency, array() ) . ' (' . wp_kses( $symbol, array() ) . ')' . '</option>';
								} else {
									echo '<option value="' . esc_attr( $currency ) . '">' . wp_kses( $currency, array() ) . ' (' . wp_kses( $symbol, array() ) . ')' . '</option>';
								}
							}
							?>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-tax"><?php _e( 'Tax Percentage (if not included)', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'This will be included in grand total and advance payment prices.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="number" step="0.0001" class="form-control" id="rise-tax"
                               placeholder="<?php _e( 'Tax', 'rise-hotel-booking' ) ?>"
                               name="rise-tax" value="<?php echo esc_attr( $settings['tax'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-advance-payment"><?php _e( 'Advance Payment Percentage', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'This is the percentage of grand total price that the customers will pay. Leave this at 100 if you want the users to make all the payment at the time of reservation.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="number" step="0.0001" class="form-control" id="rise-advance-payment"
                               placeholder="<?php _e( 'Advance Payment Percentage', 'rise-hotel-booking' ) ?>"
                               name="rise-advance-payment" value="<?php echo esc_attr( $settings['advance-payment'] )?>">
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-search-result-page"><?php _e( 'Room Search Results Page', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'This is the page where the search results will be displayed. Check tutorials on the official Rise Hotel Booking website to see how to create a search results page.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <select name="rise-search-result-page" id="rise-search-result-page">
							<?php
							foreach ( $pages as $page ) {
								if ( $settings['search-result-page'] == $page->ID ) {
									echo '<option value="' . esc_attr( $page->ID ) . '" selected>' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								} else {
									echo '<option value="' . esc_attr( $page->ID ) . '">' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								}
							}
							?>
                        </select>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-room-checkout-page"><?php _e( 'Room Checkout Page', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'This is the page where the checkout form will be displayed. Check tutorials on the official Rise Hotel Booking website to see how to create a checkout page.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <select name="rise-room-checkout-page" id="rise-room-checkout-page">
							<?php
							foreach ( $pages as $page ) {
								if ( $settings['room-checkout-page'] == $page->ID ) {
									echo '<option value="' . esc_attr( $page->ID ) . '" selected>' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								} else {
									echo '<option value="' . esc_attr( $page->ID ) . '">' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								}
							}
							?>
                        </select>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-room-checkout-page"><?php _e( 'Terms and Conditions Page', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'This is the page where the terms and conditions will be displayed. Check tutorials on the official Rise Hotel Booking website to see how to create a terms and conditions page.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <select name="rise-terms-and-conditions-page" id="rise-terms-and-conditions-page">
							<?php
							foreach ( $pages as $page ) {
								if ( $settings['terms-and-conditions-page'] == $page->ID ) {
									echo '<option value="' . esc_attr( $page->ID ) . '" selected>' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								} else {
									echo '<option value="' . esc_attr( $page->ID ) . '">' . wp_kses( get_the_title( $page->ID ), array() ) . '</option>';
								}
							}
							?>
                        </select>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-allow-editing-bookings"><?php _e( 'Allow editing existing booking items?', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'If you enable this option, website administrators will be able to add and remove new rooms to an existing bookings from the Bookings page in the dashboard.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="checkbox" class="form-control" id="rise-allow-editing-bookings"
                               placeholder="<?php _e( 'Allow editing bookings?', 'rise-hotel-booking' ) ?>"
                               name="rise-allow-editing-bookings"
                               value="true" <?php echo wp_kses( $settings['allow-editing-bookings'], array() ) == 'true' ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-delete-data-when-removed"><?php _e( 'Delete all data when plugin is removed?', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'If you enable this option, all data that belongs to the plugin (such as rooms, bookings, settings etc.) will be deleted when plugin is uninstalled.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="checkbox" class="form-control" id="rise-delete-data-when-removed"
                               placeholder="<?php _e( 'Delete all data when plugin is removed?', 'rise-hotel-booking' ) ?>"
                               name="rise-delete-data-when-removed"
                               value="true" <?php echo wp_kses( $settings['delete-data-when-removed'], array() ) == 'true' ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-show-in-footer-and-checkout">
								<?php
								_e(
									'Show "Booking Engine Powered by Rise" link in footer and checkout?',
									'rise-hotel-booking'
								)
								?>
                            </label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'If you enable this option, the Booking engine powered by Rise link will be displayed in the footer of the website and in the checkout page.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="checkbox" class="form-control" id="rise-show-in-footer-and-checkout"
                               placeholder="<?php _e( "Show 'Powered by Rise' link in footer and checkout?", "rise-hotel-booking" ) ?>"
                               name="rise-show-in-footer-and-checkout"
                               value="true" <?php echo wp_kses( $settings['show-in-footer-and-checkout'], array() ) == 'true' ? 'checked' : '' ?>>
                    </div>
                </div>

                <!-- hotel info tab -->
                <div class="rise-setting" id="rise-settings-hotel-info" style="display: none;">
                    <div class="rise-tutorial">
                        <p>
							<?php echo wp_kses( $tutorial, array(
								'a' => array(
									'href'   => array(),
									'target' => array()
								)
							) ) ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-hotel-name"><?php _e( 'Hotel Name', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Name of the hotel.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="text" class="form-control" id="rise-hotel-name"
                               placeholder="<?php _e( 'Hotel Name', 'rise-hotel-booking' ) ?>"
                               name="rise-hotel-name" value="<?php echo esc_attr( $settings['hotel-name'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-hotel-address"><?php _e( 'Hotel Address', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Address of the hotel.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="text" class="form-control" id="rise-hotel-address"
                               placeholder="<?php _e( 'Hotel Address', 'rise-hotel-booking' ) ?>"
                               name="rise-hotel-address" value="<?php echo esc_attr( $settings['hotel-address'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-city"><?php _e( 'City', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'City the hotel is located at.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>

                        <input type="text" class="form-control" id="rise-city"
                               placeholder="<?php _e( 'City', 'rise-hotel-booking' ) ?>" name="rise-city"
                               value="<?php echo esc_attr( $settings['city'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-state"><?php _e( 'State / Province', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'State / Province the hotel is located at.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="text" class="form-control" id="rise-state"
                               placeholder="<?php _e( 'State / Province', 'rise-hotel-booking' ) ?>"
                               name="rise-state"
                               value="<?php echo esc_attr( $settings['state'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-country"><?php _e( 'Country', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Country the hotel is located at.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="text" class="form-control" id="rise-country"
                               placeholder="<?php _e( 'Country', 'rise-hotel-booking' ) ?>"
                               name="rise-country" value="<?php echo esc_attr( $settings['country'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-zip"><?php _e( 'Zip Code', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Zip Code of the region hotel is located at.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="number" class="form-control" id="rise-zip"
                               placeholder="<?php _e( 'Zip Code', 'rise-hotel-booking' ) ?>" name="rise-zip"
                               value="<?php echo esc_attr( $settings['zip'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-phone"><?php _e( 'Phone Number', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Phone number of the hotel.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="tel" class="form-control" id="rise-phone"
                               placeholder="<?php _e( 'Phone Number', 'rise-hotel-booking' ) ?>"
                               name="rise-phone" value="<?php echo esc_attr( $settings['phone'] ) ?>">
                    </div>
                    <div class="form-group">
                        <div class="rise-settings-title-container">
                            <label for="rise-email"><?php _e( 'Email', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Email of the hotel.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="email" class="form-control" id="rise-email"
                               placeholder="<?php _e( 'Email', 'rise-hotel-booking' ) ?>" name="rise-email"
                               value="<?php echo  esc_attr( $settings['email'] )?>">
                    </div>
                </div>

                <!-- payments tab -->
                <div class="rise-setting" id="rise-settings-payments" style="display: none;">
                    <div class="rise-tutorial">
                        <p>
							<?php echo wp_kses( $tutorial, array(
								'a' => array(
									'href'   => array(),
									'target' => array()
								)
							) ) ?>
                        </p>
                    </div>
                    <div class="rise-payments-tabs">
                        <button class="btn btn-secondary"
                                id="rise-tab-offline"><?php _e( 'Offline', 'rise-hotel-booking' ) ?></button>
                        <button class="btn btn-secondary"
                                id="rise-tab-arrival"><?php _e( 'Pay on Arrival', 'rise-hotel-booking' ) ?></button>
                        <!-- <button class="btn btn-secondary" id="rise-tab-paypal">PayPal</button> -->
                        <button class="btn btn-secondary" id="rise-tab-stripe">Stripe</button>
                        <button class="btn btn-secondary" id="rise-tab-iyzico">iyzico</button>
                    </div>

                    <!-- offline payments tab -->
                    <div class="rise-payment" id="rise-payments-offline" style="display: none;">
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-enable-offline-payments"><?php _e( 'Enable offline payments?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'When enabled, customers will be able to complete bookings without making a payment online. The booking will be added with pending status, and customer will make the payment according to the instructions you provided below.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-enable-offline-payments"
                                   placeholder="<?php _e( 'Enable-offline-payments?', 'rise-hotel-booking' ) ?>"
                                   name="rise-enable-offline-payments"
                                   value="true" <?php echo wp_kses( @$settings['enable-offline-payments'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-offline-payment-instructions"><?php _e( 'Offline Payment Instructions', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Instructions for offline payments. This will be displayed on the checkout page.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <textarea name="rise-offline-payment-instructions" id="rise-offline-payment-instructions"
                                      cols="30" rows="10"
                                      placeholder="<?php _e( 'Offline Payment Instructions', 'rise-hotel-booking' ) ?>"><?php
								echo wp_kses( @$settings['offline-payment-instructions'], array() )
								?></textarea>
                        </div>
                    </div>
                    <!-- pay on arrival payments tab -->
                    <div class="rise-payment" id="rise-payments-arrival" style="display: none;">
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-enable-arrival-payments"><?php _e( 'Enable Pay on Arrival?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'When enabled, customers will be able to complete bookings without making a payment online. The booking will be added with pending status, and the customer will make the payment at the check-in date when they arrive at the hotel.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-enable-arrival-payments"
                                   placeholder="<?php _e( 'Enable Pay on Arrival?', 'rise-hotel-booking' ) ?>"
                                   name="rise-enable-arrival-payments"
                                   value="true" <?php echo wp_kses( @$settings['enable-arrival-payments'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-arrival-payment-instructions"><?php _e( 'Pay on Arrival Instructions', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Instructions for Pay on Arrival payments. This will be displayed on the checkout page.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <textarea name="rise-arrival-payment-instructions" id="rise-arrival-payment-instructions"
                                      cols="30" rows="10"
                                      placeholder="<?php _e( 'Pay on Arrival Instructions', 'rise-hotel-booking' ) ?>"><?php
								echo wp_kses( @$settings['arrival-payment-instructions'], array() )
								?></textarea>
                        </div>
                    </div>
                    <!-- paypal payments tab -->
                    <div class="rise-payment" id="rise-payments-paypal" style="display: none;">
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-enable-paypal-payments"><?php _e( 'Enable PayPal payments?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Enable this to allow customers to make payments using PayPal.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-enable-paypal-payments"
                                   placeholder="<?php _e( 'Enable PayPal payments?', 'rise-hotel-booking' ) ?>"
                                   name="rise-enable-paypal-payments"
                                   value="true" <?php echo wp_kses( @$settings['enable-paypal-payments'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-paypal-payment-instructions"><?php _e( 'PayPal Payment Instructions', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Instructions for PayPal payments. This will be displayed on the checkout page.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <textarea name="rise-paypal-payment-instructions" id="rise-paypal-payment-instructions"
                                      cols="30" rows="10"
                                      placeholder="<?php _e( 'PayPal Payment Instructions', 'rise-hotel-booking' ) ?>"><?php
								echo wp_kses( @$settings['paypal-payment-instructions'], array() )
								?></textarea>
                        </div>
                    </div>
                    <!-- stripe payments tab -->
                    <div class="rise-payment" id="rise-payments-stripe" style="display: none;">
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-enable-stripe-payments"><?php _e( 'Enable Stripe payments?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Enable this to allow customers to make payments using Stripe.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-enable-stripe-payments"
                                   placeholder="<?php _e( 'Enable Stripe payments?', 'rise-hotel-booking' ) ?>"
                                   name="rise-enable-stripe-payments"
                                   value="true" <?php echo wp_kses( @$settings['enable-stripe-payments'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-stripe-secret-key"><?php _e( 'Stripe Secret Key', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Stripe Secret Key. This is required to make Stripe payments. For more information, visit tutorials page on Rise Hotel Booking website.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="text" class="form-control" id="rise-stripe-secret-key"
                                   placeholder="<?php _e( 'Stripe Secret Key', 'rise-hotel-booking' ) ?>"
                                   name="rise-stripe-secret-key"
                                   value="<?php echo esc_attr( @$settings['stripe-secret-key'] ) ?>">
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-stripe-payment-instructions"><?php _e( 'Stripe Payment Instructions', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Instructions for Stripe payments. This will be displayed on the checkout page.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <textarea name="rise-stripe-payment-instructions" id="rise-stripe-payment-instructions"
                                      cols="30" rows="10"
                                      placeholder="<?php _e( 'Stripe Payment Instructions', 'rise-hotel-booking' ) ?>"><?php
								echo wp_kses( @$settings['stripe-payment-instructions'], array() )
								?></textarea>
                        </div>
                    </div>
                    <!-- iyzico payments tab -->
                    <div class="rise-payment" id="rise-payments-iyzico" style="display: none;">
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-enable-iyzico-payments"><?php _e( 'Enable Iyzico payments?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Enable this to allow customers to make payments using Iyzico.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-enable-iyzico-payments"
                                   placeholder="<?php _e( 'Enable Iyzico payments?', 'rise-hotel-booking' ) ?>"
                                   name="rise-enable-iyzico-payments"
                                   value="true" <?php echo wp_kses( @$settings['enable-iyzico-payments'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="alert alert-warning">
                            <strong><?php _e( 'Warning!', 'rise-hotel-booking' ) ?></strong>
                            <p>
								<?php
								_e(
									'When enabled, Iyzico logo band will be shown in the footer and at the checkout page.',
									'rise-hotel-booking' )
								?>
                            </p>
                        </div>
						<?php
						$supportedCurrencies     = PaymentController::getIyzicoSupportedCurrencies();
						$iyzicoSupportedCurrency = in_array( SettingsRepository::getSetting( 'currency' ), $supportedCurrencies );
						if ( ! $iyzicoSupportedCurrency ) {
							?>
                            <div class="alert alert-danger">
                                <strong><?php _e( 'Error!', 'rise-hotel-booking' ); ?></strong>
                                <p>
									<?php
									_e( 'Your site currency is not supported by Iyzico. Supported currencies are: ', 'rise-hotel-booking' );
									$currencies = PaymentController::getIyzicoSupportedCurrencies();
									foreach ( $currencies as $currency ) {
										echo '<span class="badge badge-primary">' . wp_kses( $currency, array() ) . '</span> ';
									}
									?>
                                </p>
                            </div>
							<?php
						}
						?>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-iyzico-api-key"><?php _e( 'Iyzico API Key', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Iyzico API Key. This is required for Iyzico payments. For more information, visit tutorials page on Rise Hotel Booking website', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="text" class="form-control" id="rise-iyzico-api-key"
                                   placeholder="<?php _e( 'Iyzico API Key', 'rise-hotel-booking' ) ?>"
                                   name="rise-iyzico-api-key"
                                   value="<?php echo wp_kses( @$settings['iyzico-api-key'], array() ) ?>">
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-iyzico-secret-key"><?php _e( 'Iyzico Secret Key', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Iyzico Secret Key. This is required for Iyzico payments. For more information, visit tutorials page on Rise Hotel Booking website', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="text" class="form-control" id="rise-iyzico-secret-key"
                                   placeholder="<?php _e( 'Iyzico Secret Key', 'rise-hotel-booking' ) ?>"
                                   name="rise-iyzico-secret-key"
                                   value="<?php echo wp_kses( @$settings['iyzico-secret-key'], array() ) ?>">
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-iyzico-test-mode"><?php _e( 'Enable test mode?', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'This is just for test purposes. When enabled, you will not receive any payments and customers will be able create bookings without making any payments. Use with caution, never enable this on a live website.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <input type="checkbox" class="form-control" id="rise-iyzico-test-mode"
                                   placeholder="<?php _e( 'Enable test mode?', 'rise-hotel-booking' ) ?>"
                                   name="rise-iyzico-test-mode"
                                   value="true" <?php echo wp_kses( @$settings['iyzico-test-mode'], array() ) == 'true' ? 'checked' : '' ?>>
                        </div>
                        <div class="form-group d-flex flex-column">
                            <div class="rise-settings-title-container">
                                <label for="rise-iyzico-payment-instructions"><?php _e( 'Iyzico Payment Instructions', 'rise-hotel-booking' ) ?></label>
                                <span class="rise-more-info"
                                      title="<?php _e( 'Instructions for Iyzico payments. This will be displayed on the checkout page.', 'rise-hotel-booking' ); ?>">
                                    ?
                                </span>
                            </div>
                            <textarea name="rise-iyzico-payment-instructions" id="rise-iyzico-payment-instructions"
                                      cols="30" rows="10"
                                      placeholder="<?php _e( 'Iyzico Payment Instructions', 'rise-hotel-booking' ) ?>"><?php
								echo wp_kses( @$settings['iyzico-payment-instructions'], array() );
								?></textarea>
                        </div>
                    </div>

                </div>

                <!-- mail tab -->
                <div class="rise-setting" id="rise-settings-mail" style="display: none;">
                    <div class="rise-tutorial">
                        <p>
							<?php echo wp_kses( $tutorial, array(
								'a' => array(
									'href'   => array(),
									'target' => array()
								)
							) ) ?>
                        </p>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-mail-enabled"><?php _e( 'Enable sending mails', 'rise-hotel-booking' ) ?></label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'When enabled, booking confirmation mails will be sent to customers, and the mail addresses entered below.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <input type="checkbox" class="form-control" id="rise-mail-enabled"
                               placeholder="Enable sending mails"
                               name="rise-mail-enabled"
                               value="true" <?php echo wp_kses( @$settings['mail-enabled'], array() ) == 'true' ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group d-flex flex-column">
                        <div class="rise-settings-title-container">
                            <label for="rise-notification-mail-addresses">
								<?php
								_e(
									'Email address(es) to receive notifications (one address per line)',
									'rise-hotel-booking'
								)
								?>
                            </label>
                            <span class="rise-more-info"
                                  title="<?php _e( 'Enter the email addresses you want to receive notifications when a booking is made. You can enter multiple email addresses, one address per line.', 'rise-hotel-booking' ); ?>">
                                ?
                            </span>
                        </div>
                        <textarea name="rise-notification-mail-addresses" id="rise-notification-mail-addresses"
                                  cols="30" rows="10"
                                  placeholder="<?php _e( 'Email address(es) to receive notifications', 'rise-hotel-booking' ) ?>"><?php
							echo wp_kses( @$settings['notification-mail-addresses'], array() )
							?></textarea>
                    </div>
                </div>
                <div class="rise-settings-submit">
                    <input type="submit" value="<?php echo __( 'Apply Changes', 'rise-hotel-booking' ) ?>"
                           class="btn btn-success" id="rise-btn-submit">
                </div>
            </form>
        </div>
    </div>
</div>