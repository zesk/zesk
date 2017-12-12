<?php
namespace zesk;

/**
 * @see Settings
 * @author kent
 *
 */
class Class_Settings extends Class_ORM {
	public $id_column = "name";
	public $column_types = array(
		'name' => self::type_string,
		'value' => self::type_serialize,
		'modified' => self::type_modified
	);
	
	/**
	 * No auto column
	 * @var boolean
	 */
	public $auto_column = false;
}

