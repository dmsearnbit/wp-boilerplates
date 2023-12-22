<?php

class Booking {
	private $coupon;
	private $title;
	private $firstName;
	private $lastName;
	private $address;
	private $city;
	private $state;
	private $postalCode;
	private $country;
	private $phone;
	private $email;
	private $paymentMethod;
	private $additionalInformation;
	private $IDNumber;
	private $bookID;

	public function __construct(
		$coupon,
		$title,
		$firstName,
		$lastName,
		$address,
		$city,
		$state,
		$postalCode,
		$country,
		$phone,
		$email,
		$paymentMethod,
		$additionalInformation,
		$IDNumber,
		$bookID = null

	) {
		$this->coupon                = $coupon;
		$this->title                 = $title;
		$this->firstName             = $firstName;
		$this->lastName              = $lastName;
		$this->address               = $address;
		$this->city                  = $city;
		$this->state                 = $state;
		$this->postalCode            = $postalCode;
		$this->country               = $country;
		$this->phone                 = $phone;
		$this->email                 = $email;
		$this->paymentMethod         = $paymentMethod;
		$this->additionalInformation = $additionalInformation;
		$this->IDNumber              = $IDNumber;
		$this->bookID                = $bookID;
	}


	/**
	 * @return string
	 */
	public function getCoupon() {
		return $this->coupon;
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
	public function getFirstName() {
		return $this->firstName;
	}


	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->lastName;
	}


	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}


	/**
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}


	/**
	 * @return string
	 */
	public function getState() {
		return $this->state;
	}


	/**
	 * @return string
	 */
	public function getPostalCode() {
		return $this->postalCode;
	}


	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}


	/**
	 * @return string
	 */
	public function getPhone() {
		return $this->phone;
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}


	/**
	 * @return string
	 */
	public function getPaymentMethod() {
		return $this->paymentMethod;
	}


	/**
	 * @return string
	 */
	public function getAdditionalInformation() {
		return $this->additionalInformation;
	}


	/**
	 * @return string
	 */
	public function getIDNumber() {
		return $this->IDNumber;
	}


	/**
	 * @return int
	 */
	public function getBookID() {
		return $this->bookID;
	}


	/**
	 * @param string $coupon
	 */
	public function setCoupon( $coupon ) {
		$this->coupon = $coupon;
	}


	/**
	 * @param string $title
	 */
	public function setTitle( $title ) {
		$this->title = $title;
	}


	/**
	 * @param string $firstName
	 */
	public function setFirstName( $firstName ) {
		$this->firstName = $firstName;
	}


	/**
	 * @param string $lastName
	 */
	public function setLastName( $lastName ) {
		$this->lastName = $lastName;
	}


	/**
	 * @param string $address
	 */
	public function setAddress( $address ) {
		$this->address = $address;
	}


	/**
	 * @param string $city
	 */
	public function setCity( $city ) {
		$this->city = $city;
	}


	/**
	 * @param string $state
	 */
	public function setState( $state ) {
		$this->state = $state;
	}


	/**
	 * @param string $postalCode
	 */
	public function setPostalCode( $postalCode ) {
		$this->postalCode = $postalCode;
	}


	/**
	 * @param string $country
	 */
	public function setCountry( $country ) {
		$this->country = $country;
	}


	/**
	 * @param string $phone
	 */
	public function setPhone( $phone ) {
		$this->phone = $phone;
	}


	/**
	 * @param string $email
	 */
	public function setEmail( $email ) {
		$this->email = $email;
	}


	/**
	 * @param string $paymentMethod
	 */
	public function setPaymentMethod( $paymentMethod ) {
		$this->paymentMethod = $paymentMethod;
	}


	/**
	 * @param string $additionalInformation
	 */
	public function setAdditionalInformation( $additionalInformation ) {
		$this->additionalInformation = $additionalInformation;
	}


	/**
	 * @param string $IDNumber
	 */
	public function setIDNumber( $IDNumber ) {
		$this->IDNumber = $IDNumber;
	}


	/**
	 * @param int $bookID
	 */
	public function setBookID( $bookID ) {
		$this->bookID = $bookID;
	}
}