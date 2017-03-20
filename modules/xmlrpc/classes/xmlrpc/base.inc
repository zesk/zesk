<?php
namespace xmlrpc;

class Base {
	const type_boolean = 'boolean';
	const type_integer = 'int';
	const type_real = 'double';
	const type_string = 'string';
	const type_date = 'dateTime.iso8601';
	const type_object = 'struct';
	const type_array = 'array';
	const type_binary = 'base64';
	static $types = array(
		self::type_boolean,
		self::type_integer,
		self::type_real,
		self::type_string,
		self::type_date,
		self::type_object,
		self::type_array,
		self::type_binary
	);
	public static function type_valid($type) {
		return in_array($type, self::$types);
	}
}
