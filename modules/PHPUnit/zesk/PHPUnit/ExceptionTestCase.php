<?php
declare(strict_types=1);
/**
 * Base class for tests for Exceptions (objects, not things that throw them)
 *
 * @package zesk
 * @subpackage PHPUnit
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\PHPUnit;

use Throwable;
use zesk\Exception;

class ExceptionTestCase extends TestCase
{
	/**
	 * @param Throwable $e
	 */
	public function assertThrowable(Throwable $e, bool $recurse = true): void
	{
		$this->assertIsString($e->getMessage());

		$this->assertIsInteger($e->getCode());

		$this->assertIsString($e->getFile());

		$this->assertGreaterThan(0, $e->getLine());

		$prev = $e->getPrevious();
		if ($prev) {
			$this->assertInstanceOf(Throwable::class, $prev);
			if ($recurse) {
				$this->assertThrowable($prev, false);
			}
		}

		$this->assertGreaterThan(0, strlen($e->__toString()), 'Blank __toString');

		$variables = Exception::exceptionVariables($e);
		$this->assertEquals($e::class, $variables['class']);
		$this->assertEquals($e::class, $variables['throwableClass']);
		$this->assertEquals($e::class, $variables['exceptionClass']);
		$this->assertEquals($e->getCode(), $variables['code']);
		$this->assertEquals($e->getMessage(), $variables['message']);
		$this->assertEquals($e->getFile(), $variables['file']);
		$this->assertEquals($e->getLine(), $variables['line']);

		// Causes infinite loop when this is assertEquals as the trace contains PHPUnit objects
		$this->assertSameSize($e->getTrace(), $variables['trace']);
		$this->assertEquals($e->getTraceAsString(), $variables['backtrace']);
		$this->assertIsString($variables['backtrace'], 'backtrace is string');
		$this->assertIsString($variables['rawMessage'], 'rawMessage is string');
	}
}
