<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use ReflectionClass;
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

	/**
	 * @param string $includeFile
	 * @return string[]
	 */
	protected function zeskBinIncludeArgs(string $includeFile): array {
		return [$includeFile];
	}

	protected function zeskBin(): string {
		$zeskBin = $this->application->zeskHome('bin/zesk');
		$this->assertFileExists($zeskBin);
		$this->assertFileIsReadable($zeskBin);
		// 2>&1 Captures stderr to capture output with $captureFail
		return "XDEBUG_ENABLED=0 $zeskBin {*} 2>&1";
	}

	/**
	 * @param array $args
	 * @return array Lines output
	 * @throws Exception_Command
	 */
	protected function zeskBinExecute(array $args, bool $captureFail = false): array {
		try {
			return $this->application->process->executeArguments($this->zeskBin(), $args);
		} catch (Exception_Command $e) {
			if ($captureFail) {
				return $e->getOutput();
			}

			throw $e;
		}
	}

	/**
	 * Run a PHP include file and return lines of output as an array.
	 *
	 * @param string $includeFile
	 * @return array
	 * @throws Exception_Command
	 */
	public function zeskEvalFile(string $includeFile): array {
		return $this->application->process->executeArguments($this->zeskBin(), $this->zeskBinIncludeArgs($includeFile));
	}

	/**
	 * Run a PHP include file in the background and return the PID of the process.
	 *
	 * @param string $includeFile
	 * @return int
	 * @throws Exception_Command
	 */
	public function zeskEvalFileProcess(string $includeFile): int {
		$output = $this->test_sandbox(basename($includeFile) . '.log');
		return $this->application->process->executeBackground($this->zeskBin(), $this->zeskBinIncludeArgs($includeFile), $output, $output);
	}

	/**
	 * @param $message
	 * @param array $args
	 * @return void
	 */
	public function log($message, array $args = []): void {
		$this->application->logger->debug($message, $args);
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
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
		$reflectionClass = new ReflectionClass($class);
		$results = [];
		foreach ($methods as $method) {
			$classMethod = $reflectionClass->getMethod($method);
			$classMethod->setAccessible(true);
			$results[$method] = function ($object) use ($classMethod) {
				$args = func_get_args();
				array_shift($args);
				return $classMethod->invokeArgs($object, $args);
			};
		}
		return $results;
	}
}
