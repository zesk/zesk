<?php
declare(strict_types=1);

namespace zesk;

class ModuleTest extends UnitTest
{
	protected array $load_modules = ['World'];

	public function test_Module_World(): void
	{
		$module = $this->application->modules->object('World');
		$this->assertInstanceOf(\zesk\World\Module::class, $module);
		$this->assertEquals('World', $module->baseName());
		$ff = '/zesk/modules/World/World.module.json';
		$configuration = JSON::decode(File::contents($ff));
		$this->assertEquals($configuration, $module->moduleConfiguration());
		$this->assertEquals($ff, $module->moduleConfigurationFile());
		$this->assertEquals([
			'path' => '/zesk/modules/World', 'base' => 'World', 'name' => 'World',
			'configuration' => $configuration, 'configurationFile' => $ff,
		], $module->moduleData());
		$this->assertEquals('', $module->version());
	}

	public function test_Module_MySQL(): void
	{
		$module = $this->application->modules->object('Doctrine');
		$this->assertInstanceOf(\zesk\Doctrine\Module::class, $module);
		$this->assertEquals('Doctrine', $module->baseName());
		$ff = '/zesk/modules/Doctrine/Doctrine.module.json';
		$configuration = JSON::decode(File::contents($ff));
		$this->assertEquals($configuration, $module->moduleConfiguration());
		$this->assertEquals($ff, $module->moduleConfigurationFile());
		$this->assertEquals([
			'path' => '/zesk/modules/Doctrine', 'base' => 'Doctrine', 'name' => 'Doctrine',
			'configuration' => $configuration, 'configurationFile' => $ff,
		], $module->moduleData());
		$this->assertEquals('', $module->version());
	}
}
