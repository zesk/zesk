<?php
abstract class Module_Daemon_Base extends Module {
	protected static function _daemon(Process_Interruptable $process, $class = null, array $options = array()) {
		$run_seconds = avalue($options, "run_seconds", 60);
		$sleep_seconds = avalue($options, "sleep_seconds", 10);
		if ($class === null) {
			log::error("Need to subclass this {class}", array(
				"class" => __CLASS__
			));
			$process->terminate();
			return;
		}
		$timer = new Timer();
		do {
			$process->log("{class} running as pid {pid} (Run for {run_seconds} seconds) sleep={sleep_seconds}", array(
				"class" => $class,
				"pid" => zesk::pid(),
				"run_seconds" => $run_seconds,
				"sleep_seconds" => $sleep_seconds,
			), log::NOTICE);
			$process->sleep($sleep_seconds);
		} while (!$process->done() && $timer->elapsed() < $run_seconds);
		$process->log("{class} quitting voluntarily {pid}", array(
			"class" => $class,
			"pid" => zesk::pid()
		), log::NOTICE);
	}
}
