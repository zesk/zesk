<?php
/**
 * 
 */
use zesk\Timer;
use zesk\Module;

/**
 * 
 * @author kent
 *
 */
abstract class Module_Daemon_Base extends zesk\Module {
	protected static function _daemon(zesk\Interface_Process $process, $class = null, array $options = array()) {
		$run_seconds = avalue($options, "run_seconds", 60);
		$sleep_seconds = avalue($options, "sleep_seconds", 10);
		if ($class === null) {
			$this->application->logger->error("Need to subclass this {class}", array(
				"class" => __CLASS__
			));
			$process->terminate();
			return;
		}
		$timer = new Timer();
		do {
			$process->log("{class} running as pid {pid} (Run for {run_seconds} seconds) sleep={sleep_seconds}", array(
				"class" => $class,
				"pid" => zesk()->process_id(),
				"run_seconds" => $run_seconds,
				"sleep_seconds" => $sleep_seconds
			));
			$process->sleep($sleep_seconds);
		} while (!$process->done() && $timer->elapsed() < $run_seconds);
		$process->log("{class} quitting voluntarily {pid}", array(
			"class" => $class,
			"pid" => zesk()->process_id(),
			"severity" => "notice"
		));
	}
}
