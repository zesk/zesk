<?php declare(strict_types=1);
namespace zesk;

class PHP_Test extends UnitTest {
	public function test_php_basics(): void {
		$this->assert_false(!![]);
		$this->assert_true(!![
			1,
		]);
		$truthy = [
			new \stdClass(),
			[
				1,
			],
			[
				'',
			],
			[
				0,
			],
			[
				null,
			],
		];
		$falsy = [
			0,
			'',
			null,
			false,
			0.0,
		];
		foreach ($truthy as $true) {
			$this->assert(!!$true, gettype($true) . ' is not TRUE ' . var_export($true, true));
		}
		foreach ($falsy as $false) {
			$this->assert(!$false, gettype($false) . ' is not FALSE ' . var_export($false, true));
		}
	}

	/**
	 * PHP does not support Javascript-style assignment using ||, e.g.
	 *
	 * JS: var a = arg || {};
	 */
	public function test_php_andor(): void {
		$a = new \stdClass();
		$a->val = 'a';
		$b = new \stdClass();
		$b->val = 'b';

		$c = $a || $b;
		$this->assert_equal($c, true);

		$c = $a || [];
		$this->assert_equal($c, true);

		$c = false || [];
		$this->assert_equal($c, false);
	}

	public function data_provider_render() {
		return [
			[
				false,
				'false',
			],
			[
				true,
				'true',
			],
			[
				null,
				'null',
			],
			[
				0,
				'0',
			],
			[
				0.123,
				'0.123',
			],
			[
				'$Hello',
				'"\\$Hello"',
			],
			[
				[
					'1',
					'2',
					'3',
				],
				'array("1", "2", "3" )',
			],
		];
	}

	/**
	 * @dataProvider data_provider_render
	 */
	public function test_render($test, $expected): void {
		$this->assert_equal(PHP::singleton()->settings_one()->render($test), $expected);
	}

	public function test_php_references(): void {
		$bigthing = [
			'a' => [
				'kind' => 'letter',
				'code' => 65,
			],
			'b' => [
				'kind' => 'letter',
				'code' => 66,
			],
			'9' => [
				'kind' => 'number',
				'code' => ord('9'),
			],
		];

		$otherarray = [];
		$otherarray['test'] = &$bigthing['a'];
		// What happens to $bigthing?
		unset($otherarray['test']);
		// Nothing, unset applies only to the key in the array

		$this->assert_arrays_equal($bigthing, [
			'a' => [
				'kind' => 'letter',
				'code' => 65,
			],
			'b' => [
				'kind' => 'letter',
				'code' => 66,
			],
			'9' => [
				'kind' => 'number',
				'code' => ord('9'),
			],
		]);
	}
}
