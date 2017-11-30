<?php
/**
 * 
 */
namespace zesk;

/**
 * Class_Account_Referrer
 *
 * @author kent
 */
class Class_Account_Referrer extends Class_Object {
	public $columns = array(
		"ID",
		"Account",
		"IsActive",
		"CodeName",
		"Name"
	);
	public $has_one = array(
		'Account' => 'zesk\\Account'
	);
}

