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
class Class_Invoice_Payment extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	/**
	 * 
	 * @var array
	 */
	public $has_one = array(
		"invoice" => "zesk\\Invoice",
		"payment_transaction" => "zesk\\Payment_Transaction"
	);
}
