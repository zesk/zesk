<?php

/**
 * Handles running of intermittent tasks
 *
 * @documentation docs/cron.md
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/cron/classes/module/cron.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
namespace zesk\Cron;

use zesk\Request;
use zesk\Lock;
use zesk\Application;
use zesk\Hookable;
use zesk\Locale;
use zesk\System;
use zesk\Router;
use zesk\Response_Text_HTML;
use zesk\Interface_Data;
use zesk\Interface_Settings;
use zesk\Timestamp;
use zesk\arr;
use zesk\Exception;
use zesk\Exception_Parameter;
use zesk\Settings;
use zesk\Server;
use zesk\PHP;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	
	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	protected $object_classes = array(
		"zesk\\Server",
		"zesk\\Lock",
		"zesk\\Settings"
	);
	
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
	 * @var double
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
	static $intervals = array(
		"minute",
		"hour",
		"day",
		"week",
		"month",
		"year"
	);
	
	/**
	 * Run this before each hook
	 *
	 * @param string $method
	 *        	Method will be called
	 * @param array $arguments
	 *        	Arguments being passed to the method
	 */
	public function _hook_callback($method, $arguments) {
		$method_string = $this->application->hooks->callable_string($method);
		if (is_array($method) && ($method[0] instanceof Module)) {
			$name = $method[0]->codename();
			$message = "\$application->modules->object(\"$name\")->{1}()";
			$message_args = $method;
		} else {
			$message = "{method_string}(\$application)";
			$message_args = compact("method_string");
		}
		$this->application->logger->notice("zesk eval '$message'", $message_args);
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
	 * @param numeric|string $return_hint
	 * @return mixed
	 */
	public function _result_callback($method, $previous_result, $new_result, array $arguments, $return_hint) {
		$elapsed = microtime(true) - $this->start;
		if ($elapsed > ($elapsed_warn = $this->option_double("elapsed_warn", 2))) {
			$this->application->logger->warning("Cron: {method} took {elapsed} {seconds} (exceeded {elapsed_warn} {elapsed_warn_seconds})", array(
				"elapsed" => sprintf("%.3f", $elapsed),
				"seconds" => Locale::plural("second", $elapsed),
				"elapsed_warn" => sprintf("%.3f", $elapsed_warn),
				"elapsed_warn_seconds" => Locale::plural("second", $elapsed_warn),
				"method" => $this->application->hooks->callable_string($method)
			));
		}
		return Hookable::combine_hook_results($previous_result, $new_result, $arguments, $return_hint);
	}
	public function lock_name($set = null) {
		if ($set !== null) {
			$name = strval($set);
			if (empty($name)) {
				throw new Exception_Parameter("Blank lock name for {method} {set}", array(
					"method" => __METHOD__,
					"set" => $set
				));
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
		return array(
			__CLASS__ . '::page_runner' => array(
				'type' => 'boolean',
				'default' => false,
				'name' => 'Cron task runs asynchronously via web page requests.',
				'description' => "Whether to run poor-man style cron tasks from page requests."
			),
			__CLASS__ . '::time_limit' => array(
				'type' => 'integer',
				'default' => 0,
				'name' => 'Time limit for cron tasks to complete',
				'description' => "Set this to a value if cron tasks run too long, and need to be terminated after a certain number of seconds (and that\'s ok)... Uses php's time_limit ini setting to terminate the cron task after a certain amount of time."
			),
			__CLASS__ . '::page_runner_script' => array(
				'type' => "uri",
				"default" => "js/cron.js",
				"name" => "Path of script to run cron on each page. ",
				"description" => "Modify this if it conflicts with one of your own scripts. Note this page should never be cached. It should be a unique URL."
			)
		);
	}
	private function page_runner_script() {
		return $this->option('page_runner_script', 'js/cron.js');
	}
	/**
	 * Hook Module::routes
	 * Add desired routes to Router $router
	 *
	 * @param Router $router
	 *        	Current router
	 *
	 * @return void
	 */
	public function hook_routes(Router $router) {
		$router->add_route($this->page_runner_script(), array(
			"method" => array(
				$this,
				"run_js"
			),
			"content type" => "text/javascript"
		));
	}
	
	/**
	 * Hook Module::configured
	 */
	public function hook_configured() {
		if ($this->option_bool('page_runner')) {
			$this->application->hooks->add('response/html.tpl', array(
				$this,
				'page_runner'
			));
		}
	}
	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @return string
	 */
	private static function _cron_variable_prefix($suffix = "", $system = false) {
		return __CLASS__ . "::last" . ($suffix ? "_$suffix" : "") . "::" . System::uname();
	}
	/**
	 * Config setting for when cron last run (unit)
	 *
	 * @return string
	 */
	private static function _last_cron_variable($unit) {
		return __CLASS__ . "::last" . ($unit ? "_$unit" : "");
	}
	/**
	 * Time that cron was last run
	 *
	 * @return Timestamp
	 */
	private static function _last_cron_run(Interface_Data $object, $unit = null) {
		return Timestamp::factory($object->data(self::_last_cron_variable($unit)));
	}
	private static function _cron_ran(Interface_Data $object, $unit = null, Timestamp $when) {
		return $object->data(self::_last_cron_variable($unit), $when->unix_timestamp());
	}
	private function _cron_hooks($method) {
		$classes = to_list($this->application->configuration->path_get(__CLASS__ . '::classes', 'zesk\Application;zesk\Object'));
		return arr::suffix($classes, "::$method");
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
		/* @var $server Server*/
		$server = Server::singleton($application);
		/* @var $settings Settings */
		$settings = Settings::singleton($application);
		
		return $this->scopes = array(
			"cron" => array(
				"state" => $server,
				"lock" => "cron-server-" . $server->id
			),
			"cron_cluster" => array(
				"state" => $settings,
				"lock" => "cron-cluster"
			)
		);
	}
	/**
	 *
	 * @return multitype:boolean
	 */
	public function list_status() {
		$hooks = $this->application->hooks;
		$now = Timestamp::now();
		$results = array();
		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::list_status');
			return array();
		}
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$last_run = self::_last_cron_run($state);
			
			$status = $now->difference($last_run, "second") > 0;
			$cron_hooks = $this->_cron_hooks($method);
			$all_hooks = $hooks->find_all($cron_hooks);
			$all_hooks = array_merge($all_hooks, $this->application->modules->all_hook_list($method));
			foreach ($all_hooks as $hook) {
				$results[$hooks->callable_string($hook)] = $status;
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $unit);
				$status = $now->difference($last_unit_run, $unit) > 0;
				$unit_hooks = arr::suffix($cron_hooks, "_$unit");
				$all_hooks = $this->application->modules->all_hook_list($method . "_${unit}");
				$all_hooks = array_merge($all_hooks, $hooks->find_all($unit_hooks));
				foreach ($all_hooks as $hook) {
					$results[$hooks->callable_string($hook)] = $status;
				}
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
	private function _exception(Exception $e, $cron_hooks) {
		$this->application->logger->error("Exception during {hooks}: {message}\n{backtrace}", array(
			"hooks" => $cron_hooks,
			"message" => $e->getMessage(),
			"backtrace" => $e->getTraceAsString(),
			"exception" => $e
		));
		zesk()->hooks->call("exception", $e);
	}
	/**
	 * Internal function to run tasks
	 */
	private function _run() {
		$hooks = $this->application->hooks;
		$now = Timestamp::now();
		$results = array();
		
		try {
			$scopes = $this->_cron_scopes($this->application);
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . "::_cron_scopes");
			return false;
		}
		$hook_callback = array(
			$this,
			"_hook_callback"
		);
		$result_callback = array(
			$this,
			"_result_callback"
		);
		$cron_arguments = array(
			$this->application
		);
		$this->timer = null;
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$lock_name = $settings['lock'];
			$lock = Lock::instance($this->application, $lock_name);
			if ($lock->acquire() === null) {
				continue;
			}
			$last_run = self::_last_cron_run($state);
			$cron_hooks = $this->_cron_hooks($method);
			if ($now->difference($last_run, "second")) {
				self::_cron_ran($state, null, $now);
				try {
					$hooks->all_call_arguments($cron_hooks, $cron_arguments, null, $hook_callback, $result_callback);
				} catch (Exception $e) {
					$this->_exception($e, $cron_hooks);
				}
				try {
					$this->application->modules->all_hook_arguments($method, array(), null, $hook_callback, $result_callback);
				} catch (Exception $e) {
					$this->_exception($e, "Module::$method");
				}
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $unit);
				$this->application->logger->debug("Last ran {unit} {when}", array(
					"unit" => $unit,
					"when" => $last_unit_run->format()
				));
				if ($now->difference($last_unit_run, $unit) > 0) {
					self::_cron_ran($state, $unit, $now);
					$unit_hooks = $method . "_$unit";
					try {
						$unit_hooks = arr::suffix($cron_hooks, "_$unit");
						$hooks->all_call_arguments($unit_hooks, $cron_arguments, null, $hook_callback, $result_callback);
					} catch (Exception $e) {
						$this->_exception($e, $unit_hooks);
					}
					try {
						$this->application->modules->all_hook_arguments($method . "_$unit", array(), null, $hook_callback, $result_callback);
					} catch (Exception $e) {
						$this->_exception($e, $unit_hooks);
					}
				}
			}
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
		$js = array();
		$js[] = "(function (x) {";
		$js[] = "x.cron = x.cron || {};";
		$js[] = "x.cron.locked = " . ($run ? "false" : "true") . ";";
		if ($run) {
			$js[] = "x.cron.methods = " . json_encode($this->methods) . ";";
		}
		$js[] = "}(window.zesk.settings));";
		return arr::join_suffix($js, "\n");
	}
	
	/**
	 * Run cron
	 *
	 * @return boolean
	 */
	public function run() {
		$modules = $this->application->modules;
		
		$this->methods = array();
		
		$result = $modules->all_hook_arguments("cron_before", array(), true);
		if ($result === false) {
			$this->application->logger->error(__CLASS__ . "::cron_before return false");
			return $this->methods;
		}
		
		PHP::feature("time_limit", $this->option_integer("time_limit", 0));
		self::_run();
		
		$modules->all_hook_arguments("cron_after", array(
			$this->methods
		));
		
		return $this->methods;
	}
	
	/**
	 * Update a page to enable it to run cron
	 *
	 * @param Request $request
	 * @param Response_Text_HTML $response
	 */
	public function page_runner(Request $request, Response_Text_HTML $response) {
		$response->javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first',
			'share' => true
		));
		$response->javascript($this->page_runner_script(), null, array(
			'async' => 'async',
			'defer' => 'defer',
			'nocache' => true
		));
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
	 * @param integer $minute_to_hit
	 *        	Minute of the hour to hit
	 */
	public static function hourly(Interface_Settings $settings, $prefix, $minute_to_hit = 0) {
		if (empty($prefix)) {
			throw new Exception_Parameter("Prefix mus be non-empty to hourly");
		}
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
		$settings->set($last_check_setting, $now->format());
		$format = '{YYYY}-{MM}-{DD}-{hh}';
		if ($last_run !== null) {
			if ($last_run->format($format) === $now->format($format)) {
				return false;
			}
		}
		if ($last_check === null) {
			if ($now_minute === $minute_to_hit) {
				$settings->set($last_run_setting, $now->format());
				return true;
			}
		} else {
			if ($hour_minute->before($now) && $hour_minute->after($last_check)) {
				$settings->set($last_run_setting, $now->format());
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
	 * @param integer $hour_to_hit
	 *        	Hour of the day to hit, 0 ... 23
	 */
	public static function daily_hour_of_day(Interface_Settings $settings, $prefix, $hour_to_hit) {
		if (empty($prefix)) {
			throw new Exception_Parameter("Prefix mus be non-empty to daily_hour_of_day");
		}
		$hour_to_hit = intval($hour_to_hit);
		/*
		 * last_check - last time this script checked if it should run
		 * last_run - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . 'daily_last_run';
		$last_check_setting = $prefix . 'daily_last_check';
		
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
		$settings->set($last_check_setting, $now->format());
		$format = '{YYYY}-{MM}-{DD}';
		if ($last_run !== null) {
			if ($last_run->format($format) === $now->format($format)) {
				return false;
			}
		}
		if ($last_check === null) {
			if ($now_hour === $hour_to_hit) {
				$settings->set($last_run_setting, $now->format());
				return true;
			}
		} else {
			if ($today_hour->before($now) && $today_hour->after($last_check)) {
				$settings->set($last_run_setting, $now->format());
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
		return array(
			"system/panel/cron" => array(
				"title" => __("Cron Tasks"),
				"module_class" => __CLASS__
			)
		);
	}
	protected function hook_schema_updated() {
		// Changed class structure on 2016-11-23
		$update = $this->application->query_update(Settings::class);
		$nrows = $update->value("*name", "REPLACE(name, 'Module_Cron::', " . $update->database()
			->quote_text(__CLASS__ . "::") . ")")
			->where("name|LIKE", 'Module_Cron::%')
			->exec()
			->affected_rows();
		if ($nrows > 0) {
			$this->application->logger->notice("Updated {nrows} settings to use new prefix {class}", array(
				"nrows" => $nrows,
				"class" => __CLASS__
			));
		}
		$nrows = $this->application->query_delete("zesk\\Settings")
			->where("name|LIKE", array(
			'Module_Cron::%',
			'cron::%'
		))
			->exec()
			->affected_rows();
		if ($nrows > 0) {
			$this->application->logger->notice("{class}: Deleted {nrows} settings to using old prefixes", array(
				"nrows" => $nrows,
				"class" => __CLASS__
			));
		}
	}
}