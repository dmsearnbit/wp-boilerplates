<?php

include_once( RISE_LOCATION . '/Model/PricingPlan.php' );

class PricingPlansRepository {
	const TABLE_NAME = 'rise_pricing_plans';
	const TABLE_NAME_META = 'rise_pricing_plans_meta';

	/**
	 * <p><b>Returns table name with prefix included</b></p>
	 *
	 * @return string
	 */
	private static function getTableName( $meta = false ) {
		global $wpdb;

		return ! $meta ? $wpdb->prefix . PricingPlansRepository::TABLE_NAME : $wpdb->prefix . PricingPlansRepository::TABLE_NAME_META;
	}


	/**
	 * <p><b>takes a room id as argument, returns an associative array containing plan data</b></p>
	 *
	 * @param $room_id
	 *
	 * @return array|false|object|void
	 */
	public static function getRegularPlanByRoomID( $room_id ) {
		global $wpdb;

		$table     = self::getTableName();
		$plan_type = "regular";

		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND plan_type = %s", $room_id, $plan_type );
		$result = $wpdb->get_row( $sql, ARRAY_A );

		if ( $result ) {
			return $result;
		} else {
			return false;
		}

	}


	/**
	 * <p><b>returns rates for the given plan ID</b></p>
	 *
	 * @param $plan_id
	 *
	 * @return array
	 */
	public static function getRatesByPlanID( $plan_id, $getRateNames = false ) {
		global $wpdb;

		$table = self::getTableName( true );

		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE plan_id = %d AND meta_key = %s", $plan_id, 'custom_rate' );
		$result = $wpdb->get_results( $sql, ARRAY_A );

		if ( $result ) {
			$rates = array();
			foreach ( $result as $row ) {
				if (!$getRateNames) {
					$rates[] = intval( $row['meta_value'] );
					continue;
				}

				$rates[] = [
					'id' => intval($row['meta_value']),
					'name' => CustomRatesController::getRateNameByID( intval( $row['meta_value'] ) )
				];
			}

			return $rates;
		} else {
			return array();
		}

	}


	/**
	 * <p><b>returns all rates for the plans between given dates</b></p>
	 *
	 * @param $arrival_date
	 * @param $departure_date
	 * @param $room_id
	 *
	 * @return array
	 */
	public static function getRatesForDates( $arrival_date, $departure_date, $room_id, $getRateNames = false ) {
		global $wpdb;

		$table = self::getTableName();

		// TODO: add check for dates and other plans when we find out what to do if custom rates don't exist in other plans.
		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND (plan_type = %s OR plan_type = %s)", $room_id, 'regular', 'other-no-date' );
		$result = $wpdb->get_results( $sql, ARRAY_A );

		$rates = array();
		foreach ( $result as $row ) {
			$rate = array(
				'plan_type' => $row['plan_type'],
				'plan_id'   => $row['plan_id'],
				'price'     => intval( $row['price'] ),
				'rates'     => self::getRatesByPlanID( $row['plan_id'], $getRateNames )
			);

			$rates[] = $rate;
		}

		return $rates;
	}


	/**
	 * <p><b> takes a room id as argument, returns an array of PricingPlan objects</b></p>
	 *
	 * @param $room_id
	 *
	 * @return array|false
	 */
	public static function getOtherPlansByRoomID( $room_id ) {
		global $wpdb;

		$table     = self::getTableName();
		$tableMeta = self::getTableName( true );

		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND (plan_type = %s or plan_type = %s) ORDER BY priority_order ASC", $room_id, "other", "other-no-date" );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$plans = array();

		foreach ( $results as $result ) {
			$plan = new PricingPlan(
				$room_id,
				$result['start_time'],
				$result['end_time'],
				$result['price'],
				$result['priority_order']
			);
			$plan->setPlanID( $result['plan_id'] );
			$plan->setInsertDate( $result['insert_date'] );
			$plan->setPlanType( $result['plan_type'] );

			$metaSql = $wpdb->prepare( "SELECT meta_value FROM $tableMeta rise_pricing_plans_meta WHERE plan_id = %d", $result['plan_id'] );
			$meta    = $wpdb->get_results( $metaSql, ARRAY_A );
			$rates   = array();
			foreach ( $meta as $rate ) {
				$rates[] = intval( $rate['meta_value'] );
			}

			$plans[] = array(
				'plan'  => $plan,
				'rates' => $rates
			);
		}

		return empty( $plans ) ? false : $plans;
	}


	/**
	 * <p><b>takes a room id, start time and end time as argument, returns an array of PricingPlan objects</b></p>
	 *
	 * @param $room_id
	 * @param $start_time
	 * @param $end_time
	 *
	 * @return array|false
	 */
	public static function getPlansByDateRange( $room_id, $start_time, $end_time ) {
		global $wpdb;

		$table   = self::getTableName();
		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND (
                              (start_time <= %s AND end_time >= %s) 
                                  OR 
                              (start_time <= %s AND end_time >= %s) 
                                  OR 
                              (start_time >= %s AND end_time <= %s)
                          	) ORDER BY priority_order DESC",
			$room_id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$plans = array();

		foreach ( $results as $result ) {
			$plan = new PricingPlan(
				$room_id,
				$result['start_time'],
				$result['end_time'],
				$result['price'],
				$result['priority_order']
			);

			$plan->setPlanID( $result['plan_id'] );
			$plan->setInsertDate( $result['insert_date'] );
			$plan->setPlanType( $result['plan_type'] );

			array_push( $plans, $plan );
		}

		if ( count( $plans ) != 0 ) {
			return $plans;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>takes a room id, start time and end time as argument, returns an array of PricingPlan objects</b></p>
	 *
	 * @param $room_id
	 * @param $date
	 *
	 * @return array|false
	 */
	public static function getOtherPlansByDate( $room_id, $date ) {
		global $wpdb;

		$table     = self::getTableName();
		$plan_type = "other";

		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE room_id = %d AND plan_type = %s (start_time <= %s AND end_time >= %s) ORDER BY priority_order ASC", $room_id, $plan_type, $date, $date );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$plans   = array();

		foreach ( $results as $result ) {
			$plan = new PricingPlan(
				$room_id,
				$result['start_time'],
				$result['end_time'],
				$result['price'],
				$result['priority_order']
			);

			$plan->setPlanID( $result['plan_id'] );
			$plan->setInsertDate( $result['insert_date'] );
			$plan->setPlanType( $result['plan_type'] );

			array_push( $plans, $plan );
		}


		if ( count( $plans ) != 0 ) {
			return $plans;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>takes a plan id as argument, returns a PricingPlan object</b></p>
	 *
	 * @param $plan_id
	 *
	 * @return false|PricingPlan
	 */
	public static function getPlan( $plan_id ) {
		global $wpdb;

		$table = self::getTableName();

		$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE plan_id = %d", $plan_id );
		$result = $wpdb->get_row( $sql, ARRAY_A );

		if ( $result ) {
			$plan = new PricingPlan(
				$result['room_id'],
				$result['start_time'],
				$result['end_time'],
				$result['price'],
				$result['priority_order']
			);
			$plan->setPlanID( $plan_id );
			$plan->setInsertDate( $result['insert_date'] );
			$plan->setPlanType( $result['plan_type'] );

			return $plan;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>add a new plan to our plans table</b></p>
	 *
	 * @param $plan
	 * @param $rates
	 *
	 * @return bool|int
	 */
	public static function addPlan( $plan, $rates ) {
		global $wpdb;
		$table = self::getTableName();

		// columns and their data to be inserted
		$insertData = array(
			'room_id'        => $plan->getRoomID(),
			'start_time'     => $plan->getStartTime(),
			'end_time'       => $plan->getEndTime(),
			'price'          => $plan->getPrice(),
			'priority_order' => $plan->getPriority(),
			'plan_type'      => $plan->getPlanType(),
		);


		$roomTitle = get_the_title( $plan->getRoomID() );
		$startTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $plan->getStartTime() )->format( 'Y-m-d' );
		$endTime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $plan->getEndTime() )->format( 'Y-m-d' );
		$currency  = SettingsRepository::getSetting( 'currency' );
		$currency  = Currency::$currencies[ $currency ];
		$logType   = __( 'Pricing plan added', 'rise-hotel-booking' );
		if ( $plan->getPlanType() == 'regular' ) {
			$logDetails = sprintf(
				__( 'Regular pricing plan added for the room %s with the price of %s', 'rise-hotel-booking' ),
				$roomTitle,
				$plan->getPrice() . $currency
			);
		} else {
			$logDetails = sprintf(
				__( 'Pricing plan added for the room %s for the dates %s - %s with the price of %s', 'rise-hotel-booking' ),
				$roomTitle,
				$startTime,
				$endTime,
				$plan->getPrice() . $currency
			);
		}
		ActivityLogRepository::addLog( $logType, $logDetails );

		$success = $wpdb->insert( $table, $insertData );

		if ( $success ) {
			$planID = $wpdb->insert_id;
			if ( ! empty( $rates ) ) {
				CustomRatesController::setRates( $planID, $rates );
			}

		}

		return $success;
	}


	/**
	 * <p><b>used to update an existing pricing plan</b></p>
	 *
	 * @param $plan PricingPlan
	 *
	 * @return bool
	 */
	public static function updatePlan( $plan, $rates ) {
		global $wpdb;
		$table = self::getTableName();

		$currentRates = self::getRatesByPlanID( $plan->getPlanID() );
		$currentPrice = self::getPlan( $plan->getPlanID() )->getPrice();

		$updateData = array(
			'start_time'     => $plan->getStartTime(),
			'end_time'       => $plan->getEndTime(),
			'price'          => $plan->getPrice(),
			'priority_order' => $plan->getPriority(),
			'plan_type'      => $plan->getPlanType(),
		);

		$where = array(
			'plan_id' => $plan->getPlanID()
		);

		if ( ! empty( $rates ) ) {
			CustomRatesController::setRates( $plan->getPlanID(), $rates );
		}

		$currency  = SettingsRepository::getSetting( 'currency' );
		$currency  = Currency::$currencies[ $currency ];
		$price     = $plan->getPrice() . $currency;
		$roomTitle = get_the_title( $plan->getRoomID() );

		$logType = __( 'Pricing plan updated', 'rise-hotel-booking' );
		if ( $plan->getPlanType() == 'regular' ) {
			$logDetails = sprintf(
				__( 'Regular pricing plan for the room %s updated.', 'rise-hotel-booking' ),
				$roomTitle,
				$price
			);
		} else {
			$logDetails = sprintf(
				__( 'Pricing plan for the room %s updated: %s - %s, with the price %s', 'rise-hotel-booking' ),
				$roomTitle,
				DateTime::createFromFormat( 'Y-m-d H:i:s', $plan->getStartTime() )->format( 'Y-m-d' ),
				DateTime::createFromFormat( 'Y-m-d H:i:s', $plan->getEndTime() )->format( 'Y-m-d' ),
				$price
			);
		}

		$areRatesTheSame  = is_array( $currentRates ) && is_array( $rates ) && count( $currentRates ) == count( $rates )
		                    && array_diff( $currentRates, $rates ) === array_diff( $rates, $currentRates );
		$arePricesTheSame = $currentPrice == $plan->getPrice();

		// if the user changed either the rates or the price, we log it
		if ( ! $areRatesTheSame || ! $arePricesTheSame ) {
			ActivityLogRepository::addLog( $logType, $logDetails );
		}

		return $wpdb->update( $table, $updateData, $where );
	}


	/**
	 * <p><b>Deletes a plan by its id</b></p>
	 *
	 * @param $plan_id
	 *
	 * @return bool
	 */
	public static function deletePlan( $plan_id ) {
		global $wpdb;
		$table     = self::getTableName();
		$tableMeta = self::getTableName( true );

		$where = array(
			'plan_id' => $plan_id
		);

		$plan = self::getPlan( $plan_id );

		if ( $wpdb->delete( $table, $where ) && $wpdb->delete( $tableMeta, $where ) ) {
			ActivityLogRepository::addLog(
				__( 'Pricing plan deleted', 'rise-hotel-booking' ),
				sprintf(
					__( 'Pricing plan deleted for the room %s', 'rise-hotel-booking' ),
					get_the_title( $plan->getRoomID() )
				)
			);

			return true;
		} else {
			return false;
		}
	}


	/**
	 * <p><b>Returns the amount of nights between two dates.</b></p>
	 *
	 * @param $checkIn
	 * @param $checkOut
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getNumberOfNights( $checkIn, $checkOut ) {
		$checkIn  = new DateTime( $checkIn );
		$checkOut = new DateTime( $checkOut );

		return $checkOut->diff( $checkIn )->format( "%a" );
	}


	/**
	 * <p><b>Returns prices of a room between two dates as an array</b></p>
	 *
	 * @param $roomID
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getPrices( $roomID, $startDate, $endDate, $planID = null ) {
		// formatting dates
		$startDateDT        = new DateTime( $startDate . ' 00:00:00' );
		$formattedStartDate = $startDateDT->format( 'Y-m-d H:i:s' );

		$endDateDT        = new DateTime( $endDate . ' 23:59:59' );
		$formattedEndDate = $endDateDT->format( 'Y-m-d H:i:s' );

		$allPlans = PricingPlansRepository::getPlansByDateRange( $roomID, $formattedStartDate, $formattedEndDate );

		if ( empty( $allPlans ) ) {
			return array();
		}

		// this will give us an array of DatePeriod objects in a given data range
		$datePeriod = new DatePeriod(
			$startDateDT,
			new DateInterval( 'P1D' ),
			$endDateDT
		);

		// creating an array of dates
		$dates = array();
		foreach ( $datePeriod as $singleCalendarDay ) {
			array_push( $dates, $singleCalendarDay->format( 'Y-m-d' ) );
		}

		if ( $planID ) {
			$givenPlan = self::getPlan( $planID );
		}

		$datesPrices = array();
		foreach ( $dates as $singleCalendarDay ) {
			$datePrice          = null;
			$singleDayTimeStamp = strtotime( $singleCalendarDay );
			foreach ( $allPlans as $plan ) {
				// we compare timestamps because it's easier
				// TODO: compare datetime objects instead of timestamps
				if ( $singleDayTimeStamp >= strtotime( $plan->getStartTime() ) && $singleDayTimeStamp <= strtotime( $plan->getEndTime() ) ) {
					$datePrice = $plan->getPrice();
				}
			}

			$datesPrices[ $singleCalendarDay ] = isset( $givenPlan ) ? $givenPlan->getPrice() : $datePrice;
		}

		return $datesPrices;
	}


	/**
	 * <p><b>Returns price of a single room in a single date</b></p>
	 *
	 * @param $roomID
	 * @param $date
	 *
	 * @return false|mixed|null
	 * @throws Exception
	 */
	public static function getPrice( $roomID, $date ) {
		$regularPlan = PricingPlansRepository::getRegularPlanByRoomID( $roomID );

		// if regular plan does not exist that means the user did not create any plan for this room
		// because we don't allow creating other plans before setting a price on regular plan,
		// so we just return an error
		if ( ! $regularPlan ) {
			return false;
		}

		// formatting dates
		$dateDT        = new DateTime( $date . ' 00:00:00' );
		$formattedDate = $dateDT->format( 'Y-m-d H:i:s' );

		$otherPlans = PricingPlansRepository::getOtherPlansByDate( $roomID, $formattedDate );

		$price = null;
		if ( $otherPlans ) {
			foreach ( $otherPlans as $plan ) {
				// we compare timestamps because it's easier
				if ( strtotime( $date ) >= strtotime( $plan->getStartTime() ) && strtotime( $date ) <= strtotime( $plan->getEndTime() ) ) {
					$price = $plan->getPrice();
					break;
				}
			}
		}

		// if we didn't find any plan that is valid for current date, set the price for current date to regular plan price
		if ( is_null( $price ) ) {
			$price = $regularPlan['price'];
		}

		return $price;

	}


}