<?php
/**
 *
 */
namespace zesk;

/**
 * The health module monitors your application health and helps to diagnose issues across a larger server farm by
 * centralizing error logging.
 *
 * @author kent
 *
 */
class Module_Health extends Module {
	protected $path = null;

	protected $classes = array(
		"zesk\\Health_Event",
		"zesk\\Health_Events",
	);

	protected $disabled = false;

	public function initialize() {
		parent::initialize();
		$this->disabled = $this->option_bool("disabled");
		$this->path = $path = $this->option("event_path", $this->application->data_path("health-events"));
		Directory::depend($path);
		set_error_handler(array(
			$this,
			"error_handler",
		), E_ALL | E_STRICT);
		set_exception_handler(array(
			$this,
			"exception_handler",
		));
		$this->application->hooks->add("exception", array(
			$this,
			"caught_exception_handler",
		));
	}

	private static $error_codes = array(
		E_ERROR => "E_ERROR",
		E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
		E_WARNING => "E_WARNING",
		E_PARSE => "E_PARSE",
		E_NOTICE => "E_NOTICE",
		E_STRICT => "E_STRICT",
		E_DEPRECATED => "E_DEPRECATED",
		E_CORE_ERROR => "E_CORE_ERROR",
		E_CORE_WARNING => "E_CORE_WARNING",
		E_COMPILE_ERROR => "E_COMPILE_ERROR",
		E_COMPILE_WARNING => "E_COMPILE_WARNING",
		E_USER_ERROR => "E_USER_ERROR",
		E_USER_WARNING => "E_USER_WARNING",
		E_USER_NOTICE => "E_USER_NOTICE",
	);

	private static $fatal_errors = array(
		E_ERROR => true,
		E_RECOVERABLE_ERROR => true,
		E_PARSE => true,
		E_CORE_ERROR => true,
		E_COMPILE_ERROR => true,
		E_USER_ERROR => true,
	);

	private function clean_backtrace(array $backtrace) {
		foreach ($backtrace as $index => $stackframe) {
			if (array_key_exists('args', $stackframe)) {
				if ($this->option_bool("keep_backtrace_arguments")) {
					$new_args = $stackframe['args'];
					foreach ($new_args as $index => $arg) {
						if (is_resource($arg) || is_callable($arg)) {
							$fake_arg = new \stdClass();
							$fake_arg['get_resource_type'] = get_resource_type($arg);
							$fake_arg['strval'] = strval($arg);
							$new_args[$index] = $fake_arg;
						}
					}
					$backtrace[$index]['args'] = $new_args;
				} else {
					unset($backtrace[$index]['args']);
				}
			}
		}
		return $backtrace;
	}

	/**
	 * Error handler.
	 *
	 * 2017-04-12 Removed $errcontext due to removal in PHP 7.2:
	 *
	 * https://wiki.php.net/rfc/deprecations_php_7_2
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @return void|mixed|boolean
	 */
	public function error_handler($errno, $errstr, $errfile, $errline) {
		if ($this->disabled) {
			return false;
		}
		$type = strtolower(StringTools::unprefix(avalue(self::$error_codes, $errno, $errno), "E_"));
		$this->log(array(
			"type" => $type,
			"code" => $errno,
			"fatal" => $fatal = avalue(self::$fatal_errors, $errno),
			"message" => $errstr,
			"file" => $errfile,
			"line" => $errline,
			"backtrace" => $this->clean_backtrace(debug_backtrace()),
			"_SERVER" => $_SERVER,
			"_REQUEST" => $_REQUEST,
		));
		if ($fatal) {
			die("$type $errstr");
		}
		return $this->option_bool("skip_php_handler", false);
	}

	public function caught_exception_handler($exception) {
		$this->_exception_handler($exception, false);
	}

	public function exception_handler($exception) {
		$this->_exception_handler($exception, true);
	}

	public function disabled($set = null) {
		if ($set === null) {
			return $this->disabled;
		}
		$this->disabled = to_bool($set);
		return $this;
	}

	private function _exception_handler($exception, $fatal = true) {
		if ($this->disabled) {
			return;
		}
		/* @var $exception Throwable */
		/* @var $exception Exception */
		$trace = $exception->getTrace();
		$trace0 = $trace[0];
		$this->log(array(
			"type" => "exception",
			"fatal" => $fatal,
			"exception" => $exception,
			"message" => $exception->getMessage(),
			"backtrace" => $this->clean_backtrace($trace),
			"file" => avalue($trace0, 'file', '-'),
			"line" => avalue($trace0, 'line', '-'),
			"_SERVER" => $_SERVER,
			"_REQUEST" => $_REQUEST,
		));
	}

	public function log(array $event) {
		if ($this->disabled) {
			return;
		}
		$event = $this->call_hook_arguments("log", array(
			$event,
		), $event);
		$event_object = Health_Event::event_log($this->application, $event, $this->path);
		$this->application->logger->error($event['message'], $event);
		return $event_object;
	}

	public static function daemon(Interface_Process $process) {
		$app = $process->application();
		$app->health_module()->run_daemon($process);
	}

	/**
	 * Do not log errors while processing events. Unserialized resources, etc. will cause additional errors which we are OK to ignore
	 *
	 * @param Interface_Process $process
	 */
	public function run_daemon(Interface_Process $process) {
		$this->disabled(true);

		declare(ticks = 1) {
			while (!$process->done()) {
				if (!Health_Event::event_process($process->application(), $this->path)) {
					$process->sleep(1);
				}
			}
		}
		$this->disabled(false);
	}

	public function hook_cron_cluster_hour() {
		$purge_events_fatal_hours = $this->option_integer("purge_events_fatal_hours", -1);
		$purge_events_non_fatal_hours = $this->option_integer("purge_events_non_fatal_hours", 24 * 7);

		$this->purge_old_events('Health_Event', 'when', $purge_events_fatal_hours, $purge_events_non_fatal_hours);
		$this->purge_old_events('Health_Events', 'first', $purge_events_fatal_hours, $purge_events_non_fatal_hours);
	}

	private function purge_old_events($class, $date_column, $fatal_hours, $non_fatal_hours) {
		if ($fatal_hours > 0) {
			$this->purge_event_types($class, $date_column, array(
				"fatal" => true,
			), Timestamp::now()->add_unit(-abs($fatal_hours), Timestamp::UNIT_HOUR), "fatal");
		}
		if ($non_fatal_hours > 0) {
			$this->purge_event_types($class, $date_column, array(
				"fatal" => false,
			), Timestamp::now()->add_unit(-abs($non_fatal_hours), Timestamp::UNIT_HOUR), "non-fatal");
		}
	}

	private function purge_event_types($class, $date_column, array $where, Timestamp $when, $description) {
		$delete = $this->application->orm_registry($class)->query_delete()->where($where + array(
			"$date_column|<=" => $when,
		));
		$delete->execute();
		$this->application->logger->warning("Deleted {description} {n} {classes} older than {when}", array(
			"n" => $nrows = $delete->affected_rows(),
			"description" => $description,
			"classes" => $this->application->locale->plural($class, $nrows),
			"when" => $when,
		));
	}
}
