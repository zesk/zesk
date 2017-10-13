<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/search.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

/**
 * @see Search
 * @author kent
 *
 */
class Class_Search extends Class_Object {
	public $id_column = "id";
	public $column_types = array(
		"id" => self::type_id,
		"search" => self::type_string,
		"tlds" => self::type_string
	);
	public $has_many = array(
		"queries" => array(
			"class" => "zesk\\Whois\\Query"
		)
	);
}

