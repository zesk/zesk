<?php
/**
 * @version $Id: Preference.php 4412 2017-03-08 05:16:44Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
namespace zesk;

class Class_Preference extends Class_ORM {
	public $id_column = "id";
	
	/**
	 * Column type definition for Object
	 *
	 * @var unknown
	 */
	public $column_types = array(
		"id" => self::type_id,
		"user" => self::type_object,
		"type" => self::type_object,
		"value" => self::type_serialize
	);
	
	/**
	 * Which keys are used to find this in the database uniquely
	 *
	 * @var array
	 */
	public $find_keys = array(
		"user",
		"type"
	);
	
	/**
	 * Links to other objects
	 *
	 * @var array
	 */
	public $has_one = array(
		'user' => "zesk\\User",
		'type' => "zesk\\Preference_Type"
	);
}
