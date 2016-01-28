<?php
class Module_Daemon_Pontianak extends Module_Daemon_Base {
	static $daemon_options = array(
		'run_seconds' => 240,
		'sleep_seconds' => 60
	);
	public static function daemon(Process_Interruptable $process) {
		parent::_daemon($process, __CLASS__, self::$daemon_options);
	}
}