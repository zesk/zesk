<?php
/**
 * $URL$
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class JSON {
	/**
	 * Is the name passed a valid member name which does not require quotes in JavaScript?
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public static function valid_member_name($name) {
		return preg_match('/^[$A-Za-z_][$A-Za-z_0-9]*$/', $name) !== 0;
	}
	
	/**
	 * Quote a member name if necessary
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function object_member_name_quote($name) {
		if (self::valid_member_name($name)) {
			return $name;
		}
		return self::quote($name);
	}
	
	/**
	 * Quote a JSON token properly
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function quote($name) {
		return '"' . addcslashes($name, "\t\n\r\"\\") . '"';
	}
	
	/**
	 * Pretty output of JSON
	 * 
	 * @param mixed $mixed
	 * @return string
	 */
	public static function encode_pretty($mixed) {
		return json_encode($mixed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	/**
	 * Like json_encode, except handles special variable name cases to NOT encode JavaScript
	 *
	 * JSON::encode(array('*method' => 'open_window', 'count' => 5)) =
	 * "{'method':open_window,'count':5}"
	 *
	 * Useful when you want to pass JS code, variables, or anything JavaScript via JSON
	 *
	 * @param mixed $mixed
	 *        	Item to encode using JSON
	 * @return string JSON string of encoded item
	 */
	public static function encode($mixed) {
		static $recursion = 0;
		if (is_array($mixed) || is_object($mixed)) {
			if ($recursion > 10) {
				return '"Recursion ' . type($mixed) . '"';
			}
			$result = array();
			if (!is_object($mixed) && !arr::is_assoc($mixed)) {
				foreach ($mixed as $v) {
					$recursion++;
					$result[] = self::encode($v);
					$recursion--;
				}
				return '[' . implode(',', $result) . ']';
			} else if (is_object($mixed)) {
				if (method_exists($mixed, "to_json")) {
					$mixed = $mixed->to_json();
				} else if (method_exists($mixed, "__toString")) {
					$mixed = strval($mixed);
				} else {
					$mixed = get_class($mixed) . ":no-json-method";
				}
				return self::encode($mixed);
			} else {
				foreach ($mixed as $k => $v) {
					if (substr($k, 0, 1) === '*') {
						$result[] = self::quote(substr($k, 1)) . ":" . $v;
					} else {
						$recursion++;
						$result[] = self::quote($k) . ":" . self::encode($v);
						$recursion--;
					}
				}
				return '{' . implode(',', $result) . '}';
			}
		} else if (is_bool($mixed)) {
			return $mixed ? "true" : "false";
		} else if (is_int($mixed) || is_double($mixed)) {
			return $mixed;
		} else if (is_string($mixed)) {
			return self::quote($mixed);
		} else if (is_resource($mixed)) {
			return '"' . strval($mixed) . '"';
		} else if ($mixed === null) {
			return 'null';
		} else {
			die("Unknown type: $mixed " . gettype($mixed));
		}
	}
	
	/**
	 * Like json_encode, except removes unnecessary quotes in keys
	 *
	 * JSON::encodex(array('*method' => 'open_window', 'count' => 5)) =
	 * "{method:open_window,count:5}"
	 *
	 * Useful when you want to pass JS code, variables, or anything JavaScript via JSON
	 *
	 * @param mixed $mixed
	 *        	Item to encode using JSON
	 * @return string JSON string of encoded item
	 */
	public static function encodex($mixed) {
		if (is_array($mixed) || is_object($mixed)) {
			$result = array();
			if (!is_object($mixed) && !arr::is_assoc($mixed)) {
				foreach ($mixed as $v) {
					$result[] = self::encodex($v);
				}
				return '[' . implode(',', $result) . ']';
			} else if (is_object($mixed) && method_exists($mixed, "to_json")) {
				$mixed = $mixed->to_json();
				return self::encodex($mixed);
			} else {
				foreach ($mixed as $k => $v) {
					if (substr($k, 0, 1) === '*') {
						$result[] = self::object_member_name_quote(substr($k, 1)) . ":" . $v;
					} else {
						$result[] = self::object_member_name_quote($k) . ":" . self::encodex($v);
					}
				}
				return '{' . implode(',', $result) . '}';
			}
		} else if (is_bool($mixed)) {
			return $mixed ? "true" : "false";
		} else if (is_int($mixed) || is_double($mixed)) {
			return $mixed;
		} else if (is_string($mixed)) {
			return self::quote($mixed);
		} else if (is_resource($mixed)) {
			return '"' . strval($mixed) . '"';
		} else if ($mixed === null) {
			return 'null';
		} else {
			die("Unknown type: $mixed " . gettype($mixed));
		}
	}
	
	/**
	 * Like json_decode, except if the decoding fails throw an exception
	 *
	 * @param string $string
	 *        	A JSON string to decode
	 * @return the decoded JSON string, or the default value if it fails
	 * @throws Exception_Parse
	 */
	static function decode($string, $assoc = true) {
		$string = trim($string);
		if ($string === 'null') {
			return null;
		}
		if (function_exists('json_decode')) {
			$result = json_decode($string, $assoc);
			if ($result !== null) {
				return $result;
			}
			$e = self::last_error();
			if (!$e) {
				$e = new Exception_Parse("Unable to parse JSON: {string}", array(
					"string" => $string
				));
			}
			throw $e;
		} else {
			// Throws own error
			return self::zesk_decode($string, $assoc);
		}
	}
	
	/**
	 * 
	 * @param unknown $code
	 * @return string|mixed|array
	 */
	private static function error_to_string($code) {
		if (!defined('JSON_ERROR_NONE')) {
			return "Unknown code $code";
		}
		static $errors = array(
			JSON_ERROR_NONE => null,
			JSON_ERROR_DEPTH => "Maximum stack depth has been exceeded",
			JSON_ERROR_STATE_MISMATCH => "Malformed JSON",
			JSON_ERROR_CTRL_CHAR => "Control character error, malformed JSON",
			JSON_ERROR_SYNTAX => "Syntax error, malformed JSON",
			JSON_ERROR_UTF8 => "Malformed UTF-8 characters"
		);
		//			JSON_ERROR_RECURSION => "One or more recursive references in the value to be encoded",
		//			JSON_ERROR_INF_OR_NAN => "One or more NAN or INF in value",
		//			JSON_ERROR_UNSUPPORTED_TYPE => "A value of a type that cannot be encoded was given"
		return avalue($errors, $code, "Unknown code $code");
	}
	private static function last_error() {
		if (function_exists('json_last_error')) {
			$code = json_last_error();
			$message = self::error_to_string($code);
			if ($message === null) {
				return null;
			}
			return new Exception_Parse($message, array(
				"json_last_error" => $code
			), $code);
		}
		return null;
	}
	
	/**
	 * Used to track state for Zesk's internal JSON decoder
	 * 
	 * @var Exception
	 */
	static $last_error = null;
	
	/**
	 * Like json_decode except use Zesk's internal parser (slow)
	 *
	 * @param string $string        	
	 * @param string $assoc        	
	 * @return mixed
	 */
	static function zesk_decode($string, $assoc = false) {
		$string = trim($string);
		try {
			self::$last_error = null;
			list($length, $result) = self::_decode_value($string, 0, $assoc);
			if ($length === strlen($string)) {
				return $result;
			}
		} catch (Exception $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
			self::$last_error = $e;
		}
		return null;
	}
	
	/**
	 * Decode JSON string starting with white space, then a value
	 *
	 * @param string $string
	 *        	JSON string optionally beginning with whitespace which will be trimmed
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @param boolean $assoc
	 *        	Whether arrays should be created as associative arrays, or objects
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_white_value($string, $offset, $assoc = false) {
		$white = 0;
		if (!preg_match("/^\s+/", $string, $match)) {
			return self::_decode_value($string, $offset);
		}
		$white = strlen($match[0]);
		list($length, $result) = self::_decode_value(substr($string, $white), $offset + $white, $assoc);
		return array(
			$length + $white,
			$result
		);
	}
	/**
	 * This is somewhat of a pain in the ass to implement and maintain, so do the basics here, but
	 * assume PHP 5.2 and json will
	 * be core of PHP in the near future.
	 *
	 * @param string $string        	
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @param boolean $assoc        	
	 * @throws Exception_Parse
	 * @return array
	 */
	private static function _decode_value($string, $offset, $assoc = false) {
		static $begins = array(
			'null' => null,
			'true' => true,
			'false' => false
		);
		if ($string === '') {
			throw new Exception_Parse("Invalid empty JSON string at offset {offset}", array(
				"offset" => $offset
			));
		}
		foreach ($begins as $match => $value) {
			if (begins($string, $match)) {
				return array(
					strlen($match),
					$value
				);
			}
		}
		$c = $string[0];
		switch ($c) {
			case '"':
				return self::_decode_string($string, $offset);
			case '[':
				return self::_decode_array($string, $offset, $assoc);
			case '{':
				return self::_decode_object($string, $offset, $assoc);
		}
		if (preg_match('/[-0-9]/', $c)) {
			return self::_decode_number($string, $offset);
		}
		throw new Exception_Parse("Invalid JSON token \"{char}\" at position {offset}", array(
			"char" => $c,
			"offset" => $offset
		));
	}
	
	/**
	 * JSON parse number
	 *
	 * @param string $string        	
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @throws Exception_Parse
	 * @return array
	 */
	private static function _decode_number($string, $offset) {
		$match = null;
		if (preg_match('/-?[0-9]+(\.[0-9]+([eE][-+]?[0-9]+)?)?/', $string, $match)) {
			if (preg_match('/^-?[0-9]+$/', $match[0])) {
				return array(
					strlen($match[0]),
					intval($match[0])
				);
			}
			return array(
				strlen($match[0]),
				doubleval($match[0])
			);
		}
		throw new Exception_Parse("Invalid JSON numeric token \"{string}...\" at position {offset}", array(
			"string" => substr($string, 16),
			"offset" => $offset
		));
	}
	
	/**
	 * JSON decode array
	 *
	 * @param string $string        	
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @throws Exception_Parse
	 * @return array
	 */
	private static function _decode_array($string, $offset, $assoc) {
		$len = strlen($string);
		$result = array();
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if (strpos(" \r\n\t", $c) !== false) {
				$i++;
				continue;
			}
			if ($c === ']') {
				return array(
					$i + 1,
					$result
				);
			}
			list($item_len, $item) = self::_decode_value(substr($string, $i), $offset + $i, $assoc);
			$i += $item_len;
			$result[] = $item;
			while ($i < $len) {
				$c = $string[$i];
				if (strpos(" \r\n\t", $c) !== false) {
					$i++;
					continue;
				}
				if ($c === ']') {
					return array(
						$i + 1,
						$result
					);
				}
				if ($c === ',') {
					$i++;
					break;
				}
				throw new Exception_Parse("Invalid JSON array at offset {offset} expecting comma or end bracket", array(
					"offset" => $offset + $i
				));
			}
		}
		throw new Exception_Parse("Unterminated array structure at offset {offset}", array(
			"offset" => $offset
		));
	}
	
	/**
	 * JSON decode object
	 *
	 * @param string $string        	
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @throws Exception_Parse
	 * @return array
	 */
	private static function _decode_object($string, $offset, $assoc) {
		$len = strlen($string);
		$result = array();
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if (strpos(" \r\n\t", $c) !== false) {
				$i++;
				continue;
			}
			if ($c === '}') {
				return array(
					$i + 1,
					$assoc ? $result : (object) $result
				);
			}
			if ($c !== '"') {
				throw new Exception_Parse("Invalid object key at offset {offset}", array(
					"offset" => $offset + $i
				));
			}
			list($key_len, $key) = self::_decode_string(substr($string, $i), $offset);
			$i += $key_len - 1;
			do {
				++$i;
				if ($i > $len) {
					throw new Exception_Parse("Unterminated object value looking for : at offset {offset}", array(
						"offset" => $offset + $i
					));
				}
				$c = $string[$i];
			} while (strpos(" \r\n\t", $c) !== false);
			if ($c !== ":") {
				throw new Exception_Parse("Missing : value at offset {offset}", array(
					"offset" => $offset + $i
				));
			}
			++$i;
			list($value_len, $value) = self::_decode_white_value(substr($string, $i), $offset + $i, $assoc);
			//echo "Parsed $value_len bytes: " . substr(substr($string, $i), 0, $value_len) . "\n";
			$i += $value_len;
			$result[$key] = $value;
			while ($i < $len) {
				$c = $string[$i];
				if (strpos(" \r\n\t", $c) !== false) {
					$i++;
					continue;
				}
				if ($c === '}') {
					return array(
						$i + 1,
						$result
					);
				}
				if ($c === ',') {
					$i++;
					break;
				}
				throw new Exception_Parse("Invalid JSON object at offset {offset} expecting comma or end bracket, found \"{char}\"", array(
					"char" => $c,
					"offset" => $offset + $i
				));
			}
		}
		throw new Exception_Parse("Unterminated array structure at offset {offset}", array(
			"offset" => $offset
		));
	}
	
	/**
	 * JSON decode string
	 *
	 * @param string $string        	
	 * @param integer $offset
	 *        	Current offset in the total string - for debugging only
	 * @throws Exception_Parse
	 * @return array
	 */
	private static function _decode_string($string, $offset) {
		static $strc = array(
			'"' => '"',
			'\\' => '\\',
			'/' => '/',
			'b' => "\x08",
			'f' => "\f",
			'n' => "\n",
			'r' => "\r",
			't' => "\t"
		);
		
		$len = strlen($string);
		$result = '';
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if ($c === '"') {
				return array(
					$i + 1,
					$result
				);
			}
			$i++;
			if ($c !== '\\') {
				$result .= $c;
				continue;
			}
			
			if ($i >= $len) {
				throw new Exception_Parse("Unterminated string at offset {offset}", array(
					"offset" => $offset + $i
				));
			}
			$c = $string[$i];
			if (array_key_exists($c, $strc)) {
				$result .= $strc[$c];
			} else if ($c === 'u') {
				++$i;
				if ($i + 4 >= $len) {
					throw new Exception_Parse("Invalid unterminated hex code at string {offset}", array(
						"offset" => $offset + $i
					));
				}
				$result .= Hexadecimal::decode(substr($string, $i, $i + 3));
				$i += 4;
			} else {
				throw new Exception_Parse("Invalid escape sequence {char} at offset {offset}", array(
					'char' => $c,
					"offset" => $offset + $i
				));
			}
		}
		throw new Exception_Parse("Invalid unterminated string at end of JSON string {offset}", array(
			"offset" => $offset + $i
		));
	}
}

