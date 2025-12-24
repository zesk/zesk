<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Application;
use zesk\Configuration;
use zesk\Exception\SemanticsException;
use zesk\Kernel;
use zesk\Options;
use zesk\Types;

/**
 * @uses Configuration
 * @coversDefaultClass \zesk\Configuration
 */
class ConfigurationTest extends TestCase
{
	/**
	 *
	 */
	public static function data_has(): array
	{
		return [
			[
				true,
				Application::class,
			],
			[
				true,
				[
					Application::class,
					'deprecated',
				],
			],
			[
				true,
				Kernel::class,
			],
			[
				true,
				[
					Application::class,
					Application::OPTION_USER_HOME,
				],
			],
			[
				true,
				Options::class,
			],
			[
				false,
				md5('HOME'),
			],
			[
				false,
				'HOME',
			],
			[
				false,
				'HoMe',
			],
			[
				false,
				'0192830128301283123',
			],
		];
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @covers ::setPath ::getPath
	 */
	public function test_set(): void
	{
		$this->assertEquals(null, $this->application->configuration->getPath('a::b::c'));
		$this->application->configuration->setPath('a::b::c', 9876);
		$this->assertEquals(9876, $this->application->configuration->getPath('a::b::c'));
	}

	/**
	 * @param $expected
	 * @param $key
	 * @return void
	 * @dataProvider data_has
	 * @covers ::pathExists
	 */
	public function test_has($expected, $key): void
	{
		$this->assertEquals($expected, $this->application->configuration->pathExists($key), implode('::', Types::toList($key)));
	}

	/**
	 * @return void
	 * @covers ::__set ::toArray ::__get
	 */
	public function test_get(): void
	{
		$configuration = new Configuration();
		$configuration->a = 'b';
		$result = $configuration->toArray();
		$this->assertEquals('b', $result['a']);
	}

	public static function data_value_types(): array
	{
		return [
			['Hello'],
			[1],
			['null'],
			[null],
			[false],
			[true],
			[123.423],
			[new stdClass()],
		];
	}

	/**
	 * @dataProvider data_value_types
	 * @param mixed $value
	 * @covers ::getPath ::setPath
	 * @param mixed $value
	 * @return void
	 * @throws SemanticsException
	 */
	public function test_value_types(mixed $value): void
	{
		$configuration = new Configuration();
		$configuration->setPath('TEST::ROOT', $value);
		$this->assertEquals($configuration->getPath('TEST::ROOT'), $value);
		$this->assertNull($configuration->getPath('test::RooT'));
		$this->assertNull($configuration->getPath([
			'test',
			'RooT',
		]));
		$this->assertNull($configuration->getPath([
			'Test',
			'Root',
		]));
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @covers ::getPath ::setPath
	 */
	public function test_setk(): void
	{
		$k = 'a';
		$k1 = 'b';
		$v = md5(microtime());
		$this->application->configuration->setPath([
			$k,
			'b',
		], $v);
		$this->application->configuration->setPath([
			$k,
			'c',
		], $v);
		$this->application->configuration->setPath([
			$k,
			'd',
		], $v);

		$this->assertEquals([
			'b' => $v,
			'c' => $v,
			'd' => $v,
		], $this->application->configuration->getPath($k));
	}
}
