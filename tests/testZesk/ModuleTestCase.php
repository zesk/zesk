<?php
declare(strict_types=1);

namespace testZesk;

use zesk\Doctrine\Module as DoctrineModule;
use zesk\World\Module as WorldModule;

class ModuleTestCase extends TestCase {
	
	protected array $loadModules = ['World'];
	
	public function test_Module_World(): void
	{
		$module = $this->application->modules->object('World');
		$this->assertInstanceOf(WorldModule::class, $module);
	}
	
	public function test_Module_Doctrine(): void
	{
		$module = $this->application->modules->object('Doctrine');
		$this->assertInstanceOf(DoctrineModule::class, $module);
	}
}
