<?php

abstract class Module_Log extends Module {
	const FATAL = log::FATAL;
	const ERROR = log::ERROR;
	const WARNING = log::WARNING;
	const NOTICE = log::NOTICE;
	const DEBUG = log::DEBUG;
	const ALL = log::ALL;

	/**
	 * Default internal logging hook
	 *
	 * @param array $log
	 */
	abstract public function hook_log_send(array $log);

	/**
	 * Modify the log before sending. Add variables, global state, whatever you want to save for later.
	 *
	 * @param array $log
	 * @return array
	 */
	public function hook_log_alter(array $log) {
		return $log;
	}

}