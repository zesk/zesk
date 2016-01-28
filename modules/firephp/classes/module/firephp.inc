<?php
class Module_FirePHP extends Module {

	private static function fp_incs() {
		$fp_inc = array();
		$fp_inc[] = FIREPHP_MODULE_PATH . 'lib/FirePHPCore/FirePHP.class.php';
		$fp_inc[] = FIREPHP_MODULE_PATH . 'lib/FirePHPCore/fb.php';
		return $fp_inc;
	}

	public static function validate() {
		$fp_incs = self::fp_incs();
		file::depends($fp_incs);
		return true;
	}

	static $inited = false;

	static $loaded = false;

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

	public static function log_send($message, $loglevel = log::DEBUG) {
		if (!self::_init()) {
			return false;
		}
		if (zesk::$console) {
			return false;
		}
		static $level_map = array(
			log::ERROR => FirePHP::ERROR,
			log::WARNING => FirePHP::WARN,
			log::FATAL => FirePHP::LOG,
			log::NOTICE => FirePHP::INFO,
			log::DEBUG => FirePHP::DUMP
		);

		$fb_level = avalue($level_map, $loglevel, FirePHP::LOG);
		FB::send($message, null, $fb_level);
		// FirePHP::TABLE
		// FirePHP::GROUP_START
		// FirePHP::GROUP_END
		// FirePHP::EXCEPTION
	}
}

