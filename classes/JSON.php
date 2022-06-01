<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use __PHP_Incomplete_Class;
use http\Exception\RuntimeException;
use stdClass;

if (!defined('JSON_INVALID_UTF8_IGNORE')) {
	define('JSON_INVALID_UTF8_IGNORE', 0);
}

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
	public static function valid_member_name(string $name): bool {
		return preg_match('/^[$A-Za-z_][$A-Za-z_0-9]*$/', $name) !== 0;
	}

	/**
	 * Quote a member name if necessary
	 *
	 * @param string $name
	 * @return string
	 */
	public static function object_member_name_quote(string $name): string {
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
	public static function quote(string $name): string {
		return '"' . addcslashes($name, "\t\n\r\"\\") . '"';
	}

	/**
	 * Pretty output of JSON
	 *
	 * @param mixed $mixed
	 * @return string
	 */
	public static function encode_pretty(mixed $mixed): string {
		return json_encode($mixed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
	}

	/**
	 * Default methods to use in prepare
	 * @var array
	 */
	private static array $default_methods = [
		'json',
		'to_json',
		'toJSON',
		'__toJSON',
	];

	/**
	 * Prepare internal objects to simple JSON-capable structures.
	 *
	 * @param mixed $mixed
	 * @param ?array $methods
	 * @param array $arguments Optional arguments passed to $methods
	 * @return mixed
	 */
	public static function prepare(mixed $mixed, array $methods = [], array $arguments = []): mixed {
		if ($mixed === null) {
			return null;
		}
		if (count($methods) === 0) {
			$methods = self::$default_methods;
		}
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::prepare($v, $methods, $arguments);
			}
			return $mixed;
		}
		if (is_object($mixed)) {
			foreach ($methods as $method) {
				if (method_exists($mixed, $method)) {
					return call_user_func_array([
						$mixed,
						$method,
					], $arguments);
				}
			}
			if (method_exists($mixed, '__toString')) {
				return strval($mixed);
			}
			$result = [];
			foreach (get_object_vars($mixed) as $k => $v) {
				$result[$k] = self::prepare($v, $methods, $arguments);
			}
			return $result;
		}
		return flatten($mixed);
	}

	/**
	 * Prefer default JSON options, so use this for compatibility (this will probably go away)
	 *
	 * @param mixed $mixed
	 *            Item to encode using JSON
	 * @return string JSON string of encoded item
	 * @throws Exception_Semantics
	 */
	public static function encode(mixed $mixed): string {
		$result = json_encode($mixed, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
		if ($result === false) {
			throw new Exception_Semantics('JSON encode failed');
		}
		return $result;
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
	 *            Item to encode using JSON
	 * @return string JSON string of encoded item
	 */
	public static function zencode(mixed $mixed): string {
		static $recursion = 0;
		if (is_array($mixed) || is_object($mixed)) {
			if ($recursion > 10) {
				return '"Recursion ' . type($mixed) . '"';
			}
			$result = [];
			if (!is_object($mixed) && !ArrayTools::isAssoc($mixed)) {
				foreach ($mixed as $v) {
					$recursion++;
					$result[] = self::zencode($v);
					$recursion--;
				}
				return '[' . implode(',', $result) . ']';
			} elseif (is_object($mixed)) {
				if (method_exists($mixed, 'to_json')) {
					$mixed = $mixed->to_json();
				} elseif (method_exists($mixed, '__toString')) {
					$mixed = strval($mixed);
				} elseif ($mixed instanceof stdClass) {
					$mixed = get_object_vars($mixed);
					if (count($mixed) === 0) {
						return '{}';
					}
				} else {
					$mixed = get_class($mixed) . ':no-json-method';
				}
				return self::zencode($mixed);
			} else {
				foreach ($mixed as $k => $v) {
					$k = strval($k);
					if (str_starts_with($k, '*')) {
						$result[] = self::quote(substr($k, 1)) . ':' . $v;
					} else {
						$recursion++;
						$result[] = self::quote($k) . ':' . self::zencode($v);
						$recursion--;
					}
				}
				return '{' . implode(',', $result) . '}';
			}
		} elseif (is_bool($mixed)) {
			return $mixed ? 'true' : 'false';
		} elseif (is_int($mixed) || is_float($mixed)) {
			return strval($mixed);
		} elseif (is_string($mixed)) {
			return self::quote($mixed);
		} elseif (is_resource($mixed)) {
			return '"' . strval($mixed) . '"';
		} elseif ($mixed === null) {
			return 'null';
		} elseif ($mixed instanceof __PHP_Incomplete_Class) {
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
	 *            Item to encode using JSON
	 * @return string JSON string of encoded item
	 */
	public static function encodex(mixed $mixed): string {
		if (is_array($mixed) || is_object($mixed)) {
			$result = [];
			if (!is_object($mixed) && !ArrayTools::isAssoc($mixed)) {
				foreach ($mixed as $v) {
					$result[] = self::encodex($v);
				}
				return '[' . implode(',', $result) . ']';
			} elseif (is_object($mixed) && method_exists($mixed, 'to_json')) {
				$mixed = $mixed->to_json();
				return self::encodex($mixed);
			} else {
				foreach ($mixed as $k => $v) {
					$k = strval($k);
					if (str_starts_with($k, '*')) {
						$result[] = self::object_member_name_quote(substr($k, 1)) . ':' . $v;
					} else {
						$result[] = self::object_member_name_quote($k) . ':' . self::encodex($v);
					}
				}
				return '{' . implode(',', $result) . '}';
			}
		} elseif (is_bool($mixed)) {
			return $mixed ? 'true' : 'false';
		} elseif (is_int($mixed) || is_float($mixed)) {
			return strval($mixed);
		} elseif (is_string($mixed)) {
			return self::quote($mixed);
		} elseif (is_resource($mixed)) {
			return '"' . strval($mixed) . '"';
		} elseif ($mixed === null) {
			return 'null';
		} else {
			die("Unknown type: $mixed " . gettype($mixed));
		}
	}

	/**
	 * Like json_decode, except if the decoding fails throw an exception
	 *
	 * @param string $string
	 *            A JSON string to decode
	 * @param bool $assoc
	 * @return mixed the decoded JSON string, or the default value if it fails
	 * @throws Exception_Parse
	 */
	public static function decode(string $string, bool $assoc = true): mixed {
		$string = trim($string);
		if ($string === 'null') {
			return null;
		}
		$result = json_decode($string, $assoc);
		if ($result !== null) {
			return $result;
		}
		$e = self::lastError();
		if (!$e) {
			$e = new Exception_Parse('Unable to parse JSON: {string}', [
				'string' => $string,
			]);
		}

		throw $e;
	}

	/**
	 *
	 * @param int $code
	 * @return ?string
	 */
	private static function errorToString(int $code): ?string {
		static $errors = [
			JSON_ERROR_DEPTH => 'Maximum stack depth has been exceeded',
			JSON_ERROR_STATE_MISMATCH => 'Malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Control character error, malformed JSON',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters',
			JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
			JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF in value',
			JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
		];
		if ($code === JSON_ERROR_NONE) {
			return null;
		}
		return $errors[$code] ?? "Unknown code $code";
	}

	/**
	 * @return ?Exception_Parse
	 */
	private static function lastError(): ?Exception_Parse {
		$code = json_last_error();
		$message = self::errorToString($code);
		if ($message === null) {
			return null;
		}
		return new Exception_Parse($message, [
			'json_last_error' => $code,
		], $code);
	}

	/**
	 * Used to track state for internal JSON decoder
	 *
	 * @var Exception
	 */
	public static ?\Exception $last_error = null;

	/**
	 * Like json_decode except use internal parser (slow)
	 *
	 * @param string $string
	 * @param boolean $assoc
	 * @return mixed
	 */
	public static function zesk_decode(string $string, bool $assoc = false): mixed {
		$string = trim($string);

		try {
			self::$last_error = null;
			[$length, $result] = self::_decode_value($string, 0, $assoc);
			if ($length === strlen($string)) {
				return $result;
			}
		} catch (\Exception $e) {
			// Not worthy of a hook here, methinks - KMD 2018-02
			// Kernel::singleton()->hooks->call("exception", $e);
			self::$last_error = $e;
		}
		return null;
	}

	/**
	 * Decode JSON string starting with white space, then a value
	 *
	 * @param string $string
	 *            JSON string optionally beginning with whitespace which will be trimmed
	 * @param int $offset
	 *            Current offset in the total string - for debugging only
	 * @param boolean $assoc
	 *            Whether arrays should be created as associative arrays, or objects
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_white_value(string $string, int $offset, bool $assoc = false): array {
		if (!preg_match("/^\s+/", $string, $match)) {
			return self::_decode_value($string, $offset);
		}
		$white = strlen($match[0]);
		[$length, $result] = self::_decode_value(substr($string, $white), $offset + $white, $assoc);
		return [
			$length + $white,
			$result,
		];
	}

	/**
	 * This is somewhat of a pain in the ass to implement and maintain, so do the basics here, but
	 * assume PHP 5.2 and json will
	 * be core of PHP in the near future.
	 *
	 * @param string $string
	 * @param int $offset
	 *            Current offset in the total string - for debugging only
	 * @param boolean $assoc
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_value(string $string, int $offset, bool $assoc = false): array {
		static $begins = [
			'null' => null,
			'true' => true,
			'false' => false,
		];
		if ($string === '') {
			throw new Exception_Parse('Invalid empty JSON string at offset {offset}', [
				'offset' => $offset,
			]);
		}
		foreach ($begins as $match => $value) {
			if (begins($string, $match)) {
				return [
					strlen($match),
					$value,
				];
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

		throw new Exception_Parse('Invalid JSON token "{char}" at position {offset}', [
			'char' => $c,
			'offset' => $offset,
		]);
	}

	/**
	 * JSON parse number
	 *
	 * @param string $string
	 * @param int $offset
	 *            Current offset in the total string - for debugging only
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_number(string $string, int $offset): array {
		$match = null;
		if (preg_match('/-?[0-9]+(\.[0-9]+([eE][-+]?[0-9]+)?)?/', $string, $match)) {
			if (preg_match('/^-?[0-9]+$/', $match[0])) {
				return [
					strlen($match[0]),
					intval($match[0]),
				];
			}
			return [
				strlen($match[0]),
				floatval($match[0]),
			];
		}

		throw new Exception_Parse('Invalid JSON numeric token "{string}..." at position {offset}', [
			'string' => substr($string, 16),
			'offset' => $offset,
		]);
	}

	/**
	 * JSON decode array
	 *
	 * @param string $string
	 * @param int $offset Current offset in the total string - for debugging only
	 * @param bool $assoc
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_array(string $string, int $offset, bool $assoc): array {
		$len = strlen($string);
		$result = [];
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if (str_contains(" \r\n\t", $c)) {
				$i++;

				continue;
			}
			if ($c === ']') {
				return [
					$i + 1,
					$result,
				];
			}
			[$item_len, $item] = self::_decode_value(substr($string, $i), $offset + $i, $assoc);
			$i += $item_len;
			$result[] = $item;
			while ($i < $len) {
				$c = $string[$i];
				if (str_contains(" \r\n\t", $c)) {
					$i++;

					continue;
				}
				if ($c === ']') {
					return [
						$i + 1,
						$result,
					];
				}
				if ($c === ',') {
					$i++;

					break;
				}

				throw new Exception_Parse('Invalid JSON array at offset {offset} expecting comma or end bracket', [
					'offset' => $offset + $i,
				]);
			}
		}

		throw new Exception_Parse('Unterminated array structure at offset {offset}', [
			'offset' => $offset,
		]);
	}

	/**
	 * JSON decode object
	 *
	 * @param bool $assoc
	 * @param string $string
	 * @param int $offset
	 *            Current offset in the total string - for debugging only
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_object(string $string, int $offset, bool $assoc): array {
		$len = strlen($string);
		$result = [];
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if (str_contains(" \r\n\t", $c)) {
				$i++;

				continue;
			}
			if ($c === '}') {
				return [
					$i + 1,
					$assoc ? $result : (object) $result,
				];
			}
			if ($c !== '"') {
				throw new Exception_Parse('Invalid object key at offset {offset}', [
					'offset' => $offset + $i,
				]);
			}
			[$key_len, $key] = self::_decode_string(substr($string, $i), $offset);
			$i += $key_len - 1;
			do {
				++$i;
				if ($i > $len) {
					throw new Exception_Parse('Unterminated object value looking for : at offset {offset}', [
						'offset' => $offset + $i,
					]);
				}
				$c = $string[$i];
			} while (str_contains(" \r\n\t", $c));
			if ($c !== ':') {
				throw new Exception_Parse('Missing : value at offset {offset}', [
					'offset' => $offset + $i,
				]);
			}
			++$i;
			[$value_len, $value] = self::_decode_white_value(substr($string, $i), $offset + $i, $assoc);
			//echo "Parsed $value_len bytes: " . substr(substr($string, $i), 0, $value_len) . "\n";
			$i += $value_len;
			$result[$key] = $value;
			while ($i < $len) {
				$c = $string[$i];
				if (str_contains(" \r\n\t", $c)) {
					$i++;

					continue;
				}
				if ($c === '}') {
					return [
						$i + 1,
						$result,
					];
				}
				if ($c === ',') {
					$i++;

					break;
				}

				throw new Exception_Parse('Invalid JSON object at offset {offset} expecting comma or end bracket, found "{char}"', [
					'char' => $c,
					'offset' => $offset + $i,
				]);
			}
		}

		throw new Exception_Parse('Unterminated array structure at offset {offset}', [
			'offset' => $offset,
		]);
	}

	/**
	 * JSON decode string
	 *
	 * @param string $string
	 * @param int $offset
	 *            Current offset in the total string - for debugging only
	 * @return array
	 * @throws Exception_Parse
	 */
	private static function _decode_string(string $string, int $offset): array {
		static $string_characters = [
			'"' => '"',
			'\\' => '\\',
			'/' => '/',
			'b' => "\x08",
			'f' => "\f",
			'n' => "\n",
			'r' => "\r",
			't' => "\t",
		];

		$len = strlen($string);
		$result = '';
		$i = 1;
		while ($i < $len) {
			$c = $string[$i];
			if ($c === '"') {
				return [
					$i + 1,
					$result,
				];
			}
			$i++;
			if ($c !== '\\') {
				$result .= $c;

				continue;
			}

			if ($i >= $len) {
				throw new Exception_Parse('Unterminated string at offset {offset}', [
					'offset' => $offset + $i,
				]);
			}
			$c = $string[$i];
			if (array_key_exists($c, $string_characters)) {
				$result .= $string_characters[$c];
			} elseif ($c === 'u') {
				++$i;
				if ($i + 4 >= $len) {
					throw new Exception_Parse('Invalid unterminated hex code at string {offset}', [
						'offset' => $offset + $i,
					]);
				}
				$result .= Hexadecimal::decode(substr($string, $i, $i + 3));
				$i += 4;
			} else {
				throw new Exception_Parse('Invalid escape sequence {char} at offset {offset}', [
					'char' => $c,
					'offset' => $offset + $i,
				]);
			}
		}

		throw new Exception_Parse('Invalid unterminated string at end of JSON string {offset}', [
			'offset' => $offset + $i,
		]);
	}
}
