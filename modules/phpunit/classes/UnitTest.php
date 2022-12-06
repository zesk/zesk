<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use ReflectionException;

/**
 * Glue for old tests
 *
 */
class UnitTest extends PHPUnit_TestCase {
	public function awaitFile(string $filename, float $timeout = 5.0): void {
		$timer = new Timer();
		while (!is_file($filename) && !is_readable($filename)) {
			usleep(10000);
			$this->assertLessThan($timeout, $timer->elapsed(), "Timer elapsed beyond $timeout seconds awaiting $filename");
		}
	}

	protected function zeskBinInclude(string $includeFile): string {
		$zeskBin = $this->application->zeskHome('bin/zesk');
		$this->assertFileExists($zeskBin);
		$this->assertFileIsReadable($zeskBin);
		$escIncludeFile = escapeshellarg("include(\"$includeFile\")");
		$command = "XDEBUG_ENABLED=0 $zeskBin eval -i $escIncludeFile";
		return $command;
	}

	public function zeskEvalFile(string $includeFile): array {
		$command = $this->zeskBinInclude($includeFile);
		return $this->application->process->execute($command);
	}

	public function zeskEvalFileProcess(string $includeFile): int {
		$command = $this->zeskBinInclude($includeFile);
		return $this->application->process->executeBackground($command);
	}

	public function log($message, array $args = []): void {
		$this->application->logger->debug($message, $args);
	}

	final public function option(string $name, mixed $default = null): mixed {
		return $this->application->configuration->getPath([get_class($this), $name], $default);
	}

	/**
	 * Assert an object or class implements an interface
	 *
	 * @param string $interface_class
	 * @param object|string $instanceof
	 * @param string $message
	 * @return void
	 */
	final public function assertImplements(string $interface_class, object|string $instanceof, string $message = ''): void {
		$interfaces = class_implements($instanceof);
		$this->assertInArray($interface_class, $interfaces, $message);
	}

	/**
	 * @param mixed $mixed
	 * @param array $array
	 * @param string $message
	 * @return void
	 */
	final protected function assertInArray(mixed $mixed, array $array, string $message = ''): void {
		if (!empty($message)) {
			$message .= "\n";
		}
		$message .= "Array does not contain value \"$mixed\" (values: " . implode(', ', array_values($array)) . ')';
		$this->assertTrue(in_array($mixed, $array), $message);
	}

	/**
	 * @param mixed $mixed
	 * @param array $array
	 * @param string $message
	 * @return void
	 */
	final protected function assertNotInArray(mixed $mixed, array $array, string $message = ''): void {
		if (!empty($message)) {
			$message .= "\n";
		}
		$message .= "Array contains value and should not \"$mixed\" (values: " . implode(', ', array_values($array)) . ')';
		$this->assertFalse(in_array($mixed, $array), $message);
	}

	/**
	 * Central place to dump variables to output.
	 * Use PHP output to facilitate generating tests whose output can be copied for first writing
	 * and manual verification.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function dump(mixed $value): string {
		return PHP::singleton()->settings_one()->render($value);
	}

	/**
	 * Given a class and a method, make the available method not-private to do blackbox testing
	 *
	 * @param string $class
	 * @param array $methods
	 * @return array
	 * @throws ReflectionException
	 */
	public function exposePrivateMethod(string $class, array $methods): array {
		$refl = new \ReflectionClass($class);
		$results = [];
		foreach ($methods as $method) {
			$cmethod = $refl->getMethod($method);
			$cmethod->setAccessible(true);
			$results[$method] = function ($object) use ($cmethod) {
				$args = func_get_args();
				array_shift($args);
				return $cmethod->invokeArgs($object, $args);
			};
		}
		return $results;
	}
}
