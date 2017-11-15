<?php
/**
 * $URL
 * @author $Author: kent $
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage whois
 */
namespace zesk\Whois;

use zesk\Class_Object;

/**
 * @see Contact
 * @author kent
 *
 */
class Class_Contact extends Class_Object {
	public $column_types = array(
		"id" => self::type_id,
		"contact" => self::type_object,
		"type" => self::type_string,
		"result" => self::type_object
	);
	protected $has_one = array(
		"contact" => "zesk\\Contact",
		"result" => "zesk\\Whois\\Result"
	);
}
