<?php

class Class_Zesk_Account extends Class_Object {

	public $has_one = array(
		"payment" => "Payment",
		"payment_account" => "Account",
		"referrer" => "Account_Referrer",
		"account_type" => "Account_Type",
		"commission" => "Commission"
	);

}

