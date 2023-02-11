<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
			$json[] = JSON::encodeJavaScript($arg);
		}
		return implode(', ', $json);
	}

	/**
	 * Begin JavaScript obfuscation output capture
	 *
	 * Depends on output buffering
	 * @throws Exception_Semantics
	 */
	public static function obfuscate_begin(): void {
		if (self::$obfuscated) {
			throw new Exception_Semantics('Already called obfuscate_begin');
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
	public static function obfuscate_end(array $function_map = []) {
		if (!self::$obfuscated) {
			throw new Exception_Semantics('Need to call obfuscate_begin first');
		}
		self::$obfuscated = false;
		$formatting = [
			"\t" => ' ',
			"\n" => '',
			'  ' => ' ',
			', ' => ',',
			' {' => '{',
			'{ ' => '{',
			' =' => '=',
			'= ' => '=',
			'; ' => ';',
			'+ ' => '+',
			' +' => '+',
			'} ' => '}',
			'if (' => 'if(',
			') {' => '){',
			' if(' => 'if(',
			'elseif' => 'else if',
		];
		$js = ob_get_clean();
		$formatting = array_merge($formatting, $function_map);
		return str_replace(array_keys($formatting), array_values($formatting), $js);
	}

	/**
	 * Clean a JavaScript function name
	 *
	 * @param string $name function name to clean
	 * @return string
	 */
	public static function clean_function_name(string $name) {
		$name = preg_replace('/[^A-Za-z0-9_]/', '', $name);
		return $name;
	}

	/**
	 * Return JavaScript null token for empty values
	 *
	 * @param mixed $x value to display
	 * @return string
	 */
	public static function null(mixed $x): string {
		if (empty($x)) {
			return 'null';
		}
		return strval($x);
	}

	/**
	 * Clean an array of JavaScript code; ensure each line ends with a semicolon, remove
	 * empty values, and trim each line
	 *
	 * @param array $javascript
	 * @return string
	 */
	public static function clean_code(array $javascript): string {
		return implode(";\n", ArrayTools::valuesRemoveSuffix(ArrayTools::listTrimClean($javascript, ' ', ''), ';'));
	}

	/**
	 * Convert string to JavaScript string and return single-quoted string
	 *
	 * @param $x string to quote
	 * @return string Quoted and properly escaped JavaScript string
	 */
	public static function string(string $x): string {
		$x = str_replace('\'', '\\\'', $x);
		$x = str_replace("\n", "\\n' +\n'", $x);
		return "'$x'";
	}
}
