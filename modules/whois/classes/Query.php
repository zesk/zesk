<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/query.inc $
 * @author $Author: kent $
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

use zesk\Object;

/**
 *
 * @see Class_Query
 * @author kent
 *
 */
class Query extends Object {
	const PORT = 43;
	
	/**
	 *
	 * @param unknown $obj
	 */
	function setSearchID($obj) {
		$this->set_member("WhoisSearchID", $obj);
	}
	function is_done() {
		return $this->member_is_empty("RawResults");
	}
	private static function protocol($serverName, $string) {
		$s = fsockopen($serverName, self::PORT, $errno, $errstr, 3);
		if (!$s) {
			return null;
		}
		$result = "";
		fputs($s, $string . "\n");
		while (!feof($s)) {
			$data = fgets($s, 4096);
			if ($data === false) {
				break;
			}
			$result .= $data;
		}
		return $result;
	}
	private function whois_server_refresh() {
		$server = $this->server;
		if ($server instanceof Server) {
			return $server;
		}
		$name = $this->Name;
		
		$words = explode(".", $name);
		if (count($words) > 2) {
			$off = count($words) - 2;
			$tld = $words[$off] . "." . $words[$off + 1];
			$server = Server::find_server($this->application, $tld);
		} else {
			$off = count($words) - 1;
			$tld = $words[$off];
			$server = Server::find_server($this->application, $tld);
		}
		return $server;
	}
	public function whois() {
		$server = $this->whois_server_refresh();
		if ($server instanceof Server) {
			$serverName = $server->name();
			$result = self::protocol($serverName, $this->name);
			if ($result === false) {
				$result = "Connection failed to $serverName";
			} else {
				$this->parse($result);
			}
			$this->Results = $result;
		}
		$this->store();
	}
	function _setObject($class, $field, $token, $variable) {
		$obj = Object::register("$class", array(
			"$field" => trim($token)
		));
		$this->set_member("$variable", $obj);
		return $obj;
	}
	function parse($results) {
		$lines = explode("\n", $results);
		$taken = false;
		foreach ($lines as $line) {
			$line = trim($line);
			$pos = strpos($line, ":");
			if (($pos !== false) && ($pos > 0)) {
				$name = strtolower(substr($line, 0, $pos));
				$value = substr($line, $pos + 1);
				if ($name == "name server") {
					$obj = $this->_setObject("CNameServer", "Host", $value, "NameServers");
					$taken = true;
				} else {
					$attr[$name] = $value;
				}
			}
		}
		
		$token = "registrar";
		if (!empty($attr[$token])) {
			$this->_setObject("CDomainRegistrar", "Name", $attr[$token], "Registrar");
			$taken = true;
		} else {
			$this->set_member("Registrar", "None.");
		}
		$token = "whois server";
		if (!empty($attr[$token])) {
			$this->_setObject("CWhoisServer", "Host", $attr[$token], "WhoisServer");
			$taken = true;
		}
		$this->Taken = $taken;
		return false;
	}
	function registrar() {
		return $this->memberGet("Registrar");
	}
	function whoisServer() {
		return $this->memberGet("WhoisServer");
	}
	function nameServer() {
		return $this->memberGet("NameServer");
	}
}

