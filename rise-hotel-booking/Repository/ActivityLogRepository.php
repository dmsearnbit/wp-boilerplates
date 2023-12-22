<?php

class ActivityLogRepository {
	const TABLE_NAME = 'rise_activity_log';

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
	 * <p><b>Get logs by page and search filter</b></p>
	 *
	 * @param $start
	 * @param $length
	 * @param $draw
	 * @param $search
	 * @param $orderData
	 *
	 * @return array
	 */
	public static function getLogsByPage( $start, $length, $draw, $search, $orderData ) {
		global $wpdb;
		$table = self::getTableName();

		// order query
		$order = "";
		if ( ! empty( $orderData ) ) {
			$orderBy = $orderData['order-by'];
			$dir     = $orderData['dir'];
			$order   = "ORDER BY $orderBy $dir";
		}

		if ( $search ) {
			$search        = '%' . $search . '%';
			$sql           = "SELECT * FROM $table WHERE (`activity_type` LIKE '$search' OR `details` LIKE '$search') $order LIMIT $start, $length";
			$filteredCount = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE (`activity_type` LIKE '$search' OR `details` LIKE '$search')" );

		} else {
			$sql           = "SELECT * FROM $table $order LIMIT $start, $length";
			$count         = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
			$filteredCount = $count;
			$totalCount    = $count;
		}

		if ( ! isset( $totalCount ) ) {
			$totalCount = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return [
			"draw"            => $draw,
			"recordsTotal"    => $totalCount,
			"recordsFiltered" => $filteredCount,
			"data"            => $results
		];
	}


	/**
	 * <p><b>Inserts new activity log record</b></p>
	 *
	 * @param $activityType
	 * @param $details
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public static function addLog( $activityType, $details ) {
		global $wpdb;
		$table = self::getTableName();

		$sql = "INSERT INTO $table (activity_type, details) VALUES (%s, %s)";
		$sql = $wpdb->prepare( $sql, $activityType, $details );

		return $wpdb->query( $sql );
	}
}