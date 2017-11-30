<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/server.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

/**
 * @see Server
 * @author kent
 *
 */
class Class_Server extends Class_Object {
	protected $column_types = array(
		"id" => self::type_id,
		"tld" => self::type_string,
		"host" => self::type_string
	);
}
