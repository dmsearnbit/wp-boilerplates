<?php

class Room {
	private $ID;
	private $title;
	private $content;
	private $quantity;
	private $numberOfAdults;
	private $status;
	private $totalPrice;

	public function __construct( $ID, $title, $content, $quantity, $numberOfAdults, $status, $totalPrice ) {
		$this->ID             = $ID;
		$this->title          = $title;
		$this->content        = $content;
		$this->quantity       = $quantity;
		$this->numberOfAdults = $numberOfAdults;
		$this->status         = $status;
		$this->totalPrice     = $totalPrice;
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @return int
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * @return int
	 */
	public function getNumberOfAdults() {
		return $this->numberOfAdults;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return float
	 */
	public function getTotalPrice() {
		return $this->totalPrice;
	}

	/**
	 * @param int $ID
	 */
	public function setID( $ID ) {
		$this->ID = $ID;
	}

	/**
	 * @param string $title
	 */
	public function setTitle( $title ) {
		$this->title = $title;
	}

	/**
	 * @param string $content
	 */
	public function setContent( $content ) {
		$this->content = $content;
	}

	/**
	 * @param int $quantity
	 */
	public function setQuantity( $quantity ) {
		$this->quantity = $quantity;
	}

	/**
	 * @param int $numberOfAdults
	 */
	public function setNumberOfAdults( $numberOfAdults ) {
		$this->numberOfAdults = $numberOfAdults;
	}

	/**
	 * @param string $status
	 */
	public function setStatus( $status ) {
		$this->status = $status;
	}

	/**
	 * @param float $totalPrice
	 */
	public function setTotalPrice( $totalPrice ) {
		$this->totalPrice = $totalPrice;
	}

}
