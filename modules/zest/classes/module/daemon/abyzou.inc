<?php
class Module_Daemon_Abyzou extends Module_Daemon_Base {
	static $daemon_options = array(
		'run_seconds' => 10,
		'sleep_seconds' => 10
	);
	public static function daemon(Process_Interruptable $process) {
		self::_daemon($process, __CLASS__, self::$daemon_options);
	}
}
