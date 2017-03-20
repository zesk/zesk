<?php
class Module_Daemon_Belphegor extends zesk\Module_Daemon_Base {
	static $daemon_options = array(
		'run_seconds' => 120,
		'sleep_seconds' => 60
	);
	public static function daemon(zesk\Interface_Process $process) {
		parent::_daemon($process, __CLASS__, self::$daemon_options);
	}
}
