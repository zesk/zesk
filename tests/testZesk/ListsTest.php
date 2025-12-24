<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

/**
 *
 * @author kent
 *
 */

use zesk\ArrayTools;
use zesk\Lists;
use zesk\Types;

/**
 * @coversDefaultClass \zesk\Lists
 * @uses Lists
 */
class ListsTest extends TestCase
{
	public static function dataAppend(): array
	{
		$lists = [
			[
				'a',
				'b',
				'c',
				'dee',
				'random',
			],
			[],
			[
				1,
				2,
				3,
				4,
				5,
			],
		];
		$seps = [
			';',
			'.',
			'/',
			' ',
			'DUDE',
		];
		$adds = [
			[
				'a',
				'b',
				'c',
				'dee',
				'random',
			],
			[
				'dude',
			],
			[],
			[
				null,
			],
			[
				'',
				'',
				'',
			],
			[
				1,
				2,
				3,
				'',
			],
			[
				1,
				2,
				3,
				4,
				5,
			],
		];
		$datum = [];
		foreach ([
			false,
			true,
		] as $list_is_array) {
			foreach ([
				false,
				true,
			] as $add_is_array) {
				foreach ($lists as $list) {
					foreach ($adds as $add) {
						foreach ($seps as $sep) {
							$add_cleaned = count($add) === 0 ? [] : ($add_is_array ? ArrayTools::clean($add, null) : explode($sep, implode($sep, $add)));
							$expected = $list_is_array ? array_merge($list, $add_cleaned) : implode($sep, array_merge($list, $add_cleaned));

							$datum[] = [
								$expected,
								$list_is_array ? $list : implode($sep, $list),
								$add_is_array ? $add : (count($add) === 0 ? [] : implode($sep, $add)),
								$sep,
							];
						}
					}
				}
			}
		}
		return $datum;
	}

	/**
	 * @dataProvider dataAppend
	 * @covers ::append
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string|array $add
	 * @param string $sep
	 * @return void
	 */
	public function test_append(string|array $expected, string|array $list, string|array $add, string $sep): void
	{
		$actual = Lists::append($list, $add, $sep);
		$this->assertEquals(Types::type($expected), Types::type($actual));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider dataAppend
	 * @covers ::appendUnique
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string|array $add
	 * @param string $sep
	 * @return void
	 */
	public function test_appendUnique(string|array $expected, string|array $list, string|array $add, string $sep): void
	{
		if (is_array($expected)) {
			$expected = array_unique($expected);
		} else {
			$expected = implode($sep, array_unique(explode($sep, $expected)));
		}
		$actual = Lists::appendUnique($list, $add, $sep);
		$this->assertEquals(Types::type($expected), Types::type($actual));
		$this->assertEquals($expected, $actual);
	}

	public static function dataContains(): array
	{
		return [
			[true, 'a;b;c;d;ee;ff', 'a'],
			[false, 'a;b;c;d;ee;ff', 'A'],
			[true, 'a;b;c;d;ee;ff', 'b'],
			[true, 'a;b;c;d;ee;ff', 'c'],
			[true, 'a;b;c;d;ee;ff', 'd'],
			[true, 'a;b;c;d;ee;ff', 'ee'],
			[false, 'a;b;c;d;ee;ff', ';ee;'],
			[true, 'a;b;c;d;ee;ff', 'ff'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string|array $list
	 * @param string $item
	 * @return void
	 * @covers ::contains
	 * @dataProvider dataContains
	 */
	public function test_contains(bool $expected, string|array $list, string $item): void
	{
		$this->assertEquals($expected, Lists::contains($list, $item));
	}

	public static function dataPop(): array
	{
		return [
			['a;b', 'a;b;c', ';'],
			[['a', 'b'], ['a', 'b', 'c'], ';'],
			[['a', 'b'], ['a', 'b', 'c'], '-'],
			[['a', 'b'], ['a', 'b', 'c'], '+'],
			['', 'a;b;c', '-'],
			['a;', 'a;b;c', 'b'],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string $sep
	 * @covers ::pop
	 * @dataProvider dataPop
	 * @return void
	 */
	public function test_pop(string|array $expected, string|array $list, string $sep): void
	{
		$this->assertEquals($expected, Lists::pop($list, $sep));
	}

	public static function dataPrepend(): array
	{
		return [
			['a;b;c', 'b;c', 'a', ';'],
			[['a', 'b', 'c'], ['b', 'c'], 'a', ';'],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string $prepend
	 * @param string $sep
	 * @return void
	 * @covers ::prepend
	 * @dataProvider dataPrepend
	 */
	public function testPrepend(string|array $expected, string|array $list, string $prepend, string $sep): void
	{
		$this->assertEquals($expected, Lists::prepend($list, $prepend, $sep));
	}

	public static function data_keysRemove(): array
	{
		return [
			[
				'a;c;d;e',
				'a;b;c;d;e',
				'b',
				';',
			],
			[
				'a;c;d;e',
				'a;b;c;d;e',
				'b',
				';',
			],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string $item
	 * @param string $sep
	 * @return void
	 * @covers ::keysRemove
	 * @dataProvider data_keysRemove
	 */
	public function test_keysRemove(string|array $expected, string|array $list, string $item, string $sep): void
	{
		$this->assertEquals($expected, Lists::keysRemove($list, $item, $sep));
	}

	public static function dataUnique(): array
	{
		return [
			[
				'X;a;A;b;c;D;F',
				'X;a;A;b;b;c;c;D;F;a;X',
				';',
			],
			[
				explode(';', 'X;a;A;b;c;D;F'),
				explode(';', 'X;a;A;b;b;c;c;D;F;a;X'),
				'-',
			],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $list
	 * @param string $sep
	 * @return void
	 * @covers ::unique
	 * @dataProvider dataUnique
	 */
	public function testUnique(string|array $expected, string|array $list, string $sep): void
	{
		$this->assertEquals($expected, Lists::unique($list, $sep));
	}
}
