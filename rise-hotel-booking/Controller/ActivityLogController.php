<?php

include_once( RISE_LOCATION . '/Repository/ActivityLogRepository.php' );

class ActivityLogController {
	public function __construct() {
		// add activity log page under rise_room post type
		add_action( 'admin_menu', array( $this, 'addPage' ) );
	}


	/**
	 * <p><b>Adds activity log page under rise_room post type</b></p>
	 */
	public function addPage() {
		add_submenu_page(
			'edit.php?post_type=rise_room',
			__( 'Activity Log', 'rise-hotel-booking' ),
			__( 'Activity Log', 'rise-hotel-booking' ),
			'manage_options',
			'rise_activity_log',
			array( $this, 'activityLogHTML' )
		);
	}


	/**
	 * <p><b>Includes activity log view</b></p>
	 */
	public function activityLogHTML() {
		$page_title = get_admin_page_title();
		include( RISE_LOCATION . '/View/AdminPanel/ActivityLog.php' );
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1',
			'/get-activity-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getActivityLogAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}


	/**
	 * <p><b>API endpoint to get activity logs</b></p>
	 * @param $request
	 *
	 * @return array|false
	 */
	public function getActivityLogAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}
		$start  = $request->get_param( 'start' );
		$length = $request->get_param( 'length' );
		$draw   = $request->get_param( 'draw' );
		$search = $request->get_param( 'search' );
		$order  = $request->get_param( 'order' );

		// set order by variable to their column name in database
		switch ( $order[0]['column'] ) {
			case 1:
				$orderBy = 'activity_type';
				break;
			case 2:
				$orderBy = 'details';
				break;
			case 3:
				$orderBy = 'date';
				break;
			default:
				$orderBy = 'id';
				break;
		}

		$orderData = array(
			'order-by' => $orderBy,
			'dir' => $order[0]['dir'] == 'asc' ? 'ASC' : 'DESC'
		);

		$search = sanitize_text_field( $search['value'] );

		$logs = ActivityLogRepository::getLogsByPage( $start, $length, $draw, $search, $orderData );
		$res  = [
			'draw'            => $logs['draw'],
			'recordsTotal'    => $logs['recordsTotal'],
			'recordsFiltered' => $logs['recordsFiltered'],
			'data'            => [],
		];

		foreach ( $logs['data'] as $log ) {
			array_push( $res['data'], [
				$log['id'],
				$log['activity_type'],
				$log['details'],
				$log['date'],
			] );
		}

		return $res;
	}
}