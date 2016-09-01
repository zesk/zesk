<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/str.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * String class for string and text manipulation.
 *
 * @see text, arr, preg
 * @author kent
 */
class str {
	public static function case_match($string, $pattern) {
		$char1 = substr($pattern, 0, 1);
		$char2 = substr($pattern, 1, 1);
		if ($char1 == strtolower($char1)) {
			return strtolower($string);
		} else if ($char2 == strtolower($char2)) {
			return ucfirst($string);
		} else {
			return strtoupper($string);
		}
	}
	/**
	 * Synonym for pair - split a string into a pair with defaults
	 *
	 * @param string $string        	
	 * @param string $delim        	
	 * @param string $left        	
	 * @param string $right        	
	 * @return string
	 * @see pair
	 */
	public static function pair($string, $delim = ".", $left = null, $right = null) {
		return pair($string, $delim, $left, $right);
	}
	
	/**
	 * Synonym for pairr - split a string into a pair with defaults, searching backwards for
	 * delimiter
	 *
	 * @param string $string        	
	 * @param string $delim        	
	 * @param string $left        	
	 * @param string $right        	
	 * @return string
	 * @see pairr
	 */
	public static function pairr($string, $delim = ".", $left = null, $right = null) {
		return pairr($string, $delim, $left, $right);
	}
	
	/**
	 * Return portion of string to the left of a matched string
	 *
	 * @param string $str
	 *        	String to slice up
	 * @param string $find
	 *        	String to find
	 * @param string $default
	 *        	Default string to return if not found. If null, returns $str, otherwise returns
	 *        	$default
	 * @return string
	 */
	public static function left($str, $find, $default = null) {
		if (($pos = strpos($str, $find)) === false) {
			return $default === null ? $str : $default;
		}
		return substr($str, 0, $pos);
	}
	
	/**
	 * Return portion of string to the left of a matched string, searching backwards for $find
	 *
	 * @param string $str
	 *        	String to slice up
	 * @param string $find
	 *        	String to find
	 * @param string $default
	 *        	Default string to return if not found. If null, returns $str, otherwise returns
	 *        	$default
	 * @return string
	 */
	public static function rleft($str, $find, $default = null) {
		if (($pos = strrpos($str, $find)) === false) {
			return $default === null ? $str : $default;
		}
		return substr($str, 0, $pos);
	}
	
	/**
	 * Return portion of string to the right of a matched string
	 *
	 * @param string $str
	 *        	String to slice up
	 * @param string $find
	 *        	String to find
	 * @param string $default
	 *        	Default string to return if not found. If null, returns $str, otherwise returns
	 *        	$default
	 * @return string
	 */
	public static function right($str, $find, $default = null) {
		if (($pos = strpos($str, $find)) === false) {
			return $default === null ? $str : $default;
		}
		return substr($str, $pos + strlen($find));
	}
	
	/**
	 * Return portion of string to the right of a matched string, searching backwards for $find
	 *
	 * @param string $str
	 *        	String to slice up
	 * @param string $find
	 *        	String to find
	 * @param string $default
	 *        	Default string to return if not found. If null, returns $str, otherwise returns
	 *        	$default
	 * @return string
	 */
	public static function rright($str, $find, $default = null) {
		if (($pos = strrpos($str, $find)) === false) {
			return $default === null ? $str : $default;
		}
		return substr($str, $pos + strlen($find));
	}
	
	/**
	 * Parses boolean values, but does not accept numeric ones.
	 *
	 * @param mixed $value
	 *        	Value to parse
	 * @param mixed $default
	 *        	Value to return if parsing fails.
	 * @return mixed Parsed boolean value, or $default
	 * @see to_bool
	 */
	public static function to_bool($value, $default = false) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return $default;
		}
		return to_bool($value, $default);
	}
	
	/**
	 * Convert a boolean to a string version of it "true" or "false"
	 *
	 * @param mixed $bool        	
	 * @return string
	 */
	public static function from_bool($bool) {
		return to_bool($bool) ? 'true' : 'false';
	}
	
	/**
	 * Capitalize words in a sentence -> Captialize Words In A Sentence.
	 *
	 * @param string $phrase        	
	 * @return string
	 */
	public static function capitalize($phrase) {
		if (function_exists('mb_convert_case')) {
			return mb_convert_case($phrase, MB_CASE_TITLE);
		} else {
			$items = explode(" ", strtolower($phrase));
			foreach ($items as $i => $word) {
				$items[$i] = ucfirst($word);
			}
			return implode(" ", $items);
		}
	}
	
	/**
	 * Extract a field from a line, similar to awk.
	 * Note that any delimiters within the string
	 * are converted to a single delimiter, so:
	 *
	 * str::field("a b c d e f", null, " \t", 3) === array("a","b","c d e f")
	 *
	 * @param string $string
	 *        	Text to extract fields from
	 * @param integer $index
	 *        	Field number to extract, or null to extract all fields as an array
	 * @param mixed $delim
	 *        	field, or array when $index is null
	 * @param integer $max_fields
	 *        	Maximum fields to create
	 */
	public static function field($string, $index = null, $delim = " \t", $max_fields = null) {
		$d = $delim[0];
		$v = preg_replace('/[' . preg_quote($delim, '/') . ']+/', $d, $string);
		$v = $max_fields !== null ? explode($d, $v, $max_fields) : explode($d, $v);
		return $index === null ? $v : avalue($v, $index);
	}
	
	/**
	 * Determine if a string begins with another string
	 *
	 * @param string $haystack
	 *        	String to search
	 * @param string $needle
	 *        	String to find
	 * @param boolean $case_insensitive
	 *        	Case insensitive comparison
	 * @return boolean Whether the haystack begins with needle
	 * @see begins
	 */
	public static function begins($haystack, $needle, $case_insensitive = false) {
		if (is_array($haystack)) {
			foreach ($haystack as $k) {
				if (self::begins($k, $needle, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needle)) {
			foreach ($needle as $needles) {
				if (self::begins($haystack, $needles, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		$check_string = substr($haystack, 0, strlen($needle));
		return $case_insensitive ? (strcasecmp($check_string, $needle) === 0 ? true : false) : ($check_string === $needle ? true : false);
	}
	
	/**
	 * Determine if a string contains with another string
	 *
	 * @param string $haystack
	 *        	String to search
	 * @param string $needle
	 *        	String to find
	 * @param boolean $case_insensitive
	 *        	Case insensitive comparison
	 * @return boolean Whether the haystack contains the needle
	 * @see str::begins, str::ends
	 */
	public static function contains($haystack, $needle, $case_insensitive = false) {
		if (is_array($haystack)) {
			foreach ($haystack as $k) {
				if (self::contains($k, $needle, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needle)) {
			foreach ($needle as $needles) {
				if (self::contains($haystack, $needles, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		return $case_insensitive ? (stripos($haystack, $needle) !== 0 ? true : false) : (strpos($haystack, $needle) !== 0 ? true : false);
	}
	
	/**
	 * Determine if a string ends with another string
	 *
	 * @param mixed $haystack
	 *        	A string or array of strings to search
	 * @param mmixed $needle
	 *        	A string or array of strings to find
	 * @param boolean $case_insensitive
	 *        	Case insensitive comparison
	 * @return boolean
	 */
	public static function ends($haystack, $needle, $case_insensitive = false) {
		if (is_array($haystack)) {
			foreach ($haystack as $k) {
				if (self::ends($k, $needle, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needle)) {
			foreach ($needle as $needles) {
				if (self::ends($haystack, $needles, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		$n = strlen($needle);
		if ($n === 0) {
			return true;
		}
		if ($case_insensitive) {
			if (strcasecmp(substr($haystack, -$n), $needle) === 0) {
				return true;
			}
		} else {
			if (substr($haystack, -$n) === $needle) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Unprefix a string (remove a prefix if found at start of a string)
	 *
	 * @param string $string        	
	 * @param mixed $prefix
	 *        	A string or an array of strings to unprefix. First matched string is used to
	 *        	unprefix the string.
	 * @return string
	 */
	public static function unprefix($string, $prefix, $case_insensitive = false) {
		if (is_array($prefix)) {
			foreach ($prefix as $pre) {
				$new_string = self::unprefix($string, $pre, $case_insensitive);
				if ($new_string !== $string) {
					return $new_string;
				}
			}
			return $string;
		} else {
			return self::begins($string, $prefix, $case_insensitive) ? strval(substr($string, strlen($prefix))) : $string;
		}
	}
	
	/**
	 * Unsuffix a string (remove a suffix if found at end of a string)
	 *
	 * @param string $string        	
	 * @param mixed $suffix
	 *        	A string or an array of strings to unsuffix. First matched string is used to
	 *        	unsuffix the string.
	 * @return string
	 */
	public static function unsuffix($string, $suffix, $case_insensitive = false) {
		if (is_array($suffix)) {
			foreach ($suffix as $suff) {
				$new_string = self::unsuffix($string, $suff, $case_insensitive);
				if ($new_string !== $string) {
					return $new_string;
				}
			}
			return $string;
		} else {
			return self::ends($string, $suffix, $case_insensitive) ? strval(substr($string, 0, -strlen($suffix))) : $string;
		}
	}
	
	/**
	 * Return whether a string is UTF16
	 * Based on presence of BOM
	 *
	 * @param string $str        	
	 * @return boolean
	 */
	public static function is_utf16($str, &$be = null) {
		if (strlen($str) < 2) {
			return false;
		}
		$c0 = ord($str[0]);
		$c1 = ord($str[1]);
		if ($c0 == 0xFE && $c1 == 0xFF) {
			$be = true;
			return true;
		} else if ($c0 == 0xFF && $c1 == 0xFE) {
			$be = false;
			return true;
		}
		return false;
	}
	
	/**
	 * Return whether a string is ASCII
	 *
	 * @param string $str        	
	 * @return boolean
	 */
	public static function is_ascii($str) {
		if (strlen($str) < 2) {
			return true;
		}
		$n = strlen($str);
		for ($i = 0; $i < $n; $i++) {
			if (ord($str[$i]) > 128) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Is a string valid UTF-8?
	 *
	 * @param string $str        	
	 * @return boolean
	 */
	public static function is_utf8($str) {
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($str[$i]);
			if ($c === 0) {
				return false;
			}
			if ($c > 128) {
				if (($c > 247)) {
					return false;
				} else if ($c > 239) {
					$bytes = 4;
				} else if ($c > 223) {
					$bytes = 3;
				} else if ($c > 191) {
					$bytes = 2;
				} else {
					return false;
				}
				if (($i + $bytes) > $len) {
					return false;
				}
				while ($bytes > 1) {
					$i++;
					$b = ord($str[$i]);
					if ($b < 128 || $b > 191) {
						return false;
					}
					$bytes--;
				}
			}
		}
		return true;
	}
	
	/**
	 * str::filter
	 *
	 * rules are an array of pattern => boolean
	 * - pattern is a Perl-Regular Expression, delimited correctly e.g. '/\.(inc|php)$/'
	 * - pass just a boolean value (numeric index) to force a result
	 *
	 * @param string $string
	 *        	A string to match
	 * @param string $in_pattern
	 *        	A pattern to include, matches are included (true)
	 * @param string $ex_pattern
	 *        	A pattern to exclude, matches are excluded (false)
	 * @param boolean $default
	 *        	If in_pattern and $ex_pattern are not strings, then default value is returned
	 * @return boolean
	 */
	public static function filter($string, array $rules, $default = null) {
		foreach ($rules as $pattern => $result) {
			$result = to_bool($result);
			if (is_numeric($pattern)) {
				return $result;
			}
			if (is_string($pattern) && preg_match($pattern, $string)) {
				return $result;
			}
		}
		return $default;
	}
	
	/**
	 * Replace first occurrance of a strings in another string
	 *
	 * If not found, return the content unchanged.
	 *
	 * @param string $search
	 *        	String to find
	 * @param string $replace
	 *        	String to replace
	 * @param string $content
	 *        	Content in which to replace string
	 * @return string
	 */
	public static function replace_first($search, $replace, $content) {
		$x = explode($search, $content, 2);
		if (count($x) === 1) {
			return $content;
		}
		return implode($replace, $x);
	}
	
	/**
	 * Add an ellipsis into a string at a word boundary and at a certain string length.
	 *
	 * @param string $text        	
	 * @param number $length        	
	 * @param string $dot_dot_dot        	
	 * @return string
	 */
	public static function ellipsis_word($text, $length = 20, $dot_dot_dot = " ...") {
		if ($length < 0) {
			return $text;
		}
		if (str::length($text) < $length) {
			return $text;
		}
		$text = str::substr($text, 0, $length);
		$off = 0;
		$aa = array(
			" ",
			"\n",
			"\t"
		);
		$letters = str::str_split($text);
		for ($i = count($letters) - 1; $i--; $i >= 0) {
			if (in_array($letters[$i], $aa)) {
				$off = $i;
				break;
			}
		}
		if ($off === 0) {
			$off = $length;
		}
		return str::substr($text, 0, $off) . $dot_dot_dot;
	}
	
	/**
	 * Pad a string with zeros up to the length specified.
	 *
	 * @param number $number
	 *        	Number to pad
	 * @param number $length
	 *        	Number of characters to pad
	 * @return string
	 */
	public static function zero_pad($number, $length = 2) {
		$number = strval($number);
		$number_length = strlen($number);
		if ($number_length >= $length) {
			return $number;
		}
		return str_repeat("0", $length - $number_length) . $number;
	}
	
	/**
	 * Convert tabs to spaces, intelligently.
	 *
	 * If no $tab_width is specified, uses global "tab_width", or 4 for tab width.
	 *
	 * @see http://www.nntp.perl.org/group/perl.macperl.anyperl/154
	 * @param string $text        	
	 * @param integer $tab_width        	
	 * @return string
	 */
	public static function detab($text, $tab_width = null) {
		if ($tab_width === null) {
			global $zesk;
			/* @var $zesk zesk\Kernel */
			$tab_width = to_integer($zesk->configuration->get('tab_width', 4));
		}
		//	$text =~ s{(.*?)\t}{$1.(' ' x ($g_tab_width - length($1) % $g_tab_width))}ge;
		$func = create_function('$matches', "{ return \$matches[1] . str_repeat(' ', $tab_width - strlen(\$matches[1]) % $tab_width); }");
		return preg_replace_callback('@^(.*?)\t@m', $func, $text);
	}
	
	/**
	 * Splits a string into an array of individual characters.
	 * Not recommended for large strings.
	 *
	 * @return array
	 * @param string $string        	
	 * @deprecated 2013 use str_split instead
	 */
	public static function explode_chars($string) {
		return str_split($string, 1);
	}
	
	/**
	 * Split a string into characters
	 *
	 * @deprecated 2013 use str_split
	 * @see str_split
	 * @param unknown $str        	
	 * @return array
	 * @see str::str_split
	 */
	public static function cexplode($string) {
		return str_split($string, 1);
	}
	
	/**
	 * Split a multi-byte string into characters/glyphs
	 *
	 * If the optional split_length parameter is specified, the returned array will be broken down
	 * into chunks with each being split_length in length, otherwise each chunk will be one
	 * character in length.
	 *
	 * FALSE is returned if split_length is less than 1. If the split_length length exceeds the
	 * length of string, the entire string is returned as the first (and only) array element.
	 *
	 * @param string $string        	
	 * @return array
	 */
	public static function str_split($string, $split_length = 1, $encoding = null) {
		if ($split_length < 1) {
			return false;
		}
		$ret = array();
		$len = self::length($string, $encoding);
		for ($i = 0; $i < $len; $i += $split_length) {
			$ret[] = self::substr($string, $i, $split_length, "UTF-8");
		}
		return $ret;
	}
	
	/**
	 *
	 * @deprecated 2014-02-05 Use preg::matches to get improved functionality
	 * @see preg::matches
	 * @param unknown $pattern        	
	 * @param unknown $string        	
	 * @param string $flag        	
	 * @param number $offset        	
	 * @return multitype:
	 */
	public static function matches($pattern, $string, $flag = PREG_SET_ORDER, $offset = 0) {
		if ($flag === null) {
			$flag = PREG_SET_ORDER;
		}
		$matches = array();
		if (!preg_match_all($pattern, $string, $matches, $flag, $offset)) {
			//			static $error = null;
			// 			$last_error = error_get_last();
			// 			if ($last_error !== null && serialize($last_error) !== $error) {
			// 				$error = serialize($last_error);
			// 				echo $error;
			// 				echo _backtrace();
			// 			}
			return array();
		}
		return $matches;
	}
	
	/**
	 * Quote a CSV field correctly.
	 * If it contains a quote (") a comma (,), or a newline(\n), then quote it.
	 * Quotes are double-quoted, so:
	 *
	 * """Hello"", he said."
	 *
	 * is unquoted as:
	 *
	 * "Hello", he said.
	 *
	 * @param string $x
	 *        	A value to write to a CSV file
	 * @return string A correctly quoted CSV value
	 */
	public static function csv_quote($x) {
		if ((strpos($x, '"') !== false) || (strpos($x, ",") !== false) || (strpos($x, "\n") !== false)) {
			return '"' . str_replace('"', '""', $x) . '"';
		}
		return $x;
	}
	
	/**
	 * Quote a single CSV row
	 *
	 * @param array $x        	
	 * @return string
	 */
	public static function csv_quote_row($x) {
		$yy = array();
		foreach ($x as $col) {
			$yy[] = self::csv_quote($col);
		}
		return implode(",", $yy) . "\r\n";
	}
	
	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $x
	 *        	of arrays of strings
	 * @return string
	 */
	public static function csv_quote_rows($x) {
		$yy = "";
		foreach ($x as $row) {
			$yy .= self::csv_quote_row($row);
		}
		return $yy;
	}
	public static function from_camel_case($string) {
		return preg_replace_callback('/[A-Z]/', function ($matches) {
			return "_" . strtolower($matches[0]);
		}, $string);
	}
	public static function to_camel_case($string) {
		$result = "";
		foreach (explode('_', $string) as $i => $token) {
			$result .= $i === 0 ? strtolower($token) : strtoupper($token[0]) . strtolower(substr($token, 1));
		}
		return $result;
	}
	
	/**
	 * Retreve the length of a mutlt-byte string
	 *
	 * @param string $string        	
	 * @param string $encoding        	
	 * @see mb_internal_encoding
	 * @return integer
	 */
	public static function length($string, $encoding = null) {
		if (function_exists("mb_strlen")) {
			if ($encoding === null) {
				$encoding = mb_internal_encoding();
			}
			return mb_strlen($string, $encoding);
		}
		if ($encoding && $encoding !== "UTF-8") {
			$string = utf8::from_charset($string, $encoding);
		}
		return strlen(utf8::to_iso8859($string));
	}
	
	/**
	 * Retrieve a substring of a multi-byte string
	 *
	 * @param string $string        	
	 * @param integer $start        	
	 * @param integer $length        	
	 * @param string $encoding        	
	 * @see mb_internal_encoding
	 * @see mb_substr
	 * @return string
	 */
	public static function substr($string, $start, $length = null, $encoding = null) {
		if (function_exists("mb_substr")) {
			if ($encoding === null) {
				$encoding = mb_internal_encoding();
			}
			return mb_substr($string, $start, $length, $encoding);
		}
		// Use preg_match_all to extract characters
		if ($encoding && $encoding !== "UTF-8") {
			$string = utf8::from_charset($string, $encoding);
		}
		preg_match_all('/./us', $string, $match);
		return implode('', $length === null ? array_slice($match[0], $start) : array_slice($match[0], $start, $length));
	}
}

