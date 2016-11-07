<?php

/**
 * Class to abstract the IPBan table in the database
 *
 * @author kent
 *
 */
class Class_IPBan extends zesk\Class_Object {

	/**
	 * ID column
	 *
	 * @var string
	 */
	public $id_column = "ip";

	/**
	 * Auto column (none)
	 *
	 * @var string
	 */
	public $auto_column = "";

	/**
	 * How to retrieve this record
	 *
	 * @var array`
	 */
	public $find_keys = array(
		"ip"
	);

	/**
	 * How to retrieve this record
	 *
	 * @var array`
	*/
	public $duplicate_keys = array(
		"ip"
	);

	/**
	 * Columns
	 *
	 * @var array`
	*/
	public $columns = array(
		"ip",
		"server",
		"severity",
		"created",
		"recent",
		"complaints"
	);

	/**
	 * Column types
	 *
	 * @var array`
	*/
	public $column_types = array(
		"ip" => self::type_ip,
		"server" => self::type_object,
		"severity" => self::type_integer,
		"created" => self::type_created,
		"recent" => self::type_modified,
		"complaints" => self::type_integer
	);
}
