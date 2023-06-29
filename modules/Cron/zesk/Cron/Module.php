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
use zesk\Cron\Attributes\Cron;
use zesk\Doctrine\Lock;
use zesk\Doctrine\Server;
use zesk\Doctrine\Settings;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;
use zesk\Exception\UnimplementedException;
use zesk\Hookable;
use zesk\HookMethod;
use zesk\Application\Hooks;
use zesk\Interface\MetaInterface;
use zesk\Interface\SettingsInterface;
use zesk\Module as BaseModule;
use zesk\PHP;
use zesk\Request;
use zesk\Response;
use zesk\Router;
use zesk\Temporal;
use zesk\Timestamp;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule
{
	/**
	 *
	 */
	public const HOOK_BEFORE = 'cron::before';

	/**
	 *
	 */
	public const HOOK_AFTER = 'cron::after';

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
		Temporal::UNIT_SECOND, Temporal::UNIT_MINUTE, Temporal::UNIT_HOUR, Temporal::UNIT_DAY, Temporal::UNIT_WEEK,
		Temporal::UNIT_MONTH, Temporal::UNIT_YEAR,
	];

	/**
	 * State variable which changes during _run to determine where we're executing
	 *
	 * @var string
	 */
	protected string $hook_source = '';

	/**
	 * @return string
	 */
	public function lockName(): string
	{
		return $this->lock_name;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws ParameterException
	 */
	public function setLockName(string $name): self
	{
		if (empty($name)) {
			throw new ParameterException('Blank lock name for {method} {name}', [
				'method' => __METHOD__, 'name' => $name,
			]);
		}
		$this->lock_name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	private function page_runner_script(): string
	{
		return $this->optionString('runnerScript', 'js/cron.js');
	}

	/**
	 * Hook Module::routes
	 * Add desired routes to Router $router
	 *
	 * @param Router $router
	 *            Current router
	 *
	 * @return void
	 * @throws ClassNotFound
	 * @throws SemanticsException
	 * @see self::hook_routes()
	 */
	#[HookMethod(handles: Application::HOOK_ROUTES, argumentTypes: [Router::class])]
	public function hook_routes(Router $router): void
	{
		$router->addRoute($this->page_runner_script(), [
			'method' => [
				$this, 'run_js',
			], 'content type' => 'text/javascript',
		]);
	}

	/**
	 * Hook Module::configured
	 * @see self::hook_configured()
	 */
	#[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
	public function hook_configured(): void
	{
		if ($this->optionBool('page_runner')) {
			$this->application->hooks->registerHook(Response\HTML::HOOK_HTML_OPEN, $this->page_runner(...));
		}
	}

	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @param string $prefix
	 * @param string $unit
	 * @return string
	 */
	private static function _lastCronVariableName(string $prefix, string $unit): string
	{
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
	private static function _lastCronRun(MetaInterface $object, string $prefix = '', string $unit = ''): Timestamp
	{
		return Timestamp::factory($object->meta(self::_lastCronVariableName($prefix, $unit)));
	}

	private static function _cronJustRan(MetaInterface $object, $prefix, $unit, Timestamp $when): void
	{
		$object->setMeta(self::_lastCronVariableName($prefix, $unit), $when->unixTimestamp());
	}

	/**
	 * @param MetaInterface $object
	 * @param string $prefix
	 * @param string $unit
	 * @return void
	 */
	private static function _cronReset(MetaInterface $object, string $prefix = '', string $unit = null): void
	{
		$name = self::_lastCronVariableName($prefix, $unit);
		if ($object instanceof Server) {
			$object->deleteAllMeta($name);
		} else {
			$object->deleteMeta($name);
		}
	}

	/**
	 * This can be slow and memory intensive on large codebases.
	 *
	 * Loads all PHP files in the application root and then returns all #[Cron]`
	 *
	 * @return array
	 */
	private function _collectCrons(): array
	{
		return Hookable::staticAttributeMethods($this, Cron::class);
	}

	/**
	 * Retrieve the objects which store our state
	 *
	 * @return array
	 * @throws ClassNotFound
	 */
	private function _cronScopes(): array
	{
		if (count($this->scopes)) {
			return $this->scopes;
		}
		$server = Server::singleton($this->application);
		$settings = $this->application->settings();

		return $this->scopes = [
			Cron::SCOPE_SERVER => [
				'state' => $server, 'prefix' => '', 'lock' => 'cron-server-' . $server->id,
			], Cron::SCOPE_APPLICATION => [
				'state' => $settings, 'prefix' => '', 'lock' => 'cron-application-' . $this->application->id(),
			], Cron::SCOPE_CLUSTER => [
				'state' => $settings, 'prefix' => '_cluster', 'lock' => 'cron-cluster',
			],
		];
	}

	/**
	 * @return $this
	 * @throws ClassNotFound
	 */
	public function reset(): self
	{
		$scopes = $this->_cronScopes();
		foreach ($scopes as $settings) {
			$state = $settings['state'];
			/* @var $state MetaInterface */
			self::_cronReset($state);
			foreach (self::$intervals as $unit) {
				self::_cronReset($state, $settings['prefix'], $unit);
			}
		}
		return $this;
	}

	/**
	 *
	 */
	public function listStatus(): array
	{
		$allCronMethods = $this->_collectCrons();
		return array_keys($allCronMethods);
	}

	/**
	 *
	 * @return array
	 * @throws ClassNotFound
	 */
	public function lastRun(): array
	{
		$scopes = $this->_cronScopes();
		$results = [];
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state MetaInterface */
			$last_run = self::_lastCronRun($state);
			$results[$method] = $last_run;
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_lastCronRun($state, $settings['prefix'], $unit);
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
	private function _exception(Throwable $e, string|array $cron_hooks): void
	{
		$this->application->error("Exception during {hooks}: {message}\n{backtrace}", [
			'hooks' => $cron_hooks, 'message' => $e->getMessage(), 'backtrace' => $e->getTraceAsString(),
			'exception' => $e,
		]);
		$this->application->invokeHooks(Application::HOOK_EXCEPTION, [$this->application, $e]);
	}

	/**
	 * Do things which cron depends on
	 */
	private function _critical_cron_tasks(): void
	{
		// This may never run if our locks do not get cleaned
		Lock::deleteUnusedLocks($this->application);
	}

	/**
	 * Internal function to run tasks
	 * @throws ClassNotFound
	 */
	private function _run(): void
	{
		$now = Timestamp::now();

		$app = $this->application;
		$scopes = $this->_cronScopes();

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
				$this->application->warning('{method}: Unable to acquire lock {lock_name}, skipping scope {scope_method}', [
					'method' => __METHOD__, 'scope_method' => $method, 'lock_name' => $lock_name,
				]);
			}
		}

		$allCronMethods = $this->_collectCrons();
		/**
		 * Now only run scopes for which we acquired a lock. Exceptions are passed to a hook and logged.
		 */
		foreach ($scopes as $scope => $settings) {
			$this->hook_source = '';
			$state = $settings['state'];
			/* @var $state MetaInterface */
			$last_run = self::_lastCronRun($state);
			$scopeCronMethods = array_filter($allCronMethods, fn (Cron $cronAttribute) => $cronAttribute->scope === $scope);
			if ($now->difference($last_run)) {
				self::_cronJustRan($state, null, null, $now);
				if (count($scopeCronMethods) !== 0) {
					$unitCronMethods = array_filter($scopeCronMethods, fn (Cron $cronAttribute) => $cronAttribute->schedule === Temporal::UNIT_SECOND);

					foreach ($unitCronMethods as $method => $cronAttribute) {
						/* @var Cron $cronAttribute */
						try {
							$app->info('Running {method}', ['method' => $method]);
							$cronAttribute->run(null, [$app]);
						} catch (Throwable $e) {
							$this->_exception($e, $cronAttribute->name);
						}
					}
				}
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_lastCronRun($state, $settings['prefix'], $unit);
				$this->application->debug('Last ran {unit} {when}', [
					'unit' => $unit, 'when' => $last_unit_run->format(),
				]);
				if ($now->difference($last_unit_run, $unit) > 0) {
					self::_cronJustRan($state, $settings['prefix'], $unit, $now);
					if (count($scopeCronMethods) !== 0) {
						$unitCronMethods = array_filter($scopeCronMethods, fn (Cron $cronAttribute) => $cronAttribute->schedule === $unit);

						foreach ($unitCronMethods as $method => $cronAttribute) {
							/* @var Cron $cronAttribute */
							try {
								$app->info('Running {method}', ['method' => $method]);
								$cronAttribute->run(null, [$app]);
							} catch (Throwable $e) {
								$this->_exception($e);
							}
						}
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
	 * @throws UnimplementedException
	 */
	public function run_js(): string
	{
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
	 * @throws UnimplementedException
	 */
	public function run(): array
	{
		$this->methods = [];

		foreach (Hookable::staticHooksFor($this, self::HOOK_BEFORE, true) as $hook) {
			$hook->run(null, []);
		}

		PHP::setFeature(PHP::FEATURE_TIME_LIMIT, $this->optionInt('time_limit'));
		$this->_critical_cron_tasks();
		$this->_run();

		foreach (Hookable::staticHooksFor($this, self::HOOK_AFTER, true) as $hook) {
			$hook->run(null, []);
		}

		return $this->methods;
	}

	/**
	 * Update a page to enable it to run cron
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws SemanticsException
	 */
	#[HookMethod(Response\HTML::HOOK_HTML_OPEN)]
	public function page_runner(Request $request, Response $response): void
	{
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
	public function hourly(SettingsInterface $settings, string $prefix, int $minute_to_hit = 0): Closure|null
	{
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
	public function dailyHourOfDay(SettingsInterface $settings, string $prefix, int $hour_to_hit): Closure|null
	{
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
	private function _manageRepeatState(SettingsInterface $settings, string $lastRunSetting, string $lastCheckSetting, string $settingFormat, string $targetUnit, int $targetValue, Timestamp $targetTimestamp): Closure|null
	{
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
}
