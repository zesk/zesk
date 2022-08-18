<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Timer_Test extends UnitTest {
	public function test_now(): void {
		Timer::now();
	}

	public function test_basics(): void {
		$initTime = false;
		$offset = 0;
		$x = new Timer($initTime, $offset);

		Timer::now();

		$x->stop();

		$x->mark();

		$x->elapsed();

		$comment = '';
		$x->output($comment);

		$comment = '';
		$x->dump($comment);
	}
}
