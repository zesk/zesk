<?php declare(strict_types=1);
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

	protected $classes = [
		'zesk\\Health_Event',
		'zesk\\Health_Events',
	];

	protected $disabled = false;

	public function initialize(): void {
		parent::initialize();
		$this->disabled = $this->optionBool('disabled');
		$this->path = $path = $this->option('event_path', $this->application->data_path('health-events'));
		Directory::depend($path);
		set_error_handler([
			$this,
			'error_handler',
		], E_ALL | E_STRICT);
		set_exception_handler([
			$this,
			'exception_handler',
		]);
		$this->application->hooks->add('exception', [
			$this,
			'caught_exception_handler',
		]);
	}

	private static $error_codes = [
		E_ERROR => 'E_ERROR',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_WARNING => 'E_WARNING',
		E_PARSE => 'E_PARSE',
		E_NOTICE => 'E_NOTICE',
		E_STRICT => 'E_STRICT',
		E_DEPRECATED => 'E_DEPRECATED',
		E_CORE_ERROR => 'E_CORE_ERROR',
		E_CORE_WARNING => 'E_CORE_WARNING',
		E_COMPILE_ERROR => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR => 'E_USER_ERROR',
		E_USER_WARNING => 'E_USER_WARNING',
		E_USER_NOTICE => 'E_USER_NOTICE',
	];

	private static $fatal_errors = [
		E_ERROR => true,
		E_RECOVERABLE_ERROR => true,
		E_PARSE => true,
		E_CORE_ERROR => true,
		E_COMPILE_ERROR => true,
		E_USER_ERROR => true,
	];

	private function clean_backtrace(array $backtrace) {
		foreach ($backtrace as $index => $stackframe) {
			if (array_key_exists('args', $stackframe)) {
				if ($this->optionBool('keep_backtrace_arguments')) {
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
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return void|mixed|boolean
	 */
	public function error_handler($errno, $errstr, $errfile, $errline) {
		if ($this->disabled) {
			return false;
		}
		$type = strtolower(StringTools::unprefix(avalue(self::$error_codes, $errno, $errno), 'E_'));
		$this->log([
			'type' => $type,
			'code' => $errno,
			'fatal' => $fatal = avalue(self::$fatal_errors, $errno),
			'message' => $errstr,
			'file' => $errfile,
			'line' => $errline,
			'backtrace' => $this->clean_backtrace(debug_backtrace()),
			'_SERVER' => $_SERVER,
			'_REQUEST' => $_REQUEST,
		]);
		if ($fatal) {
			die("$type $errstr");
		}
		return $this->optionBool('skip_php_handler', false);
	}

	public function caught_exception_handler($exception): void {
		$this->_exception_handler($exception, false);
	}

	public function exception_handler($exception): void {
		$this->_exception_handler($exception, true);
	}

	public function disabled($set = null) {
		if ($set === null) {
			return $this->disabled;
		}
		$this->disabled = toBool($set);
		return $this;
	}

	private function _exception_handler($exception, $fatal = true): void {
		if ($this->disabled) {
			return;
		}
		/* @var $exception Throwable */
		/* @var $exception Exception */
		$trace = $exception->getTrace();
		$trace0 = $trace[0];
		$this->log([
			'type' => 'exception',
			'fatal' => $fatal,
			'exception' => $exception,
			'message' => $exception->getMessage(),
			'backtrace' => $this->clean_backtrace($trace),
			'file' => avalue($trace0, 'file', '-'),
			'line' => avalue($trace0, 'line', '-'),
			'_SERVER' => $_SERVER,
			'_REQUEST' => $_REQUEST,
		]);
	}

	public function log(array $event) {
		if ($this->disabled) {
			return;
		}
		$event = $this->call_hook_arguments('log', [
			$event,
		], $event);
		$event_object = Health_Event::event_log($this->application, $event, $this->path);
		$this->application->logger->error($event['message'], $event);
		return $event_object;
	}

	public static function daemon(Interface_Process $process): void {
		$app = $process->application();
		$app->health_module()->run_daemon($process);
	}

	/**
	 * Do not log errors while processing events. Unserialized resources, etc. will cause additional errors which we are OK to ignore
	 *
	 * @param Interface_Process $process
	 */
	public function run_daemon(Interface_Process $process): void {
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

	public function hook_cron_cluster_hour(): void {
		$purge_events_fatal_hours = $this->optionInt('purge_events_fatal_hours', -1);
		$purge_events_non_fatal_hours = $this->optionInt('purge_events_non_fatal_hours', 24 * 7);

		$this->purge_old_events('Health_Event', 'when', $purge_events_fatal_hours, $purge_events_non_fatal_hours);
		$this->purge_old_events('Health_Events', 'first', $purge_events_fatal_hours, $purge_events_non_fatal_hours);
	}

	private function purge_old_events($class, $date_column, $fatal_hours, $non_fatal_hours): void {
		if ($fatal_hours > 0) {
			$this->purge_event_types($class, $date_column, [
				'fatal' => true,
			], Timestamp::now()->addUnit(-abs($fatal_hours), Timestamp::UNIT_HOUR), 'fatal');
		}
		if ($non_fatal_hours > 0) {
			$this->purge_event_types($class, $date_column, [
				'fatal' => false,
			], Timestamp::now()->addUnit(-abs($non_fatal_hours), Timestamp::UNIT_HOUR), 'non-fatal');
		}
	}

	private function purge_event_types($class, $date_column, array $where, Timestamp $when, $description): void {
		$delete = $this->application->ormRegistry($class)->query_delete()->where($where + [
			"$date_column|<=" => $when,
		]);
		$delete->execute();
		$this->application->logger->warning('Deleted {description} {n} {classes} older than {when}', [
			'n' => $nrows = $delete->affectedRows(),
			'description' => $description,
			'classes' => $this->application->locale->plural($class, $nrows),
			'when' => $when,
		]);
	}
}
