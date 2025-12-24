<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Exception\KeyNotFound;
use zesk\Exception\UnimplementedException;
use zesk\PHP;

/**
 * @coversDefaultClass \zesk\PHP
 */
class PHPTest extends TestCase
{
	public static function data_provider_clean_function(): array
	{
		return [
			[
				'',
				'',
			],
			[
				'  z e s k \\-O~b@j%e^c t!@#$%',
				'__z_e_s_k___O_b_j_e_c_t_____',
			],
			[
				'bunch,of-random-chars',
				'bunch_of_random_chars',
			],
		];
	}

	/**
	 * @dataProvider data_provider_clean_function
	 *
	 * @param string $name
	 * @param string $expected
	 * @covers ::cleanFunction
	 */
	public function test_clean_function(string $name, string $expected): void
	{
		$result = PHP::cleanFunction($name);
		$this->assertEquals($result, $expected, 'PHP::cleanFunction');
	}

	/**
	 * @return void
	 * @coversNothing
	 */
	public function test_php_basics(): void
	{
		$this->assertFalse(!![]);
		$this->assertTrue(!![1, ]);
		$truthy = [
			new stdClass(),
			[1, ],
			['', ],
			[0, ],
			[null, ],
		];
		$falsy = [
			0,
			'',
			null,
			false,
			0.0,
		];
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
	 * @coversNothing
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

	/**
	 * @return void
	 * @covers ::ini_path
	 */
	public function test_ini_path(): void
	{
		$file = PHP::ini_path();
		$this->assertFileExists($file);
	}

	/**
	 * @return void
	 * @throws KeyNotFound
	 * @covers ::setSettings
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
				'["1", "2", "3", ]',
			],
		];
	}

	/**
	 * @dataProvider data_provider_render
	 * @covers ::singleton ::settingsOneLine ::render
	 */
	public function test_render($test, $expected): void
	{
		$this->assertEquals(PHP::singleton()->settingsOneLine()->render($test), $expected);
	}

	public static function data_setFeature(): array
	{
		return [
			[
				PHP::FEATURE_MEMORY_LIMIT,
				512 * 1024 * 1024,
			],
			[
				PHP::FEATURE_TIME_LIMIT,
				10000,
			],
			[
				'not-a-feature',
				null,
			],
		];
	}

	/**
	 * @dataProvider data_setFeature
	 * @param string $setting
	 * @param int|null $value
	 * @return void
	 * @throws UnimplementedException;
	 * @covers ::setFeature
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

	/**
	 * @dataProvider dataProviderClean
	 *
	 * @param string $name
	 * @param string $expected
	 * @covers ::cleanClass
	 */
	public function testClean(string $name, string $expected): void
	{
		$result = PHP::cleanClass($name);
		$this->assertEquals($result, $expected, "PHP::clean_class($name) = $result !== $expected");
	}

	public static function dataProviderClean(): array
	{
		return [
			[
				'',
				'',
			],
			[
				'  z e s k \\-O~b@j%e^c t!@#$%',
				'__z_e_s_k_\\_O_b_j_e_c_t_____',
			],
		];
	}

	/**
	 * @return void
	 * @coversNothing
	 */
	public function test_php_references(): void
	{
		$bigThing = [
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

		$otherArray = [];
		$otherArray['test'] = &$bigThing['a'];
		// What happens to $bigthing?
		unset($otherArray['test']);
		// Nothing, unset applies only to the key in the array

		$this->assertEquals($bigThing, [
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
