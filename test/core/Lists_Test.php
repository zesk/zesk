<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Lists_Test extends UnitTest {
	public function append_data_provider() {
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
								$list_is_array ? $list : implode($sep, $list),
								$add_is_array ? $add : (count($add) === 0 ? [] : implode($sep, $add)),
								$sep,
								$expected,
							];
						}
					}
				}
			}
		}
		return $datum;
	}

	/**
	 * @dataProvider append_data_provider
	 */
	public function test_append($list, $add, $sep, $expected): void {
		$actual = Lists::append($list, $add, $sep);
		$this->assertEquals(type($expected), type($actual));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider append_data_provider
	 */
	public function test_appendUnique($list, $add, $sep, $expected): void {
		if (is_array($expected)) {
			$expected = array_unique($expected);
		} else {
			$expected = implode($sep, array_unique(explode($sep, $expected)));
		}
		$actual = Lists::appendUnique($list, $add, $sep);
		$this->assertEquals(type($expected), type($actual));
		$this->assertEquals($expected, $actual);
	}

	public function test_contains(): void {
		$llist = null;
		$item = null;
		$sep = ';';
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'a'));
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'b'));
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'c'));
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'd'));
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'ee'));
		$this->assertTrue(Lists::contains('a;b;c;d;ee;ff', 'ff'));
	}

	public function test_pop(): void {
		$sep = ';';
		$this->assertEquals('a;b', Lists::pop('a;b;c', $sep));
	}

	public function test_prepend(): void {
		$this->assertEquals('a;b;c', Lists::prepend('b;c', 'a'));
	}

	public static function data_keysRemove(): array {
		return [
			['a;c;d;e', 'a;b;c;d;e', 'b', ';'],
			['a;c;d;e', 'a;b;c;d;e', 'b', ';'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_keysRemove
	 */
	public function test_keysRemove($expected, $list, $item, $sep): void {
		$this->assertEquals($expected, Lists::keysRemove($list, $item, $sep));
	}

	public function test_unique(): void {
		$llist = 'X;a;A;b;b;c;c;D;F;a;X';
		$sep = ';';
		$this->assertEquals('X;a;A;b;c;D;F', Lists::unique($llist, $sep));
	}
}
