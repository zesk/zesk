<?php
declare(strict_types=1);

namespace zesk;

class StringTools_Test extends Test_Unit {
	/**
	 * @return array[]
	 */
	public function begins_data(): array {
		return [
			["food", "foo", false, true],
			["food", "Foo", true, true],
			["Food", "Foo", true, true],
			["Food", "foo", true, true],
			["Food", "foo", false, false],
		];
	}

	/**
	 * @return void
	 * @dataProvider begins_data
	 */
	public function test_begins(string $haystack, string $needle, bool $lower, bool $expected): void {
		$this->assertEquals($expected, StringTools::begins($haystack, $needle, $lower), "StringTools::begins(\"$haystack\", \"$needle\", " . to_text($lower) . ")");
	}

	public function capitalize_data() {
		return [
			["", ""],
			["hello", "Hello", ],
			["I WOULD LIKE SOME HELP", "I Would Like Some Help", ],
			["a rather fun title to have for the new ages", "A Rather Fun Title To Have For The New Ages", ],
		];
	}

	/**
	 * @return void
	 * @dataProvider capitalize_data
	 */
	public function test_capitalize($phrase, $expected): void {
		$this->assertEquals($expected, StringTools::capitalize($phrase));
	}

	/**
	 * @return array
	 */
	public function to_camel_data(): array {
		return [
			["long_ass_string", "longAssString"],
			["_long_ass_string", "LongAssString"],
		];
	}

	/**
	 * @return void
	 * @dataProvider to_camel_data
	 */
	public function test_to_camel_case($test, $expected): void {
		$this->assertEquals($expected, StringTools::to_camel_case($test));
	}

	/**
	 * @return array
	 */
	public function from_camel_data(): array {
		return [
			["longAssString", "long_ass_string"],
			["LongAssString", "_long_ass_string"],
		];
	}

	/**
	 * @return void
	 * @dataProvider from_camel_data
	 */
	public function test_from_camel_case($test, $expected): void {
		$this->assertEquals($expected, StringTools::from_camel_case($test));
	}

	public function case_match_data(): array {
		return [
			["test", "John", "Test"],
			["Test", "John", "Test"],
			["test", "John", "Test"],
			["TeSt", "John", "Test"],
			["test", "JOHN", "TEST"],
			["Test", "JOHN", "TEST"],
			["test", "JOHN", "TEST"],
			["TeSt", "JOHn", "TEST"],
			["test", "john", "test"],
			["Test", "john", "test"],
			["test", "john", "test"],
			["TeSt", "john", "test"],
		];
	}

	/**
	 * @param string $string
	 * @param string $pattern
	 * @param string $expected
	 * @return void
	 * @dataProvider case_match_data
	 */
	public function test_case_match(string $string, string $pattern, string $expected): void {
		$this->assertEquals($expected, StringTools::case_match($string, $pattern));
	}

	public function ellipsis_word_data(): array {
		return [
			["A quick brown fox jumps over the lazy dog.", 1, "...", "A ..."],
			["A quick brown fox jumps over the lazy dog.", 5, "...", "A ..."],
			["A quick brown fox jumps over the lazy dog.", 10, "...", "A quick brown fox ..."],
			["A quick brown fox jumps over the lazy dog.", 15, "...", "A quick brown fox ..."],
			["A quick brown fox jumps over the lazy dog.", 20, "...", "A quick brown fox ..."],
			["A quick brown fox jumps over the lazy dog.", 25, "...", "A quick brown fox ..."],
			["A quick brown fox jumps over the lazy dog.", 30, "...", "A quick brown fox ..."],
		];
	}

	/**
	 * @return void
	 * @dataProvider ellipsis_word_data
	 */
	public function test_ellipsis_word($text, $number, $dot_dot_dot, $expected): void {
		$this->assertEquals($expected, StringTools::ellipsis_word($text, $number, $dot_dot_dot));
	}

	public function ends_data(): array {
		return [
			["a", "A", false, false],
			["A", "a", false, false],
			["a", "a", false, true],
			["A", "A", false, true],
			["a", "A", true, true],
			["A", "a", true, true],
			["a", "a", true, true],
			["A", "A", true, true],
			["1", "1", false, true],
			["1", "1", true, true],
			["could_be_setting_with_password", "_password", false, true],
			["_password ", "_password", false, false],
		];
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider ends_data
	 */
	public function test_ends(string $haystack, string $needle, bool $lower, bool $expected): void {
		$this->assertEquals($expected, StringTools::ends($haystack, $needle, $lower), "StringTools::ends(\"$haystack\", \"$needle\", " . to_text($lower) . ")");
	}

	/**
	 * @return array
	 */
	public function to_bool_data(): array {
		return [
			["", false, ],
			[null, false, ],
			[0, false, ],
			[0.0, false, ],
			[[], false, ],
			[false, false, ],
			['f', false, ],
			['0', false, ],
			['false', false, ],
			['FALSE', false, ],
			['off', false, ],
			[true, true, ],
			[1, false, ],
			[new \stdClass(), true, ],
			[[1], true, ],
			[[0], true, ],
		];
	}

	/**
	 * @return void
	 * @dataProvider to_bool_data
	 */
	public function test_from_bool(mixed $mixed, bool $expected): void {
		$this->assertEquals($expected ? 'true' : 'false', StringTools::from_bool($mixed));
	}

	/**
	 * @return void
	 * @dataProvider to_bool_data
	 */
	public function test_to_bool_data(mixed $mixed, bool $expected): void {
		$this->assertEquals($expected, StringTools::to_bool($mixed));
	}

	public function test_is_ascii(): void {
		$str = "string";
		$this->assert(StringTools::is_ascii($str));
		$str = chr(255) . chr(254) . "Hello";
		$this->assertFalse(StringTools::is_ascii($str));
	}

	public function is_utf16_data(): array {
		return [
			[chr(0xFF) . chr(0xFE) . "is this utf16", true, chr(0xFF)],
			[chr(0xFE) . chr(0xFF) . "is this utf16", true, chr(0xFE)],
			[chr(0xFF) . chr(0xFF) . "is this utf16", false, ""],
			[chr(0xFE) . chr(0xFE) . "is this utf16", false, ""],
			["", false, ""],
			["1", false, ""],
			["22", false, ""],
		];
	}

	/**
	 * @return void
	 * @dataProvider is_utf16_data
	 */
	public function test_is_utf16(string $content, bool $isUTF16, bool $beShouldBe): void {
		$be = false;
		$this->assertEquals($isUTF16, StringTools::is_utf16($content, $be));
		if ($isUTF16) {
			$this->assertEquals($beShouldBe, $be, "BOM matches");
		}
	}

	public function wrap_tests() {
		return [
			[['This is a [simple] example', '<strong>[]</strong>'], 'This is a <strong>simple</strong> example', ],
			[['This is a [1:simple] example', '<strong>[]</strong>'], 'This is a simple example', ],
			[
				['This is an example with [two] [items] example', '<strong>[]</strong>', '<em>[]</em>'],
				'This is an example with <strong>two</strong> <em>items</em> example',
			],
			[
				['This is an example with [two] [0:items] example', '<strong>[]</strong>', '<em>[]</em>'],
				'This is an example with <strong>two</strong> <strong>items</strong> example',
			],
			[
				['This is an example with [1:two] [items] example', '<strong>[]</strong>', '<em>[]</em>'],
				'This is an example with <em>two</em> <em>items</em> example',
			],

			[
				['This is an example with [1:two] [1:items] example', '<strong>[]</strong>', '<em>[]</em>'],
				'This is an example with <em>two</em> <em>items</em> example',
			],

			[
				['Nested example with [outernest [nest0] [nest1]] example', '<0>[]</0>', '<1>[]</1>', '<2>[]</2>'],
				'Nested example with <2>outernest <0>nest0</0> <1>nest1</1></2> example',
			],
		];
	}

	/**
	 * @param array $arguments
	 * @param string $expected
	 * @return void
	 * @dataProvider wrap_tests
	 */
	public function test_wrap(array $arguments, string $expected): void {
		$this->assertEquals(call_user_func_array([
			HTML::class,
			'wrap',
		], $arguments), $expected, "HTML::wrap ${arguments[0]} => $expected failed");
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

	public function left_data(): array {
		return [
			["haystack to find that thing", "that", "default", "haystack to find "],
			["haystack to find that thing", "that", null, "haystack to find "],
			["haystack to find that thing", "thot", "default", "default"],
			["haystack to find that thing", "thot", null, "haystack to find that thing"],
		];
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param mixed $default
	 * @param mixed $expected
	 * @return void
	 * @dataProvider left_data
	 */
	public function test_left(string $haystack, string $needle, mixed $default, mixed $expected): void {
		$this->assertEquals($expected, StringTools::left($haystack, $needle, $default));
	}

	public function pair_data(): array {
		return [
			["string", "delim", "left", "right", ["left", "right"]],
			["string", "r", "left", "right", ["st", "ing"]],
			["NAME=VALUE", "=", "left", "right", ["NAME", "VALUE"]],
			["=VALUE", "=", "left", "right", ["", "VALUE"]],
			["NAME=", "=", "left", "right", ["NAME", ""]],
			["NAME=", "!", "left", "right", ["left", "right"]],
			["NAME=VALUE=VALUE", "=", "left", "right", ["NAME", "VALUE=VALUE"]],
			["=VALUE=VALUE", "=", "left", "right", ["", "VALUE=VALUE"]],
			["NAME=NAME=", "=", "left", "right", ["NAME", "NAME="]],
			["NAME=NAME=", "!", "left", "right", ["left", "right"]],
		];
	}

	/**
	 * @param string $string
	 * @param string $delim
	 * @param string $left
	 * @param string $right
	 * @param array $expected
	 * @return void
	 * @dataProvider pair_data
	 */
	public function test_pair(string $string, string $delim, string $left, string $right, array $expected): void {
		$this->assertEquals($expected, StringTools::pair($string, $delim, $left, $right));
	}

	public function pairr_data(): array {
		return [
			["string", "delim", "left", "right", ["left", "right"]],
			["string", "r", "left", "right", ["st", "ing"]],
			["NAME=VALUE", "=", "left", "right", ["NAME", "VALUE"]],
			["=VALUE", "=", "left", "right", ["", "VALUE"]],
			["NAME=", "=", "left", "right", ["NAME", ""]],
			["NAME=", "!", "left", "right", ["left", "right"]],
			["NAME=VALUE=VALUE", "=", "left", "right", ["NAME=VALUE", "VALUE"]],
			["=VALUE=VALUE", "=", "left", "right", ["=VALUE", "VALUE"]],
			["NAME=NAME=", "=", "left", "right", ["NAME=NAME", ""]],
			["NAME=NAME=", "!", "left", "right", ["left", "right"]],
		];
	}

	/**
	 * @return void
	 * @dataProvider pairr_data
	 */
	public function test_pairr(string $string, string $delim, string $left, string $right, array $expected): void {
		$this->assertEquals($expected, StringTools::pairr($string, $delim, $left, $right));
	}

	public function replace_first_data(): array {
		return [
			["is", "at", "This is a test", "That is a test"],
			[
				"DOG",
				"CAT",
				"I have several DOG and I only like all DOGs",
				"I have several CAT and I only like all DOGs",
			],
		];
	}

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $content
	 * @param string $expected
	 * @return void
	 * @dataProvider replace_first_data
	 */
	public function test_replace_first(string $search, string $replace, string $content, string $expected): void {
		$this->assertEquals($expected, StringTools::replace_first($search, $replace, $content) === "That is a test");
	}

	public function right_data(): array {
		return [
			["NAME and VALUE", "and", "default", " VALUE", ],
			["NAME and VALUE", "and V", "default", "ALUE", ],
			["NAME and VALUE", " ", "default", " and VALUE", ],
			["NAME and VALUE", "D", "default", "default", ],
			["NAME and VALUE", "D", null, "NAME and VALUE", ],
		];
	}

	/**
	 * @param $haystack
	 * @param $needle
	 * @param $default
	 * @return void
	 * @dataProvider right_data
	 */
	public function test_right(string $haystack, string $needle, ?string $default, string $expected): void {
		$this->assertEquals($expected, StringTools::right($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public function rleft_data(): array {
		return [
			["NAME and VALUE", "and", "default", "NAME ", ],
			["NAME and VALUE", "and V", "default", "NAME ", ],
			["NAME and VALUE", " ", "default", "NAME and ", ],
			["NAME and VALUE", "D", "default", "default", ],
			["NAME and VALUE", "D", null, "NAME and VALUE", ],
		];
	}

	/**
	 * @dataProvider rleft_data
	 * @param string $haystack
	 * @param string $needle
	 * @param mixed $default
	 * @param mixed $expected
	 * @return void
	 */
	public function test_rleft(string $haystack, string $needle, ?string $default, string $expected): void {
		$this->assertEquals($expected, StringTools::rleft($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public function rright_data(): array {
		return [
			["NAME and VALUE", "and", "default", " VALUE", ],
			["NAME and VALUE", "and V", "default", "ALUE", ],
			["NAME and VALUE", " ", "default", " and VALUE", ],
			["NAME and VALUE", "D", "default", "default", ],
			["NAME and VALUE", "D", null, "NAME and VALUE", ],
		];
	}

	/**
	 * @dataProvider rright_data
	 * @param string $haystack
	 * @param string $needle
	 * @param string|null $default
	 * @param string $expected
	 * @return void
	 */
	public function test_rright(string $haystack, string $needle, ?string $default, string $expected): void {
		$this->assertEquals($expected, StringTools::rright($haystack, $needle, $default));
	}

	/**
	 * @param mixed $value
	 * @param bool $expected
	 * @return void
	 * @dataProvider to_bool_data_original
	 */
	public function test_to_bool(mixed $value, bool $expected): void {
		$this->assertEquals($expected, StringTools::to_bool($value));
	}

	public function to_bool_data_original() {
		return [
			[true, true],
			["t", true],
			["T", true],
			["y", true],
			["Y", true],
			["Yes", true],
			["yES", true],
			["oN", true],
			["on", true],
			["enabled", true],
			["trUE", true],
			["true", true],

			["f", false],
			["F", false],
			["n", false],
			["N", false],
			["no", false],
			["NO", false],
			["OFF", false],
			["off", false],
			["disabled", false],
			["DISABLED", false],
			["false", false],
			["null", false],
			["", false],

			[0, false],
			["0", false],

			[1, true],
			["1", true],

			["01", true],
			[[], false],
			[new \stdClass(), true],

		];
	}

	/**
	 * @return array[]
	 */
	public function unprefix_data(): array {
		return [
			["string", "str", false, "ing"],
			["string", "str", true, "ing"],

			["string", "Str", false, "string"],
			["string", "Str", true, "ing"],

			["String", "str", false, "String"],
			["String", "str", true, "ing"],

			["String", "Str", false, "ing"],
			["String", "Str", true, "ing"],
		];
	}

	/**
	 * @param string $string
	 * @param string $prefix
	 * @param string $expected
	 * @return void
	 * @dataProvider unprefix_data
	 */
	public function test_unprefix(string $string, string $prefix, bool $case_insensitive, string $expected): void {
		$this->assertEquals($expected, StringTools::unprefix($string, $prefix));
	}

	/**
	 * @return array[]
	 */
	public function unsuffix_data(): array {
		return [
			["string", "ing", false, "str"],
			["string", "ing", true, "str"],

			["string", "ING", false, "string"],
			["string", "ING", true, "Str"],

			["String", "ing", false, "Str"],
			["String", "ing", true, "Str"],

			["String", "Ing", false, "String"],
			["String", "Ing", true, "Str"],
		];
	}

	/**
	 * @param string $string
	 * @param string $prefix
	 * @param bool $case_insensitive
	 * @param string $expected
	 * @return void
	 * @dataProvider unsuffix_data
	 */
	public function test_unsuffix(string $string, string $prefix, bool $case_insensitive, string $expected): void {
		$this->assertEquals($expected, StringTools::unsuffix($string, $prefix));
	}

	public function zero_pad_data(): array {
		return [
			["0", 2, "00"],
			["00", 2, "00"],
			["1", 2, "01"],
			["01", 2, "01"],
			["0", 3, "000"],
			["00", 3, "000"],
			["1", 3, "001"],
			["01", 3, "001"],
			["xx", 4, "00xx"],
		];
	}

	/**
	 * @param string $string
	 * @param string $expected
	 * @return void
	 * @dataProvider zero_pad_data
	 */
	public function test_zero_pad(string $string, int $length, string $expected): void {
		$this->assertEquals($expected, StringTools::zero_pad($string, $length));
	}

	public function lalign_data(): array {
		return [
			["text", 10, "-", true, "text------"],
			["text ", 10, "-", true, "text -----"],
			["textificiation", 10, "-", false, "textificiation"],
			["textificiation", 10, "-", true, "textificia"],
		];
	}

	/**
	 * @param string $text
	 * @param int $length
	 * @param string $padding
	 * @param bool $trim
	 * @param string $expected
	 * @return void
	 * @dataProvider lalign_data
	 */
	public function test_lalign(string $text, int $length, string $padding, bool $trim, string $expected): void {
		$this->assertEquals($expected, Text::lalign($text, $length, $padding, $trim));
	}

	public function ralign_data(): array {
		return [
			["text", 10, "-", true, "------text"],
			["text ", 10, "-", true, "-----text "],
			["textificiation", 10, "-", false, "textificiation"],
			["textificiation", 10, "-", true, "textificia"],
		];
	}

	/**
	 * @param string $text
	 * @param int $length
	 * @param string $padding
	 * @param bool $trim
	 * @param string $expected
	 * @return void
	 * @dataProvider ralign_data
	 */
	public function test_ralign(string $text, int $length, string $padding, bool $trim, string $expected): void {
		$this->assertEquals($expected, Text::ralign($text, $length, $padding, $trim));
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
