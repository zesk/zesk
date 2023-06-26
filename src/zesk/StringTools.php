<?php
declare(strict_types=1);
/**
 * String manipulation functions, largely based on latin languages.
 *
 * @copyright &copy; 2023 Market Acumen, Inc
 * @package zesk
 */

namespace zesk;

class StringTools {
	/**
	 * Clean tokens from a string
	 *
	 * @test_inline $this->assertEquals(map_clean("He wanted {n} days"), "He wanted  days");
	 * @test_inline $this->assertEquals(map_clean();
	 *
	 * @param mixed $string
	 * @param string $prefixChar
	 * @param string $suffixChar
	 * @return mixed
	 */
	public static function cleanTokens(string $string, string $prefixChar = '{', string $suffixChar = '}'): string {
		$delimiter = '#';
		$suffix = preg_quote($suffixChar, $delimiter);
		return preg::replace($delimiter . preg_quote($prefixChar, $delimiter) . '[^' . $suffix . ']*' . $suffix . $delimiter, '', $string);
	}

	/**
	 * Return true if string contains tokens which can be mapped using prefix/suffix
	 *
	 * @param string $string
	 * @param string $prefixChar
	 * @param string $suffixChar
	 * @return boolean
	 */
	public static function hasTokens(string $string, string $prefixChar = '{', string $suffixChar = '}'): bool {
		$tokens = self::extractTokens($string, $prefixChar, $suffixChar);
		return count($tokens) !== 0;
	}

	/**
	 * Retrieve tokens from a string
	 *
	 * @param string $string
	 * @param string $prefixChar
	 * @param string $suffixChar
	 * @return array
	 */
	public static function extractTokens(string $string, string $prefixChar = '{', string $suffixChar = '}'): array {
		$delimiter = '#';
		$prefix = preg_quote($prefixChar, $delimiter);
		$suffix = preg_quote($suffixChar, $delimiter);
		$matches = [];
		$pattern = $delimiter . $prefix . '[^' . $suffix . ']*' . $suffix . $delimiter;
		if (!preg_match_all($pattern, $string, $matches)) {
			return [];
		}
		return $matches[0];
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
	public static function glue(string $left, string $glue, string $right): string {
		return rtrim($left, $glue) . $glue . ltrim($right, $glue);
	}

	/**
	 * Unquote a string and optionally return the quote removed.
	 *
	 * Meant to work with unique pairs of quotes, so passing in "AAABAC" will break it.
	 *
	 * @param string $string A string to unquote
	 * @param string $quotes A list of unique quote pairs to unquote
	 * @param string $leftQuote Returns the left quote removed
	 * @return string
	 */
	public static function unquote(string $string, string $quotes = '\'\'""', string &$leftQuote = ''): string {
		if (strlen($string) < 2) {
			$leftQuote = '';
			return $string;
		}
		$q = substr($string, 0, 1);
		$leftOffset = strpos($quotes, $q);
		if ($leftOffset === false) {
			$leftQuote = '';
			return $string;
		}
		$rightQuoteCharacter = $quotes[$leftOffset + 1];
		if (substr($string, -1) === $rightQuoteCharacter) {
			$leftQuote = $quotes[$leftOffset];
			return substr($string, 1, -1);
		}
		return $string;
	}

	/**
	 * Breaks a string in half at a given delimiter, and returns default values if delimiter is not found.
	 *
	 * Usage is generally:
	 *
	 *    `list($table, $field) = pair($thing, ".", $default_table, $thing);`
	 *
	 * @param string $a A string to parse into a pair
	 * @param string $delim The delimiter to break the string apart
	 * @param string $left The default left value if delimiter is not found
	 * @param string $right The default right value if delimiter is not found
	 * @param string $includeDelimiter If "left" includes the delimiter in the left value, if "right" includes the
	 *            delimiter in the right value Any other value the delimiter is stripped from the results
	 * @return array A size 2 array containing the left and right portions of the pair
	 */
	public static function pair(string $a, string $delim = '.', string $left = '', string $right = '', string $includeDelimiter = ''): array {
		$n = strpos($a, $delim);
		$delim_len = strlen($delim);
		return ($n === false) ? [
			$left, $right,
		] : [
			substr($a, 0, $n + ($includeDelimiter === 'left' ? $delim_len : 0)),
			substr($a, $n + ($includeDelimiter === 'right' ? 0 : $delim_len)),
		];
	}

	/**
	 * Same as pair, but does a reverse search for the delimiter
	 *
	 * @param string $a
	 *            A string to parse into a pair
	 * @param string $delim
	 *            The delimiter to break the string apart
	 * @param string $left
	 *            The default left value if delimiter is not found
	 * @param string $right
	 *            The default right value if delimiter is not found
	 * @param string $include_delimiter
	 *            If "left" includes the delimiter in the left value
	 *            If "right" includes the delimiter in the right value
	 *          Any other value the delimiter is stripped from the results
	 * @return array A size 2 array containing the left and right portions of the pair
	 * @see pair
	 */
	public static function reversePair(string $a, string $delim = '.', string $left = '', string $right = '', string $include_delimiter = ''): array {
		$n = strrpos($a, $delim);
		$delim_len = strlen($delim);
		return ($n === false) ? [
			$left, $right,
		] : [
			substr($a, 0, $n + ($include_delimiter === 'left' ? $delim_len : 0)),
			substr($a, $n + ($include_delimiter === 'right' ? 0 : $delim_len)),
		];
	}

	/**
	 * Generic function to create paths correctly. Note that any double-separators are removed and converted to single-slashes so
	 * this is unsuitable for use with URLs. Use glue() instead.
	 *
	 * @param string $separator Token used to divide path
	 * @param array $mixed List of path items, or array of path items to concatenate
	 * @return string with a properly formatted path
	 * @see glue
	 * @see Host
	 * @inline_test self::joinArray("/", ["", "", ""]) === "/"
	 * @inline_test self::joinArray("/", ["", null, false]) === "/"
	 * @inline_test self::joinArray("/", ["", "", "", null, false, "a", "b"]) === "/a/b"
	 */
	public static function joinArray(string $separator, array $mixed): string {
		$r = array_shift($mixed);
		if (is_array($r)) {
			$r = self::joinArray($separator, $r);
		} elseif (!is_string($r)) {
			$r = '';
		}
		foreach ($mixed as $p) {
			if ($p === null) {
				continue;
			}
			if (is_array($p)) {
				$p = self::joinArray($separator, $p);
			}
			if (is_string($p)) {
				$r .= ((substr($r, -1) === $separator || substr($p, 0, 1) === $separator)) ? $p : $separator . $p;
			}
		}
		$separator_quoted = preg_quote($separator);
		return preg_replace("|$separator_quoted$separator_quoted+|", $separator, $r);
	}

	/**
	 * Simplistic case conversion for strings to match when mapping words
	 *
	 * @param string $string
	 * @param string $pattern
	 * @return string
	 * @see StringTools_Test::test_caseMatch()
	 */
	public static function caseMatch(string $string, string $pattern): string {
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
	 * @param ?string $default
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
	 * @param ?string $default
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
		return Types::toBool($value, $default);
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
	 * Capitalize words in a sentence -> Capitalize Words In A Sentence.
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
	 *            Case-insensitive comparison
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
	 * @param array|string $haystack
	 *            String to search
	 * @param array|string $needle
	 *            String to find
	 * @param bool $case_insensitive Case insensitive comparison
	 * @return bool Whether the haystack contains the needle
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
	 * @param bool $case_insensitive Case insensitive comparison
	 * @return bool
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
	 * Remove prefix from a string (if found at start of a string)
	 *
	 * @param string|array $string
	 * @param string|array $prefix A string or an array of strings to removePrefix. First matched string is used to
	 *            removePrefix the string.
	 * @param bool $case_insensitive
	 * @return string|array
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
	 * Return the shortest section of $string which also matches $match
	 *
	 * @param string $string
	 * @param string $match
	 * @return string
	 *
	 * @see StringTools_Test::test_prefixMatch()
	 */
	public static function prefixMatch(string $string, string $match): string {
		$stringParts = str_split($string);
		$matchParts = str_split($match);
		$matched = [];
		foreach ($stringParts as $i => $char) {
			if (($matchParts[$i] ?? null) !== $char) {
				break;
			}
			$matched[] = $char;
		}
		return implode('', $matched);
	}

	/**
	 * Remove suffixes from string values (remove a suffix iff found at end of a string)
	 *
	 * @param string|array $string
	 * @param mixed $suffixes
	 *            A string or an array of strings to removeSuffix. First matched string is used to
	 *            removeSuffix the string.
	 * @param bool $case_insensitive
	 * @return string|array
	 */
	public static function removeSuffix(string|array $string, array|string $suffixes, bool $case_insensitive = false): string|array {
		if (is_array($string)) {
			$result = [];
			foreach ($string as $k => $v) {
				$result[$k] = self::removeSuffix($v, $suffixes, $case_insensitive);
			}
			return $result;
		} elseif (is_array($suffixes)) {
			foreach ($suffixes as $suffix) {
				$new_string = self::removeSuffix($string, $suffix, $case_insensitive);
				if ($new_string !== $string) {
					return $new_string;
				}
			}
			return $string;
		} else {
			return self::ends($string, $suffixes, $case_insensitive) ? substr($string, 0, -strlen($suffixes)) : $string;
		}
	}

	/**
	 * Return whether a string is UTF16
	 * Based on presence of BOM
	 *
	 * @param string $str
	 * @param bool $be
	 * @return boolean
	 */
	public static function isUTF16(string $str, bool &$be = false): bool {
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
	public static function isASCII(string $str): bool {
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
	public static function isUTF8(string $str): bool {
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
	 * @param ?bool $default The default value to return if all rules are parsed and nothing matches
	 *
	 * @return ?bool
	 */
	public static function filter(string $string, array $rules, bool $default = null): ?bool {
		foreach ($rules as $pattern => $result) {
			$result = Types::toBool($result);
			if (is_string($pattern)) {
				if (preg_match($pattern, $string)) {
					return $result;
				}
			} else {
				return $result;
			}
		}
		return $default;
	}

	/**
	 * Replace first occurrence of a strings in another string
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
	public static function replaceFirst(string $search, string $replace, string $content): string {
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
	 * @param int $length
	 * @param string $dot_dot_dot
	 * @return string
	 */
	public static function ellipsisWord(string $text, int $length = 20, string $dot_dot_dot = ' ...'): string {
		if ($length < 0) {
			return $text;
		}
		if (StringTools::length($text) <= $length) {
			return $text;
		}
		$text = StringTools::substring($text, 0, $length);
		$off = 0;
		$aa = [
			' ', "\n", "\t",
		];
		$letters = StringTools::split($text);
		if (count($letters) >= 1) {
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
		return StringTools::substring($text, 0, $off) . $dot_dot_dot;
	}

	/**
	 * Pad a string with zeros up to the length specified.
	 *
	 * @param int|string $number Number to pad
	 * @param int $length Number of characters to pad
	 * @return string
	 */
	public static function zeroPad(int|string $number, int $length = 2): string {
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
	 * @param string $replace
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
	 * Split a multibyte string into characters/glyphs
	 *
	 * If the optional split_length parameter is specified, the returned array will be broken down
	 * into chunks with each being split_length in length, otherwise each chunk will be one
	 * character in length.
	 *
	 * FALSE is returned if split_length is less than 1. If the split_length length exceeds the
	 * length of string, the entire string is returned as the first (and only) array element.
	 *
	 * @param string $string
	 * @param int $split_length
	 * @param string $encoding
	 * @return array
	 */
	public static function split(string $string, int $split_length = 1, string $encoding = 'UTF-8'): array {
		if ($split_length < 1) {
			$split_length = 1;
		}
		$ret = [];
		$len = self::length($string, $encoding);
		for ($i = 0; $i < $len; $i += $split_length) {
			$ret[] = self::substring($string, $i, $split_length, $encoding);
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
	 * @param string $cell
	 *            A value to write to a CSV file
	 * @return string A correctly quoted CSV value
	 */
	public static function csvQuote(string $cell): string {
		if ((str_contains($cell, '"')) || (str_contains($cell, ',')) || (str_contains($cell, "\n"))) {
			return '"' . str_replace('"', '""', $cell) . '"';
		}
		return $cell;
	}

	/**
	 * Quote a single CSV row
	 *
	 * @param array $row
	 * @return string
	 */
	public static function csvQuoteRow(array $row): string {
		$yy = [];
		foreach ($row as $col) {
			$yy[] = self::csvQuote($col);
		}
		return implode(',', $yy) . "\r\n";
	}

	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $rows
	 *            of arrays of strings
	 * @return string
	 */
	public static function csvQuoteRows(array $rows): string {
		$yy = '';
		foreach ($rows as $row) {
			$yy .= self::csvQuoteRow($row);
		}
		return $yy;
	}

	/**
	 * Converts camelCaseStringToConvert to camel_case_string_to_convert
	 * @param string $string
	 * @return string
	 */
	public static function fromCamelCase(string $string): string {
		return preg_replace_callback('/[A-Z]/', fn ($matches) => '_' . strtolower($matches[0]), $string);
	}

	/**
	 * Converts camel_case_string_to_convert to camelCaseStringToConvert
	 *
	 * @param string $string
	 * @return string
	 */
	public static function toCamelCase(string $string): string {
		$result = '';
		foreach (explode('_', $string) as $i => $token) {
			$result .= $i === 0 ? strtolower($token) : strtoupper($token[0]) . strtolower(substr($token, 1));
		}
		return $result;
	}

	/**
	 * Retrieve the length of a multibyte string
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
	public static function substring(string $string, int $start, int $length, string $encoding = 'UTF-8'): string {
		if ($encoding === '') {
			$encoding = mb_internal_encoding();
		}
		return mb_substr($string, $start, $length, $encoding);
	}

	/**
	 * MySQL, MariaDB
	 */
	public const SINGLE_QUOTE = '\'';

	/**
	 * MySQL, MariaDB
	 */
	public const BACKSLASH_SINGLE_QUOTE = '\\\'';

	/**
	 * Most SQL
	 */
	public const SEMICOLON_END_OF_STATEMENT = ';';

	/**
	 * Divide SQL commands into different distinct commands
	 *
	 * @param string $sqlScript
	 * @param string $quote
	 * @param string $sqlEscapedQuote
	 * @param string $endOfStatement
	 * @return array
	 */
	public static function splitSQLStatements(string $sqlScript, string $quote = self::SINGLE_QUOTE, string $sqlEscapedQuote = self::BACKSLASH_SINGLE_QUOTE, string $endOfStatement = self::SEMICOLON_END_OF_STATEMENT): array {
		$token = '*!@::@!*';
		$map = [$sqlEscapedQuote => $token];
		$inverseMap = [$token => $sqlEscapedQuote];
		// Convert our string to make pattern matching easier
		$sqlScript = strtr($sqlScript, $map);
		$index = 0;
		$matches = [];
		$pattern = '/' . $quote . '[^' . $quote . ']*' . $quote . '/';
		while (preg_match($pattern, $sqlScript, $matches) !== 0) {
			[$from] = $matches;
			$to = chr(1) . '{' . $index . '}' . chr(2);
			$index++;
			// Map BACK to the original string, not the munged one
			$inverseMap[$to] = $from;
			$sqlScript = strtr($sqlScript, [
				$from => $to,
			]);
		}
		// Split on end of line character and remove blank lines
		$sqlStatements = array_filter(ArrayTools::listTrimClean(explode($endOfStatement, $sqlScript . $endOfStatement)));
		// Convert everything back to what it is supposed to be
		return Types::replaceSubstrings($sqlStatements, $inverseMap);
	}
}
