<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Configuration;
use zesk\Exception\SemanticsException;

/**
 * @coversDefaultClass \zesk\Configuration
 */
class ConfigurationTest extends TestCase
{
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
