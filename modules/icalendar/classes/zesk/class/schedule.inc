<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Schedule
 * @author kent
 *
 */
class Class_Schedule extends Class_ORM {
	public $id_column = "id";

	/**
	 *
	 * @var unknown
	 */
	public $has_one = [
		"account" => 'Account',
	];

	/**
	 *
	 * @var array
	 */
	public $column_types = [
		'id' => self::type_id,
		'account' => self::type_object,
		'frequency' => self::type_integer,
		'unit' => self::type_string,
		'weekday' => self::type_integer,
		'monthday' => self::type_integer,
		'hour' => self::type_integer,
	];

	public $column_defaults = [
		'frequency' => 1,
		'unit' => 'week',
	];
}
