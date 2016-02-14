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
class Module_Cron extends Module {
	
	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	protected $classes = array(
		"Server",
		"Lock",
		"Settings"
	);
	
	/**
	 * Database lock name
	 *
	 * @var string
	 */
	protected $lock_name = "zesk::cron";
	
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
		$method = zesk::callable_string($method);
		log::notice("zesk eval '$method(Application::instance())'");
		$this->methods[] = $method;
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
			zesk::add_hook('response/html.tpl', array(
				$this,
				'page_runner'
			));
		}
	}
	private static function _cron_variable_prefix($suffix = "", $system = false) {
		return __CLASS__ . "::last" . ($suffix ? "_$suffix" : "") . "::" . system::uname();
	}
	/**
	 * Time that cron was last run
	 *
	 * @return Timestamp
	 */
	private static function _last_cron_variable($unit) {
		return __CLASS__ . "::last" . ($unit ? "_$unit" : "");
	}
	private static function _last_cron_run(Interface_Data $object, $unit = null) {
		return Timestamp::factory($object->data(self::_last_cron_variable($unit)));
	}
	private static function _cron_ran(Interface_Data $object, $unit = null, Timestamp $when) {
		return $object->data(self::_last_cron_variable($unit), $when->unix_timestamp());
	}
	private static function _cron_hooks($method) {
		return arr::suffix(zesk::getl(__CLASS__ . '::classes', 'zesk\Application;Object'), "::$method");
	}
	
	/**
	 * Retrieve the objects which store our state
	 *
	 * @return array:Interface_Data
	 */
	private function _cron_scopes() {
		if (is_array($this->scopes)) {
			return $this->scopes;
		}
		/* @var $server Server*/
		$server = zesk::singleton("Server");
		/* @var $settings Settings */
		$settings = zesk::singleton('Settings');
		
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
		$now = Timestamp::factory('now');
		$results = array();
		try {
			$scopes = $this->_cron_scopes();
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . '::list_status');
			return array();
		}
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$last_run = self::_last_cron_run($state);
			
			$status = $now->difference($last_run, "second") > 0;
			$cron_hooks = self::_cron_hooks($method);
			$hooks = zesk::find_all_hooks($cron_hooks);
			$hooks = array_merge($hooks, $this->application->module->all_hook_list($method));
			foreach ($hooks as $hook) {
				$results[zesk::callable_string($hook)] = $status;
			}
			foreach (self::$intervals as $unit) {
				$last_unit_run = self::_last_cron_run($state, $unit);
				$status = $now->difference($last_unit_run, $unit) > 0;
				$unit_hooks = arr::suffix($cron_hooks, "_$unit");
				$hooks = $this->application->module->all_hook_list($method . "_${unit}");
				$hooks = array_merge($hooks, zesk::find_all_hooks($unit_hooks));
				foreach ($hooks as $hook) {
					$results[zesk::callable_string($hook)] = $status;
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
		log::error("Exception during {hooks}: {message}\n{backtrace}", array(
			"hooks" => $cron_hooks,
			"message" => $e->getMessage(),
			"backtrace" => $e->getTraceAsString(),
			"exception" => $e
		));
		zesk::hook("exception", $e);
	}
	/**
	 * Internal function to run tasks
	 */
	private function _run() {
		$now = Timestamp::factory('now');
		$results = array();
		
		try {
			$scopes = $this->_cron_scopes();
		} catch (Exception $e) {
			$this->_exception($e, __CLASS__ . "::_cron_scopes");
			return false;
		}
		$callback = array(
			$this,
			"_hook_callback"
		);
		$cron_arguments = array(
			$this->application
		);
		foreach ($scopes as $method => $settings) {
			$state = $settings['state'];
			/* @var $state Interface_Data */
			$lock_name = $settings['lock'];
			if (($lock = Lock::get_lock($lock_name)) !== null) {
				$last_run = self::_last_cron_run($state);
				$cron_hooks = self::_cron_hooks($method);
				if ($now->difference($last_run, "second")) {
					self::_cron_ran($state, null, $now);
					try {
						zesk::all_hook_array($cron_hooks, $cron_arguments, null, $callback);
					} catch (Exception $e) {
						$this->_exception($e, $cron_hooks);
					}
					try {
						$this->application->module->all_hook_array($method, array(), null, $callback);
					} catch (Exception $e) {
						$this->_exception($e, "Module::$method");
					}
				}
				foreach (self::$intervals as $unit) {
					$last_unit_run = self::_last_cron_run($state, $unit);
					log::debug("Last ran {unit} {when}", array(
						"unit" => $unit,
						"when" => $last_unit_run->format()
					));
					if ($now->difference($last_unit_run, $unit) > 0) {
						self::_cron_ran($state, $unit, $now);
						$unit_hooks = $method . "_$unit";
						try {
							$unit_hooks = arr::suffix($cron_hooks, "_$unit");
							zesk::all_hook_array($unit_hooks, $cron_arguments, null, $callback);
						} catch (Exception $e) {
							$this->_exception($e, $unit_hooks);
						}
						try {
							$this->application->module->all_hook_array($method . "_$unit", array(), null, $callback);
						} catch (Exception $e) {
							$this->_exception($e, $unit_hooks);
						}
					}
				}
				$lock->release();
			}
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
	 * Is cron running?
	 *
	 * @return boolean
	 */
	public static function running() {
		return Object::class_instance("Settings")->database()->is_locked(self::lock_name);
	}
	
	/**
	 * Run cron
	 *
	 * @return boolean
	 */
	public function run() {
		$this->methods = array();
		
		$result = Module::all_hook_array("cron_before", array(), true);
		if ($result === false) {
			log::error("Module::cron_before return false");
			return $this->methods;
		}
		
		zesk::php_set("time_limit", zesk::get(__CLASS__ . "::time_limit", 0));
		self::_run();
		
		Module::all_hook_array("cron_after", array(
			$this->methods
		));
		
		return $this->methods;
	}
	
	/**
	 * Update a page to enable it to run cron
	 *
	 * @param Request $request        	
	 * @param Response_HTML $response        	
	 */
	public function page_runner(Request $request, Response_HTML $response) {
		$response->cdn_javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first',
			'root_dir' => zesk::root()
		));
		$response->javascript(u($this->page_runner_script()), null, array(
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
	public static function hourly($prefix, $minute_to_hit = 0) {
		if (empty($prefix)) {
			throw new Exception_Parameter("Prefix mus be non-empty to hourly");
		}
		$settings = Settings::instance();
		/*
		 * last_check - last time this script checked if it should run
		 * last_run - last time this cron task was actually ran
		 */
		$last_run_setting = $prefix . 'hourly_last_run';
		$last_check_setting = $prefix . 'hourly_last_check';
		
		$now = Timestamp::factory('now');
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
	public static function daily_hour_of_day($prefix, $hour_to_hit) {
		if (empty($prefix)) {
			throw new Exception_Parameter("Prefix mus be non-empty to daily_hour_of_day");
		}
		$settings = Settings::instance();
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
}
