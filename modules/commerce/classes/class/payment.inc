<?php
/**
 *
 */
namespace zesk;

/**
 * Class_Payment
 */
class Class_Payment extends Class_Object {
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
		'payment_type' => "zesk\\Payment_Type",
		'owner' => "zesk\\User",
		'account' => "zesk\\Account"
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		'created' => self::type_timestamp,
		'modified' => self::type_timestamp,
		'validated' => self::type_timestamp,
		'decline_notified' => self::type_timestamp,
		'decline_notify_count' => self::type_integer
	);
}
