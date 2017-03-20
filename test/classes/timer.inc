<?php
use zesk\Timer;
/**
 * 
 * @author kent
 *
 */
class Test_Timer extends Test_Unit {

	function test_now() {
		Timer::now();
	}

	function test_basics() {

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
