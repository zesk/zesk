<?php
declare(strict_types=1);

/**
 * Handles running of intermittent tasks
 *
 * @documentation docs/cron.md
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Cron;

use zesk\Exception_Directory_Create;
use zesk\Exception_Directory_Permission;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Request;
use zesk\Lock;
use zesk\Application;
use zesk\Hookable;
use zesk\System;
use zesk\Router;
use zesk\Response;
use zesk\Interface_Data;
use zesk\Interface_Settings;
use zesk\Timestamp;
use zesk\ArrayTools;
use zesk\Exception;
use zesk\Exception_Parameter;
use zesk\Server;
use zesk\PHP;
use zesk\File;
use zesk\ORM\ORMBase;
use zesk\Configure\Engine;
use zesk\Module as BaseModule;

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
	protected $lock_name = __CLASS__;

	/**
	 * Debugging - place to accumulate called methods so we
	 * can audit which methods are called by cron
	 *
	 * @var array
	 */
	private $methods = null;

	/**
	 * Cached value for scopes
	 *
	 * @var array
	 */
	private $scopes = null;

	/**
	 *
	 * @var integer
	 */
	private $start = null;

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
	public static $intervals = [
		'minute', 'hour', 'day', 'week', 'month', 'year',
	];

	/**
	 * State variable which changes during _run to determine where we're executing
	 *
	 * @var string
	 */
	protected $hook_source = null;

	/**
	 * Set up our module
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->hooks->add(Engine::class . '::command_crontab', [
			$this, 'command_crontab',
		]);
	}

	/**
	 * command crontab help
	 * @param string $command_name
	 * @return string[]
	 */
	private function command_crontab_help($command_name) {
		return [
			'example' => 'crontab file [flags]', 'arguments' => [
				'file' => 'File to install as crontab', 'map' => 'list of flags for file, currently suports "map" flag',
			], 'description' => 'Install crontab for current user',
		];
	}

	/**
	 *
	 * @param Engine $command
	 * @param array $arguments
	 * @param string $command_name
	 * @return array
	 * @throws Exception_File_NotFound
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_Permission
	 */
	public function command_crontab(Engine $command, array $arguments, $command_name) {
		$file = array_shift($arguments);
		if ($file === '--help') {
			return $this->command_crontab_help($command_name);
		}
		$file = $this->application->paths->expand($file);
		$flags = $command->parse_file_flags($arguments);
		$map = toBool($flags['map'] ?? null);
		File::depends($file);
		$temp_path = $this->application->paths->temporary();
		$mapped_target = null;
		$target = $file;
		if ($map) {
			$mapped_target = File::temporary($temp_path);
			file_put_contents($mapped_target, $command->map(trim(file_get_contents($file)) . "\n"));
			$target = $mapped_target;
			$command->verboseLog('Mapping crontab file to {mapped_target}', [
				'mapped_target' => $mapped_target,
			]);
		}
		$compare = File::temporary($temp_path);

		try {
			$command->exec("crontab -l > $compare");
		} catch (\Exception $e) {
			file_put_contents($compare, '');
		}

		try {
			$result = $command->file_update_helper($target, $compare, 'crontab');
			if ($result === true) {
				$command->exec('crontab {target}', [
					'target' => $target,
				]);
			}
		} catch (Exception $e) {
			$command->error('Installing crontab failed {exceptionCode} {message}', Exception::exceptionVariables($e));
			$result = false;
		}
		if ($mapped_target) {
			File::unlink($mapped_target);
		}
		File::unlink($compare);
		return $result;
	}

	/**
	 * Run this before each hook
	 *
	 * @param string $method
	 *            Method will be called
	 * @param array $arguments
	 *            Arguments being passed to the method
	 */
	public function _hook_callback($method, $arguments): void {
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
	 * @param mixed $method
	 * @param mixed $previous_result
	 * @param mixed $new_result
	 * @return mixed
	 */
	public function _result_callback($method, $previous_result, $new_result, array $arguments) {
		$elapsed = microtime(true) - $this->start;
		if ($elapsed > ($elapsed_warn = $this->optionFloat('elapsed_warn', 2))) {
			$locale = $this->application->locale;
			$this->application->logger->warning('Cron: {method} took {elapsed} {seconds} (exceeded {elapsed_warn} {elapsed_warn_seconds})', [
				'elapsed' => sprintf('%.3f', $elapsed), 'seconds' => $locale->plural('second', $elapsed),
				'elapsed_warn' => sprintf('%.3f', $elapsed_warn),
				'elapsed_warn_seconds' => $locale->plural('second', int($elapsed_warn)),
				'method' => $this->application->hooks->callable_string($method),
			]);
		}
		return Hookable::combineHookResults($previous_result, $new_result, $arguments);
	}

	public function lock_name($set = null) {
		if ($set !== null) {
			$name = strval($set);
			if (empty($name)) {
				throw new Exception_Parameter('Blank lock name for {method} {set}', [
					'method' => __METHOD__, 'set' => $set,
				]);
			}
			$this->lock_name = $name;
			return $this;
		}
		return $this->lock_name;
	}

	/**
	 * Hook Module::settings
	 *
	 * @return array
	 */
	public function hook_settings() {
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

	private function page_runner_script() {
		return $this->option('page_runner_script', 'js/cron.js');
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
	 * @return string
	 */
	private static function _cron_variable_prefix($suffix = '', $system = false) {
		return __CLASS__ . '::last' . ($suffix ? "_$suffix" : '') . '::' . System::uname();
	}

	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @return string
	 */
	private static function _last_cron_variable($prefix, $unit) {
		return __CLASS__ . '::last' . $prefix . ($unit ? "_$unit" : '');
	}

	/**
	 * Time that cron was last run
	 *
	 * @return Timestamp
	 */
	private static function _last_cron_run(Interface_Data $object, $prefix = '', $unit = null) {
		return Timestamp::factory($object->data(self::_last_cron_variable($prefix, $unit)));
	}

	private static function _cron_ran(Interface_Data $object, $prefix, $unit, Timestamp $when) {
		return $object->data(self::_last_cron_variable($prefix, $unit), $when->unixTimestamp());
	}

	private static function _cron_reset(Interface_Data $object, $prefix = '', $unit = null): void {
		$name = self::_last_cron_variable($prefix, $unit);
		if ($object instanceof Server) {
			/* @var $object Server */
			$object->deleteAllData($name);
		} else {
			$object->deleteData($name, null);
		}
	}

	private function _cron_hooks($method) {
		$classes = to_list($this->application->configuration->getPath(__CLASS__ . '::classes', [
			Application::class, ORMBase::class,
		]));
		return ArrayTools::suffixValues($classes, "::$method");
	}

	/**
	 * Retrieve the objects which store our state
	 *
	 * @return array:Interface_Data
	 */
	private function _cron_scopes(Application $application) {
		if (is_array($this->scopes)) {
			return $this->scopes;
		}
		/* @var $server Server */
		$server = Server::singleton($application);
		/* @var $settings Settings */
		$settings = $application->callHook('settings');
		if (!$settings instanceof Interface_Settings) {
		}

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

	public function reset() {
		$scopes = $this->_cron_scopes($this->application);
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			self::_cron_reset($state);
			foreach (self::$intervals as $unit) {
				self::_cron_reset($state, $settings['prefix'], $unit);
			}
		}
		return true;
	}

	/**
	 *
	 * @return multitype:boolean
	 */
	public function list_status() {
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
			/* @var $state Interface_Data */
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
				$unit_hooks = ArrayTools::suffixValues($cron_hooks, "_$unit");
				$all_hooks = $this->application->modules->listAllHooks($method . "_${unit}");
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
	 * @return Timestamp[string]
	 */
	public function last_run() {
		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::list_status');
			return [];
		}
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$last_run = self::_last_cron_run($state);
			$results[$method] = $last_run;
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $settings['prefix'], $unit);
				$results["${method}_${unit}"] = $last_unit_run;
			}
		}
		return $results;
	}

	/**
	 * Log an exception during a cron run
	 *
	 * @param Exception $e
	 * @param unknown $cron_hooks
	 */
	private function _exception(Exception $e, $cron_hooks): void {
		$this->application->logger->error("Exception during {hooks}: {message}\n{backtrace}", [
			'hooks' => $cron_hooks, 'message' => $e->getMessage(), 'backtrace' => $e->getTraceAsString(),
			'exception' => $e,
		]);
		$this->application->hooks->call('exception', $e);
	}

	/**
	 *
	 */
	private function _critical_crons(): void {
		// This may never run if our locks do not get cleaned
		Lock::cron_cluster_minute($this->application);
	}

	/**
	 * Internal function to run tasks
	 */
	private function _run() {
		$hooks = $this->application->hooks;
		$locale = $this->application->locale;
		$now = Timestamp::now();
		$results = [];

		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::_cron_scopes');
			return false;
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
		$this->timer = null;
		/**
		 * Collect locks and replace member 'lock' with Lock object once acquired.
		 *
		 * If can not acquire the lock, someone else is doing it, so don't bother running that scope.
		 */
		foreach ($scopes as $method => $settings) {
			$lock_name = $settings['lock'];
			$lock = Lock::instance($this->application, $lock_name);
			if ($lock->acquire() === null) {
				unset($scopes[$method]);
				$this->application->logger->warning('{method}: Unable to acquire lock {lock_name}, skipping scope {scope_method}', [
					'method' => __METHOD__, 'scope_method' => $method, 'lock_name' => $lock_name,
				]);
			} else {
				$scopes[$method]['lock'] = $lock;
			}
		}

		/**
		 * Now only run scopes for which we acquired a lock. Exceptions are passed to a hook and logged.
		 */
		foreach ($scopes as $method => $settings) {
			$this->hook_source = null;
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$last_run = self::_last_cron_run($state);
			$cron_hooks = $this->_cron_hooks($method);
			if ($now->difference($last_run, 'second')) {
				self::_cron_ran($state, null, null, $now);

				try {
					$this->hook_source = $method . ' second global hooks->all_call';
					$hooks->allCallArguments($cron_hooks, $cron_arguments, null, $hook_callback, $result_callback);
				} catch (Exception $e) {
					$this->_exception($e, $cron_hooks);
				}

				try {
					$this->hook_source = $method . ' second module->all_hook';
					$this->application->modules->allHookArguments($method, [], null, $hook_callback, $result_callback);
				} catch (Exception $e) {
					$this->_exception($e, "Module::$method");
				}
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $settings['prefix'], $unit);
				$this->application->logger->debug('Last ran {unit} {when}', [
					'unit' => $unit, 'when' => $last_unit_run->format($locale),
				]);
				if ($now->difference($last_unit_run, $unit) > 0) {
					self::_cron_ran($state, $settings['prefix'], $unit, $now);
					$unit_hooks = $method . "_$unit";

					try {
						$unit_hooks = ArrayTools::suffixValues($cron_hooks, "_$unit");
						$this->hook_source = $method . " $unit hooks->all_call";
						$hooks->allCallArguments($unit_hooks, $cron_arguments, null, $hook_callback, $result_callback);
					} catch (Exception $e) {
						$this->_exception($e, $unit_hooks);
					}

					try {
						$this->hook_source = $method . " $unit modules->all_hook";
						$this->application->modules->allHookArguments($method . "_$unit", [], null, $hook_callback, $result_callback);
					} catch (Exception $e) {
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
		}
	}

	/**
	 * Run cron from a JavaScript request
	 *
	 * @return string
	 */
	public function run_js() {
		$run = $this->run();
		$js = [];
		$js[] = '(function (x) {';
		$js[] = 'x.cron = x.cron || {};';
		$js[] = 'x.cron.locked = ' . ($run ? 'false' : 'true') . ';';
		if ($run) {
			$js[] = 'x.cron.methods = ' . json_encode($this->methods) . ';';
		}
		$js[] = '}(window.zesk.settings));';
		return ArrayTools::joinSuffix($js, "\n");
	}

	/**
	 * Run cron
	 *
	 * @return boolean
	 */
	public function run() {
		$modules = $this->application->modules;

		$this->methods = [];

		$result = $modules->allHookArguments('cron_before', [], true);
		if ($result === false) {
			$this->application->logger->error(__CLASS__ . '::cron_before return false');
			return $this->methods;
		}

		PHP::feature('time_limit', $this->optionInt('time_limit', 0));
		$this->_critical_crons();
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
	 */
	public function page_runner(Request $request, Response $response): void {
		$response->javascript('/share/zesk/js/zesk.js', [
			'weight' => 'first', 'share' => true,
		]);
		$response->javascript($this->page_runner_script(), null, [
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
	 * if (!Module_Cron::hourly(__CLASS__, 15)) {
	 * return;
	 * }
	 * // Do our stuff
	 * }
	 * </code>
	 *
	 * @param string $prefix
	 * @param int $minute_to_hit
	 *            Minute of the hour to hit
	 */
	public static function hourly(Interface_Settings $settings, $prefix, $minute_to_hit = 0) {
		if (empty($prefix)) {
			throw new Exception_Parameter('Prefix mus be non-empty to hourly');
		}
		$locale = $settings->application->locale;
		/*
		 * last_check - last time this script checked if it should run
		 * last_run - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . 'hourly_last_run';
		$last_check_setting = $prefix . 'hourly_last_check';

		$now = Timestamp::now();
		$hour_minute = clone $now;
		$hour_minute->minute($minute_to_hit)->second(0);

		$last_run = $settings->get($last_run_setting);
		if ($last_run) {
			$last_run = Timestamp::factory($last_run);
		}
		$last_check = $settings->get($last_check_setting);
		if ($last_check) {
			$last_check = Timestamp::factory($last_check);
		}
		$now_minute = $now->minute();
		$settings->set($last_check_setting, $now->format($locale));
		$format = '{YYYY}-{MM}-{DD}-{hh}';
		if ($last_run !== null) {
			if ($last_run->format($locale, $format) === $now->format($locale, $format)) {
				return false;
			}
		}
		if ($last_check === null) {
			if ($now_minute === $minute_to_hit) {
				$settings->set($last_run_setting, $now->format($locale));
				return true;
			}
		} else {
			if ($hour_minute->before($now) && $hour_minute->after($last_check)) {
				$settings->set($last_run_setting, $now->format($locale));
				return true;
			}
		}
		return false;
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
	 * @param string $prefix
	 * @param int $hour_to_hit
	 *            Hour of the day to hit, 0 ... 23
	 */
	public static function daily_hour_of_day(Interface_Settings $settings, $prefix, $hour_to_hit) {
		if (empty($prefix)) {
			throw new Exception_Parameter('Prefix mus be non-empty to daily_hour_of_day');
		}
		$hour_to_hit = intval($hour_to_hit);
		/*
		 * last_check - last time this script checked if it should run
		 * last_run - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . 'daily_last_run';
		$last_check_setting = $prefix . 'daily_last_check';

		$locale = $settings->application->locale;
		$now = Timestamp::factory('now');
		$today_hour = clone $now;
		$today_hour->hour($hour_to_hit)->minute(0)->second(0);
		$last_run = $settings->get($last_run_setting);
		if ($last_run) {
			$last_run = Timestamp::factory($last_run);
		}
		$last_check = $settings->get($last_check_setting);
		if ($last_check) {
			$last_check = Timestamp::factory($last_check);
		}
		$now_hour = $now->hour();
		$settings->set($last_check_setting, $now->format($locale));
		$format = '{YYYY}-{MM}-{DD}';
		if ($last_run !== null) {
			if ($last_run->format($locale, $format) === $now->format($locale, $format)) {
				return false;
			}
		}
		if ($last_check === null) {
			if ($now_hour === $hour_to_hit) {
				$settings->set($last_run_setting, $now->format($locale));
				return true;
			}
		} else {
			if ($today_hour->before($now) && $today_hour->after($last_check)) {
				$settings->set($last_run_setting, $now->format($locale));
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * @param Template $template
	 * @return string[][]
	 */
	protected function hook_system_panel() {
		return [
			'system/panel/cron' => [
				'title' => 'Cron Tasks', 'module_class' => __CLASS__,
			],
		];
	}

	protected function _fix_settings(Settings $settings): void {
		// Changed class structure on 2016-11-23
		$settings->prefix_updated('Module_Cron::', __CLASS__ . '::');
		$settings->prefix_updated('zesk\\Module_Cron::', __CLASS__ . '::');
		$nrows = $settings->query_delete()->addWhere('name|LIKE', [
			'Module_Cron::%', 'cron::%',
		])->execute()->affectedRows();
		if ($nrows > 0) {
			$this->application->logger->notice('{class}: Deleted {nrows} settings to using old prefixes', [
				'nrows' => $nrows, 'class' => __CLASS__,
			]);
		}
	}

	/**
	 *
	 */
	protected function hook_schema_updated(): void {
		$settings = $this->application->ormRegistry(Settings::class);
		$this->_fix_settings($settings);
	}
}
