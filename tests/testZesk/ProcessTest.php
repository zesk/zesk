<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

/**
 * @coversDefaultClass \zesk\Process
 */
class ProcessTest extends TestCase
{
	/**
	 * @return void
	 * @covers ::id
	 */
	public function test_pid(): void
	{
		$this->assertGreaterThan(0, $this->application->process->id());
	}

	/**
	 * @return void
	 * @covers ::id
	 */
	public function test_running(): void
	{
		$process = $this->application->process;
		$pid = $this->application->process->id();
		$this->assertIsInteger($pid);
		$this->assertTrue($process->alive($pid));
		$this->assertFalse($process->alive(32766));
	}
}
