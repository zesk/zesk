<?php declare(strict_types=1);
namespace zesk;

class StringTools_Test extends Test_Unit {
	public function test_begins(): void {
		$haystack = null;
		$needle = null;
		$lower = false;
		StringTools::begins($haystack, $needle, $lower);
	}

	public function test_capitalize(): void {
		$phrase = null;
		StringTools::capitalize($phrase);
	}

	public function test_case_match(): void {
		$string = null;
		$pattern = null;
		StringTools::case_match($string, $pattern);
	}

	public function test_ellipsis_word(): void {
		$s = null;
		$n = 20;
		$dot_dot_dot = "...";
		StringTools::ellipsis_word($s, $n, $dot_dot_dot);
	}

	public function test_ends(): void {
		$haystack = null;
		$needle = null;
		$lower = false;
		StringTools::ends($haystack, $needle, $lower);
	}

	public function test_from_bool(): void {
		$bool = null;
		StringTools::from_bool($bool);
	}

	public function test_is_ascii(): void {
		$str = "string";
		$this->assert(StringTools::is_ascii($str));
		$str = chr(255) . chr(254) . "Hello";
		$this->assert(!StringTools::is_ascii($str));
	}

	public function test_is_utf16(): void {
		$str = null;
		$be = null;
		StringTools::is_utf16($str, $be);
	}

	public function test_wrap(): void {
		$phrase = null;
		HTML::wrap($phrase);

		$this->assert(HTML::wrap('This is a [simple] example', '<strong>[]</strong>') === 'This is a <strong>simple</strong> example', "'" . HTML::wrap('This is a [simple] example', '<strong>[]</strong>') . "' === 'This is a <strong>simple</strong> example'");

		$this->assert(HTML::wrap('This is a [1:simple] example', '<strong>[]</strong>') === 'This is a simple example', HTML::wrap('This is a [1:simple] example', '<strong>[]</strong>') . " === 'This is a simple example'");

		$this->assert(HTML::wrap('This is an example with [two] [items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <strong>two</strong> <em>items</em> example');

		$this->assert(HTML::wrap('This is an example with [two] [0:items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <strong>two</strong> <strong>items</strong> example');

		$this->assert(HTML::wrap('This is an example with [1:two] [items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <em>two</em> <em>items</em> example', HTML::wrap('This is an example with [1:two] [items] example', '<strong>[]</strong>', '<em>[]</em>') . ' === This is an example with <em>two</em> <em>items</em> example');

		$this->assert(HTML::wrap('This is an example with [1:two] [1:items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <em>two</em> <em>items</em> example', HTML::wrap('This is an example with [1:two] [1:items] example', '<strong>[]</strong>', '<em>[]</em>') . ' === This is an example with <em>two</em> <em>items</em> example');

		$this->assert(HTML::wrap('Nested example with [outernest [nest0] [nest1]] example', '<0>[]</0>', '<1>[]</1>', '<2>[]</2>') === 'Nested example with <2>outernest <0>nest0</0> <1>nest1</1></2> example', HTML::wrap('Nested example with [outernest [nest0] [nest1]] example', '<0>[]</0>', '<1>[]</1>', '<2>[]</2>') . ' === Nested example with <2>outernest <0>nest0</0> <1>nest1</1></2> example');
	}

	public function test_is_utf8(): void {
		$test_dir = $this->application->zesk_home('test/test-data');

		$files = [
			"utf16-le-no-bom.data" => false,
			"utf16-no-bom.data" => false,
			"iso-latin-1.data" => true,
			"gb-18030.data" => true,
			"utf16-le.data" => false,
			"iso-latin-9.data" => true,
			"utf16.data" => false,
		];
		$str = null;
		$this->assert(StringTools::is_utf8('') === true);
		$this->assert(StringTools::is_utf8('????, ???') === true);
		$this->assert(StringTools::is_utf8('????, ???') === true);
		foreach ($files as $f => $isutf8) {
			$content = file_get_contents(path($test_dir, $f));
			echo "Testing file $f\n";
			Debug::output(urlencode($content));
			echo "\n--END--\n";
			$this->assert(StringTools::is_utf8($content) === $isutf8);
		}
	}

	public function test_left(): void {
		$str = null;
		$find = null;
		$default = null;
		StringTools::left($str, $find, $default);
	}

	public function test_pair(): void {
		$string = null;
		$delim = '.';
		$left = null;
		$right = null;
		StringTools::pair($string, $delim, $left, $right);
	}

	public function test_pairr(): void {
		$string = null;
		$delim = '.';
		$left = null;
		$right = null;
		StringTools::pairr($string, $delim, $left, $right);
	}

	public function test_replace_first(): void {
		$search = "is";
		$replace = "at";
		$content = "This is a test";
		$this->assert(StringTools::replace_first($search, $replace, $content) === "That is a test");
	}

	public function test_right(): void {
		$str = null;
		$find = null;
		$default = null;
		StringTools::right($str, $find, $default);
	}

	public function test_rleft(): void {
		$str = null;
		$find = null;
		$default = null;
		StringTools::rleft($str, $find, $default);
	}

	public function test_rright(): void {
		$str = null;
		$find = null;
		$default = null;
		StringTools::rright($str, $find, $default);
	}

	public function test_to_bool(): void {
		$value = null;
		$default = false;
		StringTools::to_bool($value, $default);
		$this->assert(StringTools::to_bool(true, null) === true);
		$this->assert(StringTools::to_bool("t", null) === true);
		$this->assert(StringTools::to_bool("T", null) === true);
		$this->assert(StringTools::to_bool("y", null) === true);
		$this->assert(StringTools::to_bool("Y", null) === true);
		$this->assert(StringTools::to_bool("Yes", null) === true);
		$this->assert(StringTools::to_bool("yES", null) === true);
		$this->assert(StringTools::to_bool("oN", null) === true);
		$this->assert(StringTools::to_bool("on", null) === true);
		$this->assert(StringTools::to_bool("enabled", null) === true);
		$this->assert(StringTools::to_bool("trUE", null) === true);
		$this->assert(StringTools::to_bool("true", null) === true);

		$this->assert(StringTools::to_bool("f", null) === false);
		$this->assert(StringTools::to_bool("F", null) === false);
		$this->assert(StringTools::to_bool("n", null) === false);
		$this->assert(StringTools::to_bool("N", null) === false);
		$this->assert(StringTools::to_bool("no", null) === false);
		$this->assert(StringTools::to_bool("NO", null) === false);
		$this->assert(StringTools::to_bool("OFF", null) === false);
		$this->assert(StringTools::to_bool("off", null) === false);
		$this->assert(StringTools::to_bool("disabled", null) === false);
		$this->assert(StringTools::to_bool("DISABLED", null) === false);
		$this->assert(StringTools::to_bool("false", null) === false);
		$this->assert(StringTools::to_bool("null", null) === false);
		$this->assert(StringTools::to_bool("", null) === false);

		$this->assert(StringTools::to_bool(0, null) === null);
		$this->assert(StringTools::to_bool("0", null) === null);

		$this->assert(StringTools::to_bool(1, null) === null);
		$this->assert(StringTools::to_bool("1", null) === null);

		$this->assert(StringTools::to_bool("01", null) === null);
		$this->assert(StringTools::to_bool([], null) === null);
		$this->assert(StringTools::to_bool(new \stdClass(), null) === null);
	}

	public function test_unprefix(): void {
		$string = null;
		$prefix = null;
		StringTools::unprefix($string, $prefix);
	}

	public function test_unsuffix(): void {
		$string = null;
		$suffix = null;
		StringTools::unsuffix($string, $suffix);
	}

	public function test_zero_pad(): void {
		$s = null;
		StringTools::zero_pad($s);
		$this->assert_equal(StringTools::zero_pad('0'), '00');
		$this->assert_equal(StringTools::zero_pad('00'), '00');

		$this->assert_equal(StringTools::zero_pad('1'), '01');
		$this->assert_equal(StringTools::zero_pad('01'), '01');
		$this->assert_equal(StringTools::zero_pad('xx', 4), '00xx');
	}

	public function test_lalign(): void {
		$text = null;
		$n = -1;
		$pad = " ";
		$_trim = false;
		Text::lalign($text, $n, $pad, $_trim);
	}

	public function test_ralign(): void {
		$text = null;
		$n = -1;
		$pad = " ";
		$_trim = false;
		Text::ralign($text, $n, $pad, $_trim);
	}

	public function test_filter(): void {
		$name = null;
		$default = true;
		$this->assert(StringTools::filter($name, [], true) === true);
		$this->assert(StringTools::filter($name, [], false) === false);
		$tests = [
			[
				'foo.php',
				[
					'/.*\.php$/' => true,
				],
				null,
				true,
			],
			[
				'foo.php.no',
				[
					'/.*\.php$/' => true,
				],
				null,
				null,
			],
			[
				'user/.svn/',
				[
					'/\.svn/' => false,
					true,
				],
				null,
				false,
			],
			[
				'code/split-testing/php/.cvsignore',
				[
					'/php/' => false,
				],
				true,
				false,
			],
		];
		foreach ($tests as $index => $test) {
			[$name, $rules, $default, $result] = $test;
			$this->assert_equal(StringTools::filter($name, $rules, $default), $result, "Test #$index failed: $name");
		}
	}

	public function test_substr(): void {
		// Never knew this'
		$foo = "OK,";
		$result = substr($foo, 3);
		if (PHP_VERSION_ID > 0o70000) {
			// Fixed in 7.0
			$this->assert_equal(gettype($result), "string");
			$this->assert_equal($result, "");
		} else {
			$this->assert_equal(gettype($result), "boolean");
			$this->assert_equal($result, false);
		}
	}

	public function test_replace_first1(): void {
		$this->assert(StringTools::replace_first("a", "b", "abracadabra") === "bbracadabra");
		$this->assert(StringTools::replace_first("bra", "strap", "abracadabra") === "astrapcadabra");
	}
}
