<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/query.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

use zesk\Class_Object;

class Query extends Class_Object {
	protected $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string,
		"registrar" => self::type_object,
		"server" => self::type_object,
		"created" => self::type_created,
		"fetched" => self::type_timestamp,
		"results" => self::type_object,
		"taken" => self::type_boolean
	);
	protected $has_one = array(
		"server" => "zesk\\Whois\\Server",
		"registrar" => "zesk\\Whois\\Registrar",
		"result" => "zesk\\Whois\\Result"
	);
	protected $has_many = array(
		"nameservers" => array(
			"class" => "zesk\\Whois\\NameServer"
		)
	);
}

