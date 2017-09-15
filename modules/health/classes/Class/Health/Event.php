<?php
namespace zesk;

/**
 * @see Health_Event
 * @author kent
 *
 */
class Class_Health_Event extends Class_Object {
	public $id_column = "id";
	public $has_one = array(
		"events" => "zesk\\Health_Events",
		"server" => "zesk\\Server"
	);
	public $column_types = array(
		"id" => self::type_id,
		"events" => self::type_object,
		"when" => self::type_timestamp,
		"when_msec" => self::type_integer,
		"server" => self::type_object,
		"application" => self::type_string,
		"context" => self::type_string,
		"type" => self::type_string,
		"fatal" => self::type_boolean,
		"message" => self::type_string,
		"file" => self::type_string,
		"line" => self::type_integer,
		"backtrace" => self::type_serialize,
		"data" => self::type_serialize
	);
	public $column_defaults = array(
		'when_msec' => 0
	);
}
