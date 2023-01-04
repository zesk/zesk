<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Health_Events
 * @author kent
 *
 */
class Class_Health_Events extends Class_Base {
	/**
	 * Name of the ORM which should share this database with.
	 *
	 * Allows objects to be grouped into a database (by module) or functionality, for example.
	 *
	 * @var string
	 */
	protected $database_group = 'Health_Event';

	public string $id_column = 'id';

	public array $find_keys = [
		'date',
		'hash',
	];

	public array $has_one = [
		'server' => 'zesk\\Server',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,

		'date' => self::TYPE_DATE,
		'hash' => self::TYPE_HEX,

		'server' => self::TYPE_OBJECT,
		'application' => self::TYPE_STRING,
		'context' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'message' => self::TYPE_STRING,
		'fatal' => self::TYPE_BOOL,

		'first' => self::TYPE_TIMESTAMP,
		'first_msec' => self::TYPE_INTEGER,
		'recent' => self::TYPE_TIMESTAMP,
		'recent_msec' => self::TYPE_INTEGER,

		'total' => self::TYPE_INTEGER,
	];

	public array $column_defaults = [
		'first_msec' => 0,
		'recent_msec' => 0,
	];
}
