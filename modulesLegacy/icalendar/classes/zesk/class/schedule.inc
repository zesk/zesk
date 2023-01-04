<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Schedule
 * @author kent
 *
 */
class Class_Schedule extends Class_Base {
	public string $id_column = 'id';

	/**
	 *
	 */
	public array $has_one = [
		'account' => 'Account',
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'account' => self::TYPE_OBJECT,
		'frequency' => self::TYPE_INTEGER,
		'unit' => self::TYPE_STRING,
		'weekday' => self::TYPE_INTEGER,
		'monthday' => self::TYPE_INTEGER,
		'hour' => self::TYPE_INTEGER,
	];

	public array $column_defaults = [
		'frequency' => 1,
		'unit' => 'week',
	];
}
