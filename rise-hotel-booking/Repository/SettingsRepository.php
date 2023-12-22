<?php

class SettingsRepository {
	const TABLE_NAME = 'options';
	const OPTION_PREFIX = 'rise_';
	const OPTIONS = array(
		'currency',
		'tax',
		'advance-payment',
		'allow-editing-bookings',
		'hotel-name',
		'hotel-address',
		'city',
		'state',
		'country',
		'zip',
		'phone',
		'email',
		'search-result-page',
		'room-checkout-page',
		'terms-and-conditions-page',
		'delete-data-when-removed',
		'enable-offline-payments',
		'enable-arrival-payments',
		'enable-paypal-payments',
		'enable-stripe-payments',
		'enable-iyzico-payments',
		'offline-payment-instructions',
		'arrival-payment-instructions',
		'paypal-payment-instructions',
		'stripe-payment-instructions',
		'iyzico-payment-instructions',
		'show-in-footer-and-checkout',
		'stripe-secret-key',
		'iyzico-api-key',
		'iyzico-secret-key',
		'iyzico-test-mode',
		'mail-enabled',
		'notification-mail-addresses'
	);


	/**
	 * <p><b>Returns table name with table prefix included</b></p>
	 *
	 * @return string
	 */
	private static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . SettingsRepository::TABLE_NAME;
	}


	/**
	 * <p><b>Updates a row in options table</b></p>
	 *
	 * @param $option
	 * @param $value
	 *
	 * @return bool|int
	 */
	public static function updateSetting( $option, $value ) {
		global $wpdb;
		$option = self::OPTION_PREFIX . $option;
		$table  = SettingsRepository::getTableName();
		$sql    = $wpdb->prepare( "INSERT INTO $table (option_name, option_value, autoload) VALUES (%s, %s, %s)
 										ON DUPLICATE KEY UPDATE option_name = %s, option_value = %s, autoload = %s",
			$option, $value, 'yes', $option, $value, 'yes' );

		return $wpdb->query( $sql );
	}


	/**
	 * <p><b>Returns an option's value</b></p>
	 *
	 * @param $option
	 *
	 * @return mixed
	 */
	public static function getSetting( $option ) {
		global $wpdb;
		$table  = self::getTableName();
		$option = self::OPTION_PREFIX . $option;

		$sql     = $wpdb->prepare( "SELECT option_value FROM $table WHERE option_name = %s", $option );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return empty( $results[0] ) ? null : $results[0]['option_value'];
	}


	/**
	 * <p><b>Adds the given option and value to wp_options table. If the given option name already exists,
	 * the sql query doesn't update the existing option.</b></p>
	 *
	 * @param $option
	 * @param $value
	 *
	 * @return bool|int
	 */
	public static function setDefaultValue( $option, $value ) {
		global $wpdb;
		$option = self::OPTION_PREFIX . $option;
		$table  = SettingsRepository::getTableName();

		// the reason we're doing option_name = option_name on update is to basically tell mysql to ignore this operation.
		$sql = $wpdb->prepare( "INSERT INTO $table (option_name, option_value, autoload) VALUES (%s, %s, %s)
 										ON DUPLICATE KEY UPDATE option_name = option_name",
			$option, $value, 'yes' );

		return $wpdb->query( $sql );
	}
}