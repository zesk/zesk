<?php
/**
 * @copyright 2013 &copy; Market Acumen, Inc.
 * @author kent
 * @see Content_Data
 */
namespace zesk;

/**
 * @see Content_Data
 * @author kent
 *
 */
class Class_Content_Data extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public $id_column = "id";

	/**
	 *
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"size" => self::type_integer,
		'md5hash' => self::type_hex,
		'type' => self::type_string,
		'data' => self::type_serialize,
		"checked" => self::type_timestamp,
		"missing" => self::type_timestamp,
	);

	/**
	 *
	 * @var array
	 */
	public $find_keys = array(
		'md5hash',
	);
}
