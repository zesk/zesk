<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Directory;
use zesk\PHP;
use zesk\StringTools;

/**
 * @coversDefaultClass \zesk\StringTools
 * @uses StringTools
 */
class StringToolsTest extends TestCase
{
	/**
	 * @param string $expected
	 * @param string $test
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider dataMapClean
	 * @covers ::cleanTokens
	 * @covers ::hasTokens
	 */
	public function test_mapClean(string $expected, string $test, string $prefix, string $suffix): void
	{
		$this->assertTrue(StringTools::hasTokens($test));
		$this->assertEquals($expected, StringTools::cleanTokens($test, $prefix, $suffix));
	}

	public static function dataMapClean(): array
	{
		return [
			[
				'He wanted  [days]',
				'He wanted {n} [days]',
				'{',
				'}',
			],
			[
				'He wanted {n} ',
				'He wanted {n} [days]',
				'[',
				']',
			],
			[
				'He wanted {n} [days]',
				'He wanted {n} [days]',
				'[',
				'}',
			],
			[
				'He wanted ',
				'He wanted {n} [days]',
				'{',
				']',
			],
			[
				'except}',
				'{}{}{}{}{}{all}{of}{this}{is}{removed}except}{}',
				'{',
				'}',
			],
		];
	}

	public static function dataJoinArray(): array
	{
		return [
			[
				'^one^two^three^',
				'^',
				[
					'^^one',
					'^two^',
					'three^',
				],
			],
			[
				'path/part/whoops/embed/deep',
				'/',
				[
					'path',
					'part',
					[
						'whoops',
						[
							'embed',
							'deep',
						],
					],
				],
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $separator
	 * @param array $mixed
	 * @return void
	 * @dataProvider dataJoinArray
	 * @covers ::joinArray
	 */
	public function test_joinArray(string $expected, string $separator, array $mixed): void
	{
		$this->assertEquals($expected, StringTools::joinArray($separator, $mixed));
	}

	/**
	 * @return array[]
	 */
	public static function dataBegins(): array
	{
		return [
			[
				'food',
				'',
				false,
				true,
			],
			[
				'food',
				'',
				true,
				true,
			],
			[
				'food',
				'foo',
				false,
				true,
			],
			[
				'food',
				['foo'],
				false,
				true,
			],
			[
				['food'],
				'foo',
				false,
				true,
			],
			[
				['food'],
				['foo'],
				false,
				true,
			],
			[
				'food',
				'foo',
				false,
				true,
			],
			[
				'food',
				'Foo',
				true,
				true,
			],
			[
				'Food',
				'Foo',
				true,
				true,
			],
			[
				'Food',
				'foo',
				true,
				true,
			],
			[
				'Food',
				'foo',
				false,
				false,
			],
		];
	}

	/**
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataBegins
	 * @covers ::begins
	 */
	public function testBegins(string|array $haystack, string|array $needle, bool $lower, bool $expected): void
	{
		$this->assertEquals($expected, StringTools::begins($haystack, $needle, $lower));
	}

	public static function dataCapitalize(): array
	{
		return [
			[
				'',
				'',
			],
			[
				'hello',
				'Hello',
			],
			[
				'I WOULD LIKE SOME HELP',
				'I Would Like Some Help',
			],
			[
				'a rather fun title to have for the new ages',
				'A Rather Fun Title To Have For The New Ages',
			],
		];
	}

	/**
	 * @param string $phrase
	 * @param string $expected
	 * @return void
	 * @dataProvider dataCapitalize
	 * @covers ::capitalize
	 */
	public function testCapitalize(string $phrase, string $expected): void
	{
		$this->assertEquals($expected, StringTools::capitalize($phrase));
	}

	/**
	 * @return array
	 */
	public static function dataToCamelCase(): array
	{
		return [
			[
				'longAssString',
				'long_ass_string',
			],
			[
				'LongAssString',
				'_long_ass_string',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @dataProvider dataToCamelCase
	 * @covers ::toCamelCase
	 */
	public function testToCamelCase(string $expected, string $test): void
	{
		$this->assertEquals($expected, StringTools::toCamelCase($test));
	}

	/**
	 * @return array
	 */
	public static function dataFromCamelCase(): array
	{
		return [
			[
				'long_ass_string',
				'longAssString',
			],
			[
				'_long_ass_string',
				'LongAssString',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @dataProvider dataFromCamelCase
	 * @covers ::fromCamelCase
	 */
	public function testFromCamelCase(string $expected, string $test): void
	{
		$this->assertEquals($expected, StringTools::fromCamelCase($test));
	}

	/**
	 * @return array[]
	 */
	public static function dataCaseMatch(): array
	{
		return [
			[
				'test',
				'John',
				'Test',
			],
			[
				'Test',
				'John',
				'Test',
			],
			[
				'test',
				'John',
				'Test',
			],
			[
				'TeSt',
				'John',
				'Test',
			],
			[
				'test',
				'JOHN',
				'TEST',
			],
			[
				'Test',
				'JOHN',
				'TEST',
			],
			[
				'test',
				'JOHN',
				'TEST',
			],
			[
				'TeSt',
				'JOHn',
				'TEST',
			],
			[
				'test',
				'john',
				'test',
			],
			[
				'Test',
				'john',
				'test',
			],
			[
				'test',
				'john',
				'test',
			],
			[
				'TeSt',
				'john',
				'test',
			],
		];
	}

	/**
	 * @param string $string
	 * @param string $pattern
	 * @param string $expected
	 * @return void
	 * @dataProvider dataCaseMatch
	 * @covers ::caseMatch
	 */
	public function testCaseMatch(string $string, string $pattern, string $expected): void
	{
		$this->assertEquals($expected, StringTools::caseMatch($string, $pattern));
	}

	public static function dataEllipsisWord(): array
	{
		$n = 20;
		$random_string = self::randomHex($n);
		return [
			[
				$random_string,
				-1,
				'...',
				$random_string,
			],
			[
				$random_string,
				$n - 1,
				'!',
				substr($random_string, 0, -1) . '!',
			],
			[
				$random_string,
				$n,
				'...',
				$random_string,
			],
			[
				$random_string,
				$n + 1,
				'...',
				$random_string,
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				1,
				'...',
				'A...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				5,
				'...',
				'A...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				10,
				'...',
				'A quick...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				15,
				'...',
				'A quick brown...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				20,
				'...',
				'A quick brown fox...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				25,
				'...',
				'A quick brown fox jumps...',
			],
			[
				'A quick brown fox jumps over the lazy dog.',
				30,
				'...',
				'A quick brown fox jumps over...',
			],
		];
	}

	/**
	 * @param $text
	 * @param $number
	 * @param $dot_dot_dot
	 * @param $expected
	 * @return void
	 * @dataProvider dataEllipsisWord
	 * @covers ::ellipsisWord
	 */
	public function testEllipsisWord($text, $number, $dot_dot_dot, $expected): void
	{
		$this->assertEquals($expected, StringTools::ellipsisWord($text, $number, $dot_dot_dot));
	}

	public static function dataReplaceTabs(): array
	{
		return [
			[
				'    Hello',
				"\tHello",
				-1,
				' ',
			],
			[
				'A+++Hello',
				"A\tHello",
				-1,
				'+',
			],
			[
				'AB??Hello',
				"AB\tHello",
				-1,
				'?',
			],
			[
				'ABC_Hello',
				"ABC\tHello",
				-1,
				'_',
			],
			[
				'ABCD    Hello',
				"ABCD\tHello",
				-1,
				' ',
			],
			[
				'ABCD Hello',
				"ABCD\tHello",
				5,
				' ',
			],
			[
				"  Hello\n  Hello",
				"\tHello\n\tHello",
				2,
				' ',
			],
			[
				" Hello\n Hello",
				"\tHello\n\tHello",
				1,
				' ',
			],
			[
				"Hello\nHello",
				"\tHello\n\tHello",
				0,
				' ',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $text
	 * @param int $tab_width
	 * @param string $replace
	 * @return void
	 * @dataProvider dataReplaceTabs
	 * @covers ::replaceTabs
	 */
	public function testReplaceTabs(string $expected, string $text, int $tab_width, string $replace): void
	{
		$this->assertEquals($expected, StringTools::replaceTabs($text, $tab_width, $replace));
	}

	public static function dataContains(): array
	{
		$trueTests = [];
		foreach (array_merge(self::dataBegins(), self::dataEnds()) as $test) {
			$expected = $test[3];
			if ($expected) {
				$trueTests[] = $test;
			}
		}
		return array_merge($trueTests, [
			[
				[
					'walking',
					'runner',
				],
				'alk',
				false,
				true,
			],
			[
				[
					'walking',
					'runner',
				],
				['alk'],
				false,
				true,
			],
			[
				'walking',
				['alk'],
				false,
				true,
			],
			[
				'walking',
				['ner'],
				false,
				false,
			],
			[
				'walking',
				['kin'],
				false,
				true,
			],
			[
				'walking',
				[
					'foo',
					'g',
				],
				false,
				true,
			],
			[
				'walking',
				[
					'foo',
					'',
				],
				false,
				true,
			],
			[
				'walking',
				'',
				false,
				true,
			],
			[
				'walking',
				'',
				true,
				true,
			],
		]);
	}

	/**
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataContains
	 * @covers ::contains
	 */
	public function testContains(string|array $haystack, string|array $needle, bool $lower, bool $expected): void
	{
		$this->assertEquals($expected, StringTools::contains($haystack, $needle, $lower));
	}

	public static function dataEnds(): array
	{
		return [
			[
				'a',
				'',
				false,
				true,
			],
			[
				'a',
				'',
				true,
				true,
			],
			[
				[
					'walking',
					'runner',
				],
				'ing',
				false,
				true,
			],
			[
				[
					'walking',
					'runner',
				],
				['ing'],
				false,
				true,
			],
			[
				'walking',
				['ing'],
				false,
				true,
			],
			[
				'walking',
				['ner'],
				false,
				false,
			],
			[
				'walking',
				['g'],
				false,
				true,
			],
			[
				'walking',
				[
					'ner',
					'g',
				],
				false,
				true,
			],
			[
				'a',
				'A',
				false,
				false,
			],
			[
				'A',
				'a',
				false,
				false,
			],
			[
				'a',
				'a',
				false,
				true,
			],
			[
				'A',
				'A',
				false,
				true,
			],
			[
				'a',
				'A',
				true,
				true,
			],
			[
				'A',
				'a',
				true,
				true,
			],
			[
				'a',
				'a',
				true,
				true,
			],
			[
				'A',
				'A',
				true,
				true,
			],
			[
				'1',
				'1',
				false,
				true,
			],
			[
				'1',
				'1',
				true,
				true,
			],
			[
				'could_be_setting_with_password',
				'_password',
				false,
				true,
			],
			[
				'_password ',
				'_password',
				false,
				false,
			],
		];
	}

	/**
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @param bool $lower
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataEnds
	 * @covers ::ends
	 */
	public function testEnds(string|array $haystack, string|array $needle, bool $lower, bool $expected): void
	{
		$this->assertEquals($expected, StringTools::ends($haystack, $needle, $lower));
	}

	/**
	 * @return array
	 */
	public static function dataToBool(): array
	{
		return [
			[
				'',
				false,
			],
			[
				null,
				false,
			],
			[
				0,
				false,
			],
			[
				0.0,
				false,
			],
			[
				[],
				false,
			],
			[
				false,
				false,
			],
			[
				'f',
				false,
			],
			[
				'0',
				false,
			],
			[
				'false',
				false,
			],
			[
				'FALSE',
				false,
			],
			[
				'off',
				false,
			],
			[
				true,
				true,
			],
			[
				1,
				true,
			],
			[
				new stdClass(),
				true,
			],
			[
				[1],
				true,
			],
			[
				[0],
				true,
			],
		];
	}

	/**
	 * @param mixed $mixed
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataToBool
	 * @covers ::fromBool
	 */
	public function testFromBool(mixed $mixed, bool $expected): void
	{
		$this->assertEquals($expected ? 'true' : 'false', StringTools::fromBool($mixed));
	}

	/**
	 * @param mixed $mixed
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataToBool
	 * @covers ::toBool
	 */
	public function testToBoolData(mixed $mixed, bool $expected): void
	{
		$this->assertEquals($expected, StringTools::toBool($mixed));
	}

	public static function dataIsASCII(): array
	{
		return [
			[true, 'string'],
			[false, chr(255) . chr(254) . 'Hello'],
		];
	}

	/**
	 * @param bool $isAscii
	 * @param string $test
	 * @return void
	 * @dataProvider dataIsASCII
	 * @covers ::isASCII
	 */
	public function testIsASCII(bool $isAscii, string $test): void
	{
		$this->assertEquals($isAscii, StringTools::isASCII($test), 'StringTools::isASCII(' . PHP::dump($test) . ')');
	}

	public static function dataIsUTF16(): array
	{
		return [
			[
				chr(0xFF) . chr(0xFE) . 'is this utf16',
				true,
				false,
			],
			[
				chr(0xFE) . chr(0xFF) . 'is this utf16',
				true,
				true,
			],
			[
				chr(0xFF) . chr(0xFF) . 'is this utf16',
				false,
				false,
			],
			[
				chr(0xFE) . chr(0xFE) . 'is this utf16',
				false,
				false,
			],
			[
				'',
				false,
				false,
			],
			[
				'1',
				false,
				false,
			],
			[
				'22',
				false,
				false,
			],
		];
	}

	/**
	 * @param string $content
	 * @param bool $isUTF16
	 * @param bool $beShouldBe
	 * @return void
	 * @dataProvider dataIsUTF16
	 * @covers ::isUTF16
	 */
	public function testIsUTF16(string $content, bool $isUTF16, bool $beShouldBe): void
	{
		$be = false;
		$this->assertEquals($isUTF16, StringTools::isUTF16($content, $be));
		if ($isUTF16) {
			$this->assertEquals($beShouldBe, $be, 'BOM matches');
		}
	}

	public static function dataIsUTF8(): array
	{
		return [
			[true, '????, ???'],
			[true, ''],
		];
	}

	/**
	 * @dataProvider dataIsUTF8
	 * @param bool $expected
	 * @param string $test
	 * @return void
	 * @covers ::isUTF8
	 */
	public function testIsUTF8(bool $expected, string $test): void
	{
		$this->assertEquals($expected, StringTools::isUTF8($test));
	}

	public static function dataIsUTF8File(): array
	{
		return [
			[
				'utf16-le-no-bom.data',
				false,
			],
			[
				'utf16-no-bom.data',
				false,
			],
			[
				'iso-latin-1.data',
				true,
			],
			[
				'gb-18030.data',
				true,
			],
			[
				'utf16-le.data',
				false,
			],
			[
				'iso-latin-9.data',
				true,
			],
			[
				'utf16.data',
				false,
			],
			[
				'utf8.data',
				true,
			],
			[
				'utf8-cn.data',
				true,
			],
		];
	}

	/**
	 * @param string $f
	 * @param bool $isUTF8
	 * @return void
	 * @dataProvider dataIsUTF8File
	 * @covers ::isUTF8
	 */
	public function testIs_utf8_file(string $f, bool $isUTF8): void
	{
		$test_dir = $this->application->zeskHome('tests/test-data');
		$content = file_get_contents(Directory::path($test_dir, $f));
		$this->assertEquals($isUTF8, StringTools::isUTF8($content));
	}

	public static function dataLeft(): array
	{
		return [
			[
				'haystack to find that thing',
				'that',
				'default',
				'haystack to find ',
			],
			[
				'haystack to find that thing',
				'that',
				null,
				'haystack to find ',
			],
			[
				'haystack to find that thing',
				'thou',
				'default',
				'default',
			],
			[
				'haystack to find that thing',
				'thou',
				null,
				'haystack to find that thing',
			],
		];
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param mixed $default
	 * @param mixed $expected
	 * @return void
	 * @covers ::left
	 * @dataProvider dataLeft
	 */
	public function test_left(string $haystack, string $needle, mixed $default, mixed $expected): void
	{
		$this->assertEquals($expected, StringTools::left($haystack, $needle, $default));
	}

	public static function dataPair(): array
	{
		return [
			[
				'string',
				'delim',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
			[
				'string',
				'r',
				'left',
				'right',
				[
					'st',
					'ing',
				],
			],
			[
				'NAME=VALUE',
				'=',
				'left',
				'right',
				[
					'NAME',
					'VALUE',
				],
			],
			[
				'=VALUE',
				'=',
				'left',
				'right',
				[
					'',
					'VALUE',
				],
			],
			[
				'NAME=',
				'=',
				'left',
				'right',
				[
					'NAME',
					'',
				],
			],
			[
				'NAME=',
				'!',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
			[
				'NAME=VALUE=VALUE',
				'=',
				'left',
				'right',
				[
					'NAME',
					'VALUE=VALUE',
				],
			],
			[
				'=VALUE=VALUE',
				'=',
				'left',
				'right',
				[
					'',
					'VALUE=VALUE',
				],
			],
			[
				'NAME=NAME=',
				'=',
				'left',
				'right',
				[
					'NAME',
					'NAME=',
				],
			],
			[
				'NAME=NAME=',
				'!',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
		];
	}

	/**
	 * @param string $string
	 * @param string $delim
	 * @param string $left
	 * @param string $right
	 * @param array $expected
	 * @return void
	 * @covers ::pair
	 * @dataProvider dataPair
	 */
	public function test_pair(string $string, string $delim, string $left, string $right, array $expected): void
	{
		$this->assertEquals($expected, StringTools::pair($string, $delim, $left, $right));
	}

	public static function dataReversePair(): array
	{
		return [
			[
				'string',
				'delim',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
			[
				'string',
				'r',
				'left',
				'right',
				[
					'st',
					'ing',
				],
			],
			[
				'NAME=VALUE',
				'=',
				'left',
				'right',
				[
					'NAME',
					'VALUE',
				],
			],
			[
				'=VALUE',
				'=',
				'left',
				'right',
				[
					'',
					'VALUE',
				],
			],
			[
				'NAME=',
				'=',
				'left',
				'right',
				[
					'NAME',
					'',
				],
			],
			[
				'NAME=',
				'!',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
			[
				'NAME=VALUE=VALUE',
				'=',
				'left',
				'right',
				[
					'NAME=VALUE',
					'VALUE',
				],
			],
			[
				'=VALUE=VALUE',
				'=',
				'left',
				'right',
				[
					'=VALUE',
					'VALUE',
				],
			],
			[
				'NAME=NAME=',
				'=',
				'left',
				'right',
				[
					'NAME=NAME',
					'',
				],
			],
			[
				'NAME=NAME=',
				'!',
				'left',
				'right',
				[
					'left',
					'right',
				],
			],
		];
	}

	/**
	 * @param string $string
	 * @param string $delim
	 * @param string $left
	 * @param string $right
	 * @param array $expected
	 * @return void
	 * @covers ::reversePair
	 * @dataProvider dataReversePair
	 */
	public function testReversePair(string $string, string $delim, string $left, string $right, array $expected): void
	{
		$this->assertEquals($expected, StringTools::reversePair($string, $delim, $left, $right));
	}

	public static function dataReplaceFirst(): array
	{
		return [
			[
				'dude',
				'bar',
				'you pass butter',
				'you pass butter',
			],
			[
				'bar',
				'but',
				'you pass barter',
				'you pass butter',
			],
			[
				'is',
				'at',
				'This is a test',
				'That is a test',
			],
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
	 * @covers ::replaceFirst
	 * @dataProvider dataReplaceFirst
	 */
	public function test_replace_first(string $search, string $replace, string $content, string $expected): void
	{
		$this->assertEquals($expected, StringTools::replaceFirst($search, $replace, $content));
	}

	public static function dataRight(): array
	{
		return [
			[
				'NAME and VALUE',
				'and',
				'default',
				' VALUE',
			],
			[
				'NAME and VALUE',
				'and V',
				'default',
				'ALUE',
			],
			[
				'NAME and VALUE',
				' ',
				'default',
				'and VALUE',
			],
			[
				'NAME and VALUE',
				'D',
				'default',
				'default',
			],
			[
				'NAME and VALUE',
				'D',
				null,
				'NAME and VALUE',
			],
		];
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param string|null $default
	 * @param string $expected
	 * @return void
	 * @dataProvider dataRight
	 * @covers ::right
	 */
	public function testRight(string $haystack, string $needle, ?string $default, string $expected): void
	{
		$this->assertEquals($expected, StringTools::right($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public static function dataReverseLeft(): array
	{
		return [
			[
				'NAME and VALUE',
				'and',
				'default',
				'NAME ',
			],
			[
				'NAME and VALUE',
				'and V',
				'default',
				'NAME ',
			],
			[
				'NAME and VALUE',
				' ',
				'default',
				'NAME and',
			],
			[
				'NAME and VALUE',
				'D',
				'default',
				'default',
			],
			[
				'NAME and VALUE',
				'D',
				null,
				'NAME and VALUE',
			],
		];
	}

	/**
	 * @dataProvider dataReverseLeft
	 * @param string $haystack
	 * @param string $needle
	 * @param mixed $default
	 * @param mixed $expected
	 * @covers ::reverseLeft
	 * @return void
	 */
	public function testReverseLeft(string $haystack, string $needle, ?string $default, string $expected): void
	{
		$this->assertEquals($expected, StringTools::reverseLeft($haystack, $needle, $default));
	}

	/**
	 * @return array
	 */
	public static function dataReverseRight(): array
	{
		return [
			[
				'NAME and VALUE',
				'and',
				'default',
				' VALUE',
			],
			[
				'NAME and VALUE',
				'and V',
				'default',
				'ALUE',
			],
			[
				'NAME and VALUE',
				' ',
				'default',
				'VALUE',
			],
			[
				'NAME and VALUE',
				'D',
				'default',
				'default',
			],
			[
				'NAME and VALUE',
				'D',
				null,
				'NAME and VALUE',
			],
		];
	}

	/**
	 * @dataProvider dataReverseRight
	 * @param string $haystack
	 * @param string $needle
	 * @param string|null $default
	 * @param string $expected
	 * @covers ::reverseRight
	 * @return void
	 */
	public function testReverseRight(string $haystack, string $needle, ?string $default, string $expected): void
	{
		$this->assertEquals($expected, StringTools::reverseRight($haystack, $needle, $default));
	}

	/**
	 * @param mixed $value
	 * @param bool $expected
	 * @return void
	 * @dataProvider dataToBoolOriginal
	 * @covers ::toBool
	 */
	public function test_to_bool(mixed $value, bool $expected): void
	{
		$this->assertEquals($expected, StringTools::toBool($value));
	}

	public static function dataToBoolOriginal(): array
	{
		return [
			[
				true,
				true,
			],
			[
				't',
				true,
			],
			[
				'T',
				true,
			],
			[
				'y',
				true,
			],
			[
				'Y',
				true,
			],
			[
				'Yes',
				true,
			],
			[
				'yES',
				true,
			],
			[
				'oN',
				true,
			],
			[
				'on',
				true,
			],
			[
				'enabled',
				true,
			],
			[
				'trUE',
				true,
			],
			[
				'true',
				true,
			],

			[
				'f',
				false,
			],
			[
				'F',
				false,
			],
			[
				'n',
				false,
			],
			[
				'N',
				false,
			],
			[
				'no',
				false,
			],
			[
				'NO',
				false,
			],
			[
				'OFF',
				false,
			],
			[
				'off',
				false,
			],
			[
				'disabled',
				false,
			],
			[
				'DISABLED',
				false,
			],
			[
				'false',
				false,
			],
			[
				'null',
				false,
			],
			[
				'',
				false,
			],

			[
				0,
				false,
			],
			[
				'0',
				false,
			],

			[
				1,
				true,
			],
			[
				'1',
				true,
			],

			[
				'01',
				true,
			],
			[
				[],
				false,
			],
			[
				new stdClass(),
				true,
			],

		];
	}

	/**
	 * @return array[]
	 */
	public static function dataRemovePrefix(): array
	{
		return [
			[
				[
					'String',
					'stretch',
				],
				[
					'Stri',
					'Stre',
				],
				true,
				[
					'ng',
					'tch',
				],
			],
			[
				'string',
				'str',
				false,
				'ing',
			],
			[
				'string',
				'str',
				true,
				'ing',
			],

			[
				'string',
				'Str',
				false,
				'string',
			],
			[
				'string',
				'Str',
				true,
				'ing',
			],

			[
				'String',
				'str',
				false,
				'String',
			],
			[
				'String',
				'str',
				true,
				'ing',
			],

			[
				'String',
				'Str',
				false,
				'ing',
			],
			[
				'String',
				'Str',
				true,
				'ing',
			],
			[
				'String',
				'Str',
				false,
				'ing',
			],
			[
				[
					'String',
					'Stretch',
				],
				'Str',
				true,
				[
					'ing',
					'etch',
				],
			],
			[
				[
					'String',
					'stretch',
				],
				'Str',
				false,
				[
					'ing',
					'stretch',
				],
			],
			[
				[
					'String',
					'stretch',
				],
				[
					'Stri',
					'Stre',
				],
				false,
				[
					'ng',
					'stretch',
				],
			],
		];
	}

	/**
	 * @param string|array $string
	 * @param string|array $prefix
	 * @param bool $case_insensitive
	 * @param string|array $expected
	 * @return void
	 * @dataProvider dataRemovePrefix
	 * @covers ::removePrefix
	 */
	public function test_removePrefix(string|array $string, string|array $prefix, bool $case_insensitive, string|array $expected): void
	{
		$this->assertEquals($expected, StringTools::removePrefix($string, $prefix, $case_insensitive));
	}

	/**
	 * @return array[]
	 */
	public static function dataPrefixMatch(): array
	{
		return [
			[
				'',
				'',
				'',
			],
			[
				'',
				'',
				' ',
			],
			[
				' ',
				' ',
				' ',
			],
			[
				'dude',
				'dudette',
				'dudesser',
			],
			[
				'',
				'dudette',
				'Dudesser',
			],
			[
				"\t",
				"\t ",
				"\t\t",
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $string
	 * @param string $pattern
	 * @return void
	 * @covers ::prefixMatch
	 * @dataProvider dataPrefixMatch
	 */
	public function test_prefixMatch(string $expected, string $string, string $pattern): void
	{
		$this->assertEquals($expected, StringTools::prefixMatch($string, $pattern));
	}

	/**
	 * @return array[]
	 */
	public static function dataRemoveSuffix(): array
	{
		return [
			[
				'string',
				'ing',
				false,
				'str',
			],
			[
				'string',
				'ing',
				true,
				'str',
			],

			[
				'string',
				'ING',
				false,
				'string',
			],
			[
				'string',
				'ING',
				true,
				'str',
			],

			[
				'String',
				'ing',
				false,
				'Str',
			],
			[
				'String',
				'ing',
				true,
				'Str',
			],

			[
				'String',
				'Ing',
				false,
				'String',
			],
			[
				'String',
				'Ing',
				true,
				'Str',
			],

			[
				[
					'String',
					'string',
					'Blowing',
					'EatING',
				],
				'Ing',
				true,
				[
					'Str',
					'str',
					'Blow',
					'Eat',
				],
			],
			[
				[
					'String',
					'string',
					'Blowing',
					'EatIng',
				],
				'Ing',
				false,
				[
					'String',
					'string',
					'Blowing',
					'Eat',
				],
			],
		];
	}

	/**
	 * @param string|array $string
	 * @param string $suffix
	 * @param bool $case_insensitive
	 * @param string|array $expected
	 * @return void
	 * @dataProvider dataRemoveSuffix
	 * @covers ::removeSuffix
	 */
	public function testRemoveSuffix(string|array $string, string $suffix, bool $case_insensitive, string|array $expected): void
	{
		$this->assertEquals($expected, StringTools::removeSuffix($string, $suffix, $case_insensitive));
	}

	public static function dataZeroPad(): array
	{
		return [
			[
				'0',
				2,
				'00',
			],
			[
				'00',
				2,
				'00',
			],
			[
				'1',
				2,
				'01',
			],
			[
				'01',
				2,
				'01',
			],
			[
				'0',
				3,
				'000',
			],
			[
				'00',
				3,
				'000',
			],
			[
				'1',
				3,
				'001',
			],
			[
				'01',
				3,
				'001',
			],
			[
				'xx',
				4,
				'00xx',
			],
		];
	}

	/**
	 * @param string $string
	 * @param int $length
	 * @param string $expected
	 * @return void
	 * @covers ::zeroPad
	 * @dataProvider dataZeroPad
	 */
	public function test_zero_pad(string $string, int $length, string $expected): void
	{
		$this->assertEquals($expected, StringTools::zeroPad($string, $length));
	}

	public static function dataFilter(): array
	{
		return [
			[true, '', [], true],
			[false, '', [], false],
			[false, '', [], false],
			[
				true,
				'foo.php',
				[
					'/.*\.php$/' => true,
				],
				null,
			],
			[
				null,
				'foo.php.no',
				[
					'/.*\.php$/' => true,
				],
				null,
			],
			[
				false,
				'user/.svn/',
				[
					'/\.svn/' => false,
					true,
				],
				null,
			],
			[
				false,
				'code/split-testing/php/.cvsignore',
				[
					'/php/' => false,
				],
				true,
			],
		];
	}

	/**
	 * @param bool|null $expected
	 * @param string $string
	 * @param array $rules
	 * @param bool|null $default
	 * @return void
	 * @dataProvider dataFilter
	 * @covers ::filter
	 */
	public function test_filter(null|bool $expected, string $string, array $rules, bool|null $default): void
	{
		$this->assertEquals($expected, StringTools::filter($string, $rules, $default), "StringTools::filter($string, " . PHP::dump($rules) . ', ' . PHP::dump($default) . ') != ' . PHP::dump($expected));
	}

	/**
	 * @param string $expected
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @param string $encoding
	 * @return void
	 * @dataProvider dataSubstring
	 * @covers ::substring
	 */
	public function test_substr(string $expected, string $string, int $start, int $length, string $encoding): void
	{
		$this->assertEquals($expected, StringTools::substring($string, $start, $length, $encoding));
	}

	public static function dataSubstring(): array
	{
		$sample1 = '😉2345🤣789🙄1👍3456789😁';
		return [
			[
				'😉',
				$sample1,
				0,
				1,
				'',
			],
			[
				'😉',
				$sample1,
				0,
				1,
				'UTF-8',
			],
			[
				'😁',
				$sample1,
				-1,
				1,
				'',
			],
			[
				'😁',
				$sample1,
				-1,
				1,
				'UTF-8',
			],
			[
				'😉2345',
				$sample1,
				0,
				5,
				'',
			],
			[
				$sample1,
				$sample1,
				0,
				20,
				'',
			],
			[
				$sample1,
				$sample1,
				0,
				20,
				'UTF-8',
			],
			[
				'3456789',
				$sample1,
				12,
				7,
				'UTF-8',
			],
		];
	}

	/**
	 * @return void
	 * @coversNothing
	 */
	public function test_PHP_substr(): void
	{
		// Never knew this
		$foo = 'OK,';
		$result = substr($foo, 3);
		$this->assertEquals('string', gettype($result));
		$this->assertEquals('', $result);
	}

	/**
	 * @return void
	 * @covers ::replaceFirst
	 */
	public function test_replace_first1(): void
	{
		$this->assertEquals('bbracadabra', StringTools::replaceFirst('a', 'b', 'abracadabra'));
		$this->assertEquals('astrapcadabra', StringTools::replaceFirst('bra', 'strap', 'abracadabra'));
	}

	/**
	 * @param array $expected
	 * @param string $string
	 * @param int $splitLength
	 * @param string $encoding
	 * @return void
	 * @dataProvider dataSplit
	 * @covers ::split
	 */
	public function testSplit(array $expected, string $string, int $splitLength, string $encoding): void
	{
		$this->assertEquals($expected, StringTools::split($string, $splitLength, $encoding));
	}

	public static function dataSplit(): array
	{
		return [
			[
				[
					'😉',
					'🤣',
					'🙄',
					'👍',
				],
				'😉🤣🙄👍',
				-1,
				'UTF-8',
			],
			[
				[
					'😉',
					'🤣',
					'🙄',
					'👍',
				],
				'😉🤣🙄👍',
				1,
				'UTF-8',
			],
			[
				[
					'😉🤣',
					'🙄👍',
				],
				'😉🤣🙄👍',
				2,
				'UTF-8',
			],
			[
				[
					'wo',
					'rd',
					's😉',
					'🤣🙄',
					'👍',
				],
				'words😉🤣🙄👍',
				2,
				'UTF-8',
			],
			[
				[
					'wor',
					'ds😉',
					'🤣🙄👍',
				],
				'words😉🤣🙄👍',
				3,
				'UTF-8',
			],
			[
				[
					'word',
					's😉🤣🙄',
					'👍',
				],
				'words😉🤣🙄👍',
				4,
				'UTF-8',
			],
		];
	}

	public static function dataQuoteCSVRow(): array
	{
		return [
			[
				"ID,Name\r\n",
				[
					'ID',
					'Name',
				],
			],
			[
				"\"This \"\"has\"\" quotes\",\"Oxford, is it required?\"\r\n",
				[
					'This "has" quotes',
					'Oxford, is it required?',
				],
			],
			[
				"\"Just a new\nline\",Nothing\r\n",
				[
					"Just a new\nline",
					'Nothing',
				],
			],
			[
				"Simple,Line\r\n",
				[
					'Simple',
					'Line',
				],
			],
		];
	}

	public static function dataLength(): array
	{
		return [
			[
				4,
				'😉🤣🙄👍',
				'',
			],
			[
				4,
				'😉🤣🙄👍',
				'UTF-8',
			],
			[
				9,
				'words😉🤣🙄👍',
				'UTF-8',
			],
		];
	}

	/**
	 * @dataProvider dataLength
	 * @covers ::length
	 */
	public function testLength(int $expected, string $string, string $encoding): void
	{
		$this->assertEquals($expected, StringTools::length($string, $encoding));
	}

	/**
	 * @param string $expected
	 * @param array $row
	 * @return void
	 * @dataProvider dataQuoteCSVRow
	 * @covers ::quoteCSVRow
	 */
	public function testQuoteCSVRow(string $expected, array $row): void
	{
		$this->assertEquals($expected, StringTools::quoteCSVRow($row));
	}

	/**
	 * @dataProvider dataQuoteCSVRow
	 * @param string $row_expected
	 * @param array $row
	 * @return void
	 * @covers ::quoteCSVRows
	 */
	public function testQuoteCSVRows(string $row_expected, array $row): void
	{
		$total = $this->randomInteger(3, 100);
		$expected = str_repeat($row_expected, $total);
		$rows = array_fill(0, $total, $row);
		$this->assertEquals($expected, StringTools::quoteCSVRows($rows));
	}
}
