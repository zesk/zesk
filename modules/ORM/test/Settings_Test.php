<?php
declare(strict_types=1);

namespace zesk\ORM;

class Settings_Test extends ORMUnitTest {
	public function test_settings(): void {
		$this->schemaSynchronize(Settings::class);

		$this->application->setOption('settingsClass', Settings::class);
		$settings = $this->application->settings();
		$this->assertInstanceOf(Settings::class, $settings);
		$value = $this->randomHex(12);
		$name = 'foo';
		$settings->set($name, $value);
		$this->assertEquals($value, $settings->get($name));
		$settings->flush();
	}
}
