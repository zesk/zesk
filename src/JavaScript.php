<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Javascript tools
 *
 * @author kent
 */
class JavaScript
{
	/**
	 * Current obfuscation capture state
	 *
	 * @var boolean
	 */
	private static bool $obfuscated = false;

	/**
	 * Convert passed arguments into a JavaScript argument list.
	 *
	 * Arguments are any valid PHP type which can be converted to JSON, using JSON::encodex to support *-prefix key values which are passed through unmodified.
	 *
	 * @return string commas-separated list of arguments
	 */
	public static function arguments(): string
	{
		$args = func_get_args();
		$json = [];
		foreach ($args as $arg) {
			$json[] = JSON::encodeJavaScript($arg);
		}
		return implode(', ', $json);
	}

	/**
	 * JavaScript obfuscation
	 */
	public static function obfuscate(string $content, array $functionMap = []): string
	{
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
		$formatting = array_merge($formatting, $functionMap);
		return str_replace(array_keys($formatting), array_values($formatting), $content);
	}

	/**
	 * Clean a JavaScript function name
	 *
	 * @param string $name function name to clean
	 * @return string
	 */
	public static function cleanFunctionName(string $name): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '', $name);
	}

	/**
	 * Return JavaScript null token for empty values
	 *
	 * @param mixed $x value to display
	 * @return string
	 */
	public static function null(mixed $x): string
	{
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
	public static function clean_code(array $javascript): string
	{
		return implode(";\n", ArrayTools::valuesRemoveSuffix(ArrayTools::listTrimClean($javascript, ' ', ['']), ';'));
	}

	/**
	 * Convert string to JavaScript string and return single-quoted string
	 *
	 * @param $x string to quote
	 * @return string Quoted and properly escaped JavaScript string
	 */
	public static function string(string $x): string
	{
		$x = str_replace('\'', '\\\'', $x);
		$x = str_replace("\n", "\\n' +\n'", $x);
		return "'$x'";
	}
}
