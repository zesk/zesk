<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 * String manipulation functions, largely based on latin languages.
 */
class StringTools {
	public static function case_match(string $string, string $pattern): string {
		$char1 = substr($pattern, 0, 1);
		$char2 = substr($pattern, 1, 1);
		if ($char1 == strtolower($char1)) {
			return strtolower($string);
		} elseif ($char2 == strtolower($char2)) {
			return ucfirst(strtolower($string));
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
	 * @return array
	 * @see pair
	 */
	public static function pair(string $string, string $delim = '.', mixed $left = null, mixed $right = null): array {
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
	 * @return array
	 * @see reversePair
	 */
	public static function reversePair(string $string, string $delim = '.', mixed $left = null, mixed $right = null):
	array {
		return reversePair($string, $delim, $left, $right);
	}

	/**
	 * Return portion of string to the left of a matched string
	 *
	 * @param string $haystack
	 *            String to slice up
	 * @param string $needle
	 *            String to find
	 * @param ?string $default
	 *            Default string to return if not found. If null, returns $str, otherwise returns
	 *            $default
	 * @return string
	 */
	public static function left(string $haystack, string $needle, string $default = null): string {
		if (($pos = strpos($haystack, $needle)) === false) {
			return $default === null ? $haystack : $default;
		}
		return substr($haystack, 0, $pos);
	}

	/**
	 * Return portion of string to the left of a matched string, searching backwards for $find
	 *
	 * @param string $haystack
	 *            String to slice up
	 * @param string $needle
	 *            String to find
	 * @param ?string $default
	 *            Default string to return if not found. If null, returns $str, otherwise returns
	 *            $default
	 * @return string
	 */
	public static function reverseLeft(string $haystack, string $needle, string $default = null): string {
		if (($pos = strrpos($haystack, $needle)) === false) {
			return $default === null ? $haystack : $default;
		}
		return substr($haystack, 0, $pos);
	}

	/**
	 * Return portion of string to the right of a matched string
	 *
	 * @param string $haystack
	 *            String to slice up
	 * @param string $needle
	 *            String to find
	 * @param string $default
	 *            Default string to return if not found. If null, returns $str, otherwise returns
	 *            $default
	 * @return string
	 */
	public static function right(string $haystack, string $needle, string $default = null): string {
		if (($pos = strpos($haystack, $needle)) === false) {
			return $default === null ? $haystack : $default;
		}
		return substr($haystack, $pos + strlen($needle));
	}

	/**
	 * Return portion of string to the right of a matched string, searching backwards for $find
	 *
	 * @param string $haystack
	 *            String to slice up
	 * @param string $needle
	 *            String to find
	 * @param string $default
	 *            Default string to return if not found. If null, returns $str, otherwise returns
	 *            $default
	 * @return string
	 */
	public static function reverseRight(string $haystack, string $needle, string $default = null): string {
		if (($pos = strrpos($haystack, $needle)) === false) {
			return $default === null ? $haystack : $default;
		}
		return substr($haystack, $pos + strlen($needle));
	}

	/**
	 * Parses boolean values, but does not accept numeric ones.
	 *
	 * @param mixed $value
	 *            Value to parse
	 * @param mixed $default
	 *            Value to return if parsing fails.
	 * @return mixed Parsed boolean value, or $default
	 * @see toBool
	 */
	public static function toBool(mixed $value, bool $default = false): bool {
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return !empty($value);
		}
		return toBool($value, $default);
	}

	/**
	 * Convert a boolean to a string version of it "true" or "false"
	 *
	 * @param mixed $bool
	 * @return string
	 */
	public static function fromBool(mixed $bool): string {
		return self::toBool($bool) ? 'true' : 'false';
	}

	/**
	 * Capitalize words in a sentence -> Captialize Words In A Sentence.
	 *
	 * @param string $phrase
	 * @return string
	 */
	public static function capitalize(string $phrase): string {
		return mb_convert_case($phrase, MB_CASE_TITLE);
	}

	/**
	 * Extract a field from a line, similar to awk.
	 * Note that any delimiters within the string
	 * are converted to a single delimiter, so:
	 *
	 * StringTools::field("a b c d e f", null, " \t", 3) === array("a","b","c d e f")
	 *
	 * @param string $string Text to extract fields from
	 * @param ?int $index Field number to extract, or null to extract all fields as an array
	 * @param string $delim Split words using these characters (grouped)
	 * @param ?integer $max_fields Maximum fields to create, if null, then all fields
	 * @return string|array|null
	 */
	public static function field(string $string, int $index = null, string $delim = " \t", int $max_fields = null): string|array|null {
		$d = $delim[0];
		$v = preg_replace('/[' . preg_quote($delim, '/') . ']+/', $d, $string);
		$v = $max_fields !== null ? explode($d, $v, $max_fields) : explode($d, $v);
		return $index === null ? $v : ($v[$index] ?? null);
	}

	/**
	 * Kind of like UNIX "awk '{ print $index }'"
	 * Null for index means return the whole list as an array
	 *
	 * @param array $lines Array of strings to extract a column of information from
	 * @param ?int $index Column to fetch
	 * @param string $delim List of delimiter characters
	 * @param ?int $max_fields Maximum fields to extract on each line
	 * @return array
	 */
	public static function column(array $lines, int $index = null, string $delim = " \t", int $max_fields = null): array {
		$array = [];
		foreach ($lines as $k => $v) {
			if (is_string($v)) {
				$array[$k] = self::field($v, $index, $delim, $max_fields);
			}
		}
		return $array;
	}

	/**
	 * Determine if a string begins with another string
	 *
	 * @param string|array $haystack
	 *            String(s) to search
	 * @param string|array $needle
	 *            String(s) to find
	 * @param boolean $case_insensitive
	 *            Case insensitive comparison
	 * @return boolean Whether any haystack begins with any needle
	 * @see begins
	 */
	public static function begins(string|array $haystack, string|array $needle, bool $case_insensitive = false): bool {
		if (is_array($haystack)) {
			foreach ($haystack as $k) {
				if (self::begins($k, $needle, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needle)) {
			foreach ($needle as $k) {
				if (self::begins($haystack, $k, $case_insensitive)) {
					return true;
				}
			}
			return false;
		}
		$check_string = substr($haystack, 0, strlen($needle));
		return $case_insensitive ? strcasecmp($check_string, $needle) === 0 : $check_string === $needle;
	}

	/**
	 * Determine if a string contains with another string
	 *
	 * @param string $haystack
	 *            String to search
	 * @param string $needle
	 *            String to find
	 * @param boolean $case_insensitive
	 *            Case insensitive comparison
	 * @return boolean Whether the haystack contains the needle
	 * @see StringTools::begins, StringTools::ends
	 */
	public static function contains(array|string $haystack, array|string $needle, bool $case_insensitive = false): bool {
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
		return $case_insensitive ? stripos($haystack, $needle) !== false : str_contains($haystack, $needle);
	}

	/**
	 * Determine if a string ends with another string
	 *
	 * @param array|string $haystack
	 *            A string or array of strings to search
	 * @param array|string $needle
	 *            A string or array of strings to find
	 * @param boolean $case_insensitive
	 *            Case insensitive comparison
	 * @return boolean
	 */
	public static function ends(array|string $haystack, array|string $needle, bool $case_insensitive = false): bool {
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
	 *            A string or an array of strings to removePrefix. First matched string is used to
	 *            removePrefix the string.
	 * @return string
	 */
	public static function removePrefix(string|array $string, string|array $prefix, bool $case_insensitive = false): string|array {
		/* Unwrap string first to make 2nd case simpler */
		if (is_array($string)) {
			$result = [];
			foreach ($string as $item) {
				$result[] = self::removePrefix($item, $prefix, $case_insensitive);
			}
			return $result;
		} elseif (is_array($prefix)) {
			foreach ($prefix as $pre) {
				$new_string = self::removePrefix($string, $pre, $case_insensitive);
				if ($new_string !== $string) {
					/* remove prefix once and only once */
					return $new_string;
				}
			}
			return $string;
		} else {
			return self::begins($string, $prefix, $case_insensitive) ? substr($string, strlen($prefix)) : $string;
		}
	}

	/**
	 * Unsuffix a string (remove a suffix if found at end of a string)
	 *
	 * @param string|array $string
	 * @param mixed $suffix
	 *            A string or an array of strings to removeSuffix. First matched string is used to
	 *            removeSuffix the string.
	 * @return string
	 */
	public static function removeSuffix(string|array $string, string $suffix, bool $case_insensitive = false): string|array {
		if (is_array($string)) {
			$result = [];
			foreach ($string as $k => $v) {
				$result[$k] = self::removeSuffix($v, $suffix, $case_insensitive);
			}
			return $result;
		} else {
			return self::ends($string, $suffix, $case_insensitive) ? substr($string, 0, -strlen($suffix)) : $string;
		}
	}

	/**
	 * Return whether a string is UTF16
	 * Based on presence of BOM
	 *
	 * @param string $str
	 * @return boolean
	 */
	public static function is_utf16(string $str, bool &$be = false): bool {
		if (strlen($str) < 2) {
			return false;
		}
		$c0 = ord($str[0]);
		$c1 = ord($str[1]);
		if ($c0 == 0xFE && $c1 == 0xFF) {
			$be = true;
			return true;
		} elseif ($c0 == 0xFF && $c1 == 0xFE) {
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
	public static function is_ascii(string $str): bool {
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
	public static function is_utf8(string $str): bool {
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($str[$i]);
			if ($c === 0) {
				return false;
			}
			if ($c > 128) {
				if (($c > 247)) {
					return false;
				} elseif ($c > 239) {
					$bytes = 4;
				} elseif ($c > 223) {
					$bytes = 3;
				} elseif ($c > 191) {
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
	 * StringTools::filter
	 *
	 * rules are an array of pattern => boolean
	 * - pattern is a Perl-Regular Expression, delimited correctly e.g. '/\.(inc|php)$/'
	 * - pass just a boolean value (numeric index) to force a result
	 * If pattern is a numeric value, then the boolean is returned instead.
	 *
	 * Usage:
	 *
	 * StringTools::filter($path, array("/.*\.php/" => true, "/.*\.zip/" => false)));
	 *
	 * Returns true for php files, false for ZIP files, and "funky" for everything else
	 *
	 * @param string $string A string to match
	 * @param array $rules A list of patterns and how to handle them
	 * @param boolean $default The default value to return if all rules are parsed and nothing matches
	 *
	 * @return boolean
	 */
	public static function filter($string, array $rules, $default = null) {
		foreach ($rules as $pattern => $result) {
			$result = toBool($result);
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
	 *            String to find
	 * @param string $replace
	 *            String to replace
	 * @param string $content
	 *            Content in which to replace string
	 * @return string
	 */
	public static function replace_first(string $search, string $replace, string $content): string {
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
	public static function ellipsis_word(string $text, int $length = 20, string $dot_dot_dot = ' ...'): string {
		if ($length < 0) {
			return $text;
		}
		if (StringTools::length($text) <= $length) {
			return $text;
		}
		$text = StringTools::substr($text, 0, $length);
		$off = 0;
		$aa = [
			' ', "\n", "\t",
		];
		$letters = StringTools::str_split($text);
		if (count($letters) >= 0) {
			$i = count($letters) - 1;
			while ($i >= 0) {
				if (in_array($letters[$i], $aa)) {
					$off = $i;

					break;
				}
				--$i;
			}
		}
		if ($off === 0) {
			$off = $length;
		}
		return StringTools::substr($text, 0, $off) . $dot_dot_dot;
	}

	/**
	 * Pad a string with zeros up to the length specified.
	 *
	 * @param number $number
	 *            Number to pad
	 * @param number $length
	 *            Number of characters to pad
	 * @return string
	 */
	public static function zero_pad($number, $length = 2) {
		$number = strval($number);
		$number_length = strlen($number);
		if ($number_length >= $length) {
			return $number;
		}
		return str_repeat('0', $length - $number_length) . $number;
	}

	/**
	 * Convert tabs to spaces, intelligently.
	 *
	 * If no $tab_width is not specified (or negative), uses default of 4 for tab width.
	 *
	 * @see http://www.nntp.perl.org/group/perl.macperl.anyperl/154
	 * @param string $text
	 * @param int $tab_width
	 * @return string
	 */
	public static function replaceTabs(string $text, int $tab_width = -1, string $replace = ' '): string {
		if ($tab_width < 0) {
			$tab_width = 4;
		}
		//	$text =~ s{(.*?)\t}{$1.(' ' x ($g_tab_width - length($1) % $g_tab_width))}ge;
		return preg_replace_callback('@^(.*?)\t@m', fn ($matches) => $matches[1] . ($tab_width > 0 ? str_repeat($replace, $tab_width - strlen($matches[1]) % $tab_width) : ''), $text);
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
	public static function str_split(string $string, int $split_length = 1, string $encoding = 'UTF-8') {
		if ($split_length < 1) {
			$split_length = 1;
		}
		$ret = [];
		$len = self::length($string, $encoding);
		for ($i = 0; $i < $len; $i += $split_length) {
			$ret[] = self::substr($string, $i, $split_length, $encoding);
		}
		return $ret;
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
	 *            A value to write to a CSV file
	 * @return string A correctly quoted CSV value
	 */
	public static function csv_quote(string $x): string {
		if ((str_contains($x, '"')) || (str_contains($x, ',')) || (str_contains($x, "\n"))) {
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
	public static function csv_quote_row(array $x): string {
		$yy = [];
		foreach ($x as $col) {
			$yy[] = self::csv_quote($col);
		}
		return implode(',', $yy) . "\r\n";
	}

	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $x
	 *            of arrays of strings
	 * @return string
	 */
	public static function csv_quote_rows(array $x): string {
		$yy = '';
		foreach ($x as $row) {
			$yy .= self::csv_quote_row($row);
		}
		return $yy;
	}

	/**
	 * Converts camelCaseStringToConvert to camel_case_string_to_convert
	 * @param string $string
	 * @return string
	 */
	public static function from_camel_case(string $string): string {
		return preg_replace_callback('/[A-Z]/', fn ($matches) => '_' . strtolower($matches[0]), $string);
	}

	/**
	 * Converts camel_case_string_to_convert to camelCaseStringToConvert
	 *
	 * @param $string
	 * @return string
	 */
	public static function to_camel_case(string $string): string {
		$result = '';
		foreach (explode('_', $string) as $i => $token) {
			$result .= $i === 0 ? strtolower($token) : strtoupper($token[0]) . strtolower(substr($token, 1));
		}
		return $result;
	}

	/**
	 * Retreve the length of a mutti-byte string
	 *
	 * @param string $string
	 * @param string $encoding
	 * @return integer
	 * @see mb_internal_encoding
	 */
	public static function length(string $string, string $encoding = 'UTF-8'): int {
		if ($encoding === '') {
			$encoding = mb_internal_encoding();
		}
		return mb_strlen($string, $encoding);
	}

	/**
	 * Retrieve a substring of a multibyte string
	 *
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @param string $encoding
	 * @return string
	 * @see mb_substr
	 * @see mb_internal_encoding
	 */
	public static function substr(string $string, int $start, int $length, string $encoding = 'UTF-8'): string {
		if ($encoding === '') {
			$encoding = mb_internal_encoding();
		}
		return mb_substr($string, $start, $length, $encoding);
	}
}
