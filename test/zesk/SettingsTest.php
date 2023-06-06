<?php
declare(strict_types=1);

namespace zesk;

use zesk\Adapter\SettingsArray;
use zesk\Adapter\SettingsArrayNoCase;
use zesk\Adapter\SettingsConfiguration;
use zesk\Interface\SettingsInterface;

class SettingsTest extends UnitTest {
	public static array $arrayTarget = [];

	public static array $arrayNoCaseTarget = [];

	public static Configuration $testConfiguration;

	public static function data_settings_types(): array {
		self::$testConfiguration = new Configuration();
		return [
			[new SettingsArray(self::$arrayTarget)],
			[new SettingsConfiguration(new Configuration())],
			[new SettingsArrayNoCase(self::$arrayNoCaseTarget)],
		];
	}

	/**
	 * @param SettingsInterface $settings
	 * @return void
	 * @dataProvider data_settings_types
	 */
	public function test_settings(SettingsInterface $settings): void {
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
