<?php declare(strict_types=1);
namespace zesk;

class Configuration_Test extends UnitTest {
	public function value_types() {
		return [
			['Hello'],
			[1],
			['null'],
			[null],
			[false],
			[true],
			[123.423],
			[new \stdClass()],
		];
	}

	/**
	 * @dataProvider value_types
	 * @param unknown $value
	 */
	public function test_value_types(mixed $value): void {
		$configuration = new Configuration();
		$configuration->setPath('TEST::ROOT', $value);
		$this->assertEquals($configuration->getPath('TEST::ROOT'), $value);
		$this->assertEquals($configuration->getPath('test::RooT'), $value);
		$this->assertEquals($configuration->getPath([
			'test',
			'RooT',
		]), $value);
		$this->assertEquals($configuration->getPath([
			'Test',
			'Root',
		]), $value);
	}
}
