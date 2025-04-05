<?php
declare(strict_types=1);

namespace zesk;

class ModuleTest extends UnitTest
{
	protected array $load_modules = ['World'];

	public function test_Module_World(): void
	{
		$module = $this->application->modules->object('World');
		$this->assertInstanceOf(World\Module::class, $module);
	}

	public function test_Module_Doctrine(): void
	{
		$module = $this->application->modules->object('Doctrine');
		$this->assertInstanceOf(Doctrine\Module::class, $module);
	}
}
