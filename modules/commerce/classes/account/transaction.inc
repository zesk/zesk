<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/account/transaction.inc $
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

/**
 * Account_Transaction
 * 
 * @author kent
 */
class Account_Transaction extends Object {
	public static function register_transaction(array $fields) {
		return Object::factory(__CLASS__, $fields)->register();
	}
}
