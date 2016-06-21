<?php
/**
 * Implements logging to a database table
 *
 * @author kent
 * @copyright (C) 2013 Market Acumen, Inc.
 * @package zesk
 * @subpackage dblog
 */
class Module_DBLog extends Module_Log {

	/**
	 * Cached log level
	 *
	 * @var integer
	 */
	private static $log_level = null;

	/**
	 * Implements Module::classes
	 *
	 * @return array
	 */
	protected $classes = array(
		"DBLog"
	);

	/**
	 * Implements Module::log_send()
	 *
	 * @param array $args
	 */
	public function hook_log_send(array $args) {
		$level = avalue($args, '_level', log::DEBUG);
		if (self::$log_level === null) {
			self::$log_level = zesk::get('Module_DBLog::log_level', log::NOTICE);
		}
		if ($level >= self::$log_level) {
			return;
		}
		if (!array_key_exists("request_url", $args)) {
			$args['request_url'] = Request::instance()->url();
		}
		try {
			$user = User::instance();
		} catch (Exception $e) {
			log::warning("Module_DBLog::log_send threw exception - disabling");
			self::$log_level = -9999;
			return;
		}

		$defaults = array(
			'message' => "",
			"user" => $user,
			"session" => Session::instance(false),
			"ip" => IPv4::remote(),
			"pid" => zesk::pid(),
			"level" => log::DEBUG,
			"level_string" => "DEBUG",
			"module" => "unknown"
		);
		$fields = array();
		foreach ($defaults as $key => $default) {
			$fields[$key] = avalue($args, "_" . $key, $default);
			unset($args['_' . $key]);
		}
		$fields['when'] = map('{_date} {_time}', $args);
		$microsec = doubleval($args['_microtime']);
		$fields['microsec'] = intval(($microsec - intval($microsec)) * 1000000);
		foreach (to_list("_formatted;_date;_time;_microtime") as $k) {
			unset($args[$k]);
		}
		foreach ($args as $k => $v) {
			if (is_object($v)) {
				unset($args[$k]);
			}
		}
		$fields['arguments'] = $args;
		try {
			$this->application->object_factory("DBLog")->set($fields)->store();
		} catch (Database_Exception_Table_NotFound $e) {
			// Oopsie. Update the schema.
		}
	}

	/**
	 * Is the DBLog enabled?
	 *
	 * @return boolean
	 */
	public static function enabled() {
		return zesk::getb(__CLASS__ . '::enabled', Application::instance()->development());
	}
}