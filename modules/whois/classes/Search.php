<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/whois/classes/whois/search.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage whois
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk\Whois;

use zesk\Exception_Parameter;

class Search extends Object {
	private $Extrapolated;
	private $Matches;
	private $TLDs;
	function __construct($value = null, $options = false) {
		$this->Extrapolated = true;
		$this->Matches = array();
		
		parent::__construct($value, $options);
	}
	public static function character_codes() {
		return array(
			"~" => "aeiou",
			"#" => "0123456789",
			"!" => "bcdfghjklmnpqrstvwxyz",
			"?" => "abcdefghijklmnopqrstuvwxyz",
			"*" => "abcdefghijklmnopqrstuvwxyz0123456789-"
		);
	}
	private function multiply_match($add) {
		$newMatches = array();
		
		if (is_array($add)) {
			foreach ($this->Matches as $match) {
				foreach ($add as $suffix) {
					$newMatches[] = $match . $suffix;
				}
			}
		} else if (is_string($add)) {
			$len = strlen($add);
			foreach ($this->Matches as $match) {
				for ($i = 0; $i < $len; $i++) {
					$newMatches[] = $match . substr($add, $i, 1);
				}
			}
		} else {
			throw new Exception_Parameter(gettype($add) . " " . $add);
		}
		
		$this->Matches = $newMatches;
	}
	private function extend_match($add) {
		if ($add === "") {
			return;
		}
		$matchCount = count($this->Matches);
		for ($i = 0; $i < $matchCount; $i++) {
			$this->Matches[$i] = $this->Matches[$i] . $add;
		}
	}
	function _extrapolate($search) {
		$this->Matches = array();
		$this->Matches[] = "";
		
		$noSuffix = false;
		if (strstr($search, ".") !== false) {
			$noSuffix = true;
		}
		
		$keyLookup = self::character_codes();
		
		$len = strlen($search);
		$build = "";
		for ($i = 0; $i < $len; $i++) {
			$char = substr($search, $i, 1);
			if (isset($keyLookup["$char"])) {
				$this->extend_match($build);
				$build = "";
				$this->multiply_match($keyLookup["$char"]);
			} else {
				$build .= $char;
			}
		}
		$this->extend_match($build);
		if ($noSuffix) {
			return;
		}
		$this->multiply_match($this->getTLDs());
	}
}

