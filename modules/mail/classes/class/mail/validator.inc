<?php

/**
 * @see Mail_Validator
 * @author kent
 *
 */
class Class_Mail_Validator extends zesk\Class_Object {
	public $id_column = "id";

	public $name_column = "address";

	public $column_types = array(
		"id" => self::type_id,
		"hash" => self::type_hex32,
		"user" => self::type_object,
		"address" => self::type_string,
		"sent" => self::type_timestamp,
		"confirmed" => self::type_timestamp
	);
}

