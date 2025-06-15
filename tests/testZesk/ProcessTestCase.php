<?php
declare(strict_types=1);

namespace testZesk;

/**
 * @coversDefaultClass \zesk\Process
 */
class ProcessTestCase extends TestCase
{
	/**
	 * @return void
	 * @covers ::id
	 */
	public function test_pid(): void
	{
		$this->assertGreaterThan(0, $this->application->process->id());
	}

	public function test_running(): void
	{
		$process = $this->application->process;
		$pid = $this->application->process->id();
		$this->assertIsInteger($pid);
		$this->assertTrue($process->alive($pid));
		$this->assertFalse($process->alive(32766));
	}
}
