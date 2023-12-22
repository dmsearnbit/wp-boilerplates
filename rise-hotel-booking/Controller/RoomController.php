<?php

class RoomController {
	public function __construct() {
		// register custom post type room
		add_action( 'init', array( $this, 'createPostType' ) );

		// register meta boxes for rooms
		add_action( 'add_meta_boxes', array( $this, 'createMetaBoxes' ) );

		// save meta data when post is saved
		add_action( 'save_post_rise_room', array( $this, 'saveMetaData' ), 10, 2 );

		// delete post
		add_action( 'delete_post', array( $this, 'deletePost' ) );

		// move our custom meta boxes right below the editor
		add_filter( 'get_user_option_meta-box-order_rise_room', array( $this, 'moveMetaBoxesRoom' ) );

		// add custom columns to room list
		// remove comments when room types are added back
		// add_filter( 'manage_rise_room_posts_columns', array( $this, 'addCustomColumns' ) );

		// fill custom columns
		// add_action( 'manage_rise_room_posts_custom_column', array( $this, 'fillCustomColumns' ), 10, 2 );

		// reorder columns
		// add_filter( 'manage_posts_columns', array( $this, 'reorderCustomColumns' ) );
	}


	/**
	 * <p><b>create custom post type for rooms</b></p>
	 */
	public function createPostType() {
		$postTypeData = array(
			'public'              => true,
			'has_archive'         => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'labels'              => array(
				'name'               => __( 'Rise Hotel Booking', 'rise-hotel-booking' ),
				'singular_name'      => __( 'Room', 'rise-hotel-booking' ),
				'add_new'            => __( 'Add New Room', 'rise-hotel-booking' ),
				'add_new_item'       => __( 'Add New Room', 'rise-hotel-booking' ),
				'edit_item'          => __( 'Edit Room', 'rise-hotel-booking' ),
				'new_item'           => __( 'New Room', 'rise-hotel-booking' ),
				'view_item'          => __( 'View Room', 'rise-hotel-booking' ),
				'view_items'         => __( 'View Rooms', 'rise-hotel-booking' ),
				'search_items'       => __( 'Search Rooms', 'rise-hotel-booking' ),
				'not_found'          => __( 'No Rooms found', 'rise-hotel-booking' ),
				'not_found_in_trash' => __( 'No rooms found in Trash', 'rise-hotel-booking' ),
				'all_items'          => __( 'Rooms', 'rise-hotel-booking' ),

			),
			'menu_icon'           => 'dashicons-admin-home',
			'rewrite'             => array(
				'slug' => 'hotel-rooms'
			),
			'taxonomy'            => array(
				'rise_room_type',
			),
			'menu_position'       => 2
		);

		register_post_type( 'rise_room', $postTypeData );
	}


	/**
	 * <p><b>create meta boxes for rooms</b></p>
	 */
	public function createMetaBoxes() {

		// Room Settings meta box
		add_meta_box(
			'rise_room_settings',
			__( 'Room Settings', 'rise-hotel-booking' ),
			array( $this, 'roomSettingsHTML' ),
			'rise_room',
			'normal',
			'high'
		);

		// don't show existing meta data if the user is editing or adding a new rise room
		if ( get_current_screen()->post_type == 'rise_room' ) {
			?>
            <style>
                #postcustomstuff > #list-table {
                    display: none;
                }
            </style>
			<?php
		}
	}


	/**
	 * <p><b>Reorder meta boxes</b></p>
	 *
	 * @return array
	 */
	public function moveMetaBoxesRoom() {
		return array(
			'normal' => join( ",", array(
					'postcustom',
				)
			)
		);
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
		if ( $post->post_status != 'publish' ) {
			return false;
		}

		$type = __( 'New room created', 'rise-hotel-booking' );

		// not using the 3rd optional parameter $update because it always returns true even if it's a new post.
		// instead, we're checking if it's a new post or if it's being updated by comparing add and update dates
		if ( $post->post_date != $post->post_modified ) {
			$type = __( 'Room updated', 'rise-hotel-booking' );
		}

		ActivityLogRepository::addLog( $type, "$post->post_title - ID: $postID" );
		if ( isset( $_POST['rise_room_quantity'] ) ) {
			update_post_meta( $postID, 'rise_room_quantity', intval( $_POST['rise_room_quantity'] ) );
		}
		if ( isset( $_POST['rise_room_numberOfAdults'] ) ) {
			update_post_meta( $postID, 'rise_room_numberOfAdults', intval( $_POST['rise_room_numberOfAdults'] ) );
		}
		if ( isset( $_POST['rise_room_shortDescription'] ) ) {
			update_post_meta( $postID, 'rise_room_shortDescription', sanitize_text_field( $_POST['rise_room_shortDescription'] ) );
		}
	}


	/**
	 * <p><b>adds a log when post is deleted</b></p>
	 *
	 * @param $postID
	 *
	 * @return false|void
	 */
	public function deletePost( $postID ) {
		$post = get_post( $postID );
		if ( $post->post_type != 'rise_room' ) {
			return false;
		}
		ActivityLogRepository::addLog( __( 'Room deleted', 'rise-hotel-booking' ), "$post->post_title - ID: $postID" );
	}


	/**
	 * <p><b>include room settings meta box html</b></p>
	 *
	 * @param $post
	 */
	public function roomSettingsHTML( $post ) {
		$rise_room_quantity         = get_post_meta( $post->ID, 'rise_room_quantity', true );
		$rise_room_numberOfAdults   = get_post_meta( $post->ID, 'rise_room_numberOfAdults', true );
		$rise_room_shortDescription = get_post_meta( $post->ID, 'rise_room_shortDescription', true );

		include( RISE_LOCATION . '/View/AdminPanel/RoomSettingsMetaBox.php' );
	}


	/**
	 * <p><b>Create custom columns</b></p>
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function addCustomColumns( $columns ) {
		$columns['room_type'] = __( 'Room Type', 'rise-hotel-booking' );

		return $columns;
	}


	/**
	 * <p><b>fill custom columns with data</b></p>
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function fillCustomColumns( $column, $post_id ) {
		switch ( $column ) {
			case 'room_type' :
				$terms = get_the_term_list( $post_id, 'rise_room_type', '', ', ' );
				if ( is_string( $terms ) ) {
					echo wp_kses( $terms, array() );
				} else {
					_e( 'Unable to get type(s).', 'rise-hotel-booking' );
				}
				break;

		}
	}


	/**
	 * <p><b>Reorder columns</b></p>
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function reorderCustomColumns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $title ) {
			if ( $key == 'author' ) // put the room_type column before the author column
			{
				$new['room_type'] = 'Room Type';
			}
			$new[ $key ] = $title;
		}

		return $new;
	}


	public static function sanitizeRoomItems( $roomItems ) {
		// checks for dates between 1900-01-01 and 2099-12-31
		$dateRegex         = "~^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$~";
		$dateRegexWithTime = "~^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[ ](\d\d[:]\d\d[:]\d\d)$~";

		$sanitizedRoomItems = array();
		foreach ( $roomItems as $room ) {
			$insertDate = $room->getInsertDate() ? sanitize_text_field( $room->getInsertDate() ) : null;

			$ID             = intval( $room->getRoomID() );
			$checkInDate    = filter_var(
				sanitize_text_field( $room->getCheckInDate() ),
				FILTER_VALIDATE_REGEXP,
				array(
					"options" => array(
						"regexp" => $dateRegex
					)
				)
			);
			$checkOutDate   = filter_var(
				sanitize_text_field( $room->getCheckOutDate() ),
				FILTER_VALIDATE_REGEXP,
				array(
					"options" => array(
						"regexp" => $dateRegex
					)
				)
			);
			$quantity       = intval( $room->getQuantity() );
			$totalPrice     = floatval( $room->getTotalPrice() );
			$numberOfPeople = intval( $room->getNumberOfPeople() );
			$insertDate     = filter_var(
				sanitize_text_field( $insertDate ),
				FILTER_VALIDATE_REGEXP,
				array(
					"options" => array(
						"regexp" => $dateRegexWithTime
					)
				)
			);
			$itemID         = $room->getItemID() ? intval( $room->getItemID() ) : null;
			$bookID         = $room->getBookID() ? intval( $room->getBookID() ) : null;
            $planID         = $room->getPlanID() ? intval( $room->getPlanID() ) : null;

			$sanitizedRoomItems[] = new RoomItem(
				$ID,
				$checkInDate,
				$checkOutDate,
				$quantity,
				$totalPrice,
				$numberOfPeople,
				$insertDate,
				$itemID,
				$bookID,
                $planID
			);
		}

		return $sanitizedRoomItems;
	}
		
	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1',
			'/get-room-meta-box-details/(?P<roomID>\d+)/(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRoomMetaBoxDetailsAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}

    public function getRoomMetaBoxDetailsAPI($request) {
	    if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
		    return false;
	    }

	    $roomID    = intval( $request['roomID'] );
	    $startDate = sanitize_text_field( $request['startDate'] );
	    $endDate   = sanitize_text_field( $request['endDate'] );

        return RoomRepository::getRoomMetaBoxDetails( $roomID, $startDate, $endDate );
    }


	public static function getRoomWarnings() {
		$rooms = get_posts( array(
			'post_type'      => 'rise_room',
			'posts_per_page' => - 1,
		) );

		$warnings = array();

		foreach ( $rooms as $room ) {
			$quantity   = get_post_meta( $room->ID, 'rise_room_quantity', true );
			$numberOfAdults     = get_post_meta( $room->ID, 'rise_room_numberOfAdults', true );
			$postStatus = $room->post_status;
            $regularPlanExists = PricingPlansController::doesRegularPlanExist($room->ID);

			if ( $quantity < 1 ) {
				$warnings[] = array(
					'type'        => 'warning',
					'message'     => sprintf( __( 'Room %s has a quantity of %d.', 'rise-hotel-booking' ), "<b>$room->post_title</b>", "<b>$quantity</b>" ),
					'post_id'     => $room->ID,
					'post_status' => $postStatus,
				);
			}

            if ( $numberOfAdults < 1 ) {
                $warnings[] = array(
                    'type'        => 'warning',
                    'message'     => sprintf( __( 'Room %s has a number of adults of %d.', 'rise-hotel-booking' ), "<b>$room->post_title</b>", "<b>$numberOfAdults</b>" ),
                    'post_id'     => $room->ID,
                    'post_status' => $postStatus,
                );
            }

            if ( ! $regularPlanExists ) {
                $warnings[] = array(
                    'type'        => 'warning',
                    'message'     => sprintf( __( 'Room %s has no regular plan.', 'rise-hotel-booking' ), "<b>$room->post_title</b>" ),
                    'post_id'     => $room->ID,
                    'post_status' => $postStatus,
                );
            }
		}

		return $warnings;
	}
}

?>