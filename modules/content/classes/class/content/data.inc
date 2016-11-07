<?php
/**
 * @copyright 2013 &copy; Market Acumen, Inc.
 * @author kent
 * @see Content_Data
 */
class Class_Content_Data extends zesk\Class_Object {
	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_id,
		"size" => self::type_integer,
		'md5hash' => self::type_hex,
		'type' => self::type_string,
		'data' => self::type_serialize,
		"checked" => self::type_timestamp,
		"missing" => self::type_timestamp
	);
	public $find_keys = array(
		'md5hash'
	);
}
