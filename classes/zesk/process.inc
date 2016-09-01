<?php

namespace zesk;

class Process {
	/**
	 * 
	 * @var integer
	 */
	public $id = null;
	
	/**
	 * Create object
	 */
	function __construct(Kernel $zesk) {
		$this->id = intval(getmypid());
	}
	
	/**
	 * 
	 * @param integer $pid
	 * @throws \Exception_Unimplemented
	 * @return boolean
	 */
	public function alive($pid) {
		if (!function_exists("posix_kill")) {
			throw new \Exception_Unimplemented("Need --with-pcntl");
		}
		return posix_kill($pid, 0) ? true : false;
	}
}