<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Lists_Test extends Test_Unit {
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
								$add_is_array ? $add : (count($add) === 0 ? null : implode($sep, $add)),
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
	 * @data_provider append_data_provider
	 */
	public function test_append($list, $add, $sep, $expected): void {
		$actual = Lists::append($list, $add, $sep);
		$this->assert_equal(type($actual), type($expected), 'Type of list does not match return type');
		$this->assert_equal($actual, $expected, map("Lists::append({list}, {add}, \"$sep\")", [
			'list' => _dump($list),
			'add' => _dump($add),
		]), false);
	}

	/**
	 * @data_provider append_data_provider
	 */
	public function test_appendUnique($list, $add, $sep, $expected): void {
		if (is_array($expected)) {
			$expected = array_unique($expected);
		} else {
			$expected = implode($sep, array_unique(explode($sep, $expected)));
		}
		$actual = Lists::appendUnique($list, $add, $sep);
		$this->assert_equal(type($actual), type($expected));
		$this->assert_equal($actual, $expected);
	}

	public function test_contains(): void {
		$llist = null;
		$item = null;
		$sep = ';';
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'a'));
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'b'));
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'c'));
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'd'));
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'ee'));
		$this->assert_true(Lists::contains('a;b;c;d;ee;ff', 'ff'));
	}

	public function test_pop(): void {
		$llist = null;
		$sep = ';';
		Lists::pop($llist, $sep);
	}

	public function test_prepend(): void {
		$llist = null;
		$item = null;
		$sep = ';';
		Lists::prepend($llist, $item, $sep);
	}

	public function test_keysRemove(): void {
		$llist = null;
		$item = null;
		$sep = ';';
		Lists::keysRemove($llist, $item, $sep);
	}

	public function test_unique(): void {
		$llist = 'X;a;A;b;b;c;c;D;F;a;X';
		$sep = ';';
		$this->assert(Lists::unique($llist, $sep) === 'X;a;A;b;c;D;F');

		echo basename(__FILE__) . ": success\n";
	}
}
