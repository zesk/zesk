<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Health_Event
 * @author kent
 *
 */
class Class_Health_Event extends Class_ORM {
	public string $id_column = 'id';

	public array $has_one = [
		'events' => 'zesk\\Health_Events',
		'server' => 'zesk\\Server',
	];

	public array $column_types = [
		'id' => self::type_id,
		'events' => self::type_object,
		'when' => self::type_timestamp,
		'when_msec' => self::type_integer,
		'server' => self::type_object,
		'application' => self::type_string,
		'context' => self::type_string,
		'type' => self::type_string,
		'fatal' => self::type_boolean,
		'message' => self::type_string,
		'file' => self::type_string,
		'line' => self::type_integer,
		'backtrace' => self::type_serialize,
		'data' => self::type_serialize,
	];

	public array $column_defaults = [
		'when_msec' => 0,
	];
}
