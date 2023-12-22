<?php

class CustomRatesController {
	public static $pricingRatesPresets = [
		'Refundable',
		'Non-refundable',
		'Breakfast included',
		'Lunch included',
		'Dinner included'
	];


	const TABLE_NAME = 'rise_pricing_plans_meta';


	public function __construct() {
		// register custom post type
		add_action( 'init', array( $this, 'createPostType' ) );
	}


	/**
	 * <p><b>Returns table name with prefix included</b></p>
	 *
	 * @return string
	 */
	private static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}


	/**
	 * <p><b>create custom post type</b></p>
	 */
	public function createPostType() {
		$postTypeData = array(
			'public'              => true,
			'has_archive'         => true,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'labels'              => array(
				'name'               => __( 'Custom Rates', 'rise-hotel-booking' ),
				'singular_name'      => __( 'Custom Rate', 'rise-hotel-booking' ),
				'add_new'            => __( 'Add New Custom Rate', 'rise-hotel-booking' ),
				'add_new_item'       => __( 'Add New Custom Rate', 'rise-hotel-booking' ),
				'edit_item'          => __( 'Edit Custom Rate', 'rise-hotel-booking' ),
				'new_item'           => __( 'New Custom Rate', 'rise-hotel-booking' ),
				'view_item'          => __( 'View Custom Rate', 'rise-hotel-booking' ),
				'view_items'         => __( 'View Custom Rates', 'rise-hotel-booking' ),
				'search_items'       => __( 'Search Custom Rates', 'rise-hotel-booking' ),
				'not_found'          => __( 'No custom rates found', 'rise-hotel-booking' ),
				'not_found_in_trash' => __( 'No custom rates found in Trash', 'rise-hotel-booking' ),
				'all_items'          => __( 'Custom Rates', 'rise-hotel-booking' ),

			),
			'menu_icon'           => 'dashicons-tag',
			'rewrite'             => array(
				'slug' => 'hotel-pricing-rate'
			),
			'show_in_menu'        => 'edit.php?post_type=rise_room'
		);

		register_post_type( 'rise_pricing_rate', $postTypeData );
	}


	/**
	 * <p><b>adds default pricing rates</b></p>
	 */
	public function setDefaultPresets() {
		foreach ( self::$pricingRatesPresets as $preset ) {
			if ( ! post_exists( $preset ) ) {
				wp_insert_post( array(
					'post_title'  => $preset,
					'post_type'   => 'rise_pricing_rate',
					'post_status' => 'publish',
				) );
			}
		}
	}


	/**
	 * <p><b>returns all custom rates</b></p>
	 *
	 * @return int[]|WP_Post[]
	 */
	public static function getPricingRates() {
		return get_posts( array(
			'post_type'      => 'rise_pricing_rate',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'ASC'
		) );
	}


	/**
	 * <p><b>returns all pricing rates that belongs to the given plan ID</b></p>
	 *
	 * @param $pricingPlanID
	 *
	 * @return array
	 */
	public static function getPricingRatesByPlanID( $pricingPlanID ) {
		global $wpdb;
		$table = self::getTableName();

		$query = $wpdb->prepare( "SELECT * FROM $table WHERE plan_id = %d AND meta_key = %s", $pricingPlanID, 'custom_rate' );
		$rows  = $wpdb->get_results( $query, ARRAY_A );

		$rates = [];
		foreach ( $rows as $row ) {
			$rates[] = intval( $row['meta_value'] );
		}

		return $rates;
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1/get-rate-name-by-id',
			'(?P<rateID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRateNameByIDAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}


	public static function getRateNameByID( $rateID ) {
		$rate = get_post( $rateID );

		return $rate->post_title;
	}

	public function getRateNameByIDAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$rateID = intval( $request['rateID'] );

		return self::getRateNameByID( $rateID );
	}


	/**
	 * <p><b>sets the rates for given plan ID to the given rates</b></p>
	 *
	 * @param $planID
	 * @param $rates
	 */
	public static function setRates( $planID, $rates ) {
		global $wpdb;
		$table = self::getTableName();

		$wpdb->delete( $table, array(
			'plan_id'  => $planID,
			'meta_key' => 'custom_rate'
		) );

		foreach ( $rates as $rate ) {
			$wpdb->insert(
				$table,
				array(
					'plan_id'    => $planID,
					'meta_key'   => 'custom_rate',
					'meta_value' => $rate
				)
			);
		}
	}
}