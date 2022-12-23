<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2022 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

use ReflectionClass;

/**
 * Commands_Test
 */
class Commands_Test extends UnitTest {
	/**
	 * @throws \ReflectionException
	 * @throws Exception_Parameter
	 */
	public function dataIncludeClasses(): array {
		$this->setUp();
		$results = [];
		$zeskCommandPath = $this->application->zeskCommandPath();
		$this->assertNotCount(0, $zeskCommandPath);
		foreach ($zeskCommandPath as $path) {
			$files = Directory::listRecursive($path, [
				Directory::LIST_RULE_FILE => ["/\.php$/" => true], Directory::LIST_RULE_DIRECTORY_WALK => [
					'/\\\./' => false, true,
				], Directory::LIST_ADD_PATH => true,
			]);
			foreach ($files as $file) {
				require_once($file);
			}
		}
		$this->application->classes->register(get_declared_classes());
		foreach ($this->application->classes->subclasses(Command::class) as $class) {
			$reflectionClass = new ReflectionClass($class);
			if ($reflectionClass->isAbstract()) {
				continue;
			}
			$result[] = [$class];
		}
		return $result;
	}

	/**
	 * Command test
	 *
	 * @param string $class
	 * @return void
	 * @dataProvider dataIncludeClasses
	 */
	public function test_command(string $class): void {
		if (is_subclass_of($class, Command_Base::class)) {
			$argv = [$class, '--help'];
		} else {
			$argv = [$class];
		}
		$options = ['exit' => false];

		$command = $this->application->factory($class, $this->application, $options);
		$this->assertInstanceOf(Command::class, $command);

		$this->assertIsArray($command->shortcuts());
	}
}
