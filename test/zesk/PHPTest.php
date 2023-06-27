<?php
declare(strict_types=1);

namespace zesk;

use stdClass;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParseException;
use zesk\Exception\UnimplementedException;

class PHPTest extends UnitTest
{
	public function test_php_basics(): void
	{
		$this->assertFalse(!![]);
		$this->assertTrue(!![1, ]);
		$truthy = [new stdClass(), [1, ], ['', ], [0, ], [null, ], ];
		$falsy = [0, '', null, false, 0.0, ];
		foreach ($truthy as $true) {
			$this->assertTrue(!!$true, gettype($true) . ' is not TRUE ' . var_export($true, true));
		}
		foreach ($falsy as $false) {
			$this->assertTrue(!$false, gettype($false) . ' is not FALSE ' . var_export($false, true));
		}
	}

	/**
	 * PHP does not support Javascript-style assignment using ||, e.g.
	 *
	 * JS: var a = arg || {};
	 */
	public function test_php_andor(): void
	{
		$a = new stdClass();
		$a->val = 'a';
		$b = new stdClass();
		$b->val = 'b';

		$c = $a || $b;
		$this->assertEquals(true, $c);

		$c = $a || [];
		$this->assertEquals(true, $c);

		$c = false || [];
		$this->assertEquals(false, $c);
	}

	public function test_ini_path(): void
	{
		$file = PHP::ini_path();
		$this->assertFileExists($file);
	}

	/**
	 * @return void
	 * @throws KeyNotFound
	 */
	public function test_setSettings(): void
	{
		$result = new PHP();
		$settings = $result->setSettings(['array_value_separator' => "\t\t"])->settings();
		$this->assertArrayHasKey('array_value_separator', $settings);
		$this->assertArrayNotHasKey('singleton', $settings);
		$this->assertArrayNotHasKey('unserialize_exception', $settings);
		$this->assertEquals("\t\t", $settings['array_value_separator']);
	}

	public static function data_provider_render(): array
	{
		return [
			[false, 'false', ],
			[true, 'true', ],
			[null, 'null', ],
			[0, '0'],
			[0.123, '0.123'],
			['$Hello', '"\\$Hello"', ], [['1', '2', '3', ], '["1", "2", "3", ]', ],
		];
	}

	/**
	 * @dataProvider data_provider_render
	 */
	public function test_render($test, $expected): void
	{
		$this->assertEquals(PHP::singleton()->settingsOneLine()->render($test), $expected);
	}

	public static function data_setFeature(): array
	{
		return [
			[PHP::FEATURE_MEMORY_LIMIT, 512 * 1024 * 1024], [PHP::FEATURE_TIME_LIMIT, null],
			['not-a-feature', null],
		];
	}

	/**
	 * @dataProvider data_setFeature
	 * @param string $setting
	 * @param int|null $value
	 * @return void
	 * @throws UnimplementedException;
	 */
	public function test_setFeature(string $setting, ?int $value): void
	{
		if ($value === null) {
			$this->expectException(UnimplementedException::class);
			$value = 1024;
		}
		$old_value = PHP::setFeature($setting, $value);
		$this->assertEquals($value, PHP::setFeature($setting, $old_value));
	}

	public function test_php_references(): void
	{
		$bigthing = [
			'a' => ['kind' => 'letter', 'code' => 65, ], 'b' => ['kind' => 'letter', 'code' => 66, ],
			'9' => ['kind' => 'number', 'code' => ord('9'), ],
		];

		$otherarray = [];
		$otherarray['test'] = &$bigthing['a'];
		// What happens to $bigthing?
		unset($otherarray['test']);
		// Nothing, unset applies only to the key in the array

		$this->assertEquals($bigthing, [
			'a' => ['kind' => 'letter', 'code' => 65, ], 'b' => ['kind' => 'letter', 'code' => 66, ],
			'9' => ['kind' => 'number', 'code' => ord('9'), ],
		]);
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @param bool $throw
	 * @return void
	 * @throws ParseException
	 * @dataProvider data_autoType
	 */
	public function test_autoType($expected, $mixed, bool $throw = false): void
	{
		if ($throw) {
			$this->expectException(ParseException::class);
		}
		$this->assertEquals($expected, PHP::autoType($mixed, $throw));
	}

	public static function data_autoType(): array
	{
		$stdClass = new stdClass();
		return
		[
			[12093019, '12093019'],
			[0, '0'],
			[1, '1'],
			[8675309, '8675309'],
			[99.999, '99.999'],
			[$stdClass, $stdClass],
			[null, 'null'],
			['User', 'User'],
			[[], '[]'],
			[[1, 2, 3], '[1,2,3]'],
			[[1, 2, 3], '[1,2,3,]', true],
			[[1, 2, 3], '{1,2,3}', true],
			[['hello' => 'world'], '{"hello":"world"}'],
		];
	}
}
