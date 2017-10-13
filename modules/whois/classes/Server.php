<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/server.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

use zesk\IPv4;
use zesk\Application;

/**
 * @see Class_Server
 * @author kent
 *
 */
class Server extends Object {
	function _textFormat() {
		return '{TLD} ({Host})';
	}
	public static function register_server(Application $application, $tld, $name) {
		$fields = array();
		$fields["tld"] = $tld;
		$fields["name"] = $name;
		return $application->object_factory(__CLASS__, $fields)->register();
	}
	public static function find_server(Application $application, $tld) {
		$foo = $application->object_factory(__CLASS__);
		if (IPv4::valid($tld)) {
			$tld = "." . array_pop(explode(".", $tld)) . ".in-addr.arpa";
			if ($foo->find(array(
				"tld" => $tld
			))) {
				return $foo;
			}
			return $foo->find(array(
				"tld" => ".in-addr.arpa"
			));
		}
		if (substr($tld, 0, 1) !== ".") {
			$tld = ".$tld";
		}
		return $foo->find(array(
			"tld" => $tld
		));
	}
}
