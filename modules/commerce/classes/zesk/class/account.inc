<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Class_Account extends Class_Object {
	public $id_column = "id";
	public $has_one = array(
		"payment" => "Payment",
		"payment_account" => "Account",
		"referrer" => "Account_Referrer",
		"account_type" => "Account_Type",
		"commission" => "Commission"
	);
}

