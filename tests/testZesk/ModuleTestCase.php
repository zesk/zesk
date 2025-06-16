<?php
declare(strict_types=1);

namespace testZesk;

class ModuleTestCase extends TestCase
{
	protected array $loadModules = ['World'];

	public function test_Module_World(): void
	{
		$module = $this->application->modules->object('World');
		$this->assertInstanceOf(World\Module::class, $module);
	}

	public function test_Module_Doctrine(): void
	{
		$module = $this->application->modules->object('Doctrine');
		$this->assertInstanceOf(testZesk\Doctrine\Module::class, $module);
	}
}
