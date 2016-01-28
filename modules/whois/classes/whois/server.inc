<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/server.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
class Whois_Server extends Object {
	protected $columns = array(
		"ID", 
		"TLD", 
		"Host"
	);
	function _textFormat() {
		return '{TLD} ({Host})';
	}
	function bootstrap() {
		$tlds = array(
			"com", 
			"net", 
			"edu"
		);
		return true;
	}
	public static function register_server($name, $tld = "") {
		$fields = array();
		if (!empty($name)) {
			$fields["Host"] = $name;
		}
		if (!empty($tld)) {
			$fields["TLD"] = $tld;
		}
		if (empty($fields)) {
			return null;
		}
		return Object::factory(__CLASS__)->register($fields);
	}
	public static function find_server($tld) {
		$foo = new Whois_Server();
		if (ip::valid($tld)) {
			$tld = "." . array_pop(explode(".", $tld)) . ".in-addr.arpa";
			if ($foo->find(array(
				"TLD" => $tld
			))) {
				return $foo;
			}
			return $foo->find(array(
				"TLD" => ".in-addr.arpa"
			));
		}
		if (substr($tld, 0, 1) !== ".") {
			$tld = ".$tld";
		}
		return $foo->find(array(
			"TLD" => $tld
		));
	}
}
