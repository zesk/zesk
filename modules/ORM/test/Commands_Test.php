<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Command;
use zesk\UnitTest;

class Commands_Test extends UnitTest {
	/**
	 *
	 * @var array
	 */
	protected array $load_modules = ['ORM'];

	/**
	 * @return void
	 * @throws \zesk\Exception_Class_NotFound
	 */
	public function test_commands(): void {
		$classes = [
			Command_ClassCheck::class,
			Command_Classes::class,
			Command_ClassProperties::class,
			Command_ClassNew::class,
			Command_PHPSchema::class,
			Command_Schema::class,
		];

		$app = $this->application;
		foreach ($classes as $class) {
			$command = $app->factory($class, $app, [], []);
			$this->assertInstanceOf(Command::class, $command);
		}
	}
}
