<?php
use zesk\Text;

class test_string extends Test_Unit {

	function test_begins() {
		$haystack = null;
		$needle = null;
		$lower = false;
		str::begins($haystack, $needle, $lower);
	}

	function test_capitalize() {
		$phrase = null;
		str::capitalize($phrase);
	}

	function test_case_match() {
		$string = null;
		$pattern = null;
		str::case_match($string, $pattern);
	}

	function test_ellipsis_word() {
		$s = null;
		$n = 20;
		$dot_dot_dot = "...";
		str::ellipsis_word($s, $n, $dot_dot_dot);
	}

	function test_ends() {
		$haystack = null;
		$needle = null;
		$lower = false;
		str::ends($haystack, $needle, $lower);
	}

	function test_from_bool() {
		$bool = null;
		str::from_bool($bool);
	}

	function test_is_ascii() {
		$str = "string";
		$this->assert(str::is_ascii($str));
		$str = chr(255) . chr(254) . "Hello";
		$this->assert(!str::is_ascii($str));
	}

	function test_is_utf16() {
		$str = null;
		$be = null;
		str::is_utf16($str, $be);
	}

	function test_is_utf8() {
		$test_dir = zesk::root('test/test-data');

		$files = array(
			"utf16-le-no-bom.data" => false,
			"utf16-no-bom.data" => false,
			"iso-latin-1.data" => true,
			"gb-18030.data" => true,
			"utf16-le.data" => false,
			"iso-latin-9.data" => true,
			"utf16.data" => false
		);
		$str = null;
		$this->assert(str::is_utf8('') === true);
		$this->assert(str::is_utf8('????, ???') === true);
		$this->assert(str::is_utf8('????, ???') === true);
		foreach ($files as $f => $isutf8) {
			$content = file_get_contents(path($test_dir, $f));
			echo "Testing file $f\n";
			debug::output(urlencode($content));
			echo "\n--END--\n";
			$this->assert(str::is_utf8($content) === $isutf8);
		}
	}

	function test_left() {
		$str = null;
		$find = null;
		$default = null;
		str::left($str, $find, $default);
	}

	function test_pair() {
		$string = null;
		$delim = '.';
		$left = null;
		$right = null;
		str::pair($string, $delim, $left, $right);
	}

	function test_pairr() {
		$string = null;
		$delim = '.';
		$left = null;
		$right = null;
		str::pairr($string, $delim, $left, $right);
	}

	function test_replace_first() {
		$search = "is";
		$replace = "at";
		$content = "This is a test";
		$this->assert(str::replace_first($search, $replace, $content) === "That is a test");
	}

	function test_right() {
		$str = null;
		$find = null;
		$default = null;
		str::right($str, $find, $default);
	}

	function test_rleft() {
		$str = null;
		$find = null;
		$default = null;
		str::rleft($str, $find, $default);
	}

	function test_rright() {
		$str = null;
		$find = null;
		$default = null;
		str::rright($str, $find, $default);
	}

	function test_to_bool() {
		$value = null;
		$default = false;
		str::to_bool($value, $default);
		$this->assert(str::to_bool(true, null) === true);
		$this->assert(str::to_bool("t", null) === true);
		$this->assert(str::to_bool("T", null) === true);
		$this->assert(str::to_bool("y", null) === true);
		$this->assert(str::to_bool("Y", null) === true);
		$this->assert(str::to_bool("Yes", null) === true);
		$this->assert(str::to_bool("yES", null) === true);
		$this->assert(str::to_bool("oN", null) === true);
		$this->assert(str::to_bool("on", null) === true);
		$this->assert(str::to_bool("enabled", null) === true);
		$this->assert(str::to_bool("trUE", null) === true);
		$this->assert(str::to_bool("true", null) === true);

		$this->assert(str::to_bool("f", null) === false);
		$this->assert(str::to_bool("F", null) === false);
		$this->assert(str::to_bool("n", null) === false);
		$this->assert(str::to_bool("N", null) === false);
		$this->assert(str::to_bool("no", null) === false);
		$this->assert(str::to_bool("NO", null) === false);
		$this->assert(str::to_bool("OFF", null) === false);
		$this->assert(str::to_bool("off", null) === false);
		$this->assert(str::to_bool("disabled", null) === false);
		$this->assert(str::to_bool("DISABLED", null) === false);
		$this->assert(str::to_bool("false", null) === false);
		$this->assert(str::to_bool("null", null) === false);
		$this->assert(str::to_bool("", null) === false);

		$this->assert(str::to_bool(0, null) === null);
		$this->assert(str::to_bool("0", null) === null);

		$this->assert(str::to_bool(1, null) === null);
		$this->assert(str::to_bool("1", null) === null);

		$this->assert(str::to_bool("01", null) === null);
		$this->assert(str::to_bool(array(), null) === null);
		$this->assert(str::to_bool(new stdClass(), null) === null);
	}

	function test_unprefix() {
		$string = null;
		$prefix = null;
		str::unprefix($string, $prefix);
	}

	function test_unsuffix() {
		$string = null;
		$suffix = null;
		str::unsuffix($string, $suffix);
	}

	function test_zero_pad() {
		$s = null;
		str::zero_pad($s);
		$this->assert("str::zero_pad('0') == '00'");
		$this->assert("str::zero_pad('00') == '00'");

		$this->assert("str::zero_pad('1') == '01'");
		$this->assert("str::zero_pad('01') == '01'");
		$this->assert("str::zero_pad('xx',4) == '00xx'");
	}

	function test_lalign() {
		$text = null;
		$n = -1;
		$pad = " ";
		$_trim = false;
		Text::lalign($text, $n, $pad, $_trim);
	}

	function test_ralign() {
		$text = null;
		$n = -1;
		$pad = " ";
		$_trim = false;
		Text::ralign($text, $n, $pad, $_trim);
	}

	function test_filter() {
		$name = null;
		$in_pattern = null;
		$ex_pattern = null;
		$default = true;
		$this->assert(str::filter($name, $in_pattern, $ex_pattern, true) === true);
		$this->assert(str::filter($name, $in_pattern, $ex_pattern, false) === false);
		$tests = array(
			array(
				'foo.php',
				'/.*\.php$/',
				false,
				true,
				true
			),
			array(
				'foo.php',
				'/.*\.php$/',
				false,
				false,
				true
			),
			array(
				'user/.svn/',
				false,
				'/\.svn/',
				false,
				false
			),
			array(
				'code/split-testing/php/.cvsignore',
				true,
				'/php/',
				true,
				false
			)
		);
		foreach ($tests as $test) {
			list($name, $in_pattern, $ex_pattern, $default, $result) = $test;
			$this->assert(str::filter($name, $in_pattern, $ex_pattern, $default) === $result, "str::filter($name, $in_pattern, $ex_pattern," . str::from_bool($default) . ") === " . str::from_bool($result));
		}
	}

	function test_substr() {
		// Never knew this
		$foo = "OK,";
		$result = substr($foo, 3);
		$this->assert(gettype($result) === "boolean");
		$this->assert($result === false);
	}

	function test_replace_first1() {
		$this->assert(str::replace_first("a", "b", "abracadabra") === "bbracadabra");
		$this->assert(str::replace_first("bra", "strap", "abracadabra") === "astrapcadabra");
	}
}

