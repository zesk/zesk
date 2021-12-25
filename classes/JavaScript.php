<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Javascript tools
 *
 * @author kent
 */
class JavaScript {
	/**
	 * Current obfuscation capture state
	 *
	 * @var boolean
	 */
	private static $obfuscated = false;

	/**
	 * Convert passed arguments into a JavaScript argument list.
	 *
	 * Arguments are any valid PHP type which can be converted to JSON, using JSON::encodex to support *-prefix key values which are passed through unmodified.
	 *
	 * @return string commas-separated list of arguments
	 */
	public static function arguments() {
		$args = func_get_args();
		$json = [];
		foreach ($args as $arg) {
			$json[] = JSON::encodex($arg);
		}
		return implode(", ", $json);
	}

	/**
	 * Begin JavaScript obfuscation output capture
	 *
	 * Depends on output buffering
	 * @throws Exception_Semantics
	 */
	public static function obfuscate_begin(): void {
		if (self::$obfuscated) {
			throw new Exception_Semantics("Already called obfuscate_begin");
		}
		self::$obfuscated = true;
		ob_start();
	}

	/**
	 * End JavaScript obfuscation output and return obfuscated JavaScript
	 *
	 * @param array $function_map Apply string mapping at end
	 * @return string
	 * @throws Exception_Semantics
	 */
	public static function obfuscate_end($function_map = []) {
		if (!self::$obfuscated) {
			throw new Exception_Semantics("Need to call obfuscate_begin first");
		}
		self::$obfuscated = false;
		if (!is_array($function_map)) {
			$function_map = [];
		}
		$formatting = [
			"\t" => " ",
			"\n" => "",
			"  " => " ",
			", " => ",",
			" {" => "{",
			"{ " => "{",
			" =" => "=",
			"= " => "=",
			"; " => ";",
			"+ " => "+",
			" +" => "+",
			"} " => "}",
			"if (" => "if(",
			") {" => "){",
			" if(" => "if(",
			"elseif" => "else if",
		];
		$js = ob_get_clean();
		$formatting = array_merge($formatting, $function_map);
		return str_replace(array_keys($formatting), array_values($formatting), $js);
	}

	/**
	 * Clean a JavaScript function name
	 *
	 * @param string $x function name to clean
	 * @return string
	 */
	public static function clean_function_name($x) {
		if (!is_string($x)) {
			return null;
		}
		$x = preg_replace('/[^A-Za-z0-9_]/', '', $x);
		return $x;
	}

	/**
	 * Return JavaScript null token for empty values
	 *
	 * @param mixed $x value to display
	 * @return string
	 */
	public static function null($x) {
		if (empty($x)) {
			return "null";
		}
		return $x;
	}

	/**
	 * Clean an array of JavaScript code; ensure each line ends with a semicolon, remove
	 * empty values, and trim each line
	 * @param string $mixed Clean code
	 * @return string
	 */
	public static function clean_code($mixed) {
		if (is_array($mixed)) {
			$mixed = implode(";\n", ArrayTools::unsuffix(ArrayTools::trim_clean($mixed, " ", ""), ";"));
		}
		return $mixed;
	}

	/**
	 * Convert string to JavaScript string and return single-quoted string
	 *
	 * @param $x string to quote
	 * @return string Quoted and properly escaped JavaScript string
	 */
	public static function string($x) {
		$x = str_replace("'", "\\'", $x);
		$x = str_replace("\n", "\\n' +\n'", $x);
		return "'$x'";
	}
}
