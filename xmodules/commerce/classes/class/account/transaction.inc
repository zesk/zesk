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
