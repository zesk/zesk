<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Timer;

/**
 *
 * @author kent
 *
 */

/**
 * @coversDefaultClass \zesk\Timer
 * @uses Timer
 */
class TimerTest extends TestCase
{
	/**
	 * @return void
	 * @covers ::now
	 */
	public function test_now(): void
	{
		$timer = Timer::now();
		$this->assertLessThanOrEqual(Timer::now(), $timer);
	}

	/**
	 * @return void
	 * @covers ::__construct ::stop ::mark ::elapsed ::output ::dump
	 */
	public function test_basics(): void
	{
		$x = new Timer();

		$value = $x->stop();

		$mark = $x->mark();

		$this->assertGreaterThanOrEqual($value, $mark);
		$this->assertGreaterThan(0, $x->elapsed());

		$comment = '';
		$content = $x->output($comment);
		$this->assertStringContainsString('Elapsed:', $content);
		$this->assertStringContainsString('Total:', $content);
		$this->assertStringContainsString('second', $content);

		ob_start();
		$x->dump('');
		$content = ob_get_clean();

		$this->assertStringContainsString('Elapsed:', $content);
		$this->assertStringContainsString('Total:', $content);
		$this->assertStringContainsString('second', $content);
	}
}
