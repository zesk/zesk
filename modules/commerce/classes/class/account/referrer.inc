<?php

/**
 * Class_Account_Referrer
 *
 * @author kent
 */
class Class_Account_Referrer extends zesk\Class_Object {
	public $columns = array(
		"ID",
		"Account",
		"IsActive",
		"CodeName",
		"Name"
	);
	public $has_one = array(
		'Account' => 'Account'
	);
}

