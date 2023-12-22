<?php

include_once( RISE_LOCATION . '/Repository/PricingPlansRepository.php' );

class PricingPlansController {
	public function __construct() {
		// add pricing plans page under rise_room post type
		add_action( 'admin_menu', array( $this, 'addPage' ) );
	}


	/**
	 * <p><b>Adds pricing plans page under rise_room post type</b></p>
	 */
	public function addPage() {
		add_submenu_page(
			'edit.php?post_type=rise_room',
			__( 'Pricing Plans', 'rise-hotel-booking' ),
			__( 'Pricing Plans', 'rise-hotel-booking' ),
			'manage_options',
			'rise_pricing_plans',
			array( $this, 'pricingPlansHTML' )
		);
	}


	/**
	 * <p><b>Includes pricing plans view</b></p>
	 */
	public function pricingPlansHTML() {
		$rooms = get_posts( [
			'post_type'   => 'rise_room',
			'post_status' => 'publish',
			'numberposts' => - 1
		] );

		$page_title = get_admin_page_title();

		include( RISE_LOCATION . '/View/AdminPanel/PricingPlans.php' );
	}


	/**
	 * <b>Handles submitted pricing plan page form</b>
	 *
	 * @param $roomID
	 * @param $regularPrice
	 * @param $planDates
	 * @param $planIDs
	 * @param $planPrices
	 * @param $planRates
	 * @param $plansToDelete
	 * @param $planPriorities
	 * @param $regularPlanRates
	 * @param $plansWithNoDate
	 *
	 * @return bool|int|void|null
	 */
	public function handleForm( $roomID, $regularPrice, $planDates, $planIDs, $planPrices, $planRates, $plansToDelete, $planPriorities, $regularPlanRates, $plansWithNoDate ) {
		$regularPlan = PricingPlansRepository::getRegularPlanByRoomID( $roomID );

		// convert all items in regular plan rates array to int (for filtering purposes)
		if ( isset( $regularPlanRates ) && is_array( $regularPlanRates ) ) {
			$regularPlanRates = array_map( 'intval', $regularPlanRates );
		} else {
			$regularPlanRates = [];
		}

		if ( isset( $plansWithNoDate ) && is_array( $plansWithNoDate ) ) {
			$plansWithNoDate = array_map( 'intval', $plansWithNoDate );
		} else {
			$plansWithNoDate = [];
		}

		// add the regular plan if it does not exist, update the price of the regular plan if it exists
		if ( $regularPlan ) {
			$plan = new PricingPlan(
				intval( $regularPlan['room_id'] ),
				'1000-01-01 00:00:00',
				'9999-12-31 23:59:59',
				intval( $regularPrice ),
				intval( $regularPlan['priority_order'] )
			);
			$plan->setPlanType( 'regular' );
			$plan->setPlanID( intval( $regularPlan['plan_id'] ) );
			$plan->setInsertDate( sanitize_text_field( $regularPlan['insert_date'] ) );
			$result = PricingPlansRepository::updatePlan( $plan, $regularPlanRates );
		} else {
			$regularPlan = new PricingPlan(
				intval( $roomID ),
				'1000-01-01 00:00:00',
				'9999-12-31 23:59:59',
				intval( $regularPrice ),
				PHP_INT_MAX
			);
			$regularPlan->setPlanType( 'regular' );
			$result = PricingPlansRepository::addPlan( $regularPlan, $regularPlanRates );
		}

		// delete the plans in plans to delete array
		if ( ! empty( $plansToDelete ) ) {
			foreach ( $plansToDelete as $planID ) {
				PricingPlansRepository::deletePlan( $planID );
			}
		}

		if ( ! empty( $planPriorities ) ) {
			// create rates array and add the priorities in it
			$rates = array();
			foreach ( $planPriorities as $priority ) {
				$rates[ $priority ] = array();
			}
		}

		// add the rates in the rates array
		if ( ! empty( $planRates ) ) {
			foreach ( $planRates as $rate ) {
				$rateData     = explode( '-', $rate );
				$ratePriority = intval( $rateData[0] );
				$rateID       = intval( $rateData[1] );
				if ( isset( $rates[ $ratePriority ] ) ) {
					array_push( $rates[ $ratePriority ], $rateID );
				}
			}
		}

		if ( empty( $planDates ) ) {
			return $result;
		}

		// iterating over all the user-added plans (other plans)
		foreach ( $planDates as $index => $planDateString ) {
			$startTime = explode( ' - ', $planDateString )[0];
			$endTime   = explode( ' - ', $planDateString )[1];

			$startTime = DateTime::createFromFormat( 'd/m/Y', sanitize_text_field( $startTime ) )->format( 'Y-m-d 00:00:00' );
			$endTime   = DateTime::createFromFormat( 'd/m/Y', sanitize_text_field( $endTime ) )->format( 'Y-m-d 23:59:59' );

			// if we are updating an existing plan
			if ( $planIDs[ $index ] != 'null' ) {
				$plan   = PricingPlansRepository::getPlan( intval( $planIDs[ $index ] ) );
				$result = true;

				$plan->setPrice( intval( $planPrices[ $index ] ) );
				$plan->setPriority( $planPriorities[ $index ] );
				$plan->setStartTime( sanitize_text_field( $startTime ) );
				$plan->setEndTime( sanitize_text_field( $endTime ) );

				if ( in_array( intval( $planPriorities[ $index ] ), $plansWithNoDate ) ) {
					$plan->setPlanType( 'other-no-date' );
					$plan->setPriority( null );
				} else {
					$plan->setPlanType( 'other' );
				}

				$currentRates = isset( $rates[ $planPriorities[ $index ] ] ) ? $rates[ $planPriorities[ $index ] ] : array();
				$result       = PricingPlansRepository::updatePlan( $plan, $currentRates );
			} else { // if we are adding a new plan
				$plan = new PricingPlan(
					intval( $roomID ),
					sanitize_text_field( $startTime ),
					sanitize_text_field( $endTime ),
					intval( $planPrices[ $index ] ),
					$planPriorities[ $index ]
				);

				if ( in_array( intval( $planPriorities[ $index ] ), $plansWithNoDate ) ) {
					$plan->setPlanType( 'other-no-date' );
					$plan->setPriority( null );
				} else {
					$plan->setPlanType( 'other' );
				}

				$currentRates = isset( $rates[ $planPriorities[ $index ] ] ) ? $rates[ $planPriorities[ $index ] ] : array();
				$result       = PricingPlansRepository::addPlan( $plan, $currentRates );
			}

			if ( isset( $result ) && $result === false ) {
				return $result;
			}

		}

	}


	/**
	 * <p><b>Converts plan price to HTML-style value</b></p>
	 * <p>Returns string on success, false if an error occurred.</p>
	 *
	 * @param $planType
	 * @param null $roomID
	 * @param null $price
	 *
	 * @return false|string
	 */
	public function convertPriceToHTMLValue( $planType, $roomID = null, $price = null ) {
		switch ( $planType ) {
			case 'regular':
				if ( ! is_null( $roomID ) ) {
					$regularPrice = PricingPlansRepository::getRegularPlanByRoomID( intval( $roomID ) );

					return $regularPrice ? 'value="' . $regularPrice["price"] . '"' : '';
				} else {
					return false;
				}

			case 'other':
				if ( ! is_null( $price ) ) {
					return 'value="' . intval( $price ) . '"';
				} else {
					return false;
				}

			default:
				return false;
		}
	}


	/**
	 * <p><b>Returns regular plan for the given room ID</b></p>
	 *
	 * @param $roomID
	 *
	 * @return array|false|object|void
	 */
	public function getRegularPlanByRoomID( $roomID ) {
		return PricingPlansRepository::getRegularPlanByRoomID( $roomID );
	}


	/**
	 * <p><b>Returns all rates for the given plan ID</b></p>
	 *
	 * @param $planID
	 *
	 * @return array
	 */
	public function getRatesByPlanID( $planID ) {
		return PricingPlansRepository::getRatesByPlanID( $planID );
	}


	/**
	 * <p><b>Converts start date and end date to the format DateRangePicker needs</b></p>
	 *
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return string
	 */
	public function convertDateType( $startDate, $endDate ) {
		$start = DateTime::createFromFormat( 'Y-m-d 00:00:00', $startDate )->format( 'd/m/Y' );
		$end   = DateTime::createFromFormat( 'Y-m-d 23:59:59', $endDate )->format( 'd/m/Y' );

		return $start . ' - ' . $end;
	}


	/**
	 * <p><b>Gets other plans by room ID</b></p>
	 *
	 * @param $roomID
	 *
	 * @return array|false
	 */
	public function getPlans( $roomID ) {
		return PricingPlansRepository::getOtherPlansByRoomID( $roomID );
	}


	/**
	 * <p><b>Registers REST API routes</b></p>
	 */
	public function registerRoutes() {
		register_rest_route(
			'rise-hotel-booking/v1/get-prices',
			'(?P<roomID>\d+)/(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPricesAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1/get-price',
			'(?P<roomID>\d+)/(?P<date>[a-zA-Z0-9-]+)/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPriceAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);

		register_rest_route(
			'rise-hotel-booking/v1/get-rates-for-dates',
			'(?P<startDate>[a-zA-Z0-9-]+)/(?P<endDate>[a-zA-Z0-9-]+)/(?P<roomID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRatesForDatesAPI' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}


	/**
	 * <p><b>API endpoint for getPrices function in PricingPlansRepository.</b></p>
	 *
	 * @param $request
	 *
	 * @return array|false
	 * @throws Exception
	 */
	public function getPricesAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID    = intval( $request['roomID'] );
		$startDate = sanitize_text_field( $request['startDate'] ); // YYYY-MM-DD
		$endDate   = sanitize_text_field( $request['endDate'] ); // YYYY-MM-DD

		return PricingPlansRepository::getPrices( $roomID, $startDate, $endDate );
	}


	/**
	 * <p><b>API endpoint for getPrice function in PricingPlansRepository.</b></p>
	 *
	 * @param $request
	 *
	 * @return false|mixed|null
	 * @throws Exception
	 */
	public function getPriceAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID = intval( $request['roomID'] );
		$date   = sanitize_text_field( $request['date'] ); // YYYY-MM-DD

		return PricingPlansRepository::getPrice( $roomID, $date );
	}


	public function getRatesForDatesAPI( $request ) {
		if ( ! wp_verify_nonce( $request->get_headers()['x_wp_nonce'][0], 'wp_rest' ) ) {
			return false;
		}

		$roomID    = intval( $request['roomID'] );
		$startDate = sanitize_text_field( $request['startDate'] ); // YYYY-MM-DD
		$endDate   = sanitize_text_field( $request['endDate'] ); // YYYY-MM-DD

		return PricingPlansRepository::getRatesForDates( $startDate, $endDate, $roomID, true );
	}


	/**
	 * <p><b>Returns an array of dates in format Y-m-d</b></p>
	 *
	 * @param $startDate
	 * @param $endDate
	 * @param bool $excludeLastDay
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function createArrayOfDates( $startDate, $endDate, $excludeLastDay = true ) {
		$startDateObj = new DateTime( $startDate . ' 00:00:00' );

		$endDateObj = new DateTime( $endDate . ' 23:59:59' );
		if ( $excludeLastDay ) {
			$endDateObj = $endDateObj->modify( '-1 day' );
		}

		// this will give us an array of DatePeriod objects in a given data range
		$datePeriod = new DatePeriod(
			$startDateObj,
			new DateInterval( 'P1D' ),
			$endDateObj
		);

		// creating an array of dates
		$dates = array();
		foreach ( $datePeriod as $singleCalendarDay ) {
			array_push( $dates, $singleCalendarDay->format( 'Y-m-d' ) );
		}

		return $dates;
	}


	/**
	 * <p><b>Returns total price of a room in a data range</b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return int|mixed
	 * @throws Exception
	 */
	public static function getTotalPrice( $roomID, $startDate, $endDate, $planID = null ) {
		$dates = self::createArrayOfDates( $startDate, $endDate );

		$prices = PricingPlansRepository::getPrices( $roomID, $startDate, $endDate, $planID );
		if ( empty( $prices ) ) {
			//If no prices (not even a regular price) is found, return N/A
			return 'N/A';
		}

		$totalPrice = 0;

		foreach ( $dates as $date ) {
			$totalPrice += $prices[ $date ];
		}

		return $totalPrice;
	}


	public static function doesRegularPlanExist($roomID) {
		return (bool) PricingPlansRepository::getRegularPlanByRoomID($roomID);
	}


}