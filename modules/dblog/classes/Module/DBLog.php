<?php

/**
 * Implements logging to a database table
 *
 * @author kent
 * @copyright (C) 2013 Market Acumen, Inc.
 * @package zesk
 * @subpackage dblog
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_DBLog extends Module implements Logger\Handler {
	
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
	public function initialize() {
		parent::initialize();
		$logger = $this->application->logger;
		if ($logger instanceof \zesk\Logger || method_exists($logger, "register_handler")) {
			$logger->register_handler(__CLASS__, $this, $this->option_list("levels", $logger->levels_select(true)));
		} else {
			$logger->warning("{class} not supported on logger of class {logger_class}", array(
				"class" => __CLASS__,
				"logger_class" => get_class($logger)
			));
		}
	}
	
	/**
	 * Implements Module::log_send()
	 *
	 * @param array $context
	 */
	public function log($message, array $context) {
		$application = $this->application;
		
		if (!array_key_exists("request_url", $context)) {
			$context['request_url'] = $application->request()->url();
		}
		try {
			$user = $this->application->user();
		} catch (Exception $e) {
			return;
		}
		
		$defaults = array(
			'message' => "",
			"user" => $user,
			"session" => $this->application->session(false),
			"ip" => IPv4::remote(),
			"pid" => zesk()->process_id(),
			"level_string" => avalue($context, 'severity', 'info'),
			"module" => "unknown"
		);
		$fields = array();
		foreach ($defaults as $key => $default) {
			$fields[$key] = avalue($context, "_" . $key, $default);
			unset($context['_' . $key]);
		}
		$fields['when'] = map('{_date} {_time}', $context);
		$microsec = doubleval($context['_microtime']);
		$fields['microsec'] = intval(($microsec - intval($microsec)) * 1000000);
		foreach (to_list("_formatted;_date;_time;_microtime") as $k) {
			unset($context[$k]);
		}
		foreach ($context as $k => $v) {
			if (is_object($v)) {
				unset($context[$k]);
			}
		}
		$fields['arguments'] = $context;
		try {
			$this->application->object_factory("DBLog")->set($fields)->store();
		} catch (Database_Exception_Table_NotFound $e) {
			// Oopsie. Update the schema.
		}
	}
}
