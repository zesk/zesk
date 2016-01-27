<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/process/group.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

/**
 * @skip true
 * @author kent
 * @todo This test fails, but Process_Group may be deprecated with Command_Daemon functionality
 */
class Test_Process_Group extends Test_Unit {

	function test_main() {
		zesk::set('Zesk::run_path', $this->test_sandbox(null, false));

		$name = "test_process";
		$x = Process_Group::master($name);

		$x->anotherRunning();

		$x->isParent();

		$x->_pollChildren();

		$x->_childDied();

		$interrupt = false;
		$x->done($interrupt);

		$nSeconds = null;
		$x->alarm($nSeconds);

		$seconds = 2;
		$x->sleep($seconds);

		$x->pid();

		$message = null;
		$level = null;
		$x->log($message);

		$message = null;
		$x->error($message);

		$nPids = 1;
		$exitTime = false;
		$runOne = false;
		while ($x->run($nPids, $exitTime, $runOne)) {
			$x->done(true);
		}

		$x->terminated();

		echo basename(__FILE__) . ": success\n";
	}

	function test_signal_handler() {
		zesk::set('Zesk::run_path', $this->test_sandbox());

		$process_group = Process_Group::master('test');

		$signo = null;
		Process_Group::signal_handler($signo);
		echo basename(__FILE__) . ": success\n";
	}
}
