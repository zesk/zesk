<?php
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  &copy; 2025, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace testZesk;

use zesk\Exception\NotFoundException;
use zesk\PHPUnit\TestCase;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Configuration;
use zesk\Directory;
use zesk\File;
use zesk\Kernel;
use zesk\Options;
use zesk\Types;
use zesk\Version;

/**
 * @coversDefaultClass \zesk\Kernel
 */
class KernelTest extends TestCase
{
	/**
	 * @var bool
	 */
	protected bool $configureApplication = true;

	/**
	 * @var int
	 */
	public int $order = 0;

	/**
	 * @var bool
	 */
	public bool $_hook_was_called = false;

	public static function _test_hook_order_1st(KernelTest $test): void
	{
		$test->assertEquals(0, $test->order);
		$test->order++;
	}

	public static function _test_hook_order_2nd(KernelTest $test): void
	{
		$test->assertEquals(1, $test->order);
		$test->order++;
	}

	public function add_hook_was_called($arg): void
	{
		$this->assertEquals(2, $arg);
		$this->assertInstanceOf(Application::class, $this->application);
		$this->application->setOption('hook_was_called', true);
	}

	public function hook_was_called(): bool
	{
		return $this->application->optionBool('hook_was_called');
	}

	public function _backtraceLines(int $n): int
	{
		$content = Kernel::backtrace($n);
		return count(explode("\n", $content));
	}

	/**
	 * @return void
	 * @covers ::backtrace
	 */
	public function test_backtrace(): void
	{
		$this->assertGreaterThan(8, $this->_backtraceLines(-1));
		$this->assertGreaterThan(9, $this->_backtraceLines(0));
		$this->assertEquals(1, $this->_backtraceLines(1));
		$this->assertEquals(2, $this->_backtraceLines(2));
	}

	/**
	 * @return void
	 * @covers ::singleton
	 */
	public function test_kernel_singleton(): void
	{
		$kernel = Kernel::singleton();
		$this->assertInstanceOf(Kernel::class, $kernel);
	}

	/**
	 * @return void
	 * @covers ::caller ::callingFunction
	 */
	public function test_calling(): void
	{
		$this->assertEquals(__FILE__ . ' ' . __CLASS__ . '->test_calling:' . __LINE__, Kernel::callingFunction(0));
		$this->assertEquals([
			'file' => __FILE__,
			'type' => '->',
			'function' => __FUNCTION__,
			'args' => [],
			'method' => __CLASS__ . '->' . __FUNCTION__,
			'class' => __CLASS__,
			'object' => $this,
			'fileMethod' => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__,
			'lineSuffix' => ':' . (__LINE__ + 4),
			'methodLine' => __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 3),
			'fileMethodLine' => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 2),
			'line' => __LINE__ + 1,
		], ArrayTools::filterKeys(Kernel::caller(0), null, [
			'callingLine',
			'callingFile',
		]));

		for ($i = 0; $i < 4; $i++) {
			$this->assertEquals(Kernel::callingFunction($i, true), Kernel::caller($i)['fileMethodLine']);
			$this->assertEquals(Kernel::callingFunction($i, false), Kernel::caller($i)['fileMethod']);
		}
	}

	/**
	 * @return void
	 * @covers ::callingFunction
	 */
	public function test_calling_neg(): void
	{
		$this->assertEquals($this->application->zeskHome('src/Kernel.php') . ' zesk\Kernel::callingFunction', Kernel::callingFunction(-1, false));
	}
}
