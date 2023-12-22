<?php

include_once( RISE_LOCATION . '/Repository/SettingsRepository.php' );
include_once( RISE_LOCATION . '/Model/Currency.php' );

class SettingsController {
	public function __construct() {
		// add settings page
		add_action( 'admin_menu', array( $this, 'addPage' ) );
	}


	/**
	 * <p><b>Adds settings page under rise_room post type</b></p>
	 */
	public function addPage() {
		add_submenu_page(
			'edit.php?post_type=rise_room',
			__( 'Settings', 'rise-hotel-booking' ),
			__( 'Settings', 'rise-hotel-booking' ),
			'manage_options',
			'rise_settings',
			array( $this, 'settingsHTML' )
		);
	}


	/**
	 * <p><b>Returns values of every setting</b></p>
	 *
	 * @return array
	 */
	public function getSettingsValues() {
		$options = array();

		foreach ( SettingsRepository::OPTIONS as $option ) {
			$value              = SettingsRepository::getSetting( $option );
			$options[ $option ] = $value;
		}

		return $options;
	}


	/**
	 * <p><b>Draws settings page</b></p>
	 */
	public function settingsHTML() {
		$page_title = get_admin_page_title();

		$args  = array(
			'sort_order'   => 'asc',
			'sort_column'  => 'post_title',
			'hierarchical' => 1,
			'exclude'      => '',
			'include'      => '',
			'meta_key'     => '',
			'meta_value'   => '',
			'authors'      => '',
			'child_of'     => 0,
			'parent'       => - 1,
			'exclude_tree' => '',
			'number'       => '',
			'offset'       => 0,
			'post_type'    => 'page',
			'post_status'  => 'publish'
		);
		$pages = get_pages( $args );

		include( RISE_LOCATION . '/View/AdminPanel/Settings.php' );
	}


	/**
	 * <p><b>Handles submitted pricing plan page form</b></p>
	 *
	 * @param $data
	 */
	public function handleForm( $data ) {
		foreach ( $data as $key => $value ) {
			// check if the lines user entered are e-mail addresses
			if ( $key == 'notification-mail-addresses' ) {
				$lines = explode( PHP_EOL, $value );
				$value = '';
				foreach ( $lines as $line ) {
					if ( filter_var( $line, FILTER_VALIDATE_EMAIL ) ) {
						$value .= PHP_EOL . $line;
					}
				}
				$value = trim( $value );
			}

			SettingsRepository::updateSetting( $key, htmlspecialchars( $value, ENT_QUOTES ) );
		}
	}


	/**
	 *<p><b>Sets default settings and their values.</b></p>
	 */
	public function setDefaultValues() {
		$templateFileContents = file_get_contents( RISE_LOCATION . '/View/DefaultEmailTemplate.html' );
		$adminDefaultTemplate = $templateFileContents;
		$customerDefaultTemplate = $templateFileContents;

		$customerDefaultTemplate = join( "\n", array_map( "trim", explode( "\n", $customerDefaultTemplate ) ) );
		$adminDefaultTemplate    = join( "\n", array_map( "trim", explode( "\n", $adminDefaultTemplate ) ) );

		$defaultValues = [
			'advance-payment'             => 100,
			'tax'                         => 0,
			'currency'                    => 'EUR',
			'enable-offline-payments'     => 'false',
			'show-in-footer-and-checkout' => 'false',
			'mail-enabled'                => 'true',
			'notification-mail-addresses' => get_option( 'admin_email' ),
			'admin-mail-template'         => $adminDefaultTemplate,
			'customer-mail-template'      => $customerDefaultTemplate
		];

		foreach ( $defaultValues as $key => $value ) {
			SettingsRepository::setDefaultValue( $key, $value );
		}
	}

}