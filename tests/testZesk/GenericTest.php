<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Types;

/**
 * Generic test class
 */
class GenericTest extends TestCase
{
	public static function data_something(): array
	{
		$true_ish = [
			true,
			1,
			'yes',
			' 0',
			new stdClass(),
		];
		$false_ish = [
			false,
			0,
			null,
			'',
			'',
			'0',
			[],
		];
		$arguments_list = [];
		foreach ($true_ish as $true) {
			$arguments_list[] = [
				true,
				$true,
			];
		}
		foreach ($false_ish as $false) {
			$arguments_list[] = [
				false,
				$false,
			];
		}
		return $arguments_list;
	}

	/**
	 * Silly test to make sure PHP true-ish and false-ish are correct by double-boolean
	 *
	 * @param $expected
	 * @param $tested
	 * @return void
	 * @dataProvider data_something
	 * @covers       \zesk\Types::type
	 */
	public function test_something($expected, $tested): void
	{
		$this->assertEquals($expected, !(!($tested)), Types::type($tested) . ' failed');
	}
}
