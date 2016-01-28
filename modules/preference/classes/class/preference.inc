<?php
/**
 * @version $Id: preference.inc 2403 2014-04-03 04:25:41Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
class Class_Preference extends Class_Object {

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
		'user' => "User",
		'type' => "Preference_Type"
	);
}