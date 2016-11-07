<?php

/**
 * @todo rewrite this to use zesk\Logger
 * @author kent
 *
 */
class Module_FirePHP extends Module implements zesk\Logger\Handler {
	
	/**
	 * 
	 * @return string[]
	 */
	private static function fp_incs() {
		$fp_inc = array();
		$fp_inc[] = FIREPHP_MODULE_PATH . 'lib/FirePHPCore/FirePHP.class.php';
		$fp_inc[] = FIREPHP_MODULE_PATH . 'lib/FirePHPCore/fb.php';
		return $fp_inc;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public static function validate() {
		$fp_incs = self::fp_incs();
		file::depends($fp_incs);
		return true;
	}
	static $inited = false;
	static $loaded = false;
	
	/**
	 * 
	 * @return boolean
	 */
	private static function _init() {
		if (self::$inited) {
			return self::$loaded;
		}
		self::$inited = true;
		$fp_incs = self::fp_incs();
		try {
			file::depends($fp_incs);
		} catch (Exception $e) {
			return false;
		}
		foreach ($fp_incs as $fp_inc) {
			require_once $fp_inc;
		}
		self::$loaded = true;
		return true;
	}
	
	/**
	 * 
	 * @param unknown $message
	 * @param unknown $loglevel
	 * @return boolean
	 */
	public static function log($message, array $context = array()) {
		if (!self::_init()) {
			return false;
		}
		if (zesk()->console) {
			return false;
		}
		$loglevel = avalue($context, '_severity', 'info');
		static $level_map = array(
			"error" => FirePHP::ERROR,
			"warning" => FirePHP::WARN,
			"critical" => FirePHP::LOG,
			"notice" => FirePHP::INFO,
			"debug" => FirePHP::DUMP
		);
		
		$fb_level = avalue($level_map, $loglevel, FirePHP::LOG);
		FB::send($message, null, $fb_level);
	}
}

