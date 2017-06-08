<?php
/**
 * Things that should probably just be in PHP, or were added after we added these. Review
 * annually to see if we can deprecate functionality.
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/kernel.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
use zesk\Kernel;
use zesk\Text;
use zesk\PHP;
use zesk\Debug;
use zesk\JSON;
use zesk\Locale;
use zesk\Application;
use zesk\Configuration;
use zesk\arr;
use zesk\Hookable;
use zesk\Object_Iterator;

/**
 * A regular expression pattern for matching email addresses anywhere (should delimit both ends in
 * your own expression).
 * Undelimited pattern.
 *
 * @var string
 * @see preg_match
 */
define("PREG_PATTERN_EMAIL", '[-`~#$%&*\'a-zA-Z0-9_\.+=]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,}');

/**
 * A regular expression pattern for matching email addresses.
 * Delimited pattern, use for trimmed complete string.
 *
 * @var string
 * @see preg_match
 */
define("PREG_PATTERN_EMAIL_STRING", '/^' . PREG_PATTERN_EMAIL . '$/');

/**
 * Key used to seaparate paths in the globals array
 */
define("ZESK_GLOBAL_KEY_SEPARATOR", "::");

/**
 * Get our global Zesk kernel.
 * Avoids having global $zesk overwritten.
 *
 * @return Kernel
 */
function zesk() {
	return Kernel::zesk();
}

/**
 * Does NOT assume array is a 0-based key list
 *
 * @param array $a        	
 * @return NULL|mixed
 */
function first(array $a, $default = null) {
	return count($a) !== 0 ? $a[key($a)] : $default;
}

/**
 * Does NOT assume array is a 0-based key list
 *
 * @param array $a        	
 * @return NULL|mixed
 */
function last(array $a, $default = null) {
	if (($n = count($a)) === 0) {
		return $default;
	}
	if (isset($a[$n - 1])) {
		return $a[$n - 1];
	}
	return $a[last(array_keys($a))];
}

/**
 * Use in lieu of ?? in PHP before PHP 7
 *
 * e.g.
 *
 * return firstarg($value, "default");
 *
 * @return mixed|NULL
 * @see https://wiki.php.net/rfc/isset_ternary
 */
function firstarg() {
	$args = func_get_args();
	foreach ($args as $arg) {
		if (!empty($arg)) {
			return $arg;
		}
	}
	return null;
}
/**
 * Return a sane type for a variable
 *
 * @param mixed $mixed        	
 * @return string
 */
function type($mixed) {
	return is_object($mixed) ? get_class($mixed) : gettype($mixed);
}

/**
 * Does string begin with another string?
 *
 * @param string $string        	
 * @param string $prefix        	
 * @return boolean
 * @see \zesk\str::begins
 */
function begins($haystack, $needle) {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return substr($haystack, 0, $n) === $needle ? true : false;
}

/**
 * Does string begin with another string (case-insensitive)?
 *
 * @param string $string        	
 * @param string $prefix        	
 * @return boolean
 * @see \zesk\str::beginsi
 */
function beginsi($haystack, $needle) {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return strcasecmp(substr($haystack, 0, $n), $needle) === 0 ? true : false;
}

/**
 * Does string end with another string?
 *
 * @param string $string        	
 * @param string $prefix        	
 * @return boolean
 * @see \zesk\str::ends
 */
function ends($haystack, $needle) {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return (substr($haystack, -$n) === $needle) ? true : false;
}

/**
 * Does string end with another string (insensitive)?
 *
 * @param string $string        	
 * @param string $prefix        	
 * @return boolean
 * @see \zesk\str::endsi
 */
function endsi($haystack, $needle) {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return strcasecmp(substr($haystack, -$n), $needle) === 0 ? true : false;
}

/**
 * Set or get the newline character.
 *
 * @param string $set        	
 * @return string
 */
function newline($set = null) {
	if ($set !== null) {
		zesk()->newline = $set;
		return $set;
	}
	return zesk()->newline;
}

/**
 * Return a backtrace of the stack
 *
 * @param int $n
 *        	The number of frames to output. Pass a negative number to pass all frames.
 */
function _backtrace($n = -1) {
	$bt = debug_backtrace();
	array_shift($bt);
	if ($n <= 0) {
		$n = count($bt);
	}
	$result = array();
	foreach ($bt as $i) {
		$file = "closure";
		$line = "-none-";
		$class = $type = $function = $args = null;
		extract($i, EXTR_IF_EXISTS);
		$line = "$file: $line $class$type$function";
		if (is_array($args)) {
			$arg_dump = array();
			foreach ($args as $index => $arg) {
				if (is_object($arg)) {
					$arg_dump[$index] = get_class($arg);
				} else if (is_scalar($arg)) {
					$arg_dump[$index] = PHP::dump($arg);
				} else {
					$arg_dump[$index] = type($arg);
				}
			}
			if (count($arg_dump)) {
				$line .= "(" . implode(", ", $arg_dump) . ")";
			}
		}
		$result[] = $line;
		if (--$n <= 0) {
			break;
		}
	}
	return implode("\n", $result);
}

/**
 * Output a backtrace of the stack
 *
 * @param boolean $doExit
 *        	Exit the program
 * @param int $n
 *        	The number of frames to output
 */
function backtrace($exit = true, $n = -1) {
	echo _backtrace($n);
	if ($exit) {
		exit($exit);
	}
}

/**
 * Returns the name of the function or class/method which called the current code.
 * Useful for debugging.
 *
 * Moved from Debug:: class to assist in profiling bootstrap functions (for example)
 * which don't have the autoloader set yet.
 *
 * @return string
 * @param unknown $depth        	
 * @see debug_backtrace()
 * @see Debug::calling_function
 */
function calling_function($depth = 1, $include_line = true) {
	$bt = debug_backtrace();
	array_shift($bt); // Remove this function from the stack
	if ($depth > 0) {
		while ($depth-- !== 0) {
			array_shift($bt);
		}
	}
	$top = array_shift($bt);
	if (!$top) {
		return '-no calling function $depth deep-';
	}
	return avalue($top, "file") . " " . avalue($top, "class") . avalue($top, "type") . $top["function"] . ($include_line ? ':' . avalue($top, 'line') : '');
}

/**
 * Dumps a variable using print_r and surrounds with <pre> tag
 * Optionally defined because "dump" is also defined by Drush
 *
 * Probably should switch to a namespace version of this as well.
 *
 * @param mixed $x
 *        	Variable to dump
 * @param boolean $html
 *        	Whether to dump as HTML or not (surround by pre tags)
 * @return echos to page
 * @see print_r
 */
if (!function_exists("dump")) {
	function dump() {
		call_user_func_array('zesk\Debug::output', func_get_args());
	}
}

/**
 * Returns what "dump" would print (doesn't echo)
 *
 * @param mixed $x
 *        	Variable to dump
 * @return A string representation of the value
 * @see print_r, dump
 */
function _dump($x) {
	return Debug::dump($x);
}

/**
 * Another sane object type output
 *
 * @param unknown $x        	
 * @return string
 */
function vartype($x) {
	$t = gettype($x);
	if ($t === "object") {
		$t .= ":" . get_class($x);
	}
	return $t;
}

/**
 * Flushes all of the output buffers and pops any buffers off of the stack.
 * Generally useful if you are going to die on the page and want to dump something and ensure it's
 * visible.
 * This can mess up any output buffering being done without your knowledge, so in general, useful
 * for just debugging.
 *
 * @return void
 * @see ob_end_flush, ob_get_level
 */
function ob_flush_all() {
	while (ob_get_level() > 0) {
		ob_end_flush();
	}
}

/**
 * Parses $value for a boolean value.
 * Intended for parsing developer or user inputs which include the
 * values:
 * <ul>
 * <li>True, False, T, F</li>
 * <li>0,1</li>
 * <li>Yes, No, Y, N</li>
 * <li><em>empty string</em></li>
 * <li>Enabled, Disabled</li>
 * </ul>
 *
 * @param mixed $value
 *        	A value to parse to find a boolean value.
 * @param mixed $default
 *        	A value to return if parsing is unsuccessful
 * @return mixed Returns true or false, or $default if parsing fails.
 */
function to_bool($value, $default = false) {
	if (is_bool($value)) {
		return $value;
	}
	if (!is_scalar($value)) {
		return $default;
	}
	$value = strtolower($value);
	$find = ";$value;";
	if (strpos(";1;t;y;yes;on;enabled;true;", $find) !== false) {
		return true;
	}
	if (strpos(";0;f;n;no;off;disabled;false;null;;", $find) !== false) {
		return false;
	}
	return $default;
}

/**
 * Ensures a value is an integer value.
 * If not, the default value is returned.
 *
 * @param mixed $s
 *        	Value to convert to integer
 * @param mixed $def
 *        	The default value. Not converted to integer.
 * @return mixed The integer value, or $def if it can not be converted to an integer
 */
function to_integer($s, $def = null) {
	return is_numeric($s) ? intval($s) : $def;
}

/**
 * Ensures a value is an double value.
 * If not, the default value is returned.
 *
 * @param mixed $s
 *        	Value to convert to double
 * @param mixed $def
 *        	The default value. Not converted to double.
 * @return mixed The double value, or $def if it can not be converted to an integer
 */
function to_double($s, $def = null) {
	return is_numeric($s) ? doubleval($s) : $def;
}

/**
 * Converts a string to a list via explode.
 * If it's already an array, return it. Otherwise, return the default.
 *
 * @param mixed $mixed
 *        	Array or string to convert to a "list"
 * @param mixed $default
 *        	Value to return if not a string or array
 * @param string $delimiter
 *        	String list delimiter (";" is default)
 * @return array or $default
 */
function to_list($mixed, $default = array(), $delimiter = ";") {
	if (is_scalar($mixed)) {
		return explode($delimiter, strval($mixed));
	} else if (is_array($mixed)) {
		return $mixed;
	} else if (is_object($mixed) && method_exists($mixed, "to_list")) {
		return to_list($mixed->to_list());
	} else {
		return $default;
	}
}

/**
 * Converts a scalar to an array.
 * Returns default for values of null or false.
 *
 * @param mixed $mixed
 *        	If false or null, returns default value
 * @param mixed $default
 *        	Default value to return if can't easily convert to an array.
 * @return array
 */
function to_array($mixed, $default = array()) {
	if (is_array($mixed)) {
		return $mixed;
	}
	if (is_scalar($mixed) && $mixed !== false) {
		return array(
			$mixed
		);
	}
	if (is_object($mixed) && method_exists($mixed, "to_array")) {
		return $mixed->to_array();
	}
	return $default;
}

/**
 * Converts a PHP value to a string, usually for debugging.
 *
 * @param mixed $mixed        	
 * @return string
 */
function to_text($mixed) {
	if (is_bool($mixed)) {
		return $mixed ? 'true' : 'false';
	}
	if ($mixed === null) {
		return 'null';
	}
	if (is_array($mixed)) {
		return Text::format_pairs($mixed);
	}
	return strval($mixed);
}

/**
 * Converts an object into an iterator, suitable for a foreach
 *
 * @param mixed $mixed        	
 * @return array|Iterator
 */
function to_iterator($mixed) {
	if (is_array($mixed)) {
		return $mixed;
	}
	if ($mixed instanceof Iterator) {
		return $mixed;
	}
	if (empty($mixed)) {
		return array();
	}
	return array(
		$mixed
	);
}

/**
 * Converts 20G to integer value
 *
 * @param unknown $mixed        	
 * @param string $default        	
 * @return Ambigous <mixed, number>|number
 */
function to_bytes($mixed, $default = null) {
	$mixed = strtolower(trim($mixed));
	if (is_numeric($mixed)) {
		return intval($mixed);
	}
	if (!preg_match('/[0-9]+([gmk])/', $mixed, $matches)) {
		return to_integer($mixed, $default);
	}
	$b = intval($mixed);
	switch ($matches[1]) {
		case 'g':
			$b *= 1024;
		// Fall through
		case 'm':
			$b *= 1024;
		// Fall through
		case 'k':
			$b *= 1024;
	}
	return $b;
}

/**
 * Localize a string to the current locale.
 * You can use it by passing numeric parameters after the localized string, like:
 *
 * $result = __("Don't forget to {0} the {1}.", "feed", "bears");
 *
 * Or by passing an associative array as the first parameter, like:
 *
 * $result = __("Don't forget to {action} the {noun}.", array("action" => "feed", "noun" =>
 * "bears"));
 *
 * Uses the currently set locale. If you want to mix and match locales, use Locale::translate.
 *
 * @param string $phrase
 *        	Phrase to translate
 * @return string
 * @see Locale::translate
 */
function __($phrase) {
	$args = func_get_args();
	$phrase = Locale::translate($phrase);
	if (count($args) > 1) {
		array_shift($args);
		if (count($args) === 1 && is_array($args[0])) {
			$phrase = map($phrase, $args[0]);
		} else {
			$phrase = map($phrase, $args);
		}
	}
	return $phrase;
}

if (!function_exists('debugger_start_debug')) {
	function debugger_start_debug() {
	}
}

/**
 * Shorthand for array_key_exists($k,$a) ? $a[$k] : $default.
 * Asserts $a is an array, $k is a string or numeric.
 *
 * NOTE: In PHP 7 this will go away and we can use
 *
 * $foo = $a['key'] ?? $default;
 *
 * FINALLY.
 *
 * @param array $a
 *        	An array to look in
 * @param string $k
 *        	The key to look for
 * @param mixed $default
 *        	A value to return if $a[$k] is not set
 * @return mixed The value of $a[$k], or $default if not set
 * @see https://wiki.php.net/rfc/isset_ternary
 */
function avalue($a, $k, $default = null) {
	if (!is_array($a)) {
		$message = "Array (" . strval($a) . ") is of type " . type($a) . " " . _backtrace();
		error_log($message, E_USER_WARNING);
		die(__FILE__ . ':' . __LINE__ . "<br />" . $message);
		debugger_start_debug();
	}
	$k = strval($k);
	return array_key_exists($k, $a) ? $a[$k] : $default;
}

/**
 * Shorthand for array_key_exists($k,$a) || !empty($a[$k]) ? $a[$k] : $default.
 * Asserts $a is an array.
 *
 * @param array $a
 *        	An array to look in
 * @param string $k
 *        	The key to look for
 * @param mixed $default
 *        	A value to return if $a[$k] is not set or is empty
 * @return mixed The value of $a[$k] if non-empty, or $default if not set or empty
 */
function aevalue($a, $k, $default = null) {
	$k = strval($k);
	return array_key_exists($k, $a) && !empty($a[$k]) ? $a[$k] : $default;
}

/**
 * Convert a deep object into a flat one (string)
 *
 * @param mixed $mixed        	
 * @return string
 */
function flatten($mixed) {
	if (is_array($mixed)) {
		$mixed = arr::flatten($mixed);
	}
	if ($mixed === null) {
		return "";
	}
	if (is_object($mixed)) {
		return method_exists($mixed, '__toString') ? strval($mixed) : flatten(get_object_vars($mixed));
	}
	if (is_scalar($mixed)) {
		return $mixed;
	}
	return JSON::encode($mixed);
}

/**
 * Convert tokens in a string to other things.
 * Anything not a string or array is returned as-is.
 *
 * @param mixed $mixed
 *        	An array or string
 * @param array $map
 *        	Tokens to convert from/to
 * @return mixed Whatever passed in is returned (string/array)
 */
function tr($mixed, array $map) {
	if (is_array($mixed)) {
		foreach ($mixed as $k => $v) {
			$mixed[$k] = tr($v, $map);
		}
		return $mixed;
	} else if (is_string($mixed)) {
		$map = arr::flatten($map);
		return strtr($mixed, $map);
	} else if (is_object($mixed)) {
		return $mixed instanceof Hookable ? $mixed->call_hook_arguments('tr', array(
			$map
		), $mixed) : $mixed;
	} else {
		return $mixed;
	}
}

/**
 * preg_replace for arrays
 *
 * @param string $pattern
 *        	Pattern to match
 * @param string $replacement
 *        	Replacement string
 * @param mixed $subject
 *        	String or array to manipulate
 * @return mixed
 */
function preg_replace_mixed($pattern, $replacement, $subject) {
	if ($subject === null || is_bool($subject)) {
		return $subject;
	}
	if (is_array($subject)) {
		foreach ($subject as $k => $v) {
			$subject[$k] = preg_replace_mixed($pattern, $replacement, $v);
		}
		return $subject;
	}
	return preg_replace($pattern, $replacement, $subject);
}

/**
 * preg_replace_callback for arrays
 *
 * @param string $pattern
 *        	Pattern to match
 * @param string $callback
 *        	Replacement string
 * @param mixed $subject
 *        	String or array to manipulate
 * @return mixed
 */
function preg_replace_callback_mixed($pattern, $callback, $subject) {
	if ($subject === null) {
		return null;
	}
	if (is_array($subject)) {
		foreach ($subject as $k => $v) {
			$subject[$k] = preg_replace_callback_mixed($pattern, $callback, $v);
		}
		return $subject;
	}
	if (!is_scalar($subject)) {
		dump($subject);
		backtrace();
	}
	return preg_replace_callback($pattern, $callback, $subject);
}

/**
 * Map array keys and values
 *
 * @param array $target
 *        	Array to modify keys AND values
 * @param array $map
 *        	Array of name => value of search => replace
 * @param boolean $insensitive
 *        	Case sensitive search/replace (defaults to true)
 * @param string $prefix_char
 *        	Prefix character for tokens (defaults to "{")
 * @param string $suffix_char
 *        	Suffix character for tokens (defaults to "}")
 * @return array
 */
function amap(array $target, array $map, $insensitive = false, $prefix_char = "{", $suffix_char = "}") {
	return map(kmap($target, $map, $insensitive, $prefix_char, $suffix_char), $map, $insensitive, $prefix_char, $suffix_char);
}

/**
 * Map keys instead of values
 *
 * @param array $target
 *        	Array to modify keys
 * @param array $map
 *        	Array of name => value of search => replace
 * @param boolean $insensitive
 *        	Case sensitive search/replace (defaults to true)
 * @param string $prefix_char
 *        	Prefix character for tokens (defaults to "{")
 * @param string $suffix_char
 *        	Suffix character for tokens (defaults to "}")
 * @return array
 */
function kmap(array $target, array $map, $insensitive = false, $prefix_char = "{", $suffix_char = "}") {
	$new_mixed = array();
	foreach ($target as $key => $value) {
		$new_mixed[map($key, $map, $insensitive, $prefix_char, $suffix_char)] = $value;
	}
	return $new_mixed;
}

/**
 * Convert tokens in a string to other values.
 * 2015-06-13 - Changed $target is first parameter, map is 2nd (similar to __ and Logger::log) -
 * incompatible change
 * 2015-06-13 - Changed $case_sensitive -> $insensitive semantics (implying the opposite of
 * original). avoids accidentally modifying strings)
 *
 * Passing in "insensitive" to true will return a string which has unmatched tokens in lowercase.
 * So:
 *
 * @test_inline $this->assert_equal(map("{a}{B}", array("a" => "ala")), "ala{B}");
 * @test_inline $this->assert_equal(map("{a}{B}", array("a" => "ala"), true), "ala{b}");
 *
 * @param mixed $mixed
 *        	Target to modify
 * @param array $map
 *        	Array of name => value of search => replace
 * @param boolean $insensitive
 *        	Case sensitive search/replace (defaults to true)
 * @param string $prefix_char
 *        	Prefix character for tokens (defaults to "{")
 * @param string $suffix_char
 *        	Suffix character for tokens (defaults to "}")
 * @return mixed (string or array)
 */
function map($mixed, array $map, $insensitive = false, $prefix_char = "{", $suffix_char = "}") {
	if (!is_string($mixed) && !is_array($mixed)) {
		return $mixed;
	}
	if ($insensitive) {
		$map = array_change_key_case($map);
	}
	$s = array();
	foreach ($map as $k => $v) {
		if (is_array($v)) {
			if (arr::is_list($v)) {
				$v = implode(";", arr::flatten($v));
			} else {
				$v = JSON::encode($v);
			}
		} else if (is_object($v)) {
			if ($v instanceof Object_Iterator) {
				backtrace();
			}
			if ($v instanceof Configuration) {
				backtrace();
			}
			$v = strval($v);
		}
		$s[$prefix_char . $k . $suffix_char] = $v;
	}
	if ($insensitive) {
		static $func = null;
		if (!$func) {
			$func = create_function('$matches', 'return strtolower($matches[0]);');
		}
		$mixed = preg_replace_callback_mixed('/' . $prefix_char . '([-:_ =,.\/\'"A-Za-z0-9]+)' . $suffix_char . '/i', $func, $mixed);
	}
	// tr("{a}", array("{a} => null)) = "null"
	return tr($mixed, $s);
}

/**
 * Clean map tokens from a string
 *
 * @test_inline $this->assert_equal(map_clean("He wanted {n} days"), "He wanted days");
 * @test_inline $this->assert_equal(map_clean("{}{}{}{}{}{all}{of}{this}{is}{removed}except}"),
 * "except}");
 * 
 * @param mixed $mixed        	
 * @param string $prefix_char        	
 * @param string $suffix_char        	
 * @return mixed
 */
function map_clean($mixed, $prefix_char = "{", $suffix_char = "}") {
	$suff = preg_quote($suffix_char);
	return preg_replace_mixed('#' . preg_quote($prefix_char, '#') . '[^' . $suff . ']*' . $suff . '#', "", $mixed);
}

/**
 * Return true if string contains tokens which can be mapped using prefix/suffix
 * @param string $string
 * @return boolean
 */
function can_map($string, $prefix_char = "{", $suffix_char = "}") {
	$tokens = map_tokens($string, $prefix_char, $suffix_char);
	return count($tokens) !== 0;
}

/**
 * Retrieve map tokens from a string
 *
 * @param mixed $mixed        	
 * @param string $prefix_char        	
 * @param string $suffix_char        	
 * @return array
 */
function map_tokens($mixed, $prefix_char = "{", $suffix_char = "}") {
	$suff = preg_quote($suffix_char);
	$matches = array();
	if (!preg_match_all('#' . preg_quote($prefix_char, '#') . '[^' . $suff . ']*' . $suff . '#', $mixed, $matches)) {
		return array();
	}
	return $matches[0];
}

/**
 * Wrapping mapping function (_W)
 *
 * Mapping function which understands tags better. To apply styles or links certain elements within
 * a i18n phrase, use brackets
 * to delineate tags to add to the phrase, as follows:
 *
 * <pre>_W(__('This is [0:bold text] and this is [1:italic].'), '<strong>[]</strong>',
 * '<em>[italic]</em>') =
 * "This is <strong>bold text</strong> and this is <em>italic</em>."</pre>
 *
 * Supplying <strong>no</strong> positional information will replace values in order, e.g.
 *
 * <pre>_W(__('This is [bold text] and this is [italic].'), '<strong>[]</strong>',
 * '<em>[italic]</em>') =
 * "This is <strong>bold text</strong> and this is <em>italic</em>."</pre>
 *
 * Positional indicators are delimited with a number and a colon after the opening bracket. It also
 * handles nested brackets, however,
 * the inner brackets is indexed before the outer brackets, e.g.
 *
 * <pre>_W('[[a][b]]','<strong>[]</strong>','<em>[]</em>','<div>[]</div>') =
 * "<div><strong>a</strong><em>b</em></div>";
 *
 * @param string $phrase
 *        	Phrase to map
 * @return string The phrase with the links embedded.
 */
function _W($phrase) {
	$args = func_get_args();
	array_shift($args);
	if (count($args) === 1 && is_array($args[0])) {
		$args = $args[0];
	}
	$skip_s = array();
	$skip_r = array();
	$match = false;
	$global_match_index = 0;
	while (preg_match('/\[([0-9]+:)?([^\[\]]*)\]/', $phrase, $match, PREG_OFFSET_CAPTURE)) {
		$match_len = strlen($match[0][0]);
		$match_off = $match[0][1];
		$match_string = $match[2][0];
		$index = null;
		if ($match[1][1] < 0) {
			$index = $global_match_index;
		} else {
			$index = intval($match[1][0]);
		}
		$global_match_index++;
		$replace_value = avalue($args, $index, '[]');
		list($left, $right) = pair($replace_value, '[]');
		if ($left === null) {
			$replace_value = '(*' . count($skip_s) . '*)';
			$skip_s[] = $replace_value;
			$skip_r[] = $match[0][0];
		} else {
			$replace_value = $left . $match_string . $right;
		}
		$phrase = substr($phrase, 0, $match_off) . $replace_value . substr($phrase, $match_off + $match_len);
	}
	
	if (count($skip_s) === 0) {
		return $phrase;
	}
	return str_replace($skip_s, $skip_r, $phrase);
}

/**
 * Breaks a string in half at a given delimiter, and returns default values if delimiter is not
 * found.
 *
 * Usage is generally:
 *
 * list($table, $field) = pair($thing, ".", $default_table, $thing);
 *
 * @param string $a
 *        	A string to parse into a pair
 * @param string $delim
 *        	The delimiter to break the string apart
 * @param string $left
 *        	The default left value if delimiter is not found
 * @param string $right
 *        	The default right value if delimiter is not found
 * @return A size 2 array containing the left and right portions of the pair
 */
function pair($a, $delim = '.', $left = false, $right = false, $include_delimiter = null) {
	if (is_array($a)) {
		backtrace();
	}
	$n = strpos($a, $delim);
	$delim_len = strlen($delim);
	return ($n === false) ? array(
		$left,
		$right
	) : array(
		substr($a, 0, $n + ($include_delimiter === "left" ? $delim_len : 0)),
		substr($a, $n + ($include_delimiter === "right" ? 0 : $delim_len))
	);
}

/**
 * Same as pair, but does a reverse search for the delimiter
 *
 * @param string $a
 *        	A string to parse into a pair
 * @param string $delim
 *        	The delimiter to break the string apart
 * @param string $left
 *        	The default left value if delimiter is not found
 * @param string $right
 *        	The default right value if delimiter is not found
 * @return A size 2 array containing the left and right portions of the pair
 * @see pair
 */
function pairr($a, $delim = '.', $left = false, $right = false, $include_delimiter = null) {
	$n = strrpos($a, $delim);
	$delim_len = strlen($delim);
	return ($n === false) ? array(
		$left,
		$right
	) : array(
		substr($a, 0, $n + ($include_delimiter === "left" ? $delim_len : 0)),
		substr($a, $n + ($include_delimiter === "right" ? 0 : $delim_len))
	);
}

/**
 * Glue to strings together, ensuring there is one and only one character sequence between them
 *
 * Similar to path, but more useful to construct urls, e.g
 *
 * glue("http://localhost/test/","/","/foo") === "http://localhost/test/foo";
 * glue("http://localhost/test","/","/foo") === "http://localhost/test/foo";
 * glue("http://localhost/test/","/","foo") === "http://localhost/test/foo";
 * glue("http://localhost/test","/","foo") === "http://localhost/test/foo";
 *
 * @param string $left        	
 * @param string $glue        	
 * @param string $right        	
 * @return string
 */
function glue($left, $glue, $right) {
	return rtrim($left, $glue) . $glue . ltrim($right, $glue);
}

/**
 * Unquote a string and optionally return the quote removed.
 *
 * @param string $s
 *        	A string to unquote
 * @param string $quotes
 *        	A list of quote pairs to unquote
 * @param string $left_quote
 *        	Returns the quotes removed
 * @return string Unquoted string, or same string if quotes not found
 */
function unquote($s, $quotes = "''\"\"", &$left_quote = null) {
	if (is_array($s)) {
		$result = array();
		foreach ($s as $k => $ss) {
			$result[$k] = unquote($ss, $quotes, $left_quote);
		}
		return $result;
	}
	if (strlen($s) < 2) {
		$left_quote = false;
		return $s;
	}
	$q = substr($s, 0, 1);
	$qleft = strpos($quotes, $q);
	if ($qleft === false) {
		$left_quote = false;
		return $s;
	}
	$qright = $quotes[$qleft + 1];
	if (substr($s, -1) === $qright) {
		$left_quote = $quotes[$qleft];
		return substr($s, 1, -1);
	}
	return $s;
}

/**
 * Generic function to create paths correctly
 *
 * @param
 *        	string separator Token used to divide path
 * @param
 *        	array mixed List of path items, or array of path items to concatenate
 * @return string with a properly formatted path
 */
function path_from_array($separator = '/', array $mixed) {
	$r = array_shift($mixed);
	if (is_array($r)) {
		$r = path_from_array($separator, $r);
	}
	foreach ($mixed as $p) {
		if ($p === null) {
			continue;
		}
		if (is_array($p)) {
			$p = path_from_array($separator, $p);
		}
		$r .= ((substr($r, -1) === $separator || substr($p, 0, 1) === $separator)) ? $p : $separator . $p;
	}
	$separatorq = preg_quote($separator);
	$r = preg_replace("|$separatorq$separatorq+|", $separator, $r);
	return $r;
}

/**
 * Create a file path and ensure only one slash appears between path entries
 *
 * @param
 *        	mixed path Variable list of path items, or array of path items to concatenate
 * @return string with a properly formatted path
 */
function path(/* dir, dir, ... */) {
	$args = func_get_args();
	$r = path_from_array('/', $args);
	$r = preg_replace('|(/\.)+/|', "/", $r); // TODO Test this doesn't munge foo/.bar
	return $r;
}

/**
 * Create a domain name ensure one and only one dot appears between entries
 *
 * @param
 *        	mixed path Variable list of path items, or array of path items to concatenate
 * @return string with a properly formatted domain path
 */
function domain(/* name, name, ... */) {
	$args = func_get_args();
	$r = trim(path_from_array('.', $args), '.');
	return $r;
}

/**
 * Clamps a numeric value to a minimum and maximum value.
 *
 * @return mixed
 * @param mixed $minValue
 *        	The minimum value in the clamp range
 * @param mixed $value
 *        	A scalar value which serves as the value to clamp
 * @param mixed $maxValue
 *        	A scalar value which serves as the value to clamp
 */
function clamp($minValue, $value, $maxValue) {
	if ($value < $minValue) {
		return $minValue;
	}
	if ($value > $maxValue) {
		return $maxValue;
	}
	return $value;
}

/**
 * Utility for comparing floating point numbers where inaccuracies and rounding in math
 * produces close numbers which are not actually equal.
 *
 * @param real $a        	
 * @param real $b        	
 * @param real $epsilon        	
 * @return boolean
 */
function real_equal($a, $b, $epsilon = 1e-5) {
	return abs($a - $b) <= $epsilon;
}

/**
 * Can I do foreach on this object?
 *
 * @param mixed $mixed        	
 * @return boolean
 */
function can_iterate($mixed) {
	return is_array($mixed) || $mixed instanceof Traversable;
}
/**
 * Is this value close (enough) to zero? Handles rounding errors with double-precision values.
 *
 * @param double $value        	
 * @param real $epsilon        	
 * @return boolean
 */
function is_zero($value, $epsilon = 1e-5) {
	return abs($value) < $epsilon;
}

/**
 * Simple integer comparison routine, syntactic sugar
 *
 * @param integer $min        	
 * @param integer $x        	
 * @param integer $max        	
 * @return boolean
 */
function integer_between($min, $x, $max) {
	if (!is_numeric($x)) {
		return false;
	}
	return ($x >= $min) && ($x <= $max);
}

/**
 * Get the date in the UTC locale
 *
 * @param string $ts        	
 * @see getdate
 * @return array
 */
function utc_getdate($ts) {
	$otz = date_default_timezone_get();
	date_default_timezone_set("UTC");
	$result = getdate($ts);
	date_default_timezone_set($otz);
	return $result;
}

/**
 * Parse a time in UTC locale
 *
 * @param string $ts        	
 * @return integer number, or null if can not parse
 */
function utc_parse_time($ts) {
	$otz = date_default_timezone_get();
	date_default_timezone_set("UTC");
	$result = parse_time($ts);
	date_default_timezone_set($otz);
	return $result;
}

/**
 * Parse a time in the current locale
 *
 * @param string $ts        	
 * @return integer number, or null if can not parse
 */
function parse_time($ts) {
	if (empty($ts)) {
		return null;
	}
	$x = @strtotime($ts);
	if ($x < 0 || $x === false) {
		return null;
	}
	return $x;
}

/**
 * Determine if a string is a properly formatted date
 *
 * @param string $x
 *        	A string to check
 * @return boolean true if $x is a valid date
 */
function is_date($x) {
	if (empty($x)) {
		return false;
	}
	$result = @strtotime($x);
	if ($result < 0 || $result === false) {
		return false;
	}
	return true;
}

/**
 * Determine if a string is a possible email address
 *
 * @param string $email        	
 * @return boolean
 */
function is_email($email) {
	return (preg_match(PREG_PATTERN_EMAIL_STRING, $email) !== 0) ? true : false;
}

/**
 * Determine if a string is a possible phone number
 *
 * @param string $phone        	
 * @return boolean
 */
function is_phone($phone) {
	return (preg_match('/^\s*\+?[- \t0-9.\)\(x]{7,}\s*$/', $phone) !== 0) ? true : false;
}

/**
 * Gets a value from an array using a delimited separated path.
 * // Get the value of $array['foo']['bar']
 * $value = apath($array, 'foo.bar');
 *
 * @param
 *        	array array to search
 * @param
 *        	string key path, dot separated
 * @param
 *        	mixed default value if the path is not set
 * @return mixed
 * @see apath_set
 */

/**
 *
 * @param array $array        	
 * @param mixed $path
 *        	string path or array
 * @param mixed $default
 *        	value to return if value is not found
 * @param string $separator
 *        	string separator for string paths
 * @return mixed
 */
function &apath(array $array, $path, $default = null, $separator = ".") {
	// Split the keys by $separator
	$keys = is_array($path) ? $path : explode($separator, $path);
	while (true) {
		if (!is_array($array)) {
			return $default;
		}
		$key = array_shift($keys);
		$count = count($keys);
		if (isset($array[$key])) {
			if ($count === 0) {
				return $array[$key];
			}
			$array = & $array[$key];
		} else {
			return $default;
		}
	}
}

/**
 * Partner of apath - sets an array path to a specific value
 *
 * @param array $current        	
 * @param string $path
 *        	A path into the array separated by $separator (e.g. "document.title")
 * @param mixed $value
 *        	Value to set the path in the tree. Use null to delete the target item.
 * @param string $separator
 *        	Character used to separate levels in the array
 * @return array
 */
function &apath_set(array &$array, $path, $value = null, $separator = ".") {
	$current = & $array;
	// Split the keys by separator
	$keys = is_array($path) ? $path : explode($separator, $path);
	while (count($keys) > 1) {
		$key = array_shift($keys);
		if (isset($current[$key])) {
			if (!is_array($current[$key])) {
				$current[$key] = array();
			}
		} else {
			$current[$key] = array();
		}
		$current = & $current[$key];
	}
	$key = array_shift($keys);
	if ($value === null) {
		unset($current[$key]);
		return $current;
	} else {
		$current[$key] = $value;
		return $current[$key];
	}
}

/**
 * Convert a global name to a standard internal format.
 *
 * @param string $key        	
 * @return array
 */
function _zesk_global_key($key) {
	$key = explode(ZESK_GLOBAL_KEY_SEPARATOR, strtr(strtolower($key), array(
		"__" => ZESK_GLOBAL_KEY_SEPARATOR,
		"." => "_",
		"/" => "_",
		"-" => "_",
		" " => "_"
	)));
	return $key;
}

/**
 * Normalize a zesk global key
 *
 * @param string $key        	
 * @return string
 */
function zesk_global_key_normalize($key) {
	return implode(ZESK_GLOBAL_KEY_SEPARATOR, _zesk_global_key($key));
}

/**
 * Get our global application
 * 
 * @return Application
 */
function app() {
	return Application::instance();
}

