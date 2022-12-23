<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 */

namespace zesk;

use \stdClass;

class ArrayTools_Test extends UnitTest {
	public function data_changeValueCase(): array {
		return [
			[
				[
					'a' => 'ABC', 'b' => 'BCD', 'c' => 'def',
				], [
					'a' => 'abc', 'b' => 'bcd', 'c' => 'def',
				],
			], [
				[
					'a' => 'A', 'b' => 'lowercasething', 'C' => 'LoWeRCaSeThInG', 'D' => 99,
				], [
					'a' => 'a', 'b' => 'lowercasething', 'C' => 'lowercasething', 'D' => 99,
				],
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_changeValueCase
	 */
	public function test_changeValueCase(array $array, array $expected): void {
		$this->assertEquals($expected, ArrayTools::changeValueCase($array));
	}

	public function data_valuesFlipCopy(): array {
		// ordering is backwards - [input, expected]
		return [
			[
				[
					'A', 'B', 'C',
				], [
					'A' => 'A', 'B' => 'B', 'C' => 'C',
				],
			], [
				[
					'one', 'two', 'three',
				], [
					'one' => 'one', 'two' => 'two', 'three' => 'three',
				],
			], [
				[
					'1', '2', '3', 'fish', '4', '5', '1', '2',
				], [
					'1' => '1', '2' => '2', '3' => '3', 'fish' => 'fish', '4' => '4', '5' => '5',
				],
			], [
				[
					'a' => 'A', 'b' => 'B',
				], [
					'A' => 'A', 'B' => 'B',
				],
			], [
				[
					'A', 'B',
				], [
					'A' => 'A', 'B' => 'B',
				],
			],
		];
	}

	/**
	 * @param array $array
	 * @param array $expected
	 * @return void
	 * @dataProvider data_valuesFlipCopy
	 */
	public function test_valuesFlipCopy(array $array, array $expected): void {
		$this->assertEquals($expected, ArrayTools::valuesFlipCopy($array));
	}

	/**
	 * @param array $expected
	 * @param array $array
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider data_wrap
	 */
	public function test_wrap(array $expected, array $array, string $prefix, string $suffix): void {
		$this->assertEquals($expected, ArrayTools::wrapValues($array, $prefix, $suffix));
	}

	public function data_wrap(): array {
		return [
			[[], [], '', ''], [
				[
					'a',
				], [
					'a',
				], '', '',
			], [
				[
					'a' => 'ab',
				], [
					'a' => 'b',
				], 'a', '',
			], [
				[
					'a' => 'abbb',
				], ['a' => 'b', ], 'a', 'bb',
			], [
				[
					'a' => 'abcc', 2 => 'abcc', 412312 => 'a54cc',
				], [
					'a' => 'b', 2 => 'b', 412312 => 54,
				], 'a', 'cc',
			], [
				[
					'{a}', '{b}', '{c}',
				], [
					'a', 'b', 'c',
				], '{', '}',
			],
		];
	}

	/**
	 * @param array $expected
	 * @param array $array
	 * @param string $prefix
	 * @return void
	 * @dataProvider data_prefixKeys
	 */
	public function test_prefixKeys(array $expected, array $array, string $prefix): void {
		$this->assertEquals($expected, ArrayTools::prefixKeys($array, $prefix));
	}

	public function data_prefixKeys(): array {
		return [
			[
				[
					'Dudea' => 'a', 'Dudeb' => 'b', 'Dudec' => 'c', 'Dude9' => 'd',
				], [
					'a' => 'a', 'b' => 'b', 'c' => 'c', 9 => 'd',
				], 'Dude',
			],
		];
	}

	public function test_suffix(): void {
		$a = [
			'Boy', 'Girl', 'Baby',
		];
		$p = 'Big';
		$this->assertEquals(ArrayTools::suffixValues($a, $p), [
			'BoyBig', 'GirlBig', 'BabyBig',
		]);
		$arr = [
			0, 1, 2, 3, 4, 5, 6,
		];
		$str = '-Things';
		$result = ArrayTools::suffixValues($arr, $str);
		$result_correct = [
			'0-Things', '1-Things', '2-Things', '3-Things', '4-Things', '5-Things', '6-Things',
		];
		$this->assertEquals($result, $result_correct);
	}

	public function test_keysRemove(): void {
		$arr = [
			0, 1, 2, 3, 4, 5, 6,
		];
		$keys = '0;2;4;6';
		$result = ArrayTools::keysRemove($arr, toList($keys));
		$result_correct = [
			1 => 1, 3 => 3, 5 => 5,
		];
		$this->assertEquals($result, $result_correct);
	}

	public function data_hasValue(): array {
		return [
			[], [], false,

		];
	}

	public function test_include_exclude(): void {
		$a = [
			'a', 'b', 'c', 'd',
		];
		$include = '';
		$exclude = '';
		$lower = true;
		$result = ArrayTools::include_exclude($a, 'a;b;e', $exclude, $lower);
		$this->assertEquals($result, [
			'a', 'b',
		]);
		$result = ArrayTools::include_exclude($a, '', 'a;b;e', $lower);
		$this->assertEquals($result, [
			2 => 'c', 3 => 'd',
		]);

		$a = [
			'a', 'B', 'c', 'd',
		];
		// Default is to retain case
		$result = ArrayTools::include_exclude($a, '', 'a;b;e');
		$this->assertEquals($result, [
			1 => 'B', 2 => 'c', 3 => 'd',
		]);
	}

	/**
	 * @param bool $expected
	 * @param array $array
	 * @param string|array $values
	 * @return void
	 * @dataProvider data_hasAnyValues
	 */
	public function test_hasAnyValue(bool $expected, array $array, string|array $values): void {
		$this->assertEquals($expected, ArrayTools::hasAnyValue($array, $values));
	}

	public function data_hasAnyValues(): array {
		return [
			[true, ['a', 'b', 'c'], 'a'], [true, ['a', 'b', 'c'], 'b'], [true, ['a', 'b', 'c'], 'c'],
			[false, ['a', 'b', 'c'], 'd'], [true, ['a', 'b', 'c'], ['a']], [true, ['a', 'b', 'c'], ['b']],
			[true, ['a', 'b', 'c'], ['c']], [false, ['a', 'b', 'c'], ['d']], [true, ['a', 'b', 'c'], ['a', 'd']],
			[true, ['a', 'b', 'c'], ['b', 'd']], [true, ['a', 'b', 'c'], ['c', 'd']],
			[false, ['a', 'b', 'c'], ['d', 'd']],
		];
	}

	/**
	 * @param bool $expected
	 * @param array $array
	 * @param string|array $values
	 * @return void
	 * @dataProvider data_hasAnyKey
	 */
	public function test_hasAnyKey(bool $expected, array $array, string|array|int $keys): void {
		$this->assertEquals($expected, ArrayTools::hasAnyKey($array, $keys));
	}

	public function data_hasAnyKey(): array {
		return [
			[true, ['a', 'b', 'c'], 0], [true, ['a', 'b', 'c'], 1], [true, ['a', 'b', 'c'], 2],
			[false, ['a', 'b', 'c'], 'd'], [true, ['a', 'b', 'c'], [0]], [true, ['a', 'b', 'c'], [1]],
			[true, ['a', 'b', 'c'], [2]], [false, ['a', 'b', 'c'], ['d']], [true, ['a', 'b', 'c'], [0, 'd']],
			[true, ['a', 'b', 'c'], [1, 'd']], [true, ['a', 'b', 'c'], [2, 'd']], [false, ['a', 'b', 'c'], ['d', 'd']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], 'a'], [true, ['a' => 0, 'b' => 1, 'c' => 2], 'b'],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], 'c'], [false, ['a' => 0, 'b' => 1, 'c' => 2], 'd'],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['a']], [true, ['a' => 0, 'b' => 1, 'c' => 2], ['b']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['c']], [false, ['a' => 0, 'b' => 1, 'c' => 2], ['d']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['a', 'd']], [true, ['a' => 0, 'b' => 1, 'c' => 2], ['b', 'd']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['c', 'd']], [false, ['a' => 0, 'b' => 1, 'c' => 2], ['d', 'd']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['a', 'b']], [true, ['a' => 0, 'b' => 1, 'c' => 2], ['b', 'c']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['c', 'a']],
			[false, ['a' => 0, 'b' => 1, 'c' => 2], ['d', 'd', 0, 1, 2, 3, 4, 5]],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['a', 'b', 'c']],
			[true, ['a' => 0, 'b' => 1, 'c' => 2], ['a', 'b', 'c', 'd']],
		];
	}

	public function test_increment(): void {
		$arr = [];
		$k = 'item';
		$result = ArrayTools::increment($arr, $k);
		$this->assertEquals(1, $result);
		$this->assertEquals($arr, [
			'item' => 1,
		]);
		$result = ArrayTools::increment($arr, $k);
		$this->assertEquals(2, $result);
		$this->assertEquals($arr, [
			'item' => 2,
		]);
		$k = 'decimal';
		$result = ArrayTools::increment($arr, $k, 2.1);
		$this->assertEquals(2.1, $result);
		$this->assertEquals($arr, [
			'item' => 2, 'decimal' => 2.1,
		]);
		$result = ArrayTools::increment($arr, $k, 3.2);
		$this->assertEqualsWithDelta($result, 5.3, 0.000001);
		// 		$this->assertEquals($arr, array(
		// 			"item" => 2,
		// 			"decimal" => 5.3
		// 		));
	}

	public function test_insert(): void {
		$arr1 = [
			'x' => 'x', 'a' => 'a', 'y' => 'y',
		];
		$key = 'a';
		$arr2 = [
			'p' => 'p',
		];
		$this->assertEquals(ArrayTools::insert($arr1, $key, $arr2, false), [
			'x' => 'x', 'a' => 'a', 'p' => 'p', 'y' => 'y',
		], 'basic after');
		$this->assertEquals(ArrayTools::insert($arr1, $key, $arr2, true), [
			'x' => 'x', 'p' => 'p', 'a' => 'a', 'y' => 'y',
		], 'basic before');

		$arr2 = [
			'p' => 'p', 'q' => 'q',
		];

		$this->assertEquals(ArrayTools::insert($arr1, $key, $arr2, false), [
			'x' => 'x', 'a' => 'a', 'p' => 'p', 'q' => 'q', 'y' => 'y',
		], 'multi after');
		$this->assertEquals(ArrayTools::insert($arr1, $key, $arr2, true), [
			'x' => 'x', 'p' => 'p', 'q' => 'q', 'a' => 'a', 'y' => 'y',
		], 'multi before');
	}

	public function test_wrapKeys(): void {
		$arr = [
			'UserID' => 'UserUser', 'UserName' => 'UserUser', 'UserDig' => 'UserUser',
		];
		$arr_result = [
			'{UserID}' => 'UserUser', '{UserName}' => 'UserUser', '{UserDig}' => 'UserUser',
		];
		$prefix = '{';
		$suffix = '}';
		$result = ArrayTools::wrapKeys($arr, $prefix, $suffix);
		$this->assertEquals($result, $arr_result);
	}

	public function test_keysRemovePrefix(): void {
		$arr = [
			'UserID' => 'UserUser', 'UserName' => 'UserUser', 'UserDig' => 'UserUser',
		];
		$str = 'User';
		$result = ArrayTools::keysRemovePrefix($arr, $str);
		$this->assertEquals($result, [
			'ID' => 'UserUser', 'Name' => 'UserUser', 'Dig' => 'UserUser',
		]);
	}

	public function test_keysRemoveSuffix(): void {
		$arr = [
			'UserID' => 'UserUser', 'UserName' => 'UserUser', 'UserDig' => 'UserUser',
		];
		$result_correct = [
			'UserID' => 'UserUser', 'UserName' => 'UserUser', 'UserDig' => 'UserUser',
		];
		$str = 'User';
		$result = ArrayTools::keysRemoveSuffix($arr, $str);
		$this->assertEquals($result, $result_correct);

		$arr = [
			'UserIDUser' => 'UserUser', 'UserNameUser' => 'UserUser', 'UserDigUser' => 'UserUser',
			'NoSuffix' => 'UserUser',
		];
		$result_correct = [
			'UserID' => 'UserUser', 'UserName' => 'UserUser', 'UserDig' => 'UserUser', 'NoSuffix' => 'UserUser',
		];
		$str = 'User';
		$result = ArrayTools::keysRemoveSuffix($arr, $str);
		$this->assertEquals($result, $result_correct);
	}

	public function data_keysMap(): array {
		return [
			[
				['a' => 1], [], ['a' => 1],
			], [
				[
					'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4,
				], [
					'one' => 'un', 'two' => 'deux', 'three' => 'trois',
				], [
					'un' => 1, 'deux' => 2, 'trois' => 3, 'four' => 4,
				],
			], [
				[
					'a' => 'a', 'b' => 'b', 'Aardvark' => 'animal', 123 => 'one-two-three', 'Zebra' => 'stripes',
				], [
					'a' => 'b', 123 => 'Zamboni',
				], [
					'b' => 'a', 'Aardvark' => 'animal', 'Zamboni' => 'one-two-three', 'Zebra' => 'stripes',
				],
			], [
				[
					'a' => 'a', 'b' => 'b', 'Aardvark' => 'animal', 123 => 'one-two-three', 'Zebra' => 'stripes',
				], [
					'a' => 'c', 123 => 'Zamboni',
				], [
					'b' => 'b', 'c' => 'a', 'Aardvark' => 'animal', 'Zamboni' => 'one-two-three', 'Zebra' => 'stripes',
				],
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_keysMap
	 */
	public function test_keysMap(array $array, array $key_map, array $expected): void {
		$result = ArrayTools::keysMap($array, $key_map);
		$this->assertEquals($expected, $result);
	}

	public function data_pairValues(): array {
		return [
			[
				['foo' => 'mix=up'], '=', ['mix' => 'up'],
			], [
				['foo' => 'mix=up'], ' ', ['foo' => 'mix=up'],
			], [
				[99 => 'mix=up'], ' ', [99 => 'mix=up'],
			], [
				[
					'foo' => 'mix=up', 'dog = poo', 'place=place',
				], '=', ['mix' => 'up', 'dog ' => ' poo', 'place' => 'place'],
			],
		];
	}

	/**
	 * @param array $array
	 * @param string $delim
	 * @param array $expected
	 * @return void
	 * @dataProvider data_pairValues
	 */
	public function test_pairValues(array $array, string $delim, array $expected): void {
		$this->assertEquals($expected, ArrayTools::pairValues($array, $delim));
	}

	public function data_valuesMap(): array {
		return [
			[
				[
					1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
				], [], [
					1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
				],
			], [
				[
					1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
				], [
					'one' => 'un', 'two' => 'deux', 'three' => 'trois',
				], [
					1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'four',
				],
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_valuesMap
	 */
	public function test_valuesMap($array, $value_map, $expected): void {
		$result = ArrayTools::valuesMap($array, $value_map);
		$this->assertEquals($expected, $result);
	}

	public function test_merge(): void {
		$a1 = [
			'hello' => [
				'now' => 'yes', 'i' => 'do', 'nooooow!' => [
					'where' => 'are', 'you' => 'going', 'what' => 'do you mean',
				],
			],
		];
		$a2 = [
			'hold-on' => 'no', 'hello' => [
				'now1' => 'yes', 'i2' => 'do', 'i' => 'don\'t', 'nooooow!' => [
					'where1' => 'are', 'you2' => 'going', 'what3' => 'do you mean',
				],
			],
		];
		$result = ArrayTools::merge($a1, $a2);

		$correct_result = [
			'hello' => [
				'now' => 'yes', 'i' => 'don\'t', 'nooooow!' => [
					'where' => 'are', 'you' => 'going', 'what' => 'do you mean', 'where1' => 'are', 'you2' => 'going',
					'what3' => 'do you mean',
				], 'now1' => 'yes', 'i2' => 'do',
			], 'hold-on' => 'no',
		];
		$this->assertEquals($result, $correct_result);
	}

	public function test_prefix(): void {
		$arr = [
			0, 1, 2, 3, 4, 5,
		];
		$str = 'Homing-Pigeon-';
		$result = ArrayTools::prefixValues($arr, $str);
		$result_correct = [
			'Homing-Pigeon-0', 'Homing-Pigeon-1', 'Homing-Pigeon-2', 'Homing-Pigeon-3', 'Homing-Pigeon-4',
			'Homing-Pigeon-5',
		];
		$this->assertEquals($result, $result_correct);

		$a = [
			'Boy', 'Girl', 'Baby',
		];
		$p = 'Big';
		$this->assertEquals(ArrayTools::prefixValues($a, $p), [
			'BigBoy', 'BigGirl', 'BigBaby',
		]);
	}

	public function test_removePrefix(): void {
		$arr = [
			'GooBar', 'GooBird', 'GooPlan', 'gooCmon',
		];
		$str = 'Goo';
		$result = ArrayTools::valuesRemovePrefix($arr, $str);
		$result_correct = [
			'Bar', 'Bird', 'Plan', 'gooCmon',
		];

		$this->assertEquals($result, $result_correct);
	}

	public function test_removeSuffix(): void {
		$arr = [
			'0-Thing', '1-Thing', '2-Thing', '3-Thingy', '0-Thing',
		];
		$str = '-Thing';
		$result = ArrayTools::valuesRemoveSuffix($arr, $str);
		$result_correct = [
			'0', '1', '2', '3-Thingy', '0',
		];
		$this->assertEquals($result, $result_correct);
	}

	public function test_trim(): void {
		$a = [
			' foo ', " \n\t\0bar\n\t ",
		];
		$result = ArrayTools::trim($a);
		$this->assertEquals($result, [
			'foo', 'bar',
		]);
	}

	public function test_transpose(): void {
		$arr = [
			[
				'1', '2', '3',
			], [
				'4', '5', '6',
			], [
				'7', '8', '9',
			],
		];
		$result = ArrayTools::transpose($arr);
		$this->assertEquals($result, [
			[
				'1', '4', '7',
			], [
				'2', '5', '8',
			], [
				'3', '6', '9',
			],
		]);

		$arr = [
			[
				'1', '2', '3', '4',
			], [
				'5', '6', '7', '8',
			],
		];
		$result = ArrayTools::transpose($arr);
		$this->assertEquals($result, [
			[
				'1', '5',
			], [
				'2', '6',
			], [
				'3', '7',
			], [
				'4', '8',
			],
		]);

		$result2 = ArrayTools::transpose($result);
		$this->assertEquals($result2, $arr);
	}

	public function test_filterKeyPrefixes(): void {
		$a = [
			'AHello' => 94, 'Dog' => 34, 'Hello' => 1, 'hello_there' => 2, 'HELLO.THERE' => new stdClass(),
			'Hello.Kitty' => 'Kitty',
		];
		$ks = 'hello';
		$lower = false;
		$this->assertEquals(ArrayTools::filterKeyPrefixes($a, $ks, true), [
			'Hello' => 1, 'hello_there' => 2, 'HELLO.THERE' => new stdClass(), 'Hello.Kitty' => 'Kitty',
		]);
		$this->assertEquals(ArrayTools::filterKeyPrefixes($a, $ks, false), [
			'hello_there' => 2,
		]);
		$this->assertEquals(ArrayTools::filterKeyPrefixes($a, [
			'Hello', 'AHello',
		], false), [
			'AHello' => 94, 'Hello' => 1, 'Hello.Kitty' => 'Kitty',
		]);
		$this->assertEquals(ArrayTools::filterKeyPrefixes($a, [
			'Hello.', 'Dog ',
		], false), [
			'Hello.Kitty' => 'Kitty',
		]);
		$this->assertEquals(ArrayTools::filterKeyPrefixes($a, [
			'Hello.', 'Dog ',
		], true), [
			'HELLO.THERE' => new stdClass(), 'Hello.Kitty' => 'Kitty',
		]);
	}

	public function test_filter(): void {
		$arr = [
			'1', '2,3', '4',
		];
		$include = '0;2';
		$result = ArrayTools::filter($arr, $include);
		$this->assertEquals($result, [
			'1', 2 => '4',
		]);

		$include = [
			0, 2,
		];
		$result = ArrayTools::filter($arr, $include);
		$this->assertEquals($result, [
			'1', 2 => '4',
		]);

		$include = [
			'0', '2',
		];
		$result = ArrayTools::filter($arr, $include);
		$this->assertEquals($result, [
			'1', 2 => '4',
		]);

		// $x = null;
		// $keys = null;
		// $got_exception = false;
		// try {
		// ArrayTools::filter($x, $keys);
		// } catch (Exception $e) {
		// $got_exception = true;
		// }
		// $this->assertTrue($got_exception ), "Exception should be thrown");

		$x = [
			'A' => 'Kent', 'b' => 'Ruler', 'C' => 'another', 3 => 'dogs',
		];

		$a = $x;
		$b = 'A;b;C;3';
		$c = $x;
		$r = ArrayTools::filter($a, $b);
		$this->assertEquals($r, $c, __FILE__ . ':' . __LINE__);

		$a = $x;
		$b = 'a;B;c;3';
		$c = [
			3 => 'dogs',
		];
		$r = ArrayTools::filter($a, $b);
		$this->assertEquals($r, $c, __FILE__ . ':' . __LINE__);

		$a = $x;
		$b = 'A;3';
		$c = $x;
		unset($c['C']);
		unset($c['b']);
		$r = ArrayTools::filter($a, $b);
		$this->assertEquals($r, $c, __FILE__ . ':' . __LINE__);

		$a = [
			'A' => 'B', 'B', 'A', 'C' => 'D',
		];
		$ks = 'A;1';
		$this->assertEquals(ArrayTools::filter($a, $ks), [
			'A' => 'B', 1 => 'A',
		]);
	}

	public function test_find(): void {
		$exclude_files = [
			'cc_form.php', 'want-to-be-complete', 'setup/email.php', 'import-log.php', '/ab_try.php', '/ab.php',
			'/keyword/update.php', '/report/reporter.php', '/report/geo.php', '/setup/landings-generate.php',
		];
		$needles = '/setup/landings-generate.php';
		$this->assertIsArray(ArrayTools::find($exclude_files, $needles), "Can't find $needles in " . implode(',
		', $exclude_files));
	}

	public function test_has(): void {
		$array = [
			0 => 'hello', 3 => 'dude', 'kitty' => 'cat',
		];
		$this->assertTrue(ArrayTools::has($array, '0;3'));
		$this->assertTrue(ArrayTools::has($array, 3));
		$this->assertTrue(ArrayTools::has($array, 0));
		$this->assertTrue(ArrayTools::has($array, 'kitty'));
		$this->assertFalse(ArrayTools::has($array, 1));
		$this->assertFalse(ArrayTools::has($array, 'Kitty'));
	}

	public function test_isAssoc(): void {
		$array = [
			'', '', '', '', '', '', '', '',
		];
		$this->assertFalse(ArrayTools::isAssoc($array));
		$array[-1] = '';
		$this->assertTrue(ArrayTools::isAssoc($array));

		$this->assertFalse(ArrayTools::isAssoc(null));
		$this->assertFalse(ArrayTools::isAssoc(false));
		$this->assertFalse(ArrayTools::isAssoc(true));
		$this->assertFalse(ArrayTools::isAssoc(0));
		$this->assertFalse(ArrayTools::isAssoc(1));
		$this->assertFalse(ArrayTools::isAssoc('mixed'));
		$this->assertFalse(ArrayTools::isAssoc(new stdClass()));
		$this->assertFalse(ArrayTools::isAssoc([]));
		$this->assertFalse(ArrayTools::isAssoc([
			'1', '3',
		]));
		$this->assertTrue(ArrayTools::isAssoc([
			'1', 2 => '3',
		]));
		$this->assertTrue(ArrayTools::isAssoc([
			'1', 2 => '3', 4, 5 => 'f',
		]));
		$this->assertFalse(ArrayTools::isAssoc([
			1, 2, 3, 4, 5, 9,
		]));
		$this->assertFalse(ArrayTools::isAssoc(array_merge([
			1, 2, 3, 4, 5, 9,
		], [
			'a', 1, 2, 3, 4, 5, 9,
		])));
		$faker = new faker();
		$faker->__set('0', 'zero');
		$faker->__set('1', 'one');
		$this->assertFalse(ArrayTools::isAssoc($faker));
	}

	public function test_isList(): void {
		$mixed = null;
		$this->assertFalse(ArrayTools::isList($mixed));
		$this->assertFalse(ArrayTools::isList(false));
		$this->assertFalse(ArrayTools::isList(true));
		$this->assertFalse(ArrayTools::isList(0));
		$this->assertFalse(ArrayTools::isList(1));
		$this->assertFalse(ArrayTools::isList('mixed'));
		$this->assertFalse(ArrayTools::isList(new stdClass()));
		$this->assertTrue(ArrayTools::isList([]));
		$this->assertTrue(ArrayTools::isList([
			'1', '3',
		]));
		$this->assertFalse(ArrayTools::isList([
			'1', 2 => '3',
		]));
		$this->assertFalse(ArrayTools::isList([
			'1', 2 => '3', 4, 5 => 'f',
		]));
		$this->assertTrue(ArrayTools::isList([
			1, 2, 3, 4, 5, 9,
		]));
		$this->assertTrue(ArrayTools::isList(array_merge([
			1, 2, 3, 4, 5, 9,
		], [
			'a', 1, 2, 3, 4, 5, 9,
		])));

		$faker = new faker();
		$faker->__set('0', 'zero');
		$faker->__set('1', 'one');
		$this->assertFalse(ArrayTools::isList($faker));
	}

	/**
	 * @param mixed $expected
	 * @param array $source
	 * @param array $source_keys
	 * @param mixed $default
	 * @return void
	 * @dataProvider data_keysFind
	 */
	public function test_keysFind(mixed $expected, array $source, array $source_keys, mixed $default): void {
		$this->assertEquals($expected, ArrayTools::keysFind($source, $source_keys, $default));
	}

	public function data_keysFind(): array {
		;
		return [
			[
				'B', [
					'A' => 'A', 'B' => 'B',
				], [
					'B', 'C',
				], 'Dude',
			], [
				null, [
					'A' => 'A', 'B' => 'B',
				], [
					'D', 'C',
				], null,
			],
		];
	}

	public function data_max(): array {
		return [
			[
				513234, [
					1, 2, 3, 4, 6, '513234', 123, -1, 52145,
				], null,
			], [
				94123124, [
					'1', 2, 3, 99, 10000, 12, 94123123, '94123124',
				], null,
			], [44.32, [], 44.32, ], [44.32, [[], [], [], null, false, true], 44.32, ],
		];
	}

	/**
	 * @param mixed $expected
	 * @param array $array
	 * @param mixed $default
	 * @return void
	 * @dataProvider data_max
	 */
	public function test_max(mixed $expected, array $array, mixed $default): void {
		$this->assertEquals($expected, ArrayTools::max($array, $default));
	}

	public function data_min(): array {
		return [
			[
				[
					'-412312', 4, 61234, 6123, 3, new \stdClass(), [], -51235412, 3, 123, 5,
				], null, -51235412,
			], [
				[
					'1', 2, 3, 99, 10000, 12, 94123123, '94123124', '-23412312', 10000, 12, 94123123,
				], null, -23412312,
			], [[], 44.32, 44.32], [[[], [], [], null, false, true], 44.32, 44.32],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_min
	 */
	public function test_min(array $array, mixed $default, mixed $expected): void {
		$this->assertEquals($expected, ArrayTools::min($array, $default));
	}

	public function test_path(): void {
		$array = [
			'path' => [
				'to' => [
					'the' => [
						'deep' => [
							'portion' => 1231242,
						], 'funzone' => 'pigeon',
					],
				],
			],
		];
		$path = 'path.to.the.deep.portion';
		$default = null;
		$this->assertEquals(1231242, apath($array, $path, $default));
		$path = 'path.to.the.funzone';
		$this->assertEquals('pigeon', apath($array, $path, $default));
		$path = 'path.to.the.funzone.thing';
		$this->assertEquals('uh-uh', apath($array, $path, 'uh-uh'));
	}

	public function test_findInsensitive(): void {
		$haystack = 'A rather long sentence';
		$needles = [
			'Aa', 'rathI', 'lonGs', 'sentance',
		];

		$this->assertEquals(null, ArrayTools::findInsensitive($haystack, $needles));
		$haystack = 'A rather long senaatence';
		$this->assertEquals([$haystack, $needles[0], 0], ArrayTools::findInsensitive($haystack, $needles));

		$haystack = 'A rather long sentence rathI';
		$this->assertEquals([$haystack, $needles[1], 1], ArrayTools::findInsensitive($haystack, $needles));

		$haystack = 'lonGSA rather long sentence';
		$this->assertEquals([$haystack, $needles[2], 2], ArrayTools::findInsensitive($haystack, $needles));

		$haystack = 'A rather long sentance';
		$this->assertEquals([$haystack, $needles[3], 3], ArrayTools::findInsensitive($haystack, $needles));
	}

	public function test_strstr(): void {
		$needles = [
			'Aa', 'rathI', 'lonGs', 'sentance',
		];

		$haystack = 'A rather long sentence';
		$this->assertFalse(StringTools::contains($haystack, $needles));

		$haystack = 'A rather long senaatence';
		$this->assertFalse(StringTools::contains($haystack, $needles));

		$haystack = 'A rather long sentence rathI';
		$this->assertEquals(1, StringTools::contains($haystack, $needles));

		$haystack = 'lonGSA rather long sentence';
		$this->assertFalse(StringTools::contains($haystack, $needles));

		$haystack = 'A rather long sentance';
		$this->assertEquals(3, StringTools::contains($haystack, $needles));
	}

	public function data_append(): array {
		return [
			[['key' => 1], [], 'key', 1], [['key' => [1, 3]], ['key' => 1], 'key', 3],
			[['key' => [1, 3, 5]], ['key' => [1, 3]], 'key', 5],
			[['key' => [1, 3], 'key2' => 3], ['key' => [1, 3]], 'key2', 3],
		];
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @dataProvider data_append
	 */
	public function test_append(array $expected, array $actual, string $key, mixed $value): void {
		ArrayTools::append($actual, $key, $value);
		$this->assertEquals($expected, $actual);
	}

	public function test_rtrim(): void {
		$a = [
			'efgabcdabcdddabad', 'ABCabcdddd', 'abcdeddddddddddddd',
		];
		$charlist = 'abcd';
		$this->assertEquals(ArrayTools::rtrim($a, $charlist), [
			'efg', 'ABC', 'abcde',
		]);
	}

	public function test_clean(): void {
		$a = null;
		$value = '';
		$this->assertEquals([2 => false, 3 => null], ArrayTools::clean(['', '', false, null], ['']));
	}

	public function data_filterKeys(): array {
		return [
			[
				['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], ['a', 'b'], [], true,
			], [
				['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], null, ['c', 'd'], true,
			],
		];
	}

	/**
	 * @param $expected
	 * @param $array_to_filter
	 * @param $include
	 * @param $exclude
	 * @param $lower
	 * @return void
	 * @dataProvider data_filterKeys
	 */
	public function test_filterKeys($expected, $array_to_filter, $include, $exclude, $lower): void {
		$this->assertEquals($expected, ArrayTools::filterKeys($array_to_filter, $include, $exclude, $lower));
	}

	public function test_keysLeftTrim(): void {
		$charlist = 'a';
		$this->assertEquals(ArrayTools::keysLeftTrim([
			'aaaab' => 'aaaab', 'AAAAb' => 'AAAAb', 'baaaa' => 'baaaa',
		], $charlist), [
			'b' => 'aaaab', 'AAAAb' => 'AAAAb', 'baaaa' => 'baaaa',
		]);
	}

	public function test_suffixKeys(): void {
		$arr = [1 => 'one', 2 => 'two', 'three' => 3];
		$str = 'duck';
		$this->assertEquals(['1duck' => 'one', '2duck' => 'two', 'threeduck' => 3], ArrayTools::suffixKeys($arr, $str));
	}

	public function test_ltrim(): void {
		$charlist = 'aA';
		$this->assertEquals([
			'aaaab' => 'b', 'AAAAb' => 'b', 'baaaa' => 'baaaa',
		], ArrayTools::ltrim([
			'aaaab' => 'aaaab', 'AAAAb' => 'AAAAb', 'baaaa' => 'baaaa',
		], $charlist));
	}

	public function data_preg_quote(): array {
		return [
			['', '', ''], ['dude', 'd', "\du\de"], ['We are #1', '#', "We are \#1"],
		];
	}

	/**
	 * @param string $string
	 * @param string $delimiter
	 * @param string $expected
	 * @return void
	 * @dataProvider data_preg_quote
	 */
	public function test_preg_quote(string $string, string $delimiter, string $expected): void {
		$this->assertEquals($expected, ArrayTools::preg_quote($string, $delimiter));
	}

	public function data_prepend(): array {
		return [
			[[], 'key', 1, ['key' => 1]], [['key' => 1], 'key', 2, ['key' => [2, 1]]],
			[['key' => [2, 1]], 'key', 77.9, ['key' => [77.9, 2, 1]]],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_prepend
	 */
	public function test_prepend(array $actual, string $key, mixed $value, array $expected): void {
		ArrayTools::prepend($actual, $key, $value);
		$this->assertEquals($expected, $actual);
	}

	public function test_listTrimClean(): void {
		$arr = [
			"    \n\r\n\n\r\t\t\t\thello\t\n\r", "\n\n\n\r\t\n", ' world', ' ', '', null, false,
		];
		$value = '';
		$result = ArrayTools::listTrimClean($arr);
		$this->assertEquals($result, [
			0 => 'hello', 2 => 'world',
		]);
	}

	public function data_listTrim(): array {
		return [
			[
				['', false, null, [], 'a', 'b', '', false, null, [], 'c', 'd', '', false, null, []],
				['a', 'b', '', false, null, [], 'c', 'd'],
			],
		];
	}

	/**
	 * @param array $test
	 * @param array $expected
	 * @return void
	 * @dataProvider data_listTrim
	 */
	public function test_listTrim(array $test, array $expected): void {
		$this->assertEquals($expected, ArrayTools::listTrim($test));
	}
}

class faker {
	public function __set($n, $v): void {
		$this->$n = $v;
	}
}
