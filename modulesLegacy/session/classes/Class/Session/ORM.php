<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Session_ORM
 * @author kent
 *
 */
class Class_Session_ORM extends Class_Base {
	/**
	 * ID Column
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	public array $find_keys = [
		'cookie',
	];

	public array $has_one = [
		'user' => "zesk\User",
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'cookie' => self::TYPE_STRING,
		'is_one_time' => self::TYPE_BOOL,
		'user' => self::TYPE_OBJECT,
		'ip' => self::TYPE_IP4,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
		'expires' => self::TYPE_DATETIME,
		'seen' => self::TYPE_DATETIME,
		'sequence_index' => self::TYPE_INTEGER,
		'data' => self::TYPE_SERIALIZE,
	];

	public string $code_name = 'Session';

	public array $column_defaults = [
		'data' => [],
		'sequence_index' => 0,
		'ip' => '127.0.0.1',
	];
}
