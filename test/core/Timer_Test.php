<?php
declare(strict_types=1);
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
