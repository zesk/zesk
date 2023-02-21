<?php
declare(strict_types=1);

namespace zesk;

use stdClass;

class StringTools_Test extends UnitTest {
	/**
	 * @param string $expected
	 * @param string $test
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider data_mapClean
	 */
	public function test_mapClean(string $expected, string $test, string $prefix, string $suffix): void {
		$this->assertTrue(StringTools::hasTokens($test));
		$this->assertEquals($expected, StringTools::cleanTokens($test, $prefix, $suffix));
	}

	public static function data_mapClean(): array {
		return [
			['He wanted  [days]', 'He wanted {n} [days]', '{', '}', ],
			['He wanted {n} ', 'He wanted {n} [days]', '[', ']', ],
			['He wanted {n} [days]', 'He wanted {n} [days]', '[', '}', ],
			['He wanted ', 'He wanted {n} [days]', '{', ']', ],
			['except}', '{}{}{}{}{}{all}{of}{this}{is}{removed}except}{}', '{', '}', ],
		];
	}

	public static function data_joinArray(): array {
		return [
			['^one^two^three^', '^', ['^^one', '^two^', 'three^', ]],
			['path/part/whoops/embed/deep', '/', ['path', 'part', ['whoops', ['embed', 'deep']]]],
		];
	}

	/**
	 * @param string $expected
	 * @param string $separator
	 * @param array $mixed
	 * @return void
	 * @dataProvider data_joinArray
	 */
	public function test_joinArray(string $expected, string $separator, array $mixed): void {
		$separator = '^';
		$mixed = [
			'^^one', '^two^', 'three^',
		];
		$result = StringTools::joinArray($separator, $mixed);
		$this->assertEquals('^one^two^three^', $result);
	}

	/**
	 * @return array[]
	 */
	public static function data_begins(): array {
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

	public static function data_capitalize(): array {
		return [
			['', ''],
			['hello', 'Hello', ],
			['I WOULD LIKE SOME HELP', 'I Would Like Some Help', ],
			['a rather fun title to have for the new ages', 'A Rather Fun Title To Have For The New Ages', ],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_capitalize
	 */
	public function test_capitalize($phrase, $expected): void {
		$this->assertEquals($expected, StringTools::capitalize($phrase));
	}

	/**
	 * @return array
	 */
	public static function data_to_camel(): array {
		return [
			['long_ass_string', 'longAssString'],
			['_long_ass_string', 'LongAssString'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_to_camel
	 */
	public function test_to_camel_case($test, $expected): void {
		$this->assertEquals($expected, StringTools::toCamelCase($test));
	}

	/**
	 * @return array
	 */
	public static function data_from_camel(): array {
		return [
			['longAssString', 'long_ass_string'],
			['LongAssString', '_long_ass_string'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_from_camel
	 */
	public function test_from_camel_case($test, $expected): void {
		$this->assertEquals($expected, StringTools::fromCamelCase($test));
	}

	public static function data_caseMatch(): array {
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
	 * @dataProvider data_caseMatch
	 */
	public function test_caseMatch(string $string, string $pattern, string $expected): void {
		$this->assertEquals($expected, StringTools::caseMatch($string, $pattern));
	}

	public static function data_ellipsis_word(): array {
		$n = 20;
		$random_string = self::randomHex($n);
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

	/**
	 * @return void
	 * @dataProvider data_ellipsis_word
	 */
	public function test_ellipsis_word($text, $number, $dot_dot_dot, $expected): void {
		$this->assertEquals($expected, StringTools::ellipsisWord($text, $number, $dot_dot_dot));
	}

	public static function data_replaceTabs(): array {
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

	public static function data_contains(): array {
		$trueTests = [];
		foreach (array_merge(self::data_begins(), self::data_ends()) as $test) {
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

	public static function data_ends(): array {
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
	public static function data_toBool(): array {
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
			[new stdClass(), true, ],
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
		$this->assertTrue(StringTools::isASCII($str));
		$str = chr(255) . chr(254) . 'Hello';
		$this->assertFalse(StringTools::isASCII($str));
	}

	public static function data_isUTF16(): array {
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
	 * @dataProvider data_isUTF16
	 */
	public function test_isUTF16(string $content, bool $isUTF16, bool $beShouldBe): void {
		$be = false;
		$this->assertEquals($isUTF16, StringTools::isUTF16($content, $be));
		if ($isUTF16) {
			$this->assertEquals($beShouldBe, $be, 'BOM matches');
		}
	}

	public static function data_wrap_tests(): array {
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
	 * @dataProvider data_wrap_tests
	 */
	public function test_wrap(array $arguments, string $expected): void {
		$this->assertEquals(call_user_func_array([
			HTML::class,
			'wrap',
		], $arguments), $expected, "HTML::wrap ${arguments[0]} => $expected failed");
	}

	public function test_is_utf8(): void {
		$this->assertTrue(StringTools::isUTF8(''));
		$this->assertTrue(StringTools::isUTF8('????, ???'));
		$this->assertTrue(StringTools::isUTF8('????, ???'));
	}

	public static function data_is_utf8_file(): array {
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
		$this->assertEquals($isutf8, StringTools::isUTF8($content));
	}

	public static function data_left(): array {
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
	 * @dataProvider data_left
	 */
	public function test_left(string $haystack, string $needle, mixed $default, mixed $expected): void {
		$this->assertEquals($expected, StringTools::left($haystack, $needle, $default));
	}

	public static function data_pair(): array {
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
	 * @dataProvider data_pair
	 */
	public function test_pair(string $string, string $delim, string $left, string $right, array $expected): void {
		$this->assertEquals($expected, StringTools::pair($string, $delim, $left, $right));
	}

	public static function data_reversePair(): array {
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
	 * @dataProvider data_reversePair
	 */
	public function test_reversePair(string $string, string $delim, string $left, string $right, array $expected): void {
		$this->assertEquals($expected, StringTools::reversePair($string, $delim, $left, $right));
	}

	public static function data_replaceFirst(): array {
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
	 * @dataProvider data_replaceFirst
	 */
	public function test_replace_first(string $search, string $replace, string $content, string $expected): void {
		$this->assertEquals($expected, StringTools::replaceFirst($search, $replace, $content));
	}

	public static function data_right(): array {
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
	 * @dataProvider data_right
	 */
	public function test_right(string $haystack, string $needle, ?string $default, string $expected): void {
		$this->assertEquals($expected, StringTools::right($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public static function data_reverseLeft(): array {
		return [
			['NAME and VALUE', 'and', 'default', 'NAME ', ],
			['NAME and VALUE', 'and V', 'default', 'NAME ', ],
			['NAME and VALUE', ' ', 'default', 'NAME and', ],
			['NAME and VALUE', 'D', 'default', 'default', ],
			['NAME and VALUE', 'D', null, 'NAME and VALUE', ],
		];
	}

	/**
	 * @dataProvider data_reverseLeft
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
	public static function data_reverseRight(): array {
		return [
			['NAME and VALUE', 'and', 'default', ' VALUE', ],
			['NAME and VALUE', 'and V', 'default', 'ALUE', ],
			['NAME and VALUE', ' ', 'default', 'VALUE', ],
			['NAME and VALUE', 'D', 'default', 'default', ],
			['NAME and VALUE', 'D', null, 'NAME and VALUE', ],
		];
	}

	/**
	 * @dataProvider data_reverseRight
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
	 * @dataProvider data_toBoolOriginal
	 */
	public function test_to_bool(mixed $value, bool $expected): void {
		$this->assertEquals($expected, StringTools::toBool($value));
	}

	public static function data_toBoolOriginal(): array {
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
			[new stdClass(), true],

		];
	}

	/**
	 * @return array[]
	 */
	public static function data_removePrefix(): array {
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
	 * @dataProvider data_removePrefix
	 */
	public function test_removePrefix(string|array $string, string|array $prefix, bool $case_insensitive, string|array $expected):
	void {
		$this->assertEquals($expected, StringTools::removePrefix($string, $prefix, $case_insensitive));
	}

	/**
	 * @return array[]
	 */
	public static function data_prefixMatch(): array {
		return [
			['', '', ''],
			['', '', ' '],
			[' ', ' ', ' '],
			['dude', 'dudette', 'dudesser'],
			['', 'dudette', 'Dudesser'],
			["\t", "\t ", "\t\t"],
		];
	}

	/**
	 * @param string $expected
	 * @param string $string
	 * @param string $pattern
	 * @return void
	 * @dataProvider data_prefixMatch
	 */
	public function test_prefixMatch(string $expected, string $string, string $pattern): void {
		$this->assertEquals($expected, StringTools::prefixMatch($string, $pattern));
	}

	/**
	 * @return array[]
	 */
	public static function data_removeSuffix(): array {
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
	 * @dataProvider data_removeSuffix
	 */
	public function test_removeSuffix(string|array $string, string $suffix, bool $case_insensitive, string|array $expected):
	void {
		$this->assertEquals($expected, StringTools::removeSuffix($string, $suffix, $case_insensitive));
	}

	public static function data_zeroPad(): array {
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
	 * @dataProvider data_zeroPad
	 */
	public function test_zero_pad(string $string, int $length, string $expected): void {
		$this->assertEquals($expected, StringTools::zeroPad($string, $length));
	}

	public static function data_leftAlign(): array {
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
	 * @dataProvider data_leftAlign
	 */
	public function test_leftAlign(string $text, int $length, string $padding, bool $trim, string $expected): void {
		$this->assertEquals($expected, Text::leftAlign($text, $length, $padding, $trim));
	}

	public static function data_rightAlign(): array {
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
		$name = '';
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
		$this->assertEquals($expected, StringTools::substring($string, $start, $length, $encoding));
	}

	public static function data_substr(): array {
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
		// Never knew this
		$foo = 'OK,';
		$result = substr($foo, 3);
		$this->assertEquals('string', gettype($result));
		$this->assertEquals('', $result);
	}

	public function test_replace_first1(): void {
		$this->assertEquals('bbracadabra', StringTools::replaceFirst('a', 'b', 'abracadabra'));
		$this->assertEquals('astrapcadabra', StringTools::replaceFirst('bra', 'strap', 'abracadabra'));
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
		$this->assertEquals($expected, StringTools::split($string, $split_length, $encoding));
	}

	public static function data_str_split(): array {
		return [
			[['😉', '🤣', '🙄', '👍'], '😉🤣🙄👍', -1, 'UTF-8'],
			[['😉', '🤣', '🙄', '👍'], '😉🤣🙄👍', 1, 'UTF-8'],
			[['😉🤣', '🙄👍'], '😉🤣🙄👍', 2, 'UTF-8'],
			[['wo', 'rd', 's😉', '🤣🙄', '👍'], 'words😉🤣🙄👍', 2, 'UTF-8'],
			[['wor', 'ds😉', '🤣🙄👍'], 'words😉🤣🙄👍', 3, 'UTF-8'],
			[['word', 's😉🤣🙄', '👍'], 'words😉🤣🙄👍', 4, 'UTF-8'],
		];
	}

	public static function data_csv_quote_row(): array {
		return [
			["ID,Name\r\n", ['ID', 'Name']],
			["\"This \"\"has\"\" quotes\",\"Oxford, is it required?\"\r\n", ['This "has" quotes', 'Oxford, is it required?']],
			["\"Just a new\nline\",Nothing\r\n", ["Just a new\nline", 'Nothing']],
			["Simple,Line\r\n", ['Simple', 'Line']],
		];
	}

	public static function data_length(): array {
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
		$this->assertEquals($expected, StringTools::csvQuoteRow($row));
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
		$this->assertEquals($expected, StringTools::csvQuoteRows($rows));
	}
}
