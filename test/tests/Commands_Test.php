<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2022 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

/**
 * Generic test class
 */
class Commands_Test extends UnitTest {
	public function dataIncludeClasses(): array {
		$this->setUp();
		$results = [];
		$zeskCommandPath = $this->application->zeskCommandPath();
		$this->assertNotCount(0, $zeskCommandPath);
		foreach ($zeskCommandPath as $path => $prefix) {
			$files = Directory::listRecursive($path, [
				Directory::LIST_RULE_FILE => ["/\.php$/" => true], Directory::LIST_RULE_DIRECTORY_WALK => [
					'/\\\./' => false, true,
				], Directory::LIST_ADD_PATH => true,
			]);
			$oldClasses = get_declared_classes();
			foreach ($files as $file) {
				require_once($file);
				$newClasses = get_declared_classes();
				$delta = array_diff($newClasses, $oldClasses);
				$this->assertGreaterThanOrEqual(1, count($delta), $file);
				foreach ($delta as $class) {
					if (is_subclass_of($class, Command::class)) {
						try {
							$reflection = new \ReflectionClass($class);
							if (!$reflection->isAbstract()) {
								$this->assertStringStartsWith($prefix, $class);
								$results[] = [$file, $class];
							}
						} catch (\ReflectionException) {
							/* pass */
						}
					}
				}
				$oldClasses = $newClasses;
			}
		}
		usort($results, function ($a, $b) {
			/* by Class */
			return strcmp($a[1], $b[1]);
		});
		return $results;
	}

	/**
	 * Silly test to make sure PHP true-ish and false-ish are correct by double-boolean
	 *
	 * @param string $class
	 * @return void
	 * @dataProvider dataIncludeClasses
	 */
	public function test_command(string $file, string $class): void {
		if (is_subclass_of($class, Command_Base::class)) {
			$argv = [$file, '--help'];
		} else {
			$argv = [$file];
		}
		$options = ['exit' => false];

		$command = $this->application->factory($class, $this->application, $argv, $options);
		$this->assertInstanceOf(Command::class, $command);
	}
}
