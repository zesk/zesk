<?php
/**
 *
 */
namespace zesk;

/**
 * @see Domain
 * @author kent
 *
 */
class Class_Domain extends Class_ORM {
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
		"name" => self::type_string,
		"tld" => self::type_string,
	);
}
