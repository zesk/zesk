<?php

/**
 * Class_Payment
 */
class Class_Payment extends Class_Object {
	public $id_column = "id";
	public $has_one = array(
		'payment_type' => "Payment_Type",
		'owner' => "User",
		'account' => "Account"
	);
	public $column_types = array(
		'created' => self::type_timestamp,
		'modified' => self::type_timestamp,
		'validated' => self::type_timestamp,
		'decline_notified' => self::type_timestamp,
		'decline_notify_count' => self::type_integer
	);
}
