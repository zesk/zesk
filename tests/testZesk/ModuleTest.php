<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Doctrine\Module as DoctrineModule;
use zesk\Exception\NotFoundException;
use zesk\World\Module as WorldModule;

/**
 * @coversDefaultClass \zesk\Application\Modules
 * @uses Modules
 */
class ModuleTest extends TestCase
{
	protected array $loadModules = ['World'];

	/**
	 * @return void
	 * @throws NotFoundException
	 * @covers ::object
	 */
	public function test_Module_World(): void
	{
		$module = $this->application->modules->object('World');
		$this->assertInstanceOf(WorldModule::class, $module);
	}

	/**
	 * @return void
	 * @throws NotFoundException
	 * @covers ::object
	 */
	public function test_Module_Doctrine(): void
	{
		$module = $this->application->modules->object('Doctrine');
		$this->assertInstanceOf(DoctrineModule::class, $module);
	}
}
