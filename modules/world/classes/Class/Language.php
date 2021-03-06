<?php
namespace zesk;

/**
 * @see Language
 * @author kent
 *
 */
class Class_Language extends Class_ORM {
	public $id_column = 'id';

	public $name_column = 'name';

	public $name = "Language";

	public $find_keys = array(
		"code",
	);

	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_string,
		"dialect" => self::type_string,
		"name" => self::type_string,
	);

	/**
	 * @todo Make country ID two-letter code
	 * @var array
	 */
	// 	public $has_one = array()
	// 		'dialect' => 'Country'
	// 	;
}
