<?php
declare(strict_types=1);
/**
 * Handles running of intermittent tasks
 *
 * @documentation docs/cron.md
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Cron;

use Closure;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Exception\TimeoutExpired;
use zesk\Exception\Unimplemented;
use zesk\Hookable;
use zesk\Interface\MetaInterface;
use zesk\Interface\SettingsInterface;
use zesk\Module as BaseModule;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Lock;
use zesk\ORM\ORMBase;
use zesk\ORM\Server;
use zesk\PHP;
use zesk\Request;
use zesk\Response;
use zesk\Router;
use zesk\System;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule {
	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		Server::class, Lock::class,
	];

	/**
	 * Database lock name
	 *
	 * @var string
	 */
	protected string $lock_name = __CLASS__;

	/**
	 * Debugging - place to accumulate called methods in order to
	 * audit methods called by cron
	 *
	 * @var array
	 */
	private array $methods = [];

	/**
	 * Cached value for scopes
	 *
	 * @var array
	 */
	private array $scopes = [];

	/**
	 * microtime
	 */
	private float $start;

	/**
	 * The following functions are called within Module, Object, and Application classes:
	 *
	 * ::cron - Run every time cron is run
	 * ::cron_minute - Run at most once a minute
	 * ::cron_hour - Run at most once per hour
	 * ::cron_day - Run at most once per day
	 * ::cron_week - Run at most once per week
	 * ::cron_month - Run at most once per month
	 * ::cron_year - Run at most once per year
	 *
	 * @var array
	 */
	public static array $intervals = [
		'minute', 'hour', 'day', 'week', 'month', 'year',
	];

	/**
	 * State variable which changes during _run to determine where we're executing
	 *
	 * @var string
	 */
	protected string $hook_source = '';

	/**
	 * Run this before each hook
	 *
	 * @param callable|Closure|array|string $method
	 *            Method will be called
	 */
	public function _hook_callback(callable|Closure|array|string $method): void {
		$method_string = $this->application->hooks->callable_string($method);
		if (is_array($method) && ($method[0] instanceof Module)) {
			$name = $method[0]->codeName();
			$message = "\$application->modules->object(\"$name\")->{1}()";
			$message_args = $method;
		} else {
			$message = '{method_string}($application)';
			$message_args = compact('method_string');
		}
		$this->application->logger->notice("zesk eval '$message' # {source}", [
			'source' => $this->hook_source,
		] + $message_args);
		$this->methods[] = $method_string;
		$this->start = microtime(true);
	}

	/**
	 * Run this after each hook.
	 * Handles timing and warnings. Passes to default Hookable::combine_hook_results.
	 *
	 * @param callable|Closure|array|string $method
	 * @param mixed $previous_result
	 * @param mixed $new_result
	 * @return mixed
	 */
	public function _result_callback(callable|Closure|array|string $method, mixed $previous_result, mixed $new_result): mixed {
		$elapsed = microtime(true) - $this->start;
		if ($elapsed > ($elapsed_warn = $this->optionFloat('elapsed_warn', 2))) {
			$locale = $this->application->locale;
			$this->application->logger->warning('Cron: {method} took {elapsed} {seconds} (exceeded {elapsed_warn} {elapsed_warn_seconds})', [
				'elapsed' => sprintf('%.3f', $elapsed), 'seconds' => $locale->plural('second', intval($elapsed)),
				'elapsed_warn' => sprintf('%.3f', $elapsed_warn),
				'elapsed_warn_seconds' => $locale->plural('second', intval($elapsed_warn)),
				'method' => $this->application->hooks->callable_string($method),
			]);
		}
		return Hookable::combineHookResults($previous_result, $new_result);
	}

	/**
	 * @return string
	 */
	public function lockName(): string {
		return $this->lock_name;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws ParameterException
	 */
	public function setLockName(string $name): self {
		if (empty($name)) {
			throw new ParameterException('Blank lock name for {method} {name}', [
				'method' => __METHOD__, 'name' => $name,
			]);
		}
		$this->lock_name = $name;
		return $this;
	}

	/**
	 * Hook Module::settings
	 *
	 * @return array
	 */
	public function hook_settings(): array {
		return [
			__CLASS__ . '::page_runner' => [
				'type' => 'boolean', 'default' => false,
				'name' => 'Cron task runs asynchronously via web page requests.',
				'description' => 'Whether to run poor-man style cron tasks from page requests.',
			], __CLASS__ . '::time_limit' => [
				'type' => 'integer', 'default' => 0, 'name' => 'Time limit for cron tasks to complete',
				'description' => "Set this to a value if cron tasks run too long, and need to be terminated after a certain number of seconds (and that\'s ok)... Uses php's time_limit ini setting to terminate the cron task after a certain amount of time.",
			], __CLASS__ . '::page_runner_script' => [
				'type' => 'uri', 'default' => 'js/cron.js', 'name' => 'Path of script to run cron on each page. ',
				'description' => 'Modify this if it conflicts with one of your own scripts. Note this page should never be cached. It should be a unique URL.',
			],
		];
	}

	/**
	 * @return string
	 */
	private function page_runner_script(): string {
		return $this->optionString('page_runner_script', 'js/cron.js');
	}

	/**
	 * Hook Module::routes
	 * Add desired routes to Router $router
	 *
	 * @param Router $router
	 *            Current router
	 *
	 * @return void
	 */
	public function hook_routes(Router $router): void {
		$router->addRoute($this->page_runner_script(), [
			'method' => [
				$this, 'run_js',
			], 'content type' => 'text/javascript',
		]);
	}

	/**
	 * Hook Module::configured
	 */
	public function hook_configured(): void {
		if ($this->optionBool('page_runner')) {
			$this->application->hooks->add('response/html.tpl', [
				$this, 'page_runner',
			]);
		}
	}

	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @param string $suffix
	 * @return string
	 */
	private static function _cron_variable_prefix(string $suffix = ''): string {
		return __CLASS__ . '::last' . ($suffix ? "_$suffix" : '') . '::' . System::uname();
	}

	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @param string $prefix
	 * @param string $unit
	 * @return string
	 */
	private static function _last_cron_variable(string $prefix, string $unit): string {
		return __CLASS__ . '::last' . $prefix . ($unit ? "_$unit" : '');
	}

	/**
	 * Time that cron was last run
	 *
	 * @param MetaInterface $object
	 * @param string $prefix
	 * @param string $unit
	 * @return Timestamp
	 */
	private static function _last_cron_run(MetaInterface $object, string $prefix = '', string $unit = ''): Timestamp {
		return Timestamp::factory($object->meta(self::_last_cron_variable($prefix, $unit)));
	}

	private static function _cron_ran(MetaInterface $object, $prefix, $unit, Timestamp $when): void {
		$object->setMeta(self::_last_cron_variable($prefix, $unit), $when->unixTimestamp());
	}

	/**
	 * @param MetaInterface $object
	 * @param string $prefix
	 * @param $unit
	 * @return void
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	private static function _cron_reset(MetaInterface $object, string $prefix = '', $unit = null): void {
		$name = self::_last_cron_variable($prefix, $unit);
		if ($object instanceof Server) {
			$object->deleteAllMeta($name);
		} else {
			$object->deleteMeta($name);
		}
	}

	/**
	 * @param string $method
	 * @return array
	 */
	private function _cron_hooks(string $method): array {
		$classes = Types::toList($this->application->configuration->getPath(__CLASS__ . '::classes', [
			Application::class, ORMBase::class,
		]));
		return ArrayTools::suffixValues($classes, "::$method");
	}

	/**
	 * Retrieve the objects which store our state
	 *
	 * @param Application $application
	 * @return array:MetaInterface
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws Semantics
	 * @throws ConfigurationException
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 */
	private function _cron_scopes(Application $application): array {
		if (count($this->scopes)) {
			return $this->scopes;
		}
		$server = Server::singleton($application);
		$settings = $application->settings();

		return $this->scopes = [
			'cron_server' => [
				'state' => $server, 'prefix' => '', 'lock' => 'cron-server-' . $server->id,
			], 'cron' => [
				'state' => $settings, 'prefix' => '', 'lock' => 'cron-application-' . $application->id(),
			], 'cron_cluster' => [
				'state' => $settings, 'prefix' => '_cluster', 'lock' => 'cron-cluster',
			],
		];
	}

	/**
	 * @return $this
	 * @throws ClassNotFound
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws Semantics
	 * @throws TableNotFound
	 */
	public function reset(): self {
		$scopes = $this->_cron_scopes($this->application);
		foreach ($scopes as $settings) {
			$state = $settings['state'];
			/* @var $state MetaInterface */
			self::_cron_reset($state);
			foreach (self::$intervals as $unit) {
				self::_cron_reset($state, $settings['prefix'], $unit);
			}
		}
		return $this;
	}

	/**
	 *
	 * @return array
	 * @throws ParseException
	 */
	public function listStatus(): array {
		$hooks = $this->application->hooks;
		$now = Timestamp::now();
		$results = [];

		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::list_status');
			return [];
		}
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state MetaInterface */
			$last_run = self::_last_cron_run($state);

			$status = $now->difference($last_run, 'second') > 0;
			$cron_hooks = $this->_cron_hooks($method);
			$all_hooks = $hooks->findAll($cron_hooks);
			$all_hooks = array_merge($all_hooks, $this->application->modules->listAllHooks($method));
			foreach ($all_hooks as $hook) {
				$results[$hooks->callable_string($hook)] = $status;
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $settings['prefix'], $unit);
				$status = $now->difference($last_unit_run, $unit) > 0;
				$unit_hooks = ArrayTools::suffixValues($cron_hooks, "_
				$unit");
				$all_hooks = $this->application->modules->listAllHooks($method . '_' . $unit);
				$all_hooks = array_merge($all_hooks, $hooks->findAll($unit_hooks));
				foreach ($all_hooks as $hook) {
					$results[$hooks->callable_string($hook)] = $status;
				}
			}
		}
		return $results;
	}

	/**
	 *
	 * @return array
	 * @throws ParseException
	 */
	public function lastRun(): array {
		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::list_status');
			return [];
		}
		$results = [];
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state MetaInterface */
			$last_run = self::_last_cron_run($state);
			$results[$method] = $last_run;
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $settings['prefix'], $unit);
				$results[$method . '_' . $unit] = $last_unit_run;
			}
		}
		return $results;
	}

	/**
	 * Log an exception during a cron run
	 *
	 * @param Throwable $e
	 * @param string|array $cron_hooks
	 */
	private function _exception(Throwable $e, string|array $cron_hooks): void {
		$this->application->logger->error("Exception during {hooks}: {message}\n{backtrace}", [
			'hooks' => $cron_hooks, 'message' => $e->getMessage(), 'backtrace' => $e->getTraceAsString(),
			'exception' => $e,
		]);
		$this->application->hooks->call('exception', $e);
	}

	/**
	 * Do things which cron depends on
	 */
	private function _critical_cron_tasks(): void {
		// This may never run if our locks do not get cleaned
		Lock::cron_cluster_minute($this->application);
	}

	/**
	 * Internal function to run tasks
	 */
	private function _run(): void {
		$hooks = $this->application->hooks;
		$now = Timestamp::now();

		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Throwable $e) {
			$this->_exception($e, __CLASS__ . '::_cron_scopes');
			return;
		}
		$hook_callback = [
			$this, '_hook_callback',
		];
		$result_callback = [
			$this, '_result_callback',
		];
		$cron_arguments = [
			$this->application,
		];

		/**
		 * Collect locks and replace member 'lock' with Lock object once acquired.
		 *
		 * If can not acquire the lock, someone else is doing it, so don't bother running that scope.
		 */
		foreach ($scopes as $method => $settings) {
			$lock_name = $settings['lock'];
			$lock = Lock::instance($this->application, $lock_name);

			try {
				$scopes[$method]['lock'] = $lock->acquire();
			} catch (TimeoutExpired) {
				unset($scopes[$method]);
				$this->application->logger->warning('{method}: Unable to acquire lock {lock_name}, skipping scope {scope_method}', [
					'method' => __METHOD__, 'scope_method' => $method, 'lock_name' => $lock_name,
				]);
			}
		}

		/**
		 * Now only run scopes for which we acquired a lock. Exceptions are passed to a hook and logged.
		 */
		foreach ($scopes as $method => $settings) {
			$this->hook_source = '';
			$state = $settings['state'];
			/* @var $state MetaInterface */
			$last_run = self::_last_cron_run($state);
			$cron_hooks = $this->_cron_hooks($method);
			if ($now->difference($last_run, 'second')) {
				self::_cron_ran($state, null, null, $now);

				try {
					$this->hook_source = $method . ' second global hooks->all_call';
					$hooks->allCallArguments($cron_hooks, $cron_arguments, null, $hook_callback, $result_callback);
				} catch (Throwable $e) {
					$this->_exception($e, $cron_hooks);
				}

				try {
					$this->hook_source = $method . ' second module->all_hook';
					$this->application->modules->allHookArguments($method, [], null, $hook_callback, $result_callback);
				} catch (Throwable $e) {
					$this->_exception($e, "Module::$method");
				}
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $settings['prefix'], $unit);
				$this->application->logger->debug('Last ran {unit} {when}', [
					'unit' => $unit, 'when' => $last_unit_run->format(),
				]);
				if ($now->difference($last_unit_run, $unit) > 0) {
					self::_cron_ran($state, $settings['prefix'], $unit, $now);
					$unit_hooks = $method . "_$unit";

					try {
						$unit_hooks = ArrayTools::suffixValues($cron_hooks, "_$unit");
						$this->hook_source = $method . " $unit hooks->all_call";
						$hooks->allCallArguments($unit_hooks, $cron_arguments, null, $hook_callback, $result_callback);
					} catch (Throwable $e) {
						$this->_exception($e, $unit_hooks);
					}

					try {
						$this->hook_source = $method . " $unit modules->all_hook";
						$this->application->modules->allHookArguments($method . "_$unit", [], null, $hook_callback, $result_callback);
					} catch (Throwable $e) {
						$this->_exception($e, $unit_hooks);
					}
				}
			}
		}
		/**
		 * Release all of our locks at once
		 */
		foreach ($scopes as $method => $settings) {
			$lock = $settings['lock'];
			$lock->release();
			unset($scopes[$method]['lock']);
		}
	}

	/**
	 * Run cron from a JavaScript request
	 *
	 * @return string
	 * @throws Unimplemented
	 */
	public function run_js(): string {
		$run = $this->run();
		$js = [];
		$js[] = '(function (x) {';
		$js[] = 'var x = x.zesk || (x.zesk = {});';
		$js[] = 'x = x.settings || (x.settings = {});';
		$js[] = 'x.cron = x.cron || {};';
		$js[] = 'x.cron.locked = ' . ($run ? 'false' : 'true') . ';';
		if ($run) {
			$js[] = 'x.cron.methods = ' . json_encode($this->methods) . ';';
		}
		$js[] = '}(window));';
		return ArrayTools::joinSuffix($js, "\n");
	}

	/**
	 * Run cron
	 *
	 * @return array
	 * @throws Unimplemented
	 */
	public function run(): array {
		$modules = $this->application->modules;

		$this->methods = [];

		$result = $modules->allHookArguments('cron_before', [], true);
		if ($result === false) {
			$this->application->logger->error(__CLASS__ . '::cron_before return false');
			return $this->methods;
		}

		PHP::setFeature(PHP::FEATURE_TIME_LIMIT, $this->optionInt('time_limit'));
		$this->_critical_cron_tasks();
		$this->_run();

		$modules->allHookArguments('cron_after', [
			$this->methods,
		]);

		return $this->methods;
	}

	/**
	 * Update a page to enable it to run cron
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Semantics
	 */
	public function page_runner(Request $request, Response $response): void {
		$response->html()->javascript('/share/zesk/js/zesk.js', [
			'weight' => 'first', 'share' => true,
		]);
		$response->html()->javascript($this->page_runner_script(), [
			'async' => 'async', 'defer' => 'defer', 'nocache' => true,
		]);
	}

	/**
	 * Function to manage running cron hourly at a certain minute past the hour
	 *
	 * Since we don't know when cron will run, this runs cron as soon after the hour
	 * is hit (you can choose which minute of the hour to try to hit as well)
	 *
	 * You should call this from a cron call which is called at least once a minute:
	 *
	 * <code>
	 * public static function cron_minute() {
	 * if ($completion = $application->cronModule()->hourly($settings, __CLASS__, 15)) {
	 *       // Do our stuff
	 *     $completion(); // Saves our state
	 * }
	 *
	 * }
	 * </code>
	 *
	 * @param SettingsInterface $settings
	 * @param string $prefix
	 * @param int $minute_to_hit
	 *            Minute of the hour to hit
	 * @return Closure|null
	 * @throws ParameterException
	 */
	public function hourly(SettingsInterface $settings, string $prefix, int $minute_to_hit = 0): Closure|null {
		if (empty($prefix)) {
			throw new ParameterException('Prefix mus be non-empty to hourly');
		}
		/*
		 * $last_check_setting - last time this script checked if it should run
		 * $last_run_setting - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . '::hourly_last_run';
		$last_check_setting = $prefix . '::hourly_last_check';

		$hour_minute = Timestamp::now();
		$hour_minute->setMinute($minute_to_hit)->setSecond(0);

		$format = '{YYYY}-{MM}-{DD}-{hh}';
		return $this->_manageRepeatState($settings, $last_run_setting, $last_check_setting, $format, 'minute', $minute_to_hit, $hour_minute);
	}

	/**
	 * Function to manage running cron daily at a certain hour of the day.
	 *
	 * Since we don't know when cron will run, this runs cron as soon after the hour
	 * is hit
	 *
	 * You should call this from a cron call which is called at least once an hour:
	 *
	 * <code>
	 * public static function cron_hour() {
	 * if (!cron::daily_hour_of_day(__CLASS__, 10)) { // Run at 10 AM
	 * return;
	 * }
	 * // Do our stuff
	 * }
	 * </code>
	 *
	 * @param SettingsInterface $settings
	 * @param string $prefix
	 * @param int $hour_to_hit
	 *            Hour of the day to hit, 0 ... 23
	 * @return Closure|null
	 * @throws ParameterException
	 */
	public function dailyHourOfDay(SettingsInterface $settings, string $prefix, int $hour_to_hit): Closure|null {
		if (empty($prefix)) {
			throw new ParameterException('Prefix mus be non-empty to daily_hour_of_day');
		}
		/*
		 * last_check - last time this script checked if it should run
		 * last_run - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . 'daily_last_run';
		$last_check_setting = $prefix . 'daily_last_check';

		$today_hour = Timestamp::now();
		$today_hour->setHour($hour_to_hit)->setMinute(0)->setSecond(0);

		$format = '{YYYY}-{MM}-{DD}';
		return self::_manageRepeatState($settings, $last_run_setting, $last_check_setting, $format, 'hour', $hour_to_hit, $today_hour);
	}

	/**
	 * Manages repeating state
	 *
	 * @param SettingsInterface $settings
	 * @param string $lastRunSetting
	 * @param string $lastCheckSetting
	 * @param string $settingFormat
	 * @param string $targetUnit
	 * @param int $targetValue
	 * @param Timestamp $targetTimestamp
	 * @return Closure|null
	 * @throws ParseException
	 */
	private function _manageRepeatState(SettingsInterface $settings, string $lastRunSetting, string $lastCheckSetting, string $settingFormat, string $targetUnit, int $targetValue, Timestamp $targetTimestamp): Closure|null {
		$now = Timestamp::now();

		$last_run = $settings->get($lastRunSetting);
		$last_run = $last_run ? Timestamp::factory($last_run) : null;

		$last_check = $settings->get($lastCheckSetting);
		$last_check = $last_check ? Timestamp::factory($last_check) : null;
		$settings->set($lastCheckSetting, $now->format());

		$lastRunSettingValue = $now->format();

		if ($last_run !== null) {
			if ($last_run->format($settingFormat) === $now->format($settingFormat)) {
				return null;
			}
		}
		if ($last_check === null) {
			$nowUnitValue = $now->$targetUnit();
			if ($nowUnitValue === $targetValue) {
				return (function () use ($settings, $lastRunSetting, $lastRunSettingValue) {
					$settings->set($lastRunSetting, $lastRunSettingValue);
					return true;
				})(...);
			}
		} else {
			if ($targetTimestamp->before($now) && $targetTimestamp->after($last_check)) {
				return (function () use ($settings, $lastRunSetting, $lastRunSettingValue) {
					$settings->set($lastRunSetting, $lastRunSettingValue);
					return true;
				})(...);
			}
		}
		return null;
	}

	/**
	 *
	 * @return array
	 */
	protected function hook_system_panel(): array {
		return [
			'system/panel/cron' => [
				'title' => 'Cron Tasks',
				'moduleClass' => __CLASS__,
			],
		];
	}
}
