<?php

class PricingPlan {
	private $plan_id;
	private $room_id;
	private $start_time;
	private $end_time;
	private $price;
	private $priority;
	private $insert_date;
	private $plan_type;

	public function __construct( $room_id, $start_time, $end_time, $price, $priority_order ) {
		$this->room_id    = $room_id;
		$this->start_time = $start_time;
		$this->end_time   = $end_time;
		$this->price      = $price;
		$this->priority   = $priority_order;
	}

	// Getters

	/**
	 * @return int
	 */
	public function getPlanID() {
		return $this->plan_id;
	}

	/**
	 * @return int
	 */
	public function getRoomID() {
		return $this->room_id;
	}

	/**
	 * @return string
	 */
	public function getStartTime() {
		return $this->start_time;
	}

	/**
	 * @return string
	 */
	public function getEndTime() {
		return $this->end_time;
	}

	/**
	 * @return float
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @return string
	 */
	public function getInsertDate() {
		return $this->insert_date;
	}

	/**
	 * @return string
	 */
	public function getPlanType() {
		return $this->plan_type;
	}


	// Setters

	/**
	 * @param int $plan_id
	 */
	public function setPlanID( $plan_id ) {
		$this->plan_id = $plan_id;
	}

	/**
	 * @param int $room_id
	 */
	public function setRoomID( $room_id ) {
		$this->room_id = $room_id;
	}

	/**
	 * @param string $start_time
	 */
	public function setStartTime( $start_time ) {
		$this->start_time = $start_time;
	}

	/**
	 * @param string $end_time
	 */
	public function setEndTime( $end_time ) {
		$this->end_time = $end_time;
	}

	/**
	 * @param float $price
	 */
	public function setPrice( $price ) {
		$this->price = $price;
	}

	/**
	 * @param int $priority
	 */
	public function setPriority( $priority ) {
		$this->priority = $priority;
	}

	/**
	 * @param string $insert_date
	 */
	public function setInsertDate( $insert_date ) {
		$this->insert_date = $insert_date;
	}

	/**
	 * @param string $plan_type
	 */
	public function setPlanType( $plan_type ) {
		$this->plan_type = $plan_type;
	}
}