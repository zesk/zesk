<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Health_Event
 * @author kent
 *
 */
class Class_Health_Event extends Class_Base {
	public string $id_column = 'id';

	public array $has_one = [
		'events' => 'zesk\\Health_Events',
		'server' => 'zesk\\Server',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'events' => self::TYPE_OBJECT,
		'when' => self::TYPE_TIMESTAMP,
		'when_msec' => self::TYPE_INTEGER,
		'server' => self::TYPE_OBJECT,
		'application' => self::TYPE_STRING,
		'context' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'fatal' => self::TYPE_BOOL,
		'message' => self::TYPE_STRING,
		'file' => self::TYPE_STRING,
		'line' => self::TYPE_INTEGER,
		'backtrace' => self::TYPE_SERIALIZE,
		'data' => self::TYPE_SERIALIZE,
	];

	public array $column_defaults = [
		'when_msec' => 0,
	];
}
