<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/account/referrer.inc $
* @package zesk
* @subpackage objects
*/

/**
 * Account_Referrer
 * 
 * @author kent
 */
class Account_Referrer extends Object {}

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
		'Account' => 'Account'
	);
}

