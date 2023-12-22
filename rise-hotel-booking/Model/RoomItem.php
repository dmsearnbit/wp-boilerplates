<?php

class RoomItem {
	private $roomID;
	private $checkInDate;
	private $checkOutDate;
	private $quantity;
	private $totalPrice;
	private $numberOfPeople;
	private $insertDate;
	private $itemID;
	private $bookID;
	private $planID;

	public function __construct( $roomID, $checkInDate, $checkOutDate, $quantity, $totalPrice, $numberOfPeople, $insertDate = null, $itemID = null, $bookID = null, $planID = null ) {
		$this->roomID         = $roomID;
		$this->checkInDate    = $checkInDate;
		$this->checkOutDate   = $checkOutDate;
		$this->quantity       = $quantity;
		$this->totalPrice     = $totalPrice;
		$this->numberOfPeople = $numberOfPeople;
		$this->insertDate     = $insertDate;
		$this->itemID         = $itemID;
		$this->bookID         = $bookID;
		$this->planID         = $planID;
	}


	/**
	 * @return int
	 */
	public function getRoomID() {
		return $this->roomID;
	}

	/**
	 * @return string
	 */
	public function getCheckInDate() {
		return $this->checkInDate;
	}

	/**
	 * @return string
	 */
	public function getCheckOutDate() {
		return $this->checkOutDate;
	}

	/**
	 * @return int
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * @return float
	 */
	public function getTotalPrice() {
		return $this->totalPrice;
	}

	/**
	 * @return int
	 */
	public function getNumberOfPeople() {
		return $this->numberOfPeople;
	}

	/**
	 * @return string
	 */
	public function getInsertDate() {
		return $this->insertDate;
	}

	/**
	 * @return int
	 */
	public function getItemID() {
		return $this->itemID;
	}

	/**
	 * @return int
	 */
	public function getBookID() {
		return $this->bookID;
	}

	/**
	 * @return int
	 */
	public function getPlanID() {
		return $this->planID;
	}

	/**
	 * @param int $roomID
	 */
	public function setRoomID( $roomID ) {
		$this->roomID = $roomID;
	}

	/**
	 * @param string $checkInDate
	 */
	public function setCheckInDate( $checkInDate ) {
		$this->checkInDate = $checkInDate;
	}

	/**
	 * @param string $checkOutDate
	 */
	public function setCheckOutDate( $checkOutDate ) {
		$this->checkOutDate = $checkOutDate;
	}

	/**
	 * @param int $quantity
	 */
	public function setQuantity( $quantity ) {
		$this->quantity = $quantity;
	}

	/**
	 * @param float $totalPrice
	 */
	public function setTotalPrice( $totalPrice ) {
		$this->totalPrice = $totalPrice;
	}

	/**
	 * @param int $numberOfPeople
	 */
	public function setNumberOfPeople( $numberOfPeople ) {
		$this->numberOfPeople = $numberOfPeople;
	}

	/**
	 * @param string $insertDate
	 */
	public function setInsertDate( $insertDate ) {
		$this->insertDate = $insertDate;
	}

	/**
	 * @param int $itemID
	 */
	public function setItemID( $itemID ) {
		$this->itemID = $itemID;
	}

	/**
	 * @param int $bookID
	 */
	public function setBookID( $bookID ) {
		$this->bookID = $bookID;
	}

	/**
	 * @param int $planID
	 */
	public function setPlanID( $planID ) {
		$this->planID = $planID;
	}
}