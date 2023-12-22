<?php

class CouponController {
	public function __construct() {
		// register custom post type
		add_action( 'init', array( $this, 'createPostType' ) );

		// register meta boxes
		add_action( 'add_meta_boxes', array( $this, 'createMetaBoxes' ) );

		// save meta data when post is saved
		add_action( 'save_post_rise_coupon', array( $this, 'saveMetaData' ), 10, 2 );

		// delete coupon
		add_action( 'delete_post', array( $this, 'deletePost' ) );

		// create custom columns for booking
		add_filter( 'manage_rise_coupon_posts_columns', array( $this, 'setCustomColumns' ) );

		// add data to custom columns
		add_action( 'manage_rise_coupon_posts_custom_column', array( $this, 'addDataToCustomColumns' ), 10, 2 );
	}


	/**
	 * <p><b>create custom post type</b></p>
	 */
	public function createPostType() {
		$postTypeData = array(
			'public'              => true,
			'has_archive'         => true,
			'supports'            => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'labels'              => array(
				'name'               => __( 'Coupon Codes', 'rise-hotel-booking' ),
				'singular_name'      => __( 'Coupon Code', 'rise-hotel-booking' ),
				'add_new'            => __( 'Add New Code', 'rise-hotel-booking' ),
				'add_new_item'       => __( 'Add New Code', 'rise-hotel-booking' ),
				'edit_item'          => __( 'Edit Code', 'rise-hotel-booking' ),
				'new_item'           => __( 'New Code', 'rise-hotel-booking' ),
				'view_item'          => __( 'View Code', 'rise-hotel-booking' ),
				'view_items'         => __( 'View Codes', 'rise-hotel-booking' ),
				'search_items'       => __( 'Search Codes', 'rise-hotel-booking' ),
				'not_found'          => __( 'No codes found', 'rise-hotel-booking' ),
				'not_found_in_trash' => __( 'No codes found in Trash', 'rise-hotel-booking' ),
				'all_items'          => __( 'Coupon Codes', 'rise-hotel-booking' ),

			),
			'menu_icon'           => 'dashicons-tickets-alt',
			'rewrite'             => array(
				'slug' => 'hotel-coupons'
			),
			'show_in_menu'        => 'edit.php?post_type=rise_room'
		);

		register_post_type( 'rise_coupon', $postTypeData );
	}


	/**
	 * <p><b>create meta boxes</b></p>
	 */
	public function createMetaBoxes() {
		add_meta_box(
			'rise_coupon_information',
			__( 'Coupon Information', 'rise-hotel-booking' ),
			array( $this, 'couponInformationHTML' ),
			'rise_coupon',
			'normal',
			'high'
		);
	}


	/**
	 * <p><b>Booking Information meta box content</b></p>
	 *
	 * @param $post
	 */
	public function couponInformationHTML( $post ) {
		$couponCode            = get_post_meta( $post->ID, 'rise_coupon_code', true );
		$discountPercentage    = get_post_meta( $post->ID, 'rise_coupon_percentage', true );
		$utilizationDatesStart = get_post_meta( $post->ID, 'rise_coupon_utilization_dates_start', true );
		$utilizationDatesEnd   = get_post_meta( $post->ID, 'rise_coupon_utilization_dates_end', true );

		$utilizationDatesSameAsReservation = get_post_meta(
			                                     $post->ID,
			                                     'rise_coupon_utilization_same_as_reservation',
			                                     true ) == 'on';

		$reservationDatesStart = get_post_meta( $post->ID, 'rise_coupon_reservation_dates_start', true );
		$reservationDatesEnd   = get_post_meta( $post->ID, 'rise_coupon_reservation_dates_end', true );
		$quantity              = get_post_meta( $post->ID, 'rise_coupon_quantity', true );

		if ( ! empty( $utilizationDatesStart ) && ! empty( $utilizationDatesEnd ) ) {
			$utilizationDatesStart = DateTime::createFromFormat( 'Y-m-d H:i:s', $utilizationDatesStart )->format( 'd/m/Y' );
			$utilizationDatesEnd   = DateTime::createFromFormat( 'Y-m-d H:i:s', $utilizationDatesEnd )->format( 'd/m/Y' );
			$utilizationDates      = $utilizationDatesStart . ' - ' . $utilizationDatesEnd;
		}

		if ( ! empty( $reservationDatesStart ) && ! empty( $reservationDatesEnd ) ) {
			$reservationDatesStart = DateTime::createFromFormat( 'Y-m-d H:i:s', $reservationDatesStart )->format( 'd/m/Y' );
			$reservationDatesEnd   = DateTime::createFromFormat( 'Y-m-d H:i:s', $reservationDatesEnd )->format( 'd/m/Y' );
			$reservationDates      = $reservationDatesStart . ' - ' . $reservationDatesEnd;
		}

		include( RISE_LOCATION . '/View/AdminPanel/CouponInformationMetaBox.php' );
	}


	/**
	 * <p><b>save the data in meta box</b></p>
	 *
	 * @param $postID
	 * @param $post
	 *
	 * @return false|void
	 */
	public function saveMetaData( $postID, $post ) {
		// make sure this doesn't work when loading the "new post" page or when deleting the post
		if ( $post->post_status == 'trash' or $post->post_status == 'auto-draft' ) {
			return false;
		}

		if ( function_exists( 'get_current_screen' ) ) {
			if ( ! isset( get_current_screen()->post_type ) ) {
				return;
			}

			if ( get_current_screen()->post_type != 'rise_coupon' ) {
				return;
			}

			if ( empty( $_POST['rise_coupon_utilization_same_as_reservation'] ) ) {
				$_POST['rise_coupon_utilization_same_as_reservation'] = '';
			}
			$metaData = array();
			$keys     = array(
				'rise_coupon_code',
				'rise_coupon_percentage',
				'rise_coupon_utilization_dates',
				'rise_coupon_utilization_same_as_reservation',
				'rise_coupon_reservation_dates',
				'rise_coupon_quantity'
			);

			foreach ( $keys as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					if ( $key == 'rise_coupon_utilization_dates' || $key == 'rise_coupon_reservation_dates' ) {
						$dates                       = explode( ' - ', sanitize_text_field( $_POST[ $key ] ) );
						$start                       = DateTime::createFromFormat( 'd/m/Y H:i:s', sanitize_text_field( $dates[0] ) . ' 00:00:00' );
						$end                         = DateTime::createFromFormat( 'd/m/Y H:i:s', sanitize_text_field( $dates[1] ) . ' 23:59:59' );
						$metaData[ $key . '_start' ] = $start->format( 'Y-m-d H:i:s' );
						$metaData[ $key . '_end' ]   = $end->format( 'Y-m-d H:i:s' );
					} else {
						if ( $key == 'rise_coupon_percentage' || $key == 'rise_coupon_quantity' ) {
							$metaData[ $key ] = intval( $_POST[ $key ] );
						} else {
							$metaData[ $key ] = sanitize_text_field( $_POST[ $key ] );
						}
					}
				}
			}

			$remainingAmount = get_post_meta( $postID, 'rise_coupon_remaining_amount', true );
			$remainingAmount = ! $remainingAmount ? intval( $metaData['rise_coupon_quantity'] ) : $remainingAmount;

			$metaData['rise_coupon_remaining_amount'] = $remainingAmount;

			$data = array(
				'ID'         => $postID,
				'post_title' => sanitize_text_field( $_POST['rise_coupon_code'] ),
				'meta_input' => $metaData
			);

			// unhook this function from save_post hook to prevent infinite loop
			remove_action( 'save_post_rise_coupon', array( $this, 'saveMetaData' ) );

			wp_update_post( $data );

			$logType    = __( 'New coupon created', 'rise-hotel-booking' );
			$logDetails = sprintf( __( 'Coupon code: %s', 'rise-hotel-booking' ), $data['post_title'] );

			// not using the 3rd optional parameter $update because it always returns true even if it's a new post.
			// instead, we're checking if it's a new post or if it's being updated by comparing add and update dates
			if ( $post->post_date != $post->post_modified ) {
				$logType = __( 'Coupon updated', 'rise-hotel-booking' );
			}

			ActivityLogRepository::addLog( $logType, $logDetails );

			// hook it again after updating the post
			add_action( 'save_post_rise_coupon', array( $this, 'saveMetaData' ) );
		}
	}


	/**
	 * <p><b>adds a log when post is deleted </b></p>
	 *
	 * @param $postID
	 *
	 * @return false|void
	 */
	public function deletePost( $postID ) {
		$post = get_post( $postID );
		if ( $post->post_type != 'rise_coupon' ) {
			return false;
		}
		$logType    = __( 'Coupon deleted', 'rise-hotel-booking' );
		$logDetails = sprintf( __( 'Coupon %s has been deleted', 'rise-hotel-booking' ), $post->post_title );
		ActivityLogRepository::addLog( $logType, $logDetails );
	}


	/**
	 * <p><b>creates custom columns</b></p>
	 */
	public function setCustomColumns( $columns ) {
		unset( $columns['date'] );

		$columns['discount_percentage'] = __( 'Discount Percentage', 'rise-hotel-booking' );
		$columns['utilization_dates']   = __( 'Utilization Dates', 'rise-hotel-booking' );
		$columns['reservation_dates']   = __( 'Reservation Dates', 'rise-hotel-booking' );
		$columns['quantity_remaining']  = __( 'Quantity Remaining', 'rise-hotel-booking' );
		$columns['date']                = __( 'Date', 'rise-hotel-booking' );

		return $columns;
	}


	/**
	 * <p><b>adds check-in, check-out, booking status and grand total to custom columns</b></p>
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function addDataToCustomColumns( $column, $post_id ) {
		switch ( $column ) {
			case 'discount_percentage':
				$discountPercentage = get_post_meta( $post_id, 'rise_coupon_percentage', true );
				echo $discountPercentage;
				break;
			case 'utilization_dates':
				$startDate = get_post_meta( $post_id, 'rise_coupon_utilization_dates_start', true );
				$endDate   = get_post_meta( $post_id, 'rise_coupon_utilization_dates_end', true );
				$startDateObj = new DateTime( $startDate );
				$endDateObj   = new DateTime( $endDate );
				echo $startDateObj->format( 'Y-m-d' ) . ' - ' . $endDateObj->format( 'Y-m-d' );
				break;
			case 'reservation_dates':
				$startDate = get_post_meta( $post_id, 'rise_coupon_reservation_dates_start', true );
				$endDate   = get_post_meta( $post_id, 'rise_coupon_reservation_dates_end', true );
				$startDateObj = new DateTime( $startDate );
				$endDateObj   = new DateTime( $endDate );
				echo $startDateObj->format( 'Y-m-d' ) . ' - ' . $endDateObj->format( 'Y-m-d' );
				break;
			case 'quantity_remaining':
				$remainingAmount = get_post_meta( $post_id, 'rise_coupon_remaining_amount', true );
				echo $remainingAmount;
				break;
		}

	}

}