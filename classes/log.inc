<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/log.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * Logging tools
 *
 * @author kent
 */
class log {
	
	/*
	 * Signals and processes
	 * Level 0: Fatal
	 * Level 1: Error
	 * Level 2: Warning
	 * Level 3: Notice
	 * Level 4: Debug
	 * Debugging
	 *
	 */
	const FATAL = 0;
	const ERROR = 1;
	const WARNING = 2;
	const NOTICE = 3;
	const DEBUG = 4;
	const ALL = 1000000; // 1M Dollars
	const LOG_DEFAULT = self::ERROR;
	
	/**
	 * Cache of modules for logging
	 *
	 * @var array
	 */
	private static $modules = null;
	
	/**
	 * Global shutoff for logging
	 *
	 * @var boolean
	 */
	static $disabled = false;
	
	/**
	 * Global setting for time zone used for logging
	 *
	 * @var boolean
	 */
	static $utc_time = false;
	
	/**
	 * Log levels
	 *
	 * @var array
	 */
	private static $levels = array(
		self::FATAL => "FATAL",
		self::ERROR => "ERROR",
		self::WARNING => "WARNING",
		self::NOTICE => "NOTICE",
		self::DEBUG => "DEBUG"
	);
	
	/**
	 * Level index => string
	 *
	 * @return array
	 */
	public static function levels() {
		return self::$levels;
	}
	public static function level_string() {
		return avalue(self::$levels, $level = self::level(), "*bad-level-$level*");
	}
	public static function level($level = null) {
		if ($level !== null) {
			if (is_string($level)) {
				$level = avalue(array_flip(self::levels()), strtoupper($level), $level);
			}
			zesk::set(__CLASS__ . '::level', intval($level));
		}
		return zesk::geti(__CLASS__ . '::level', self::LOG_DEFAULT);
	}
	
	/**
	 * Register hooks
	 */
	public static function hooks(zesk\Kernel $zesk) {
		$zesk->hooks->add(zesk\Hooks::hook_configured, __CLASS__ . '::configured');
	}
	
	/**
	 * Configured hook
	 */
	public static function configured() {
		self::level(zesk::get(__CLASS__ . '::level', self::LOG_DEFAULT));
		$file = zesk::get(__CLASS__ . '::file');
		if ($file) {
			self::file($file);
		}
		self::$utc_time = zesk::getb(__CLASS__ . '::utc_time');
	}
	
	/**
	 * Cache to determine where a log call is coming from
	 */
	public static function modules_cache_reset() {
		self::$modules = null;
	}
	
	/**
	 * Return an array of path => module name
	 *
	 * @param boolean $reset        	
	 * @return array
	 */
	private static function modules_cache($reset = false) {
		if ($reset || self::$modules === null) {
			$modules = Module::load();
			self::$modules = array();
			foreach ($modules as $module => $data) {
				if (!$data['internal']) {
					self::$modules[$data['path']] = 'module:' . $data['name'];
				}
			}
			self::$modules += array(
				zesk::application_root() => zesk::application_class(),
				zesk::root('classes') => 'core'
			);
		}
		return self::$modules;
	}
	
	/**
	 * Determine the module which is logging a message
	 *
	 * @return string|NULL
	 */
	private static function determine_module() {
		$backtrace = debug_backtrace(0); // PHP 5.4 debug_backtrace(0,5)
		$modules = self::modules_cache();
		foreach ($backtrace as $bt) {
			$file = $class = $type = $function = null;
			extract($bt, EXTR_IF_EXISTS);
			if ($file === __FILE__) {
				continue;
			}
			foreach ($modules as $path => $name) {
				if (begins($file, $path)) {
					unset($backtrace);
					return $name;
				}
			}
			unset($backtrace);
			return $class;
		}
		return "application";
	}
	
	/**
	 * Send a log message.
	 *
	 * Note that this call adds default, internal arguments before passing to the hooks.
	 *
	 * The internal variables names all begin with an underscore to prevent collision with internal
	 * variables names.
	 *
	 * @param unknown $message        	
	 * @param array $args        	
	 * @param string $level        	
	 */
	public static function send($message, array $args = array(), $level = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (self::$disabled) {
			return;
		}
		static $sending = false;
		if ($sending) {
			// Doh.
			return;
		}
		if ($level === null) {
			$level = zesk::get(__CLASS__ . '::level_default', self::ERROR);
		}
		$log_level = self::level();
		if (intval($level) > $log_level) {
			return;
		}
		if (is_object($message)) {
			$message_args = method_exists($message, "log_variables") ? $message->log_variables() : array();
			$message = method_exists($message, "log_message") ? $message->log_message() : strval($message);
			$args = $message_args + $args;
		}
		if (is_array($message)) {
			foreach ($message as $y) {
				self::send($y, $args, $level);
			}
			return;
		}
		$pid = $zesk->process->id;
		$time = microtime(true);
		$int_time = intval($time);
		
		$extras = array();
		if (!array_key_exists('_module', $args)) {
			$extras['_module'] = self::determine_module();
		}
		$date = self::$utc_time ? "gmdate" : "date";
		$extras['_date'] = $date("Y-m-d", $int_time);
		$extras['_time'] = $date("H:i:s", $int_time) . ltrim(sprintf("%.3f", $time - $int_time), '0');
		$extras['_microtime'] = microtime(true);
		$extras['_pid'] = $pid;
		$extras['_level'] = $level;
		$extras['_level_string'] = avalue(self::levels(), $level, $level);
		$extras['_message'] = $message;
		$extras['_formatted'] = map($message, $args);
		
		$args += $extras;
		
		$sending = true;
		Module::all_hook_array('log_send', array(
			$args
		), null, __CLASS__ . "::hook_callback");
		$sending = false;
	}
	public static function hook_callback($func) {
		//echo zesk::callable_string($func) . "\n";
	}
	public static function disable($set = null) {
		if (is_bool($set)) {
			self::$disabled = $set;
		}
		return self::$disabled;
	}
	public static function file($filename = false, $mode = "a") {
		Module::internal('Log_File');
		$result = Module::object('Log_File')->file($filename, $mode);
		return $result;
	}
	/**
	 * Log a fatal error.
	 * Halts execution.
	 *
	 * @param string $message        	
	 * @param array $args        	
	 */
	public static function fatal($message, array $args = array()) {
		global $zesk;
		self::send($message, $args, self::FATAL);
		exit($zesk->hooks->call('exit'));
	}
	
	/**
	 * Log an error.
	 * Something which shouldn't happen, ever.
	 *
	 * @param string $message        	
	 * @param array $args        	
	 */
	public static function error($message, array $args = array()) {
		self::send($message, $args, self::ERROR);
	}
	/**
	 * Log an warning.
	 * Something which happens on occasion, but won't affect operation of the system.
	 *
	 * @param string $message        	
	 * @param array $args        	
	 */
	public static function warning($message, array $args = array()) {
		self::send($message, $args, self::WARNING);
	}
	
	/**
	 * Log a notice.
	 * Nice to know (something changed, data updated, etc.) are operation is normal.
	 *
	 * @param string $message        	
	 * @param array $args        	
	 */
	public static function notice($message, array $args = array()) {
		self::send($message, $args, self::NOTICE);
	}
	
	/**
	 * Debugging calls.
	 * Anything which is really for developers to see what's going on, not for normal users.
	 *
	 * @param string $message        	
	 * @param array $args        	
	 */
	public static function debug($message, array $args = array()) {
		self::send($message, $args, self::DEBUG);
	}
}

