<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/text.inc $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * @desc Text Manipulation
 */
namespace zesk;

/**
 * The text class deals with manipulation of text, specifically for output to a console or fixed-width font output, or email.
 *
 * @author kent
 */
class Text {
	/**
	 * Indent plain text
	 * 
	 * @param string $text
	 * @param number $indent_count
	 * @param boolean $trim_line_white
	 * @param string $indent_char
	 * @param string $newline
	 * @return string
	 */
	public static function indent($text, $indent_count = 1, $trim_line_white = false, $indent_char = "\t", $newline = "\n") {
		$lines = explode($newline, $text);
		$indent_string = is_string($indent_count) ? $indent_count : str_repeat($indent_char, $indent_count);
		foreach ($lines as $i => $line) {
			if ($trim_line_white) {
				$line = ltrim($line);
			}
			$lines[$i] = $indent_string . $line;
		}
		return implode($newline, $lines) . $newline;
	}
	
	/**
	 * Universally modify line break characters in a string to be a certain line break string.
	 *
	 * Can be used to convert to HTML breaks from C-style line breaks (\n)
	 * Handles both DOS and UNIX line break styles. May handle UNICODE? (Not sure)
	 *
	 * @return string
	 * @param string $string String to modify
	 * @param string $br String to use as a line break.
	 * @see str_replace()
	 */
	public static function set_line_breaks($string, $br = "\n") {
		$temp_char = '\x00\x10\x00';
		$string = str_replace("\r\n", $temp_char, $string);
		$string = str_replace("\r", $temp_char, $string);
		$string = str_replace("\n", $temp_char, $string);
		return str_replace($temp_char, $br, $string);
	}
	
	/**
	 * Format an array with labels and values
	 *
	 * @param array $fields An associative array to format
	 * @param string $padding Padding character
	 * @param string $prefix Prefix padding character per line
	 * @param string $suffix Label suffix
	 * @param string $line_end End of line character
	 * @return string Array formatted
	 */
	public static function format_array($fields, $padding = " ", $prefix = " ", $suffix = ": ", $line_end = "\n") {
		$n = 0;
		foreach ($fields as $k => $v) {
			$n = max(strlen($k), $n);
		}
		$result = "";
		foreach ($fields as $k => $v) {
			$line = "";
			if (strlen($k) < $n) {
				$line .= str_repeat($padding, $n - strlen($k));
			}
			if (is_object($v) && !method_exists($v, "__toString")) {
				continue;
			}
			if (is_array($v)) {
				// TODO Fix indent formatting
				$v = self::format_array($v, $padding, str_repeat($padding, $n), $suffix, $line_end);
			}
			$line .= $prefix . $k . $suffix . strval($v) . $line_end;
			$result .= $line;
		}
		return $result;
	}
	public static function lines_wrap($text, $prefix = "", $suffix = "", $first_prefix = null, $last_suffix = null) {
		if ($first_prefix === null) {
			$first_prefix = $prefix;
		}
		if ($last_suffix === null) {
			$last_suffix = $suffix;
		}
		return $first_prefix . implode("$suffix\n$prefix", explode("\n", $text)) . $last_suffix;
	}
	public static function fill($n, $pad = " ") {
		return substr(str_repeat($pad, $n), 0, $n);
	}
	private static function _align_helper($text, $n, $pad, &$fill, $_trim = false) {
		$fill = "";
		if ($n <= 0)
			return $text;
		$tlen = strlen($text);
		if ($tlen > $n) {
			if ($_trim) {
				return substr($text, 0, $n);
			}
			return $text;
		}
		// $tlen < $n
		$fill = self::fill($n - $tlen, $pad);
		return $text;
	}
	public static function ralign($text, $n = -1, $pad = " ", $_trim = false) {
		$fill = "";
		$text = self::_align_helper($text, $n, $pad, $fill, $_trim);
		return $fill . $text;
	}
	public static function lalign($text, $n = -1, $pad = " ", $_trim = false) {
		$fill = "";
		$text = self::_align_helper($text, $n, $pad, $fill, $_trim);
		return $text . $fill;
	}
	
	/**
	 * Delete the line comments in a string
	 *
	 * @param string $data Contents to remove line comments from
	 * @param string $line_comment The line comment prefix (e.g. #, or ', etc.)
	 * @param boolean $alone True if line comments should be along on their own line, otherwise, will trim line comments at the end of a line
	 * @return string The contents with line comments removed
	 */
	public static function remove_line_comments($data, $line_comment = "#", $alone = true) {
		$new_data = array();
		if ($alone) {
			$line_comment_len = strlen($line_comment);
			foreach (explode("\n", $data) as $line) {
				if (substr(ltrim($line), 0, $line_comment_len) == $line_comment) {
					continue;
				}
				$new_data[] = $line;
			}
		} else {
			foreach (explode("\n", $data) as $line) {
				$new_data[] = str::left($line, $line_comment, $line);
			}
		}
		return implode("\n", $new_data);
	}
	
	/**
	 * Remove C/Java/PHP/etc-style range comments
	 *
	 * @param string $text
	 * @param string $begin_comment Optional beginning string - is preg_quoted
	 * @param string $end_comment Optional ending string - is preg_quoted
	 * @return string
	 */
	public static function remove_range_comments($text, $begin_comment = "/*", $end_comment = "*/") {
		return preg_replace('#' . preg_quote($begin_comment) . '.*?' . preg_quote($end_comment) . '#s', '', $text);
	}
	public static function fill_pattern($pattern, $char_length) {
		$pattern_length = strlen($pattern);
		if ($pattern_length < $char_length) {
			$pattern = str_repeat($pattern, round($char_length / $pattern_length));
		}
		return substr($pattern, 0, $char_length);
	}
	public static function format_pairs($map, $prefix = "", $space = " ", $suffix = ": ", $br = "\n") {
		$n = 0;
		foreach (array_keys($map) as $k) {
			$n = max(strlen($k), $n);
		}
		$r = array();
		foreach ($map as $k => $v) {
			$r[] = $prefix . $k . self::fill_pattern($space, $n - strlen($k)) . $suffix . PHP::dump($v);
		}
		return implode($br, $r) . $br;
	}
	public static function trim_words($string, $wordCount) {
		$words = preg_split('/(\s+)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		$words = array_slice($words, 0, $wordCount * 2 - 1);
		return implode("", $words);
	}
	public static function words($string) {
		return count(preg_split('/(\s+)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE));
	}
	
	/**
	 * Generates a table like this:
	 *
	 * <code>
	 * +--------+-----+----+--------+
	 * | Token1 | Boo | A  | Poop   |
	 * +--------+-----+----+--------+
	 * | a      | Yo  | 1  | soft   |
	 * | b      | Yo  | 12 | soft   |
	 * | c      | Yo  | 14 | soft   |
	 * | d      | Yo  | 33 | soft   |
	 * | e      | Yo  | 89 | soft   |
	 * | f      | Yo  | 34 | softly |
	 * +--------+-----+----+--------+
	 * </code>
	 *
	 * from:
	 *
	 * <code>
	 * array(
	 * array("Token1" => "a", "Boo" => "Yo", "A" => "22", "Poop" => "soft"),
	 * array("Token1" => "b", "Boo" => "Yo", "A" => "22", "Poop" => "soft"),
	 * array("Token1" => "c", "Boo" => "Yo", "A" => "22", "Poop" => "soft"),
	 * ...
	 * array("Token1" => "f", "Boo" => "Yo", "A" => "22", "Poop" => "softly"),
	 * );
	 * </code>
	 * Sizes the columns as needed.
	 * The keys of the first row of the table is used to generate the headers.
	 *
	 * Handy for outputting SQL queries.
	 *
	 * Ignores numeric keys.
	 *
	 * @param array $table Table to convert to a text-output table
	 * @param string $prefix String to prefix every line with. Used for indenting
	 * @return string The resulting text table, with \n on each line.
	 */
	static function format_table($table, $prefix = "") {
		if (!is_array($table) || !isset($table[0]) || !is_array($table[0])) {
			return "";
		}
		$result = array();
		$allhs = array_keys($table[0]);
		$ws = array();
		$hs = array();
		foreach ($allhs as $h) {
			if (!is_numeric($h)) {
				$ws[] = strlen($h);
				$hs[] = $h;
			}
		}
		foreach ($table as $row) {
			foreach ($hs as $i => $h) {
				$ws[$i] = max(strlen($row[$h]), $ws[$i]);
			}
		}
		$line = array();
		foreach ($hs as $i => $h) {
			$line[] = str_repeat("-", $ws[$i]);
		}
		$divLine = "+-" . implode("-+-", $line) . "-+";
		$result[] = $divLine;
		
		$line = array();
		foreach ($hs as $i => $h) {
			$line[] = str_pad($h, $ws[$i]);
		}
		$result[] = "| " . implode(" | ", $line) . " |";
		$result[] = $divLine;
		foreach ($table as $row) {
			$line = array();
			foreach ($hs as $i => $h) {
				$line[] = str_pad($row[$h], $ws[$i]);
			}
			$result[] = "| " . implode(" | ", $line) . " |";
		}
		$result[] = $divLine;
		return $prefix . implode("\n$prefix", $result) . "\n";
	}
	
	/**
	 * Split a line where multiple characters may serve as a delimiter.
	 *
	 * Function Long Description.
	 *
	 * @param string $line Line to split
	 * @param string $num_columns Maximum number of columns to return
	 * @param string $delimiters Characters which may serve as a delimiter of a token.
	 * @return array The line split into $num_columns or fewer columns as an array.
	 * @see explode()
	 */
	private static function split_line($line, $num_columns = 99, $delimiters = " \t") {
		return explode($delimiters[0], preg_replace("/[" . preg_quote($delimiters) . "]+/", $delimiters[0], $line), $num_columns);
	}
	
	/**
	 * Parse a table output by many common UNIX and DOS commands.
	 *
	 * Parses a header line first, then tags non-empty lines after the header as an associative array
	 * of header name => value.
	 * Everything is trimmed of whitespace.
	 *
	 * @param string $content Multi-line table to parse.
	 * @param string $num_columns Max Number of columns expected.
	 * @param string $delimiters Delimiter characters
	 * @param string $newline Newline characters
	 * @return array An array of associative arrays representing the table. Can be passed to outputTable directly.
	 */
	static function parse_table($content, $num_columns, $delimiters = " \t", $newline = "\n") {
		$lines = explode($newline, $content);
		$hh = false;
		while (($line = array_shift($lines)) !== null) {
			$line = trim($line);
			if (!empty($line)) {
				$hh = $line;
				break;
			}
		}
		if (!$hh) {
			return null;
		}
		$hh = self::split_line($hh, $num_columns);
		if (count($hh) !== $num_columns) {
			return false;
		}
		$results = array();
		while (($line = array_shift($lines)) !== null) {
			if (empty($line)) {
				break;
			}
			$ff = self::split_line($line, $num_columns);
			if (count($hh) !== $num_columns) {
				continue;
			}
			$row = array();
			foreach ($hh as $i => $h) {
				$row[$h] = $ff[$i];
			}
			$results[] = $row;
		}
		return $results;
	}
	
	/**
	 * Returns the number of words delimited by spaces found in string.
	 *
	 * @param string $string A string to count words in
	 * @param integer $limit Max words to count
	 * @return integer The number of words found
	 */
	public static function count_words($string, $limit = -1) {
		return count(preg_split('/\s+/', trim($string), $limit));
	}
}
