<?php
global $wpdb;

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

$taxonomies = array(
	'rise_room_type',
);

$deleteData = get_option( 'rise_delete-data-when-removed' );

if ( $deleteData == 'true' && defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	$optionsTable        = $wpdb->prefix . 'options';
	$pricingPlansTable   = $wpdb->prefix . 'rise_pricing_plans';
	$bookingDetailsTable = $wpdb->prefix . 'rise_booking_details';
	$activityLogTable    = $wpdb->prefix . 'rise_activity_log';

	// delete all options
	foreach ( OPTIONS as $option ) {
		$wpdb->delete( $optionsTable, array(
			'option_name' => 'rise_' . $option
		) );
	}

	// drop all our tables
	$wpdb->query( "DROP TABLE IF EXISTS $pricingPlansTable" );
	$wpdb->query( "DROP TABLE IF EXISTS $bookingDetailsTable" );
	$wpdb->query( "DROP TABLE IF EXISTS $activityLogTable" );

	$bookingPosts = get_posts( array(
		'post_type'   => 'rise_booking',
		'numberposts' => - 1
	) );

	$roomPosts = get_posts( array(
		'post_type'   => 'rise_room',
		'numberposts' => - 1
	) );

	$couponPosts = get_posts( array(
		'post_type'   => 'rise_coupon',
		'numberposts' => - 1
	) );

	$customRatesPosts = get_posts( array(
		'post_type'   => 'rise_pricing_rate',
		'numberposts' => - 1
	) );

	// delete all bookings
	foreach ( $bookingPosts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// delete all rooms
	foreach ( $roomPosts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// delete all coupons
	foreach ( $couponPosts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// delete all custom rates
	foreach ( $customRatesPosts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// delete all terms of all our taxonomies
	// get_term and wp_delete_term are not available in uninstall.php, so we get the terms and delete them using wpdb.
	foreach ( $taxonomies as $taxonomy ) {
		// get all terms
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );

		// delete all rows related to our custom taxonomy
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
			}
		}

		// delete taxonomy
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
	}
}
