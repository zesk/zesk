<?php
declare(strict_types=1);

namespace zesk;

class Settings_Test extends UnitTest {
	public static array $arrayTarget = [];

	public static array $arrayNoCaseTarget = [];

	public static Configuration $testConfiguration;

	public static function data_settings_types(): array {
		self::$testConfiguration = new Configuration();
		return [
			[new Adapter_Settings_Array(self::$arrayTarget)],
			[new Adapter_Settings_Configuration(new Configuration())],
			[new Adapter_Settings_ArrayNoCase(self::$arrayNoCaseTarget)],
		];
	}

	/**
	 * @param Interface_Settings $settings
	 * @return void
	 * @dataProvider data_settings_types
	 */
	public function test_settings(Interface_Settings $settings): void {
		$this->assertFalse($settings->has('foo'));
		$this->assertNull($settings->get('foo'));
		$this->assertNull($settings->foo);
		$expectedValue = 'thing';
		$this->assertInstanceOf($settings::class, $settings->set('foo', $expectedValue));
		$this->assertTrue($settings->has('foo'));
		$this->assertEquals($expectedValue, $settings->get('foo'));
		$this->assertEquals($expectedValue, $settings->foo);
		$this->assertEquals(['foo' => $expectedValue], $settings->variables());
	}
}
