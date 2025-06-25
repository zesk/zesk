<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 * @package    zesk
 * @subpackage test
 */

namespace testZesk;

use stdClass;
use zesk\ArrayTools;
use zesk\Types;

/**
 * @uses   ArrayTools
 * @see    ArrayToolsTest
 */
abstract class ArrayToolsData
{
	public static function map(): array
	{
		return [
			[
				'ala{B}{c}{C}[D][d]',
				'{a}{B}{c}{C}[D][d]',
				['a' => 'ala'],
				false,
				'{',
				'}',
			],
			[
				'ala{b}{c}{c}[D][d]',
				'{a}{B}{c}{C}[D][d]',
				['a' => 'ala'],
				true,
				'{',
				'}',
			],
			[
				'ala[B]{c}{C}[D][d]',
				'[a][B]{c}{C}[D][d]',
				['a' => 'ala'],
				false,
				'[',
				']',
			],
			[
				'ala[b]{c}{C}[d][d]',
				'[a][B]{c}{C}[D][d]',
				['a' => 'ala'],
				true,
				'[',
				']',
			],
		];
	}

	public static function simplify(): array
	{
		return [
			[
				[2 => []],
				[2 => new stdClass()],
			],
			[
				[
					[2 => 'very sexy'],
					[2 => 'very lame'],
					[
						'la' => 'se',
						'me' => 'xy',
					],
				],
				[
					[2 => 'very sexy'],
					[2 => 'very lame'],
					[
						'la' => 'se',
						'me' => 'xy',
					],
				],
			],
		];
	}

	public static function replaceSubstrings(): array
	{
		return [
			[
				'very sexy',
				'very lame',
				[
					'la' => 'se',
					'me' => 'xy',
				],
			],
			[
				[2 => 'very sexy'],
				[2 => 'very lame'],
				[
					'la' => 'se',
					'me' => 'xy',
				],
			],
		];
	}

	public static function findInsensitive(): array
	{
		$needles = [
			'Aa',
			'rathI',
			'lonGs',
			'sentance',
		];
		return [
			[
				null,
				'A rather long sentence',
				$needles,
			],
			[
				[
					'A rather long senaatence',
					$needles[0],
					0,
				],
				'A rather long senaatence',
				$needles,
			],
			[
				[
					'A rather long sentence rathI',
					$needles[1],
					1,
				],
				'A rather long sentence rathI',
				$needles,
			],
			[
				[
					'lonGSA rather long sentence',
					$needles[2],
					2,
				],
				'lonGSA rather long sentence',
				$needles,
			],
			[
				[
					'A rather long sentance',
					$needles[3],
					3,
				],
				'A rather long sentance',
				$needles,
			],
		];
	}

	public static function changeValueCase(): array
	{
		return [
			[
				[
					'a' => 'ABC',
					'b' => 'BCD',
					'c' => 'def',
				],
				[
					'a' => 'abc',
					'b' => 'bcd',
					'c' => 'def',
				],
			],
			[
				[
					'a' => 'A',
					'b' => 'lowercasething',
					'C' => 'LoWeRCaSeThInG',
					'D' => 99,
				],
				[
					'a' => 'a',
					'b' => 'lowercasething',
					'C' => 'lowercasething',
					'D' => 99,
				],
			],
		];
	}

	public static function valuesFlipCopy(): array
	{
		// ordering is backwards - [input, expected]
		return [
			[
				[
					'A',
					'B',
					'C',
				],
				[
					'A' => 'A',
					'B' => 'B',
					'C' => 'C',
				],
			],
			[
				[
					'one',
					'two',
					'three',
				],
				[
					'one' => 'one',
					'two' => 'two',
					'three' => 'three',
				],
			],
			[
				[
					'1',
					'2',
					'3',
					'fish',
					'4',
					'5',
					'1',
					'2',
				],
				[
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'fish' => 'fish',
					'4' => '4',
					'5' => '5',
				],
			],
			[
				[
					'a' => 'A',
					'b' => 'B',
				],
				[
					'A' => 'A',
					'B' => 'B',
				],
			],
			[
				[
					'A',
					'B',
				],
				[
					'A' => 'A',
					'B' => 'B',
				],
			],
		];
	}

	public static function suffixValues(): array
	{
		return [
			[
				[
					'BoyBig',
					'GirlBig',
					'BabyBig',
				],
				[
					'Boy',
					'Girl',
					'Baby',
				],
				'Big',
			],
			[
				[
					'0-Things',
					'1-Things',
					'2-Things',
					'3-Things',
					'4-Things',
					'5-Things',
					'6-Things',
				],
				[
					0,
					1,
					2,
					3,
					4,
					5,
					6,
				],
				'-Things',
			],
		];
	}

	/**
	 * @return array[]
	 * @see ArrayToolsTest::test_include_exclude()
	 */
	public static function insert(): array
	{
		$arr1 = [
			'x' => 'x',
			'a' => 'a',
			'y' => 'y',
		];
		$arr2 = [
			'p' => 'p',
		];
		$arr3 = [
			'p' => 'p',
			'q' => 'q',
		];

		return [
			[
				[
					'x' => 'x',
					'a' => 'a',
					'p' => 'p',
					'y' => 'y',
				],
				$arr1,
				'a',
				$arr2,
				false,
			],
			[
				[
					'x' => 'x',
					'p' => 'p',
					'a' => 'a',
					'y' => 'y',
				],
				$arr1,
				'a',
				$arr2,
				true,
			],
			[
				[
					'x' => 'x',
					'a' => 'a',
					'p' => 'p',
					'q' => 'q',
					'y' => 'y',
				],
				$arr1,
				'a',
				$arr3,
				false,
			],
			[
				[
					'x' => 'x',
					'p' => 'p',
					'q' => 'q',
					'a' => 'a',
					'y' => 'y',
				],
				$arr1,
				'a',
				$arr3,
				true,
			],
		];
	}

	public static function wrapKeys(): array
	{
		return [
			[
				[
					'{UserID}' => 'UserUser',
					'{UserName}' => 'UserUser',
					'{UserDig}' => 'UserUser',
				],
				[
					'UserID' => 'UserUser',
					'UserName' => 'UserUser',
					'UserDig' => 'UserUser',
				],
				'{',
				'}',
			],
		];
	}

	public static function keysRemove(): array
	{
		return [
			[
				[
					1 => 1,
					3 => 3,
					5 => 5,
				],
				[
					0,
					1,
					2,
					3,
					4,
					5,
					6,
				],
				Types::toList('0;2;4;6'),
			],
		];
	}

	public static function prefixValues(): array
	{
		return [
			[
				[
					'Homing-Pigeon-0',
					'Homing-Pigeon-1',
					'Homing-Pigeon-2',
					'Homing-Pigeon-3',
					'Homing-Pigeon-4',
					'Homing-Pigeon-5',
				],
				[
					0,
					1,
					2,
					3,
					4,
					5,
				],
				'Homing-Pigeon-',
			],
			[
				[
					'BigBoy',
					'BigGirl',
					'BigBaby',
				],
				[
					'Boy',
					'Girl',
					'Baby',
				],
				'Big',
			],
		];
	}

	public static function keysRemovePrefix(): array
	{
		return [
			[
				[
					'ID' => 'UserUser',
					'Name' => 'UserUser',
					'Dig' => 'UserUser',
				],
				[
					'UserID' => 'UserUser',
					'UserName' => 'UserUser',
					'UserDig' => 'UserUser',
				],
				'User',
			],
		];
	}

	public static function find(): array
	{
		$files = [
			'cc_form.php',
			'want-to-be-complete',
			'setup/email.php',
			'import-log.php',
			'/ab_try.php',
			'/ab.php',
			'/keyword/update.php',
			'/report/reporter.php',
			'/report/geo.php',
			'/setup/landings-generate.php',
		];
		return [
			[
				[
					'want-to-be-complete',
					'want',
					1,
					1,
				],
				$files,
				[
					'report',
					'want',
				],
			],
			[
				[
					'/setup/landings-generate.php',
					'/setup/landings-generate.php',
					9,
				],
				$files,
				'/setup/landings-generate.php',
			],
		];
	}

	public static function keysRemoveSuffix(): array
	{
		return [
			[
				[
					'UserID' => 'UserUser',
					'UserName' => 'UserUser',
					'UserDig' => 'UserUser',
				],
				[
					'UserID' => 'UserUser',
					'UserName' => 'UserUser',
					'UserDig' => 'UserUser',
				],
				'User',
			],
			[
				[
					'UserID' => 'UserUser',
					'UserName' => 'UserUser',
					'UserDig' => 'UserUser',
					'NoSuffix' => 'UserUser',
				],
				[
					'UserIDUser' => 'UserUser',
					'UserNameUser' => 'UserUser',
					'UserDigUser' => 'UserUser',
					'NoSuffix' => 'UserUser',
				],
				'User',
			],
		];
	}

	public static function valuesRemovePrefix(): array
	{
		return [
			[
				[
					'Bar',
					'Bird',
					'Plan',
					'gooCmon',
				],
				[
					'GooBar',
					'GooBird',
					'GooPlan',
					'gooCmon',
				],
				'Goo',
			],
			[
				[
					'First' => 'Bar',
					'One' => 'Bird',
					'To' => 'Plan',
					'Dive' => 'gooCmon',
				],
				[
					'First' => 'GooBar',
					'One' => 'GooBird',
					'To' => 'GooPlan',
					'Dive' => 'gooCmon',
				],
				'Goo',
			],
		];
	}

	public static function valuesRemoveSuffix(): array
	{
		return [
			[
				[
					'0',
					'1',
					'2',
					'3-Thingy',
					'0',
				],
				[
					'0-Thing',
					'1-Thing',
					'2-Thing',
					'3-Thingy',
					'0-Thing',
				],
				'-Thing',
			],
			[
				[
					2 => '0',
					'1',
					'2',
					11 => '3-Thingy',
					'null' => '0',
				],
				[
					2 => '0-Thing',
					'1-Thing',
					'2-Thing',
					11 => '3-Thingy',
					'null' => '0-Thing',
				],
				'-Thing',
			],
		];
	}

	public static function filterKeyPrefixes(): array
	{
		$a = [
			'AHello' => 94,
			'Dog' => 34,
			'Hello' => 1,
			'hello_there' => 2,
			'HELLO.THERE' => new stdClass(),
			'Hello.Kitty' => 'Kitty',
		];
		return [
			[
				[
					'Hello' => 1,
					'hello_there' => 2,
					'HELLO.THERE' => new stdClass(),
					'Hello.Kitty' => 'Kitty',
				],
				$a,
				'hello',
				true,
			],
			[
				[
					'hello_there' => 2,
				],
				[
					'AHello' => 94,
					'Dog' => 34,
					'Hello' => 1,
					'hello_there' => 2,
					'HELLO.THERE' => new stdClass(),
					'Hello.Kitty' => 'Kitty',
				],
				'hello',
				false,
			],
			[
				[
					'AHello' => 94,
					'Hello' => 1,
					'Hello.Kitty' => 'Kitty',
				],
				$a,
				[
					'Hello',
					'AHello',
				],
				false,
			],
			[
				[
					'Hello.Kitty' => 'Kitty',
				],
				$a,
				[
					'Hello.',
					'Dog ',
				],
				false,
			],
			[
				[
					'HELLO.THERE' => new stdClass(),
					'Hello.Kitty' => 'Kitty',
				],
				$a,
				[
					'Hello.',
					'Dog ',
				],
				true,
			],
		];
	}

	public static function filter(): array
	{
		$arr = [
			'1',
			'2,3',
			'4',
		];
		$x = [
			'A' => 'Kent',
			'b' => 'Ruler',
			'C' => 'another',
			3 => 'dogs',
		];

		return [
			[
				[
					'1',
					2 => '4',
				],
				$arr,
				'0;2',
			],
			[
				[
					'1',
					2 => '4',
				],
				$arr,
				[
					0,
					2,
				],
			],
			[
				[
					'1',
					2 => '4',
				],
				$arr,
				[
					'0',
					'2',
				],
			],
			[
				$x,
				$x,
				'A;b;C;3',
			],
			[
				[
					3 => 'dogs',
				],
				$x,
				'a;B;c;3',
			],
			[
				[
					'A' => 'Kent',
					3 => 'dogs',
				],
				[
					'A' => 'Kent',
					3 => 'dogs',
				],
				'A;3',
			],
			[
				[
					'A' => 'B',
					1 => 'A',
				],
				[
					'A' => 'B',
					'B',
					'A',
					'C' => 'D',
				],
				'A;1',
			],
		];
	}

	public static function trim(): array
	{
		return [
			[
				[
					'foo',
					'bar',
				],
				[
					' foo ',
					" \n\t\0bar\n\t ",
				],
			],
		];
	}

	public static function has(): array
	{
		$array = [
			0 => 'hello',
			3 => 'dude',
			'kitty' => 'cat',
		];

		return [
			[
				true,
				$array,
				'0;3',
			],
			[
				true,
				$array,
				3,
			],
			[
				true,
				$array,
				0,
			],
			[
				true,
				$array,
				'kitty',
			],
			[
				false,
				$array,
				1,
			],
			[
				false,
				$array,
				'Kitty',
			],
		];
	}

	public static function merge(): array
	{
		$a1 = [
			'hello' => [
				'now' => 'yes',
				'i' => 'do',
				'nooooow!' => [
					'where' => 'are',
					'you' => 'going',
					'what' => 'do you mean',
				],
			],
		];
		$a2 = [
			'hold-on' => 'no',
			'hello' => [
				'now1' => 'yes',
				'i2' => 'do',
				'i' => 'don\'t',
				'nooooow!' => [
					'where1' => 'are',
					'you2' => 'going',
					'what3' => 'do you mean',
				],
			],
		];
		$expected = [
			'hello' => [
				'now' => 'yes',
				'i' => 'don\'t',
				'nooooow!' => [
					'where' => 'are',
					'you' => 'going',
					'what' => 'do you mean',
					'where1' => 'are',
					'you2' => 'going',
					'what3' => 'do you mean',
				],
				'now1' => 'yes',
				'i2' => 'do',
			],
			'hold-on' => 'no',
		];
		return [
			[
				$expected,
				$a1,
				$a2,
			],
		];
	}

	public static function dataTranspose(): array
	{
		$arr = [
			[
				'1',
				'2',
				'3',
			],
			[
				'4',
				'5',
				'6',
			],
			[
				'7',
				'8',
				'9',
			],
		];
		$expected = [
			[
				'1',
				'4',
				'7',
			],
			[
				'2',
				'5',
				'8',
			],
			[
				'3',
				'6',
				'9',
			],
		];
		$tests[] = [
			$expected,
			$arr,
		];
		$arr = [
			[
				'1',
				'2',
				'3',
				'4',
			],
			[
				'5',
				'6',
				'7',
				'8',
			],
		];
		$expected = [
			[
				'1',
				'5',
			],
			[
				'2',
				'6',
			],
			[
				'3',
				'7',
			],
			[
				'4',
				'8',
			],
		];
		$tests[] = [
			$expected,
			$arr,
		];
		return $tests;
	}

	public static function dataIsList(): array
	{
		return [
			[
				false,
				null,
			],
			[
				false,
				false,
			],
			[
				false,
				true,
			],
			[
				false,
				0,
			],
			[
				false,
				1,
			],
			[
				false,
				'mixed',
			],
			[
				false,
				new stdClass(),
			],
			[
				true,
				[],
			],
			[
				true,
				[
					1,
					3,
				],
			],
			[
				false,
				[
					'1',
					2 => '3',
				],
			],
			[
				false,
				[
					'1',
					2 => '3',
					4,
					5 => 'f',
				],
			],
			[
				true,
				[
					1,
					2,
					3,
					4,
					5,
					9,
				],
			],
			[
				true,
				array_merge([
					1,
					2,
					3,
					4,
					5,
					9,
				], [
					'a',
					1,
					2,
					3,
					4,
					5,
					9,
				]),
			],
			[
				false,
				new DummyObject(),
			],
		];
	}

	public static function wrap(): array
	{
		return [
			[
				[],
				[],
				'',
				'',
			],
			[
				[
					'a',
				],
				[
					'a',
				],
				'',
				'',
			],
			[
				[
					'a' => 'ab',
				],
				[
					'a' => 'b',
				],
				'a',
				'',
			],
			[
				[
					'a' => 'abbb',
				],
				['a' => 'b', ],
				'a',
				'bb',
			],
			[
				[
					'a' => 'abcc',
					2 => 'abcc',
					412312 => 'a54cc',
				],
				[
					'a' => 'b',
					2 => 'b',
					412312 => 54,
				],
				'a',
				'cc',
			],
			[
				[
					'{a}',
					'{b}',
					'{c}',
				],
				[
					'a',
					'b',
					'c',
				],
				'{',
				'}',
			],
		];
	}

	public static function prefixKeys(): array
	{
		return [
			[
				[
					'Dudea' => 'a',
					'Dudeb' => 'b',
					'Dudec' => 'c',
					'Dude9' => 'd',
				],
				[
					'a' => 'a',
					'b' => 'b',
					'c' => 'c',
					9 => 'd',
				],
				'Dude',
			],
		];
	}

	public static function hasAnyValues(): array
	{
		return [
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				'a',
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				'b',
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				'c',
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				'd',
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				['a'],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				['b'],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				['c'],
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				['d'],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					'a',
					'd',
				],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					'b',
					'd',
				],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					'c',
					'd',
				],
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				[
					'd',
					'd',
				],
			],
		];
	}

	public static function hasAnyKey(): array
	{
		return [
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				0,
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				1,
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				2,
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				'd',
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[0],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[1],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[2],
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				['d'],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					0,
					'd',
				],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					1,
					'd',
				],
			],
			[
				true,
				[
					'a',
					'b',
					'c',
				],
				[
					2,
					'd',
				],
			],
			[
				false,
				[
					'a',
					'b',
					'c',
				],
				[
					'd',
					'd',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				'a',
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				'b',
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				'c',
			],
			[
				false,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				'd',
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				['a'],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				['b'],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				['c'],
			],
			[
				false,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				['d'],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'a',
					'd',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'b',
					'd',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'c',
					'd',
				],
			],
			[
				false,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'd',
					'd',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'a',
					'b',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'b',
					'c',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'c',
					'a',
				],
			],
			[
				false,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'd',
					'd',
					0,
					1,
					2,
					3,
					4,
					5,
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'a',
					'b',
					'c',
				],
			],
			[
				true,
				[
					'a' => 0,
					'b' => 1,
					'c' => 2,
				],
				[
					'a',
					'b',
					'c',
					'd',
				],
			],
		];
	}

	public static function keysMap(): array
	{
		return [
			[
				['a' => 1],
				[],
				['a' => 1],
			],
			[
				[
					'one' => 1,
					'two' => 2,
					'three' => 3,
					'four' => 4,
				],
				[
					'one' => 'un',
					'two' => 'deux',
					'three' => 'trois',
				],
				[
					'un' => 1,
					'deux' => 2,
					'trois' => 3,
					'four' => 4,
				],
			],
			[
				[
					'a' => 'a',
					'b' => 'b',
					'Aardvark' => 'animal',
					123 => 'one-two-three',
					'Zebra' => 'stripes',
				],
				[
					'a' => 'b',
					123 => 'Zamboni',
				],
				[
					'b' => 'a',
					'Aardvark' => 'animal',
					'Zamboni' => 'one-two-three',
					'Zebra' => 'stripes',
				],
			],
			[
				[
					'a' => 'a',
					'b' => 'b',
					'Aardvark' => 'animal',
					123 => 'one-two-three',
					'Zebra' => 'stripes',
				],
				[
					'a' => 'c',
					123 => 'Zamboni',
				],
				[
					'b' => 'b',
					'c' => 'a',
					'Aardvark' => 'animal',
					'Zamboni' => 'one-two-three',
					'Zebra' => 'stripes',
				],
			],
		];
	}

	public static function pairValues(): array
	{
		return [
			[
				['foo' => 'mix=up'],
				'=',
				['mix' => 'up'],
			],
			[
				['foo' => 'mix=up'],
				' ',
				['foo' => 'mix=up'],
			],
			[
				[99 => 'mix=up'],
				' ',
				[99 => 'mix=up'],
			],
			[
				[
					'foo' => 'mix=up',
					'dog = poo',
					'place=place',
				],
				'=',
				[
					'mix' => 'up',
					'dog ' => ' poo',
					'place' => 'place',
				],
			],
		];
	}

	public static function valuesMap(): array
	{
		return [
			[
				[
					1 => 'one',
					2 => 'two',
					3 => 'three',
					4 => 'four',
				],
				[],
				[
					1 => 'one',
					2 => 'two',
					3 => 'three',
					4 => 'four',
				],
			],
			[
				[
					1 => 'one',
					2 => 'two',
					3 => 'three',
					4 => 'four',
				],
				[
					'one' => 'un',
					'two' => 'deux',
					'three' => 'trois',
				],
				[
					1 => 'un',
					2 => 'deux',
					3 => 'trois',
					4 => 'four',
				],
			],
		];
	}

	public static function keysFind(): array
	{
		return [
			[
				'B',
				[
					'A' => 'A',
					'B' => 'B',
				],
				[
					'B',
					'C',
				],
				'Dude',
			],
			[
				null,
				[
					'A' => 'A',
					'B' => 'B',
				],
				[
					'D',
					'C',
				],
				null,
			],
		];
	}

	public static function max(): array
	{
		return [
			[
				513234,
				[
					1,
					2,
					3,
					4,
					6,
					'513234',
					123,
					-1,
					52145,
				],
				null,
			],
			[
				94123124,
				[
					'1',
					2,
					3,
					99,
					10000,
					12,
					94123123,
					'94123124',
				],
				null,
			],
			[
				44.32,
				[],
				44.32,
			],
			[
				44.32,
				[
					[],
					[],
					[],
					null,
					false,
					true,
				],
				44.32,
			],
		];
	}

	public static function min(): array
	{
		return [
			[
				[
					'-412312',
					4,
					61234,
					6123,
					3,
					new stdClass(),
					[],
					-51235412,
					3,
					123,
					5,
				],
				null,
				-51235412,
			],
			[
				[
					'1',
					2,
					3,
					99,
					10000,
					12,
					94123123,
					'94123124',
					'-23412312',
					10000,
					12,
					94123123,
				],
				null,
				-23412312,
			],
			[
				[],
				44.32,
				44.32,
			],
			[
				[
					[],
					[],
					[],
					null,
					false,
					true,
				],
				44.32,
				44.32,
			],
		];
	}

	public static function append(): array
	{
		return [
			[
				['key' => 1],
				[],
				'key',
				1,
			],
			[
				[
					'key' => [
						1,
						3,
					],
				],
				['key' => 1],
				'key',
				3,
			],
			[
				[
					'key' => [
						1,
						3,
						5,
					],
				],
				[
					'key' => [
						1,
						3,
					],
				],
				'key',
				5,
			],
			[
				[
					'key' => [
						1,
						3,
					],
					'key2' => 3,
				],
				[
					'key' => [
						1,
						3,
					],
				],
				'key2',
				3,
			],
		];
	}

	public static function filterKeys(): array
	{
		return [
			[
				[
					'a' => 1,
					'b' => 2,
				],
				[
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 4,
				],
				[
					'a',
					'b',
				],
				[],
				true,
			],
			[
				[
					'a' => 1,
					'b' => 2,
				],
				[
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 4,
				],
				null,
				[
					'c',
					'd',
				],
				true,
			],
		];
	}

	public static function preg_quote(): array
	{
		return [
			[
				'',
				'',
				'',
			],
			[
				'dude',
				'd',
				"\du\de",
			],
			[
				'We are #1',
				'#',
				"We are \#1",
			],
		];
	}

	public static function prepend(): array
	{
		return [
			[
				[],
				'key',
				1,
				['key' => 1],
			],
			[
				['key' => 1],
				'key',
				2,
				[
					'key' => [
						2,
						1,
					],
				],
			],
			[
				[
					'key' => [
						2,
						1,
					],
				],
				'key',
				77.9,
				[
					'key' => [
						77.9,
						2,
						1,
					],
				],
			],
		];
	}

	public static function listTrim(): array
	{
		return [
			[
				[
					'',
					false,
					null,
					[],
					'a',
					'b',
					'',
					false,
					null,
					[],
					'c',
					'd',
					'',
					false,
					null,
					[],
				],
				[
					'a',
					'b',
					'',
					false,
					null,
					[],
					'c',
					'd',
				],
			],
		];
	}
}
