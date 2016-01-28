<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/js.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * Javascript tools
 *
 * @author kent
 *
 */
class js {
	static $obfuscated = false;

	public static function arguments() {
		$args = func_get_args();
		$json = array();
		foreach ($args as $arg) {
			$json[] = json::encode($arg);
		}
		return implode(", ", $json);
	}
	public static function obfuscate_begin() {
		if (self::$obfuscated) {
			throw new Exception_Semantics("Already called obfuscate_begin");
		}
		self::$obfuscated = true;
		ob_start();
	}

	public static function obfuscate_end($function_map = array()) {
		if (!self::$obfuscated) {
			throw new Exception_Semantics("Need to call obfuscate_begin first");
		}
		self::$obfuscated = false;
		if (!is_array($function_map)) {
			$function_map = array();
		}
		$formatting = array(
			"\t" => " ",
			"\n" => "",
			"  " => " ",
			", " => ",",
			" {" => "{",
			"{ " => "{",
			" =" => "=",
			"= " => "=",
			"{ " => "{",
			" {" => "{",
			"; " => ";",
			"+ " => "+",
			" +" => "+",
			"} " => "}",
			"if (" => "if(",
			") {" => "){",
			" if(" => "if(",
			"elseif" => "else if"
		);
		$js = ob_get_clean();
		$formatting = array_merge($formatting, $function_map);
		return str_replace(array_keys($formatting), array_values($formatting), $js);
	}

	public static function clean_function_name($x) {
		if (!is_string($x)) {
			return null;
		}
		$x = preg_replace('/[^A-Za-z0-9_]/', '', $x);
		return $x;
	}

	public static function null($x) {
		if (empty($x)) {
			return "null";
		}
		return $x;
	}

	public static function clean_code($mixed) {
		if (is_array($mixed)) {
			$mixed = implode(";\n\t", arr::unsuffix(arr::trim_clean($mixed, " ", ""), ";"));
		}
		return $mixed;
	}
	public static function string($x) {
		$x = str_replace("'", "\\'", $x);
		$x = str_replace("\n", "\\n' +\n'", $x);
		return "'$x'";
	}
}
