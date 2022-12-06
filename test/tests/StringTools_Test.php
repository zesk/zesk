<?php
declare(strict_types=1);

namespace zesk;

class StringTools_Test extends UnitTest {
	/**
	 * @return array[]
	 */
	public function data_begins(): array {
		return [
			['food', '', false, true],
			['food', '', true, true],
			['food', 'foo', false, true],
			['food', ['foo'], false, true],
			[['food'], 'foo', false, true],
			[['food'], ['foo'], false, true],
			['food', 'foo', false, true],
			['food', 'Foo', true, true],
			['Food', 'Foo', true, true],
			['Food', 'foo', true, true],
			['Food', 'foo', false, false],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_begins
	 */
	public function test_begins(string|array $haystack, string|array $needle, bool $lower, bool $expected): void {
		$this->assertEquals($expected, StringTools::begins($haystack, $needle, $lower));
	}

	public function capitalize_data() {
		return [
			['', ''],
			['hello', 'Hello', ],
			['I WOULD LIKE SOME HELP', 'I Would Like Some Help', ],
			['a rather fun title to have for the new ages', 'A Rather Fun Title To Have For The New Ages', ],
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
			['long_ass_string', 'longAssString'],
			['_long_ass_string', 'LongAssString'],
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
			['longAssString', 'long_ass_string'],
			['LongAssString', '_long_ass_string'],
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
			['test', 'John', 'Test'],
			['Test', 'John', 'Test'],
			['test', 'John', 'Test'],
			['TeSt', 'John', 'Test'],
			['test', 'JOHN', 'TEST'],
			['Test', 'JOHN', 'TEST'],
			['test', 'JOHN', 'TEST'],
			['TeSt', 'JOHn', 'TEST'],
			['test', 'john', 'test'],
			['Test', 'john', 'test'],
			['test', 'john', 'test'],
			['TeSt', 'john', 'test'],
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
		$n = 20;
		$random_string = $this->randomHex($n);
		return [
			[$random_string, -1, '...', $random_string],
			[$random_string, $n - 1, '!', substr($random_string, 0, -1) . '!'],
			[$random_string, $n, '...', $random_string],
			[$random_string, $n + 1, '...', $random_string],
			['A quick brown fox jumps over the lazy dog.', 1, '...', 'A...'],
			['A quick brown fox jumps over the lazy dog.', 5, '...', 'A...'],
			['A quick brown fox jumps over the lazy dog.', 10, '...', 'A quick...'],
			['A quick brown fox jumps over the lazy dog.', 15, '...', 'A quick brown...'],
			['A quick brown fox jumps over the lazy dog.', 20, '...', 'A quick brown fox...'],
			['A quick brown fox jumps over the lazy dog.', 25, '...', 'A quick brown fox jumps...'],
			['A quick brown fox jumps over the lazy dog.', 30, '...', 'A quick brown fox jumps over...'],
		];
	}

	public function data_replaceTabs(): array {
		return [
			['    Hello', "\tHello", -1, ' '],
			['A+++Hello', "A\tHello", -1, '+'],
			['AB??Hello', "AB\tHello", -1, '?'],
			['ABC_Hello', "ABC\tHello", -1, '_'],
			['ABCD    Hello', "ABCD\tHello", -1, ' '],
			['ABCD Hello', "ABCD\tHello", 5, ' '],
			["  Hello\n  Hello", "\tHello\n\tHello", 2, ' '],
			[" Hello\n Hello", "\tHello\n\tHello", 1, ' '],
			["Hello\nHello", "\tHello\n\tHello", 0, ' '],
		];
	}

	/**
	 * @param string $expected
	 * @param string $text
	 * @param int $tabwidth
	 * @return void
	 * @dataProvider data_replaceTabs
	 */
	public function test_replaceTabs(string $expected, string $text, int $tab_width, string $replace): void {
		$this->assertEquals($expected, StringTools::replaceTabs($text, $tab_width, $replace));
	}

	/**
	 * @return void
	 * @dataProvider ellipsis_word_data
	 */
	public function test_ellipsis_word($text, $number, $dot_dot_dot, $expected): void {
		$this->assertEquals($expected, StringTools::ellipsis_word($text, $number, $dot_dot_dot));
	}

	public function data_contains(): array {
		$trueTests = [];
		foreach (array_merge($this->data_begins(), $this->data_ends()) as $test) {
			[$haystack, $needle, $lower, $expected] = $test;
			if ($expected) {
				$trueTests[] = $test;
			}
		}
		return array_merge($trueTests, [
			[['walking', 'runner'], 'alk', false, true],
			[['walking', 'runner'], ['alk'], false, true],
			['walking', ['alk'], false, true],
			['walking', ['ner'], false, false],
			['walking', ['kin'], false, true],
			['walking', ['foo', 'g'], false, true],
			['walking', ['foo', ''], false, true],
			['walking', '', false, true],
			['walking', '', true, true],
		]);
	}

	/**
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider data_contains
	 */
	public function test_contains(string|array $haystack, string|array $needle, bool $lower, bool $expected): void {
		$this->assertEquals($expected, StringTools::contains($haystack, $needle, $lower));
	}

	public function data_ends(): array {
		return [
			['a', '', false, true],
			['a', '', true, true],
			[['walking', 'runner'], 'ing', false, true],
			[['walking', 'runner'], ['ing'], false, true],
			['walking', ['ing'], false, true],
			['walking', ['ner'], false, false],
			['walking', ['g'], false, true],
			['walking', ['ner', 'g'], false, true],
			['a', 'A', false, false],
			['A', 'a', false, false],
			['a', 'a', false, true],
			['A', 'A', false, true],
			['a', 'A', true, true],
			['A', 'a', true, true],
			['a', 'a', true, true],
			['A', 'A', true, true],
			['1', '1', false, true],
			['1', '1', true, true],
			['could_be_setting_with_password', '_password', false, true],
			['_password ', '_password', false, false],
		];
	}

	/**
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider data_ends
	 */
	public function test_ends(string|array $haystack, string|array $needle, bool $lower, bool $expected): void {
		$this->assertEquals($expected, StringTools::ends($haystack, $needle, $lower));
	}

	/**
	 * @return array
	 */
	public function data_toBool(): array {
		return [
			['', false, ],
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
			[1, true, ],
			[new \stdClass(), true, ],
			[[1], true, ],
			[[0], true, ],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_toBool
	 */
	public function test_from_bool(mixed $mixed, bool $expected): void {
		$this->assertEquals($expected ? 'true' : 'false', StringTools::fromBool($mixed));
	}

	/**
	 * @return void
	 * @dataProvider data_toBool
	 */
	public function test_to_bool_data(mixed $mixed, bool $expected): void {
		$this->assertEquals($expected, StringTools::toBool($mixed));
	}

	public function test_is_ascii(): void {
		$str = 'string';
		$this->assertTrue(StringTools::is_ascii($str));
		$str = chr(255) . chr(254) . 'Hello';
		$this->assertFalse(StringTools::is_ascii($str));
	}

	public function is_utf16_data(): array {
		return [
			[chr(0xFF) . chr(0xFE) . 'is this utf16', true, false],
			[chr(0xFE) . chr(0xFF) . 'is this utf16', true, true],
			[chr(0xFF) . chr(0xFF) . 'is this utf16', false, false],
			[chr(0xFE) . chr(0xFE) . 'is this utf16', false, false],
			['', false, false],
			['1', false, false],
			['22', false, false],
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
			$this->assertEquals($beShouldBe, $be, 'BOM matches');
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
		$this->assertTrue(StringTools::is_utf8(''));
		$this->assertTrue(StringTools::is_utf8('????, ???'));
		$this->assertTrue(StringTools::is_utf8('????, ???'));
	}

	public function data_is_utf8_file(): array {
		return [
			['utf16-le-no-bom.data', false, ],
			['utf16-no-bom.data', false, ],
			['iso-latin-1.data', true],
			['gb-18030.data', true],
			['utf16-le.data', false],
			['iso-latin-9.data', true],
			['utf16.data', false],
			['utf8.data', true],
			['utf8-cn.data', true],
		];
	}

	/**
	 * @param $f
	 * @param $isutf8
	 * @return void
	 * @dataProvider data_is_utf8_file
	 */
	public function test_is_utf8_file(string $f, bool $isutf8): void {
		$test_dir = $this->application->zeskHome('test/test-data');
		$content = file_get_contents(path($test_dir, $f));
		$this->assertEquals($isutf8, StringTools::is_utf8($content));
	}

	public function left_data(): array {
		return [
			['haystack to find that thing', 'that', 'default', 'haystack to find '],
			['haystack to find that thing', 'that', null, 'haystack to find '],
			['haystack to find that thing', 'thot', 'default', 'default'],
			['haystack to find that thing', 'thot', null, 'haystack to find that thing'],
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
			['string', 'delim', 'left', 'right', ['left', 'right']],
			['string', 'r', 'left', 'right', ['st', 'ing']],
			['NAME=VALUE', '=', 'left', 'right', ['NAME', 'VALUE']],
			['=VALUE', '=', 'left', 'right', ['', 'VALUE']],
			['NAME=', '=', 'left', 'right', ['NAME', '']],
			['NAME=', '!', 'left', 'right', ['left', 'right']],
			['NAME=VALUE=VALUE', '=', 'left', 'right', ['NAME', 'VALUE=VALUE']],
			['=VALUE=VALUE', '=', 'left', 'right', ['', 'VALUE=VALUE']],
			['NAME=NAME=', '=', 'left', 'right', ['NAME', 'NAME=']],
			['NAME=NAME=', '!', 'left', 'right', ['left', 'right']],
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
			['string', 'delim', 'left', 'right', ['left', 'right']],
			['string', 'r', 'left', 'right', ['st', 'ing']],
			['NAME=VALUE', '=', 'left', 'right', ['NAME', 'VALUE']],
			['=VALUE', '=', 'left', 'right', ['', 'VALUE']],
			['NAME=', '=', 'left', 'right', ['NAME', '']],
			['NAME=', '!', 'left', 'right', ['left', 'right']],
			['NAME=VALUE=VALUE', '=', 'left', 'right', ['NAME=VALUE', 'VALUE']],
			['=VALUE=VALUE', '=', 'left', 'right', ['=VALUE', 'VALUE']],
			['NAME=NAME=', '=', 'left', 'right', ['NAME=NAME', '']],
			['NAME=NAME=', '!', 'left', 'right', ['left', 'right']],
		];
	}

	/**
	 * @return void
	 * @dataProvider pairr_data
	 */
	public function test_pairr(string $string, string $delim, string $left, string $right, array $expected): void {
		$this->assertEquals($expected, StringTools::reversePair($string, $delim, $left, $right));
	}

	public function replace_first_data(): array {
		return [
			['dude', 'bar', 'you pass butter', 'you pass butter'],
			['bar', 'but', 'you pass barter', 'you pass butter'],
			['is', 'at', 'This is a test', 'That is a test'],
			[
				'DOG',
				'CAT',
				'I have several DOG and I only like all DOGs',
				'I have several CAT and I only like all DOGs',
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
		$this->assertEquals($expected, StringTools::replace_first($search, $replace, $content));
	}

	public function right_data(): array {
		return [
			['NAME and VALUE', 'and', 'default', ' VALUE', ],
			['NAME and VALUE', 'and V', 'default', 'ALUE', ],
			['NAME and VALUE', ' ', 'default', 'and VALUE', ],
			['NAME and VALUE', 'D', 'default', 'default', ],
			['NAME and VALUE', 'D', null, 'NAME and VALUE', ],
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
			['NAME and VALUE', 'and', 'default', 'NAME ', ],
			['NAME and VALUE', 'and V', 'default', 'NAME ', ],
			['NAME and VALUE', ' ', 'default', 'NAME and', ],
			['NAME and VALUE', 'D', 'default', 'default', ],
			['NAME and VALUE', 'D', null, 'NAME and VALUE', ],
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
		$this->assertEquals($expected, StringTools::reverseLeft($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public function rright_data(): array {
		return [
			['NAME and VALUE', 'and', 'default', ' VALUE', ],
			['NAME and VALUE', 'and V', 'default', 'ALUE', ],
			['NAME and VALUE', ' ', 'default', 'VALUE', ],
			['NAME and VALUE', 'D', 'default', 'default', ],
			['NAME and VALUE', 'D', null, 'NAME and VALUE', ],
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
		$this->assertEquals($expected, StringTools::reverseRight($haystack, $needle, $default));
	}

	/**
	 * @param mixed $value
	 * @param bool $expected
	 * @return void
	 * @dataProvider to_bool_data_original
	 */
	public function test_to_bool(mixed $value, bool $expected): void {
		$this->assertEquals($expected, StringTools::toBool($value));
	}

	public function to_bool_data_original() {
		return [
			[true, true],
			['t', true],
			['T', true],
			['y', true],
			['Y', true],
			['Yes', true],
			['yES', true],
			['oN', true],
			['on', true],
			['enabled', true],
			['trUE', true],
			['true', true],

			['f', false],
			['F', false],
			['n', false],
			['N', false],
			['no', false],
			['NO', false],
			['OFF', false],
			['off', false],
			['disabled', false],
			['DISABLED', false],
			['false', false],
			['null', false],
			['', false],

			[0, false],
			['0', false],

			[1, true],
			['1', true],

			['01', true],
			[[], false],
			[new \stdClass(), true],

		];
	}

	/**
	 * @return array[]
	 */
	public function removePrefix_data(): array {
		return [
			[['String', 'stretch'], ['Stri', 'Stre'], true, ['ng', 'tch']],
			['string', 'str', false, 'ing'],
			['string', 'str', true, 'ing'],

			['string', 'Str', false, 'string'],
			['string', 'Str', true, 'ing'],

			['String', 'str', false, 'String'],
			['String', 'str', true, 'ing'],

			['String', 'Str', false, 'ing'],
			['String', 'Str', true, 'ing'],
			['String', 'Str', false, 'ing'],
			[['String', 'Stretch'], 'Str', true, ['ing', 'etch']],
			[['String', 'stretch'], 'Str', false, ['ing', 'stretch']],
			[['String', 'stretch'], ['Stri', 'Stre'], false, ['ng', 'stretch']],
		];
	}

	/**
	 * @param string|array $string
	 * @param string|array $prefix
	 * @param bool $case_insensitive
	 * @param string|array $expected
	 * @return void
	 * @dataProvider removePrefix_data
	 */
	public function test_removePrefix(string|array $string, string|array $prefix, bool $case_insensitive, string|array $expected):
	void {
		$this->assertEquals($expected, StringTools::removePrefix($string, $prefix, $case_insensitive));
	}

	/**
	 * @return array[]
	 */
	public function removeSuffix_data(): array {
		return [
			['string', 'ing', false, 'str'],
			['string', 'ing', true, 'str'],

			['string', 'ING', false, 'string'],
			['string', 'ING', true, 'str'],

			['String', 'ing', false, 'Str'],
			['String', 'ing', true, 'Str'],

			['String', 'Ing', false, 'String'],
			['String', 'Ing', true, 'Str'],

			[['String', 'string', 'Blowing', 'EatING'], 'Ing', true, ['Str', 'str', 'Blow', 'Eat']],
			[['String', 'string', 'Blowing', 'EatIng'], 'Ing', false, ['String', 'string', 'Blowing', 'Eat']],
		];
	}

	/**
	 * @param string|array $string
	 * @param string $suffix
	 * @param bool $case_insensitive
	 * @param string $expected
	 * @return void
	 * @dataProvider removeSuffix_data
	 */
	public function test_removeSuffix(string|array $string, string $suffix, bool $case_insensitive, string|array $expected):
	void {
		$this->assertEquals($expected, StringTools::removeSuffix($string, $suffix, $case_insensitive));
	}

	public function zero_pad_data(): array {
		return [
			['0', 2, '00'],
			['00', 2, '00'],
			['1', 2, '01'],
			['01', 2, '01'],
			['0', 3, '000'],
			['00', 3, '000'],
			['1', 3, '001'],
			['01', 3, '001'],
			['xx', 4, '00xx'],
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
			['text', 10, '-', true, 'text------'],
			['text ', 10, '-', true, 'text -----'],
			['textificiation', 10, '-', false, 'textificiation'],
			['textificiation', 10, '-', true, 'textificia'],
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
	public function test_leftAlign(string $text, int $length, string $padding, bool $trim, string $expected): void {
		$this->assertEquals($expected, Text::leftAlign($text, $length, $padding, $trim));
	}

	public function data_rightAlign(): array {
		return [
			['text', 10, '-', true, '------text'],
			['text ', 10, '-', true, '-----text '],
			['textificiation', 10, '-', false, 'textificiation'],
			['textificiation', 10, '-', true, 'textificia'],
		];
	}

	/**
	 * @param string $text
	 * @param int $length
	 * @param string $padding
	 * @param bool $trim
	 * @param string $expected
	 * @return void
	 * @dataProvider data_rightAlign
	 */
	public function test_rightAlign(string $text, int $length, string $padding, bool $trim, string $expected): void {
		$this->assertEquals($expected, Text::rightAlign($text, $length, $padding, $trim));
	}

	public function test_filter(): void {
		$name = null;
		$default = true;
		$this->assertTrue(StringTools::filter($name, [], true));
		$this->assertFalse(StringTools::filter($name, [], false));
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
			$this->assertEquals(StringTools::filter($name, $rules, $default), $result, "Test #$index failed: $name");
		}
	}

	/**
	 * @param string $expected
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @param string $encoding
	 * @return void
	 * @dataProvider data_substr
	 */
	public function test_substr(string $expected, string $string, int $start, int $length, string $encoding): void {
		$this->assertEquals($expected, StringTools::substr($string, $start, $length, $encoding));
	}

	public function data_substr(): array {
		$sample1 = '😉2345🤣789🙄1👍3456789😁';
		return
		[
			['😉', $sample1, 0, 1, ''],
			['😉', $sample1, 0, 1, 'UTF-8'],
			['😁', $sample1, -1, 1, ''],
			['😁', $sample1, -1, 1, 'UTF-8'],
			['😉2345', $sample1, 0, 5, ''],
			[$sample1, $sample1, 0, 20, ''],
			[$sample1, $sample1, 0, 20, 'UTF-8'],
			['3456789', $sample1, 12, 7, 'UTF-8'],
		];
	}

	public function test_PHP_substr(): void {
		// Never knew this'
		$foo = 'OK,';
		$result = substr($foo, 3);
		if (PHP_VERSION_ID > 0o70000) {
			// Fixed in 7.0
			$this->assertEquals('string', gettype($result));
			$this->assertEquals('', $result);
		} else {
			$this->assertEquals('boolean', gettype($result));
			$this->assertEquals(false, $result);
		}
	}

	public function test_replace_first1(): void {
		$this->assertEquals('bbracadabra', StringTools::replace_first('a', 'b', 'abracadabra'));
		$this->assertEquals('astrapcadabra', StringTools::replace_first('bra', 'strap', 'abracadabra'));
	}

	/**
	 * @param array $expected
	 * @param string $string
	 * @param int $split_length
	 * @param string $encoding
	 * @return void
	 * @dataProvider data_str_split
	 */
	public function test_str_split(array $expected, string $string, int $split_length, string $encoding): void {
		$this->assertEquals($expected, StringTools::str_split($string, $split_length, $encoding));
	}

	public function data_str_split(): array {
		return [
			[['😉', '🤣', '🙄', '👍'], '😉🤣🙄👍', -1, 'UTF-8'],
			[['😉', '🤣', '🙄', '👍'], '😉🤣🙄👍', 1, 'UTF-8'],
			[['😉🤣', '🙄👍'], '😉🤣🙄👍', 2, 'UTF-8'],
			[['wo', 'rd', 's😉', '🤣🙄', '👍'], 'words😉🤣🙄👍', 2, 'UTF-8'],
			[['wor', 'ds😉', '🤣🙄👍'], 'words😉🤣🙄👍', 3, 'UTF-8'],
			[['word', 's😉🤣🙄', '👍'], 'words😉🤣🙄👍', 4, 'UTF-8'],
		];
	}

	public function data_csv_quote_row(): array {
		return [
			["ID,Name\r\n", ['ID', 'Name']],
			["\"This \"\"has\"\" quotes\",\"Oxford, is it required?\"\r\n", ['This "has" quotes', 'Oxford, is it required?']],
			["\"Just a new\nline\",Nothing\r\n", ["Just a new\nline", 'Nothing']],
			["Simple,Line\r\n", ['Simple', 'Line']],
		];
	}

	public function data_length(): array {
		return [
			[4, '😉🤣🙄👍', ''],
			[4, '😉🤣🙄👍', 'UTF-8'],
			[9, 'words😉🤣🙄👍', 'UTF-8'],
		];
	}

	/**
	 * @dataProvider data_length
	 */
	public function test_length(int $expected, string $string, string $encoding): void {
		$this->assertEquals($expected, StringTools::length($string, $encoding));
	}

	/**
	 * @param string $expected
	 * @param array $row
	 * @return void
	 * @dataProvider data_csv_quote_row
	 */
	public function test_csv_quote_row(string $expected, array $row): void {
		$this->assertEquals($expected, StringTools::csv_quote_row($row));
	}

	/**
	 * @dataProvider data_csv_quote_row
	 * @param string $row_expected
	 * @param array $row
	 * @return void
	 */
	public function test_csv_quote_rows(string $row_expected, array $row): void {
		$total = $this->randomInteger(3, 100);
		$expected = str_repeat($row_expected, $total);
		$rows = array_fill(0, $total, $row);
		$this->assertEquals($expected, StringTools::csv_quote_rows($rows));
	}
}
