<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/nameserver.inc $
 * @package zesk
 * @subpackage whois
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:07 PM
 */
namespace zesk\Whois;

use zesk\Class_Object;

/**
 * @see NameServer
 * @author kent
 *
 */
class Class_NameServer extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	/**
	 * 
	 * @var array
	 */
	public $find_keys = array(
		"domain"
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"domain" => self::type_string,
		"created" => self::type_created,
		"first_used" => self::type_timestamp,
		"last_used" => self::type_timestamp
	);
}

