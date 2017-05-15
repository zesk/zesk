<?php
/**
 *
 */
namespace zesk;

/**
 * Class_Payment_Type
 *
 * @author kent
 */
class Class_Payment_Type extends Class_Object {
	public $id_column = 'id';
	public $find_keys = array(
		'code'
	);
	public $polymorphic = "zesk\\Payment_Type";
	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_polymorph,
		'name' => self::type_string,
		'description' => self::type_string,
		'is_cc' => self::type_boolean
	);
}

