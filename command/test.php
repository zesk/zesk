<?php

/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Run automated tests in a variety of formats.
 *
 * @no_test
 *
 * @author kent
 * @category Test
 */
class Command_Test extends Command_Base {
	/**
	 *
	 * @var string
	 */
	const TEST_UNIT_CLASS = "zesk\\Test_Unit";
	
	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public $has_configuration = true;
	
	/**
	 * Load these modules prior to running command
	 *
	 * $var array
	 */
	protected $load_modules = array(
		"test"
	);
	
	/**
	 * Option types
	 *
	 * @var array
	 */
	protected $option_types = array(
		'directory' => 'dir',
		'config' => 'file',
		'test-database' => 'file',
		'debugger' => 'boolean',
		'debug-command' => 'boolean',
		'no-database' => 'boolean',
		'show-options' => 'boolean',
		'database-report' => 'boolean',
		'database-reset' => 'boolean',
		'no-buffer' => 'boolean',
		'interactive' => 'boolean',
		'deprecated' => 'boolean',
		'strict' => 'boolean',
		'sandbox' => 'boolean',
		'no-sandbox' => 'boolean',
		'show' => 'boolean',
		'*' => 'string'
	);
	
	/**
	 * Help for options types above
	 *
	 * @var array
	 */
	protected $option_help = array(
		'directory' => 'Only search for tests in this directory',
		'config' => 'Configuration file to load. Defaults to $HOME/.zesk/test.conf',
		'test-database' => 'Store tests results in specified database file - makes testing faster',
		'debugger' => 'Open debugger right before test is run',
		'debug-command' => 'Debug the sandbox execution command',
		'no-database' => 'Ignore past saved results, do not save any test results (even if specified)',
		'show-options' => "Show computed options for command before running tests",
		'database-report' => "Show a report of failed tests in the database",
		'database-reset' => "Reset all tests stored in the database, and start over. Use cautiously!",
		'no-buffer' => "Do not do any output buffering",
		'interactive' => 'Run tests interactively, continue only when successful.',
		'deprecated' => 'Throw exceptions when deprecated functions are used.',
		'strict' => 'Strict warnings count as failure.',
		'sandbox' => 'Run tests sandboxed (slower)',
		'no-sandbox' => 'Force tests to be run in memory (faster)',
		'show' => 'Show list of tests that would be run instead of running them.',
		'*' => 'Test patterns to run - either Class::method, or a string to match in the filename'
	);
	const width = 96;
	
	/**
	 * Tests run
	 *
	 * @var array
	 */
	public $tests = array();
	
	/**
	 * Test statistics
	 *
	 * @var array
	 */
	public $stats = array(
		'test' => 0,
		'pass' => 0,
		'fail' => 0,
		'skip' => 0,
		'assert' => 0
	);
	static $opened = array();
	
	/**
	 * Name of test database for results
	 *
	 * @var string
	 */
	private $test_database_file = null;
	
	/**
	 * Saved results from tests
	 *
	 * @var array
	 */
	private $test_results = array();
	
	/**
	 * Classes
	 *
	 * @todo what is this
	 * @var array
	 */
	private $classes = array();
	
	/**
	 * Includes
	 *
	 * @var array
	 */
	private $incs = array();
	
	/**
	 * Help string for this command
	 *
	 * @var string
	 */
	protected $help = "Run automated tests in a variety of formats.";
	
	/**
	 * TODO we use two different ways of doing this: static::settings() and this.
	 * Should pick one and stick with it.
	 *
	 * @var array:array
	 */
	static $zesk_globals = array(
		'Command_Test::command_run_sandbox' => array(
			'type' => 'string',
			'description' => "Command to run a unit test via a sandbox. @zesk_docs"
		),
		'Command_Test::command_local_open' => array(
			'type' => 'string',
			'description' => "Command to open a text file via a local editor when a test fails. Takes a single token {file} which is the unit test which has failed. Used during --interactive mode to load failed files for fixes. @zesk_docs"
		),
		'Command_Test::command_clear_console' => array(
			'type' => 'string',
			'description' => "Command to clear the console while running tests interactively (--interactive). @zesk_docs"
		)
	);
	
	/**
	 * Most recent test result
	 *
	 * @var boolean
	 */
	private $last_result = null;
	protected function show_options() {
		$this->log("All options:\n{options}", array(
			"options" => Text::format_pairs(arr::clean($this->option(), false))
		));
	}
	/**
	 * Run tests
	 */
	protected function run() {
		self::_initialize_test_environment($this->application);
		if ($this->option_bool('help')) {
			$this->usage("Run tests");
		}
		$this->configure("test");
		if ($this->option_bool("show-options")) {
			$this->show_options();
		}
		$this->verbose_log("Verbose mode enabled.");
		if ($this->option_bool('debug')) {
			$this->verbose_log("Debug mode enabled (lots of logging)");
		}
		if ($this->option_bool('strict')) {
			$this->verbose_log("Strict mode enabled (any strict warnings result in failure.)");
		}
		if ($this->_determine_sandbox()) {
			$this->verbose_log("Sandbox mode enabled (all tests are run in a separate process, slower)");
		}
		if ($this->option_bool("debug_options")) {
			$this->log("Command_Test options:");
			ksort($this->options);
			$this->log(Text::format_pairs($this->options));
		}
		$db_reset = $this->option("database-reset");
		$db_report = $this->option("database-report");
		$db_loaded = false;
		if ($this->option('test-database')) {
			$this->test_database_file = $this->option('test-database');
			$db_loaded = $this->load_test_database();
			$this->verbose_log($db_loaded ? "Database {name} was loaded" : "Database {name} was NOT loaded", array(
				"name" => $this->test_database_file
			));
		}
		if ($db_report) {
			if (!$db_loaded) {
				$this->usage("--database-report and --no-database are incompatible");
			}
			if (!$this->test_database_file) {
				$this->usage("--database-report requires --test-database");
			}
			$this->database_report();
			return 0;
		}
		if ($db_reset && $db_loaded) {
			$this->verbose_log("Resetting all tests stored in database {file}", array(
				"file" => $this->test_database_file
			));
			$this->test_results = array();
			$this->save_test_database();
		}
		
		if ($this->option_bool('interactive')) {
			$this->verbose_log("Interactive testing enabled.");
		}
		$tests = $this->_determine_tests_to_run();
		if ($this->option_bool('show')) {
			echo implode("\n", $tests) . "\n";
			return 0;
		}
		/*
		 * Run tests
		 */
		if (count($tests) === 0) {
			echo "No tests found.\n";
			exit(0);
		}
		$result = 0;
		foreach ($tests as $test) {
			$this->verbose_log("Running $test ...");
			try {
				$test_succeeded_mtime = avalue($this->test_results, $test);
				$test_mtime = filemtime($test);
				if ($test_mtime === $test_succeeded_mtime) {
					continue;
				}
				if (!$this->run_test($test)) {
					$result = 1;
				}
			} catch (\Exception $e) {
				$result = 1;
				echo $e->getMessage();
			}
			$this->test_results[$test] = $result === 0 ? $test_mtime : false;
			$this->save_test_database();
		}
		$this->log("# Tests completed");
		$this->log(Text::format_pairs($this->stats));
		return $result;
	}
	/**
	 * Options for list_recursive to find tests
	 *
	 * @return array
	 */
	private function _test_list_options() {
		$options = array(
			'rules_file' => array(
				'#\.application\.inc#i' => false,
				'#\.module\.inc#i' => false,
				'#.*/test/.*(\.inc|\.phpt)#i' => true,
				'#.*_test.php#i' => true,
				false
			),
			'rules_directory' => false,
			'rules_directory_walk' => array(
				'#/\.#' => false,
				true
			),
			'add_path' => true
		);
		return $options;
	}
	public function command_list() {
		return $this->_determine_tests_to_run();
	}
	/**
	 * Given current working directory and an optional directory option, find all tests to run
	 *
	 * @return array of file paths
	 */
	private function _determine_tests_to_run() {
		$cwd = getcwd();
		$path = $this->option('directory', $cwd);
		if ($path) {
			$path = Directory::make_absolute($cwd, $path);
		} else if (!Directory::is_absolute($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		
		if (!$this->has_arg()) {
			return Directory::list_recursive($path, $this->_test_list_options());
		}
		
		$matches = array();
		$tests = array();
		$this->test_results = array();
		while ($this->has_arg()) {
			$arg = $this->get_arg("test");
			if (is_file($arg)) {
				$tests[] = $arg;
				continue;
			} else {
				$matches[] = $arg;
			}
		}
		if (count($matches) > 0) {
			$potential_tests = Directory::list_recursive($path, $this->_test_list_options());
			foreach ($matches as $match) {
				$found = arr::match($potential_tests, $match);
				if (count($found) > 0) {
					$tests = array_merge($tests, $found);
				} else {
					$this->error("No match for test {arg}", array(
						"arg" => $arg
					));
				}
			}
		}
		return $tests;
	}
	
	/**
	 * Load the test database
	 *
	 * @throws Exception_Directory_NotFound
	 * @return boolean
	 */
	private function load_test_database() {
		if ($this->option_bool('no-database')) {
			return false;
		}
		$file = $this->test_database_file;
		if (file_exists($file)) {
			$this->verbose_log("Loading test database $this->test_database_file");
			try {
				$this->test_results = JSON::decode(File::contents($file));
			} catch (Exception_Parse $e) {
				$this->error("Unable to parse {file} - likely corrupt", array(
					"file" => $file
				));
			}
			if ($this->option_bool('debug_test_database')) {
				$this->log("Test database:");
				$this->log($this->test_results);
			}
			return true;
		}
		$dir = dirname($this->test_database_file);
		if (!is_dir($dir)) {
			throw new Exception_Directory_NotFound($dir, "Can not write test database to $dir");
		}
		$this->verbose_log("Will save to new test database $this->test_database_file");
		return true;
	}
	
	/**
	 * Save the test database
	 */
	private function save_test_database() {
		if ($this->option_bool('no-database')) {
			return;
		}
		if ($this->test_database_file) {
			file_put_contents($this->test_database_file, JSON::encode_pretty($this->test_results));
		}
	}
	private static function _initialize_test_environment(Application $app) {
		$app->zesk->autoloader->path($app->paths->zesk('test/classes'), array(
			'class_prefix' => __NAMESPACE__ . '\\Test_'
		));
	}
	private function _run_test_init() {
		self::$opened = array();
	}
	private function _run_test_failed($test) {
		if ($this->option_bool('automatic_open')) {
			if ($this->option_bool('automatic_open_match')) {
				$files = $this->_parse_path_info($this->last_result);
				if ($this->option_bool('debug_matched_files')) {
					echo "### MATCHED FILES:\n\t" . implode("\n\t", $files) . "\n";
				}
			} else {
				$files = array();
			}
			$files[] = $test;
			foreach ($files as $file) {
				if (!avalue(self::$opened, $file)) {
					self::$opened[$file] = true;
					$this->_local_open($file);
				}
			}
		}
	}
	private function _run_test_success($test) {
	}
	private function _parse_path_info($contents) {
		$app_root = $this->application->application_root();
		$php_extensions = $this->option("automatic_open_extensions", "inc|tpl|php|php5|phpt");
		$pattern = "#PHP-ERROR:.*?(?P<files>(?:" . ZESK_ROOT . "|$app_root)[A-Za-z_0-9./-]*\.(?:$php_extensions))#s";
		$pattern = "#(?P<files>(?:" . ZESK_ROOT . "|$app_root)[A-Za-z_0-9./-]*\.(?:$php_extensions))#s";
		$matches = null;
		if (!preg_match_all($pattern, $contents, $matches)) {
			return array();
		}
		if ($this->option_bool('debug_matched_files_preg')) {
			dump($matches);
		}
		return avalue($matches, 'files', array());
	}
	private function _local_open($file) {
		static $command = null;
		if ($command === null) {
			$command = $this->option('command_local_open', '');
			if (!$command) {
				$command = false;
				return false;
			}
		}
		if ($command === false) {
			return false;
		}
		$localopen = map($command, array(
			"file" => $file
		));
		if ($this->option("debug_run_test_command")) {
			$this->log("Local open: $localopen");
		}
		$return_var = null;
		system($localopen, $return_var);
		return ($return_var === 0);
	}
	private function _clear_console() {
		static $command = null;
		if ($command === null) {
			$command = $this->option('command_clear_console', 'clear');
			if (!$command) {
				$command = false;
				return false;
			}
		}
		if ($command === false) {
			return false;
		}
		system($command);
	}
	private function run_test($file) {
		$ext = File::extension($file);
		$method = "run_test_$ext";
		$interactive_sleep = $this->option('interactive_sleep', 4);
		if (!method_exists($this, $method)) {
			throw new Exception_Unimplemented("No way to run test with extension $ext ($file)");
		}
		$repeated = false;
		$first = true;
		$result = true;
		do {
			if ($repeated) {
				if (Process_Tools::process_code_changed()) {
					$this->log("Code changed, exiting to run again.");
					exit(1);
				}
				if ($first) {
					$this->_local_open($file);
					$first = false;
				}
				sleep($interactive_sleep);
				$this->_clear_console();
			}
			$options = $this->load_test_options(file_get_contents($file));
			if ($this->option_bool('debug_test_options') && count($options) > 0) {
				$this->verbose_log("$file test options: \n" . Text::format_pairs($options));
			}
			if (avalue($options, 'skip')) {
				break;
			}
			if ($this->option_bool('debugger')) {
				debugger_start_debug();
			}
			$is_phpunit = to_bool(avalue($options, 'phpunit'));
			if ($is_phpunit) {
				$result = $this->run_phpunit_test($file, $options);
			} else {
				$result = $this->$method($file, $options);
			}
			$repeated = true;
			if ($result === false) {
				$this->_run_test_failed($file);
			} else {
				$this->_run_test_success($file);
			}
		} while ($this->option_bool('interactive') && $result === false);
		return $result;
	}
	private function load_test_options($contents) {
		$comments = DocComment::extract($contents);
		if (count($comments) === 0) {
			return array();
		}
		return array_change_key_case(arr::kunprefix(to_array(DocComment::parse($comments[0])), "test_", true));
	}
	private function include_file_classes($file) {
		/* This class *must* cache if called more than once - include files usually aren't included more than once,
		 * so issue remains when 2nd go-round, no new classes are declared */
		$run_class = avalue($this->incs, $file);
		if (is_array($run_class)) {
			return $run_class;
		}
		$run_class = array();
		$classes = array_flip(get_declared_classes());
		$debug_class_discovery = $this->option_bool("debug_class_discovery");
		if ($debug_class_discovery) {
			ksort($classes);
			echo "# Class discovery: Declared classes\n";
			echo implode("", arr::wrap(array_keys($classes), "- `", "`\n"));
		}
		require_once $file;
		if ($debug_class_discovery) {
			echo "# Class discovery: Found classes\n";
		}
		$after_classes = get_declared_classes();
		sort($after_classes);
		foreach ($after_classes as $new_class) {
			if (!array_key_exists($new_class, $classes) && is_subclass_of($new_class, self::TEST_UNIT_CLASS)) {
				$run_class[] = $new_class;
				if ($debug_class_discovery) {
					echo "- `$new_class`\n";
				}
			} else {
				if ($debug_class_discovery) {
					echo "- `$new_class` (not " . self::TEST_UNIT_CLASS . ")\n";
				}
			}
		}
		$this->incs[$file] = $run_class;
		return $run_class;
	}
	
	/**
	 *
	 * @param array $options
	 * @return boolean
	 */
	private function _determine_sandbox(array $options = null) {
		if ($options === null) {
			$options = $this->options;
		}
		$sandbox = to_bool(avalue($options, 'sandbox', $this->option_bool('sandbox')));
		if (to_bool(avalue($options, 'no_sandbox', $this->option_bool('no_sandbox'))) === true) {
			$sandbox = false;
		}
		return $sandbox;
	}
	/**
	 * inc and php files are treated identically
	 * 
	 * @param string $file
	 * @param array $options
	 */
	private function run_test_inc($file, array $options) {
		return $this->run_test_php($file, $options);
	}
	
	/**
	 * Load a PHP file which contains a subclass of zesk\Test_Unit
	 *  
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function run_test_php($file, array $options) {
		$options += $this->options;
		try {
			$run_class = $this->include_file_classes($file);
		} catch (Exception $e) {
			$this->log("Unable to include $file without error {class} {message} - fail.", array(
				"class" => get_class($e),
				"message" => $e->getMessage()
			));
			return false;
		}
		if (count($run_class) === 0) {
			$this->log("Unable to find any {name} classes in $file - skipping", array(
				"name" => self::TEST_UNIT_CLASS
			));
			return true;
		}
		if ($this->_determine_sandbox($options)) {
			if (!$this->_determine_sandbox()) {
				$this->verbose_log("Running $file in sandbox mode. (Override)");
			}
			return $this->_run_test_sandbox($file, first($run_class), $options);
		}
		if ($this->_determine_sandbox()) {
			$this->verbose_log("Running $file in no-sandbox mode (Override).");
		}
		$final_result = true;
		try {
			foreach ($run_class as $class) {
				$object = null;
				$result = Test_Unit::run_one_class($this->application, $class, $options, $object);
				if ($object instanceof Test_Unit) {
					if ($this->stats === null) {
						$this->stats = $object->stats;
					} else {
						$this->stats = arr::sum($this->stats, $object->stats);
					}
				}
				if (!$result) {
					$final_result = false;
				}
			}
		} catch (\Exception $e) {
			$this->error("Exception {message} at {file}:{line}", Exception::exception_variables($e));
			$final_result = false;
		}
		return $final_result;
	}
	private function _run_sandbox_command() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		static $command = null;
		if ($command !== null) {
			return $command;
		}
		$command = $this->option('command_run_sandbox');
		if ($command) {
			if ($this->option('debug-command')) {
				$this->log("Run sandbox command is $command (global)");
			}
			return $command;
		}
		$env = $zesk->paths->which('env');
		$php = $zesk->paths->which('php');
		if (!$env) {
			if (!$php) {
				throw new Exception_Configuration("No env or php found in path, is PATH invalid?\n" . implode("\n\t", $zesk->paths->command()) . "\n");
			}
			$command = $php;
		} else {
			$command = $env . ' php';
		}
		$this->verbose_log("Run sandbox command is $command (computed)");
		return $command;
	}
	private function _unit_options() {
		return to_list('verbose;no-buffer');
	}
	private function determine_test_class($file) {
		$classes = $this->include_file_classes($file);
		foreach ($classes as $index => $class) {
			if ($class instanceof Test_Unit) {
				return $class;
			}
		}
		return null;
	}
	/**
	 * Run a command in the sandbox
	 *
	 * @param string $file
	 *        	File to run
	 * @param array $options
	 * @return boolean
	 */
	private function _run_test_sandbox($file, $class, array $options) {
		$command = self::_run_sandbox_command();
		$options['prefix'] = $command . " " . ZESK_ROOT . "bin/zesk-command.php --search \"" . $this->application->application_root() . "\" ";
		if (is_file($this->config)) {
			$options['prefix'] .= '--config \'' . addslashes($this->config) . '\' ';
		}
		$flags = $this->_unit_options();
		foreach ($flags as $flag) {
			$value = avalue($options, $flag);
			if (is_bool($value) || is_string($value) || is_numeric($value)) {
				$options['prefix'] .= "--set " . self::TEST_UNIT_CLASS . "::$flag=" . json_encode($value) . " ";
			}
		}
		$opts = "";
		if ($this->option_bool("debug")) {
			$opts .= "--debug ";
		}
		if ($this->option_bool("verbose")) {
			$opts .= "--verbose ";
		}
		$options['echo'] = true;
		$options['suffix'] = " module test eval $opts 'zesk\\Command_Test::run_class(\$application, \"" . strtr($class, array(
			"\\" => "\\\\"
		)) . "\", \"$file\")'";
		$options['command'] = "{prefix}{suffix}";
		
		return $this->_run_test_command($file, $options);
	}
	
	/**
	 * Glue to run sandbox tests from the site.
	 * Sets the include path correctly, then runs the class in Test_Unit.
	 *
	 * @param string $class
	 * @param string $file
	 * @return boolean
	 */
	public static function run_class(Application $application, $class, $file) {
		self::_initialize_test_environment($application);
		require_once $file;
		return Test_Unit::run_class($application, $class);
	}
	/**
	 * 
	 * @todo PHPUnit tests are not run
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function run_phpunit_test($file, array $options) {
		return true;
	}
	/**
	 * Run a test command
	 *
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function _run_test_command($file, array $options) {
		if (!file_exists($file)) {
			$this->stats['skip']++;
			return true;
		}
		$options = $options + $this->options;
		$verbose = avalue($options, 'verbose');
		$strict = avalue($options, 'strict');
		if ($verbose) {
			$pad = str_repeat(" ", max(self::width - strlen($file), 0));
			echo "$file$pad# ";
		}
		$exit_code = 0;
		$test_contents = file_get_contents($file);
		if (strpos($test_contents, '--TEST--') !== false && strpos($test_contents, '--FILE--') !== false) {
			$this->stats['skip']++;
			if ($verbose) {
				echo "* OK\n";
			}
			return true;
		}
		
		$this->stats['test']++;
		$options['prefix'] = avalue($options, 'prefix', '');
		$options['suffix'] = avalue($options, 'suffix', '');
		$command = map(avalue($options, 'command', "{prefix}$file{suffix}"), $options) . " 2>&1";
		if ($this->option('debug-command')) {
			$this->log("COMMAND: $command");
		}
		$buffer = !to_bool(avalue($options, 'no-buffer'));
		$debug_buffering = to_bool(avalue($options, 'debug_buffering'));
		if ($buffer) {
			if ($debug_buffering) {
				echo "\n" . __FILE__ . " ### ob_start(); ###\n";
			}
			ob_start();
		} else {
			if ($debug_buffering) {
				echo "\n" . __FILE__ . " ### NO ob " . Text::format_pairs($options) . " ###\n";
			}
		}
		$system_result = system($command, $exit_code);
		if ($buffer) {
			$this->last_result = $last_result = ob_get_clean();
			if ($debug_buffering) {
				echo $last_result . "\n";
				echo "\n" . __FILE__ . " ### ob_get_clean(); ###\n";
			}
		} else {
			if ($debug_buffering) {
				echo "\n" . __FILE__ . " ### NO ob END ###\n";
			}
			$this->last_result = $last_result = $system_result;
		}
		$exit_code = intval($exit_code);
		if (strpos($last_result, "PHP-ERROR") !== false) {
			$exit_code = 100;
		}
		if ($strict) {
			if (strpos($last_result, "Strict Standards:") !== false) {
				$exit_code = 101;
			}
		}
		if ($verbose) {
			echo "$exit_code";
		}
		$success = ($exit_code === 0);
		if (avalue($options, 'always_fail') || strpos($test_contents, 'ALWAYS_FAIL') !== false) {
			if ($verbose) {
				echo " always fail:";
			}
			$success = !$success;
		}
		$echo = avalue($options, 'echo');
		if (!$success) {
			if ($verbose) {
				echo " FAILED\n";
			}
			$this->tests[$file] = $last_result;
			if ($verbose || $echo) {
				$this->_test_function_output($file, $last_result);
			}
			$this->stats['fail']++;
			return false;
		} else {
			$this->stats['pass']++;
			if ($verbose) {
				echo " OK\n";
			}
		}
		return true;
	}
	
	/**
	 * Run a phpt test
	 *
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function run_test_phpt($file, array $options) {
		$extract = DocComment::extract(file_get_contents($file));
		if (count($extract)) {
			$options = arr::filter(PHP::autotype(DocComment::parse(first($extract))), "strict") + $options;
		}
		return $this->_run_test_command($file, $options);
	}
	
	/**
	 * Output test results
	 *
	 * @param string $file
	 *        	Test run
	 * @param string $result
	 *        	Results of test
	 */
	private function _test_function_output($file, $result) {
		echo "$file FAILED:\n";
		echo str_repeat("-", 80) . "\n";
		echo trim($result) . "\n";
		echo str_repeat("*", 80) . "\n";
	}
	
	/**
	 * Output database report
	 */
	private function database_report() {
		$stats = array(
			"total" => count($this->test_results),
			'pass' => 0,
			'fail' => 0
		);
		$fails = array();
		foreach ($this->test_results as $test => $value) {
			if ($value === false) {
				$stats['fail']++;
				$fails[] = "zesk --cd \"" . dirname($test) . "\" test \"$test\"";
			} else {
				$stats['pass']++;
			}
		}
		echo implode("\n", $fails) . "\n";
		echo Text::format_pairs($stats);
	}
}
