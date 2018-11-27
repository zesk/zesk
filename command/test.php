<?php

/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

use zesk\Test\Exception;

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
	 * If the tests matched were explicitly asked for
	 *
	 * @var boolean
	 */
	private $testing_everything = null;

	/**
	 * Load these modules prior to running command
	 *
	 * $var array
	 */
	protected $load_modules = array(
		"test",
	);

	/**
	 * Option types
	 *
	 * @var array
	 */
	protected $option_types = array(
		'directory' => 'dir',
		'config' => 'file',
		'debugger' => 'boolean',
		'debug-command' => 'boolean',
		'show-options' => 'boolean',
		'no-buffer' => 'boolean',
		'no-config' => 'boolean',
		'interactive' => 'boolean',
		'deprecated' => 'boolean',
		'strict' => 'boolean',
		'sandbox' => 'boolean',
		'no-sandbox' => 'boolean',
		'show' => 'boolean',
		'*' => 'string',
		/* TestDatabase related */
		'test-database' => 'file',
		'no-database' => 'boolean',
		'reset' => 'boolean',
		'report' => 'boolean',
		'format' => 'string',

		/* Deprecated 2018-03 */
		'database-reset' => 'boolean',
		'database-report' => 'boolean',
	);

	/**
	 * Help for options types above
	 *
	 * @var array
	 */
	protected $option_help = array(
		'directory' => 'Only search for tests in this directory',
		'config' => 'Configuration file to load. Defaults to $HOME/.zesk/test.conf',
		'debugger' => 'Open debugger right before test is run',
		'debug-command' => 'Debug the sandbox execution command',
		'show-options' => "Show computed options for command before running tests",
		'no-buffer' => "Do not do any output buffering",
		'no-config' => "Skip loading of any external configuration files",
		'interactive' => 'Run tests interactively, continue only when successful.',
		'deprecated' => 'Throw exceptions when deprecated functions are used.',
		'strict' => 'Strict warnings count as failure.',
		'sandbox' => 'Run tests sandboxed (slower)',
		'no-sandbox' => 'Force tests to be run in memory (faster)',
		'show' => 'Show list of tests that would be run instead of running them.',
		'*' => 'Test patterns to run - either Class::method, or a string to match in the filename',

		/* TestDatabase related */
		'test-database' => 'Store tests results in specified database file - makes testing faster',
		'no-database' => 'Ignore past saved results, do not save any test results (even if specified)',
		'reset' => "Reset all tests stored in the database, and exit. Does not run any tests.",
		'report' => "Show a report of failed tests in the database",
		'format' => 'databse-report format',

		/* Deprecated 2018-03 */
		'database-reset' => 'Deprecated 2018-03. Use --reset instead. Reset all tests stored in the database, and exit. Does not run any tests.',
		'database-report' => 'Deprecated 2018-03. Use --report instead. Show a report of failed tests in the database',

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
		'assert' => 0,
	);

	public static $opened = array();

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
	public static $zesk_globals = array(
		'Command_Test::command_run_sandbox' => array(
			'type' => 'string',
			'description' => "Command to run a unit test via a sandbox. @zesk_docs",
		),
		'Command_Test::command_local_open' => array(
			'type' => 'string',
			'description' => "Command to open a text file via a local editor when a test fails. Takes a single token {file} which is the unit test which has failed. Used during --interactive mode to load failed files for fixes. @zesk_docs",
		),
		'Command_Test::command_clear_console' => array(
			'type' => 'string',
			'description' => "Command to clear the console while running tests interactively (--interactive). @zesk_docs",
		),
	);

	/**
	 * Most recent test result
	 *
	 * @var boolean
	 */
	private $last_result = null;

	protected function show_options() {
		$this->log("All options:\n{options}", array(
			"options" => Text::format_pairs(ArrayTools::clean($this->option(), false)),
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
		if (!$this->option_bool("no-config")) {
			$this->configure("test");
		} else {
			$this->verbose_log("Skipping loading configuration files");
			$this->application->configured();
		}
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
		$db_reset = $this->option("reset", $this->option("database-reset"));
		$db_report = $this->option("report", $this->option("database-report"));
		$db_loaded = false;
		if ($this->option('test-database')) {
			$this->test_database_file = $this->option('test-database');
			$db_loaded = $this->load_test_database();
			$this->verbose_log($db_loaded ? "Database {name} was loaded" : "Database {name} was NOT loaded", array(
				"name" => $this->test_database_file,
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
		if ($db_reset) {
			if (!$db_loaded) {
				$this->error("Can not --reset unless you specify a --test-database");
			}
			$this->verbose_log("Resetting all tests stored in database {file}", array(
				"file" => $this->test_database_file,
			));
			$this->test_results = array();
			$this->save_test_database();
			return 0;
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
			return 0;
		}
		$result = 0;
		foreach ($tests as $test) {
			$success = false;

			try {
				$test_succeeded_mtime = avalue($this->test_results, $test);
				$test_mtime = filemtime($test);
				if ($this->testing_everything && $test_mtime === $test_succeeded_mtime) {
					continue;
				}
				$this->verbose_log("Running $test ...");
				if ($this->run_test($test)) {
					$success = true;
				} else {
					$success = false;
				}
			} catch (\Exception $e) {
				$success = false;
				echo $e->getMessage();
			}
			if (!$success) {
				$result++;
			}
			$this->test_results[$test] = $success ? $test_mtime : false;
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
				false,
			),
			'rules_directory' => false,
			'rules_directory_walk' => array(
				'#/\.#' => false,
				true,
			),
			'add_path' => true,
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
		} elseif (!Directory::is_absolute($path)) {
			throw new Exception_Directory_NotFound($path);
		}

		if (!$this->has_arg()) {
			$this->testing_everything = true;
			return Directory::list_recursive($path, $this->_test_list_options());
		}

		$matches = array();
		$tests = array();
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
				$found = ArrayTools::match($potential_tests, $match);
				if (count($found) > 0) {
					$tests = array_merge($tests, $found);
				} else {
					$this->error("No match for test {arg}", array(
						"arg" => $arg,
					));
				}
			}
		}
		$this->testing_everything = false;
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
			$this->verbose_log("Loading test database {file}", array(
				"file" => $file,
			));

			try {
				$this->test_results = JSON::decode(File::contents($file));
			} catch (Exception_Parse $e) {
				$this->error("Unable to parse {file} - likely corrupt", array(
					"file" => $file,
				));
				$this->test_results = array();
			}
			$this->log_db("After Load");
			return true;
		}
		$dir = dirname($this->test_database_file);
		if (!is_dir($dir)) {
			throw new Exception_Directory_NotFound($dir, "Can not write test database to $dir");
		}
		$this->verbose_log("Will save to new test database {file}", array(
			"file" => $file,
		));
		return true;
	}

	/**
	 * Output the database test results to the log
	 *
	 * @param string $message Optional context message
	 */
	private function log_db($message = null) {
		if ($this->option_bool('debug_test_database')) {
			$this->log("Test database {message}: {type}", array(
				"message" => strval($message),
				"type" => type($this->test_results),
			));
			$this->log($this->test_results);
		}
	}

	/**
	 * Save the test database
	 */
	private function save_test_database() {
		if ($this->option_bool('no-database')) {
			return;
		}
		if ($this->test_database_file) {
			$this->log_db("Before Save");
			file_put_contents($this->test_database_file, JSON::encode_pretty($this->test_results));
		}
	}

	/**
	 * Set up Zesk autoloader for test classes and support classes
	 *
	 * @param Application $app
	 */
	private static function _initialize_test_environment(Application $app) {
		$app->autoloader->path($app->paths->zesk('test/classes'), array(
			'class_prefix' => __NAMESPACE__ . '\\Test_',
		));
	}

	/**
	 * Initialize our state before running any tests
	 */
	private function _run_test_init() {
		self::$opened = array();
	}

	/**
	 * If a test fails, and configuration exists, open the file in the local editor
	 *
	 * (this can be a PITA if lots of files fail!)
	 *
	 * @param string $test Filename of test file to fail
	 */
	private function _local_editor_open_match($test) {
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

	/**
	 * If a test fails, and configuration exists, open the file in the local editor
	 *
	 * (this can be a PITA if lots of files fail!)
	 *
	 * @param string $test Filename of test file to fail
	 */
	private function _run_test_failed($test) {
		if ($this->option_bool('automatic_open')) {
			$this->_local_editor_open_match($test);
		}
	}

	/**
	 * When a test is successful, do something congratulatory
	 *
	 * @param string $test
	 */
	private function _run_test_success($test) {
		// Have a day.
	}

	/**
	 *
	 * @param unknown $contents
	 * @return array|mixed|array
	 */
	private function _parse_path_info($contents) {
		$app_root = $this->application->path();
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

	/**
	 * Security issues with allowing access to this.
	 *
	 * @configuration self::command_local_open Command line to execute, use {file} token for file to open
	 *
	 * @param string $file
	 * @return boolean
	 */
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
			"file" => $file,
		));
		if ($this->option("debug_run_test_command")) {
			$this->log("Local open: $localopen");
		}
		$return_var = null;
		system($localopen, $return_var);
		return ($return_var === 0);
	}

	/**
	 * Ouput the clear command to the console for iterating over a test
	 *
	 * @return void
	 */
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

	/**
	 * Run a test on the file given based on its extension, global options, and file-specific options.
	 *
	 * @param string $file
	 * @throws Exception_Unimplemented
	 * @return boolean
	 */
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
				if (Process_Tools::process_code_changed($this->application)) {
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
			if ($this->option_bool('debug_test_options') && count($options) >= 0) {
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

	/**
	 * Load the test options from the associated file content passed in.
	 *
	 * Parses DocComment and returns the doccomment structure for the FIRST occurrence in the file.
	 *
	 * @param unknown $contents
	 * @return array|array
	 */
	private static function load_test_options($contents) {
		$result = array();
		if (StringTools::contains($contents, array(
			"extends PHPUnit_TestCase",
			"extends \\zesk\\PHPUnit_TestCase",
		))) {
			$result['phpunit'] = true;
		}
		$comments = DocComment::extract($contents, array(
			DocComment::OPTION_LIST_KEYS => array(
				"test_module",
			),
		));
		if (count($comments) === 0) {
			return $result;
		}
		$first_comment = $comments[0];
		return $result + array_change_key_case(ArrayTools::kunprefix($first_comment->variables(), "test_", true));
	}

	/**
	 * Including a file, determine what new classes are now available. File must not have been included already.
	 *
	 * @option boolean debug_class_directory ECHO lots of debugging information regarding how class discovery differences things.
	 * @param unknown $file
	 * @return array
	 */
	private function include_file_classes($file) {
		/* This class *must* cache if called more than once - include files usually aren't included more than once,
		 * so issue remains when 2nd go-round, no new classes are declared */
		$run_file = avalue($this->incs, $file);
		if (is_array($run_file)) {
			return $run_file;
		}
		$run_file = array();
		$classes = array_flip(get_declared_classes());
		$debug_class_discovery = $this->option_bool("debug_class_discovery");
		if ($debug_class_discovery) {
			ksort($classes);
			echo "# Class discovery: Declared classes\n";
			echo implode("", ArrayTools::wrap(array_keys($classes), "- `", "`\n"));
		}
		require_once $file;
		if ($debug_class_discovery) {
			echo "# Class discovery: Found classes\n";
		}
		$after_classes = get_declared_classes();
		sort($after_classes);
		foreach ($after_classes as $new_class) {
			if (!array_key_exists($new_class, $classes) && is_subclass_of($new_class, self::TEST_UNIT_CLASS)) {
				$run_file[] = $new_class;
				if ($debug_class_discovery) {
					echo "- `$new_class`\n";
				}
			} else {
				if ($debug_class_discovery) {
					echo "- `$new_class` (not " . self::TEST_UNIT_CLASS . ")\n";
				}
			}
		}
		$this->incs[$file] = $run_file;
		return $run_file;
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

	private function setup_test_options(array $options) {
		$modules = to_list(avalue($options, "module", array()));
		if (count($modules)) {
			$this->log("Preloading modules {modules}", array(
				"modules" => $modules,
			));
			$this->application->modules->load($modules);
		}
	}

	/**
	 * Load a PHP file which contains a subclass of zesk\Test_Unit
	 *
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function run_test_php($file, array $options) {
		// Inherit from global options (overwrites only if does NOT exist in $options)
		$options += $this->options;
		$this->setup_test_options($options);

		try {
			$run_classes = $this->include_file_classes($file);
		} catch (Exception $e) {
			$this->log("Unable to include $file without error {class} {message} - fail.", array(
				"class" => get_class($e),
				"message" => $e->getMessage(),
			));
			return false;
		}
		if (count($run_classes) === 0) {
			$this->log("Unable to find any {name} classes in $file - skipping", array(
				"name" => self::TEST_UNIT_CLASS,
			));
			return true;
		}
		if ($this->_determine_sandbox($options)) {
			if (!$this->_determine_sandbox()) {
				$this->verbose_log("Running $file in sandbox mode. (Override)");
			}
			return $this->_run_test_sandbox($file, first($run_classes), $options);
		}

		$this->verbose_log("Running $file in no-sandbox mode (Override).");
		return $this->run_test_classes($run_classes, $options);
	}

	/**
	 *
	 * @param array $run_classes
	 * @param array $options
	 * @return boolean
	 */
	private function run_test_classes(array $run_classes, array $options) {
		$final_result = true;

		try {
			foreach ($run_classes as $class) {
				$object = null;
				$result = Test_Unit::run_one_class($this->application, $class, $options, $object);
				if ($object instanceof Test_Unit) {
					if ($this->stats === null) {
						$this->stats = $object->stats;
					} else {
						$this->stats = ArrayTools::sum($this->stats, $object->stats);
					}
				}
				if (!$result) {
					$final_result = false;
				}
			}
		} catch (\Exception $e) {
			$this->stats['fail']++;
			$this->error("Exception {message} at {file}:{line}", Exception::exception_variables($e));
			$final_result = false;
		}
		return $final_result;
	}

	private function _run_sandbox_command() {
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
		$env = $this->application->paths->which('env');
		$php = $this->application->paths->which('php');
		if (!$env) {
			if (!$php) {
				throw new Exception_Configuration("PATH", "No env or php found in path, is PATH invalid?\n" . implode("\n\t", $this->application->paths->command()) . "\n");
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

	/**
	 * After including a test file, return the first instanceof Test_Unit found.
	 *
	 * @param string $file
	 * @return \zesk\Test_Unit|mixed|array|unknown|NULL
	 */
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
	 * Pass loader globals along to subprocess so we work when invoked via command-line configuration only
	 *
	 * @return string
	 */
	private function _pass_along_loader_options() {
		$loader = $this->application->objects->singleton("zesk\\Command_Loader");
		if (!$loader) {
			return "";
		}
		$context = $loader->context();
		if (count($context) === 0) {
			return "";
		}
		$result = array();
		foreach ($context as $name => $value) {
			$result[] = "--set " . unquote(escapeshellarg($name)) . "=" . unquote(escapeshellarg($value));
		}
		return implode(" ", $result) . " ";
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
		$options['prefix'] = $command . " " . ZESK_ROOT . "bin/zesk-command.php --search \"" . $this->application->path() . "\" ";
		if (is_file($this->config)) {
			$options['prefix'] .= '--config \'' . addslashes($this->config) . '\' ';
		}
		$options['prefix'] .= $this->_pass_along_loader_options();
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
		$options['suffix'] = " module test eval $opts 'zesk\\Command_Test::run_file(\$application, \"$file\")'";
		$options['command'] = "{prefix}{suffix}";

		foreach ($options as $k => $v) {
			$options[$k] = tr($v, array(
				"\\" => "___",
			));
		}
		return $this->_run_test_command($file, $options);
	}

	/**
	 * Glue to run sandbox tests from the site.
	 *
	 * Sets the include path correctly, then runs the class in Test_Unit contained within automatically
	 *
	 * @param string $class
	 * @param string $file
	 * @return boolean
	 */
	public static function run_file(Application $application, $file) {
		self::_initialize_test_environment($application);

		$command = new self($application, array(
			__FILE__,
		), $application->command->option());
		$options = self::load_test_options(File::contents($file));
		$options['no_sandbox'] = true;
		if ($command->run_test_php($file, $options) === true) {
			return;
		}

		throw new Exception("{file} failed", array(
			"file" => $file,
		));
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
			echo " # ";
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
			'fail' => 0,
			'missing' => 0,
		);
		$fails = $missing = array();
		$first = null;
		$last = null;
		foreach ($this->test_results as $test => $value) {
			if (!is_file($test)) {
				$stats['missing']++;
				$missing[] = $test;
			} elseif ($value === false) {
				$stats['fail']++;
				$fails[] = $test;
			} else {
				$first = $first === null ? $value : min($first, $value);
				$last = $last === null ? $value : max($last, $value);
				$stats['pass']++;
			}
		}
		$stats['first'] = $first ? Timestamp::factory($first)->format() : null;
		$stats['last'] = $last ? Timestamp::factory($last)->format() : null;
		$stats['failing_tests'] = $fails;
		if (count($missing) > 0) {
			$stats['missing_tests'] = $missing;
		}
		$this->render_format($stats);
	}
}
