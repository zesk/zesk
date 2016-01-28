<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/account/transaction.inc $
* @package zesk
* @subpackage objects
*/

/**
 * Account_Transaction
 * 
 * @author kent
 */
class Account_Transaction extends Object {
	public static function register_transaction(array $fields) {
		return Object::factory("Account_Transaction", $fields)->register();
	}
}

/**
 * Class_Account_Transaction
 * 
 * @author kent
 */
class Class_Account_Transaction extends Class_Object {
	public $columns = array(
		"ID",
		"TransactionGroup",
		"Description",
		"Account",
		"TransactionType",
		"EffectiveDate",
		"Created",
		"Modified",
		"Invoice",
		"Amount",
		"Reconciled",
		"PaymentTransaction",
		"OrderIndex",
		"InvoiceItem"
	);
	public $find_keys = array(
		"TransactionGroup",
		"Account",
		"TransactionType"
	);
}