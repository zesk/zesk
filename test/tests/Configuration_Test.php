<?php declare(strict_types=1);
namespace zesk;

class Configuration_Test extends Test_Unit {
	public function value_types() {
		return [
			"Hello",
			1,
			"null",
			null,
			false,
			true,
			123.423,
			new \stdClass(),
		];
	}

	/**
	 * @dataProvider value_types
	 * @param unknown $value
	 */
	public function test_value_types($value): void {
		$configuration = new Configuration();
		$configuration->path_set("TEST::ROOT", $value);
		$this->assert_equal($configuration->path_get("TEST::ROOT"), $value);
		$this->assert_equal($configuration->path_get("test::RooT"), $value);
		$this->assert_equal($configuration->path_get([
			"test",
			"RooT",
		]), $value);
		$this->assert_equal($configuration->path_get([
			"Test",
			"Root",
		]), $value);
	}
}
