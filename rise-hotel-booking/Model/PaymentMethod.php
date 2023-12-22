<?php

class PaymentMethod {
	static $methods = array(
		'offline' => 'Offline Payment',
		'arrival' => 'Pay on Arrival',
		'paypal'  => 'PayPal',
		'stripe'  => 'Stripe',
		'iyzico'  => 'iyzico',
	);
}