<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Command;
use zesk\Exception\ClassNotFound;
use zesk\ORM\Command\ClassCheck;
use zesk\ORM\Command\Classes;
use zesk\ORM\Command\ClassNew;
use zesk\ORM\Command\ClassProperties;
use zesk\ORM\Command\PHPSchema;
use zesk\ORM\Command\Schema;
use zesk\PHPUnit\TestCase;

class CommandsTest extends TestCase {
	/**
	 *
	 * @var array
	 */
	protected array $load_modules = ['ORM'];

	/**
	 * @return void
	 * @throws ClassNotFound
	 */
	public function test_commands(): void {
		$classes = [
			ClassCheck::class,
			Classes::class,
			ClassProperties::class,
			ClassNew::class,
			PHPSchema::class,
			Schema::class,
		];

		$app = $this->application;
		foreach ($classes as $class) {
			$command = $app->factory($class, $app, [], []);
			$this->assertInstanceOf(Command::class, $command);
		}
	}
}
