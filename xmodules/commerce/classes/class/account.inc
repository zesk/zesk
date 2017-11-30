<?php
/**
 * Subclass in applications to extend
 * 
 * @author kent
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
		"payment" => "zesk\\Payment",
		"payment_account" => "zesk\\Account",
		"referrer" => "zesk\\Account_Referrer",
		"account_type" => "zesk\\Account_Type",
		"commission" => "zesk\\Commission"
	);
}
