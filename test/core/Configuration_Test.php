<?php
declare(strict_types=1);
namespace zesk;

use stdClass;

class Configuration_Test extends UnitTest {
	public static function data_value_types(): array {
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
	 */
	public function test_value_types(mixed $value): void {
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
}
