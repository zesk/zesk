<?php

/**
 *
 */
namespace zesk;

/**
 * Loads a Zesk Command from the command-line
 *
 * @author kent
 */
class Command_Loader {
	/**
	 * Search these paths to find application
	 *
	 * @var array
	 */
	private $search = array();

	/**
	 * Main command run
	 *
	 * @var string
	 */
	private $command = null;

	/**
	 * Was Zesk loaded?
	 *
	 * @var string
	 */
	private $is_loaded = false;

	/**
	 * List of config files to load after loading application
	 *
	 * @var array
	 */
	private $wait_configs = array();

	/**
	 * Command alaises
	 *
	 * @var array
	 */
	private $aliases = array();

	/**
	 *
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * Collect command-line context
	 *
	 * @var array
	 */
	private $global_context = array();

	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var string
	 */
	const configure_options = 'application::configure_options';

	/**
	 * Set up PHP basics so we can detect errors while testing, etc.
	 */
	public function __construct() {
		global $_ZESK;

		if (!is_array($_ZESK)) {
			$_ZESK = array();
		}

		$_ZESK['zesk\application']['configure_options']['skip_configured'] = true; // Is honored 2018-03-10 KMD

		ini_set('error_prepend_string', "\nPHP-ERROR " . str_repeat("=", 80) . "\n");
		ini_set('error_append_string', "\n" . str_repeat("*", 80) . "\n");
	}

	/**
	 * Create instance
	 *
	 * @return self
	 */
	public static function factory() {
		return new self();
	}

	/**
	 *
	 * @return array
	 */
	public function context() {
		return $this->global_context;
	}

	/**
	 * Run the command.
	 * Main entry point into this class after initialization, normally.
	 */
	public function run() {
		if (!array_key_exists('argv', $_SERVER)) {
			die('No argv key in $_SERVER\n');
		}

		$args = $_SERVER['argv'];
		assert(is_array($args));
		$args = $this->fix_zend_studio_arguments($args);
		$args = $this->argument_sugar($args);
		$this->command = array_shift($args);

		/*
		 * Main comand loop. Handle parameters
		 *
		 * --set name=value
		 * --set name
		 * --unset name
		 * --config file Load config file
		 * --cd directory
		 * --anyname=anyvalue
		 * file
		 * command
		 *
		 * For a file parameter, it's included.
		 *
		 * Once ZESK_ROOT is defined, commands are allowed.
		 *
		 * We can preset globals using the $_ZESK global which is used once at
		 * startup and then discarded.
		 *
		 * Commands process and handle arguments after the command.
		 *
		 * Each command handles its own arguments itself.
		 */
		$first_command = null;
		while (count($args) > 0) {
			$arg = array_shift($args);
			if (substr($arg, 0, 2) === '--') {
				$func = "handle_" . substr($arg, 2);
				if (method_exists($this, $func)) {
					$args = $this->$func($args);

					continue;
				}
				array_unshift($args, substr($arg, 2));
				$args = $this->handle_set($args);

				continue;
			}
			if (!class_exists('zesk\\Kernel', false)) {
				$first_command = $this->find_application();

				require_once $first_command;
				$this->application = $this->zesk_loaded($first_command);
				$this->application->console(true);
				if ($this->application->configuration->debug || $this->debug) {
					$this->debug = true;
				}
				$this->debug("Loaded application file $first_command\n");
				$this->application->objects->singleton($this);
			}
			if (substr($arg, 0, 1) === '/' && is_file($arg)) {
				require_once $arg;

				continue;
			}
			$args = $this->run_command($arg, $args);
		}
		return 0;
	}

	/**
	 *
	 * @param string $message
	 * @return number
	 */
	private function error($message) {
		return fprintf($this->stderr(), $message);
	}

	/**
	 * Determine the STDERR file
	 *
	 * @return string|unknown|resource
	 */
	private function stderr() {
		if (defined("STDERR")) {
			return STDERR;
		}
		static $stderr;
		if ($stderr) {
			return $stderr;
		}
		$stderr = fopen("php://stderr", "ab");
		return $stderr;
	}

	/**
	 * Run a command
	 *
	 * @param string $arg
	 * @param array $args
	 * @return array
	 */
	public function run_command($arg, array $args) {
		$application = $this->application;
		$command = avalue($this->aliases, $arg, $arg);
		$command = strtr($command, array(
			"_" => "/",
			"-" => "/",
		));
		list($class, $path) = $this->find_command_class($command);
		if (!$class) {
			return $args;
		}
		if (!class_exists($class, false)) {
			$this->error("Command class $class does not exist in $path ... skipping\n");
			return $args;
		}
		/* @var $command_object Command */
		$command_object = $application->objects->factory($class, $application, array_merge(array(
			$arg,
		), $args), array(
			"debug" => $this->debug,
		));
		$application->command($command_object);

		$result = $command_object->go();

		$args = $command_object->arguments_remaining();
		$this->debug("Remaining class arguments: " . JSON::encode($args));
		if ($result !== 0 && $result !== null) {
			$this->debug("Command $class returned $result");
		} else {
			$result = 0;
		}
		if ($result !== 0 || count($args) === 0) {
			exit($result);
		}
		return $args;
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $command
	 * @return string[]|NULL[]
	 */
	private function find_command_class($command) {
		$paths = $this->application->zesk_command_path();
		$class = strtr($command, array(
			"/" => "_",
		));
		$try_files = array();
		foreach ($paths as $path => $prefix) {
			$try_files[path($path, $command . ".inc")] = $prefix; // DEPRECATED TODO 2017-03
			$try_files[path($path, strtolower($command) . ".php")] = $prefix;
			$try_files[path($path, ucfirst($command) . ".php")] = $prefix;
			$try_files[path($path, strtoupper($command) . ".php")] = $prefix;
		}
		foreach ($try_files as $file => $prefix) {
			if (is_file($file)) {
				if (File::extension($file) === "inc") {
					/**
					 *
					 * @deprecated 2017-01
					 */
					$new_file = File::extension_change($file, "php");
					$this->error("Command files ending with .inc are deprecated in Zesk 0.9.0, please rename\n\n\tmv $file $new_file\n\nto use the .php extension\n");
				}
				require_once $file;
				return array(
					$prefix . $class,
					$file,
				);
			}
		}
		$this->debug("Search path: \n\t{paths}", array(
			"paths" => implode("\n\t", ArrayTools::suffix(array_keys($paths), "/$command.php")),
		));
		$this->error("Ignoring command $command - not found\n");
		return array(
			null,
			null,
		);
	}

	/**
	 * Show usage
	 *
	 * @param string $error
	 * @param number $exit_code
	 */
	private function usage($error = null, $exit_code = 1) {
		if ($error) {
			$message[] = $error;
			$message[] = "";
		}
		$message[] = 'Usage: ' . basename($this->command) . ' [ --set name=value ] command0 [ command1 command2 ... ] ';
		$message[] = '';
		$message[] = "Loads an application context, then runs a bunch of commands in order, optionally setting globals beforehand.";
		$message[] = "You can pass a --set name=value to set a zesk global at any point in the command";
		$message[] = "As well, --name=value does the same, doing --variable sets the value to true";
		$message[] = "Finally, --define name=value defines a name in the PHP scope, or --define name defines name to be true";

		fwrite(STDERR, implode("\n", $message) . "\n");
		exit($exit_code);
	}

	/**
	 * Handle running PHP commands via Zend Studio.
	 *
	 * Zend Studio places command-line arguments as part of the $_SERVER[argv] in a query
	 * string format, simply appending them like
	 *
	 * &arg0&arg1&arg2&arg3
	 *
	 * Zend Studio also strips all "quotes" away (oddly), and includes all of the debugging
	 * parameters beforehand.
	 *
	 * We scan for entries without a "=" (which will easily break many applications,
	 * unfortunately)
	 * and then fake out a real $args.
	 *
	 * For debugging only.
	 *
	 * @param array $args
	 * @return array New argv
	 */
	private function fix_zend_studio_arguments(array $args) {
		if (PHP_SAPI === 'cli') {
			foreach ($args as $index => $arg) {
				$args[$index] = rawurldecode($arg);
			}
			return $args;
		}
		if (count($args) === 1 && array_key_exists(0, $args)) {
			$qs_argv = $args[0];
		}
		$qs_argv = explode("&", $qs_argv);
		$args = array(
			__FILE__,
		);
		$found = false;
		foreach ($qs_argv as $arg) {
			if ($found || (substr($arg, 0, 1) === "-") || (strpos($arg, "=")) === false) {
				$args[] = $arg;
				$found = true;
			}
		}
		return $args;
	}

	/**
	 * Provide some syntactic sugar for input arguments, converting ___ to \
	 *
	 * @param array $args
	 * @return string
	 */
	private function argument_sugar(array $args) {
		foreach ($args as $index => $arg) {
			$args[$index] = strtr($arg, array(
				"___" => "\\",
			));
		}
		return $args;
	}

	/**
	 * Find the application file from the CWD or the search directory
	 *
	 * @return string
	 */
	public function find_application() {
		global $_ZESK;
		if (count($this->search) === 0) {
			$this->search[] = getcwd();
		}
		foreach (array(
			$_ZESK,
			$_SERVER,
		) as $super) {
			if (!is_array($super)) {
				continue;
			}
			$root_files = array_key_exists('zesk_root_files', $super) ? $super['zesk_root_files'] : null;
			if ($root_files) {
				break;
			}
		}
		if (!$root_files) {
			$root_files = "*.application.php";
		}
		$root_files = explode(" ", $root_files);
		foreach ($this->search as $dir) {
			while (!empty($dir)) {
				foreach ($root_files as $root_file) {
					$found = glob(rtrim($dir, '/') . "/$root_file");
					if (!is_array($found) || count($found) === 0) {
						continue;
					}
					sort($found);
					return $found[0];
				}
				$dir = dirname($dir);
				if ($dir === '/') {
					break;
				}
			}
		}
		$this->usage("No zesk " . implode(", ", $root_files) . " found in: " . implode(", ", $this->search));
		return null;
	}

	/**
	 *
	 * @param string $arg
	 * @return Application
	 */
	private function zesk_loaded($arg = null) {
		if ($this->is_loaded) {
			return $this->application;
		}
		if (!$this->zesk_is_loaded()) {
			if ($arg === null) {
				return null;
			}
			$this->usage("Zesk not initialized correctly.\n\n    $arg\n\nmust contain reference to:\n\n    require_once '" . ZESK_ROOT . "autoload.php';\n\n");
		}
		$this->is_loaded = true;
		$kernel = Kernel::singleton();
		$kernel->autoloader->path(ZESK_ROOT . 'command', array(
			"class_prefix" => "zesk\\Command",
			"lower" => true,
		));
		$app = $kernel->application();
		$this->aliases = array();
		$loader = new Configuration_Loader(array(
			$app->path("etc/command-aliases.json"),
			$app->zesk_home("etc/command-aliases.json"),
		), new Adapter_Settings_Array($this->aliases));
		$loader->load();
		if (count($this->wait_configs) > 0) {
			foreach ($this->wait_configs as $wait_config) {
				$this->debug("Loading $wait_config ...");
				$app->loader->load_one($wait_config);
			}
			$this->wait_configs = array();
		}
		return $app;
	}

	/**
	 *
	 * @return boolean
	 */
	private function zesk_is_loaded() {
		return class_exists('zesk\Kernel', false);
	}

	/**
	 * Handle --set
	 *
	 * Consumes one additional argument of form name=value
	 *
	 * @param array $args
	 * @return array
	 */
	private function handle_set(array $args) {
		$pair = array_shift($args);
		if ($pair === null) {
			$this->usage("--set missing argument");
		}

		list($key, $value) = explode("=", $pair, 2) + array(
			null,
			true,
		);
		if ($key === "debug") {
			$this->debug = true;
		}
		if ($this->zesk_is_loaded()) {
			$this->debug("Set global $key to $value");
			$this->application->configuration->path_set($key, $value);
		} else {
			global $_ZESK;
			$key = _zesk_global_key($key);
			$this->global_context[implode(ZESK_GLOBAL_KEY_SEPARATOR, $key)] = $value;
			\apath_set($_ZESK, $key, $value, ZESK_GLOBAL_KEY_SEPARATOR);
			$this->debug("Set global " . implode(ZESK_GLOBAL_KEY_SEPARATOR, $key) . " to $value");
		}
		return $args;
	}

	/**
	 * Handle --unset
	 *
	 * Consumes one additional argument of form name=value
	 *
	 * @param array $args
	 * @return array
	 */
	private function handle_unset(array $args) {
		$key = array_shift($args);
		if ($key === null) {
			$this->usage("--unset missing argument");
		}
		if ($this->zesk_is_loaded()) {
			$this->application->configuration->path_set($key, null);
		} else {
			global $_ZESK;
			$key = _zesk_global_key($key);
			$this->global_context[implode(ZESK_GLOBAL_KEY_SEPARATOR, $key)] = null;
			\apath_set($_ZESK, $key, null, ZESK_GLOBAL_KEY_SEPARATOR);
		}
		return $args;
	}

	/**
	 * Handle --cd
	 *
	 * @param array $args
	 * @return aray
	 */
	private function handle_cd(array $args) {
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage("--cd missing argument");
		}
		if (!is_dir($arg) && !is_link($arg)) {
			$this->usage("$arg is not a directory to --cd to");
		}
		chdir($arg);
		return $args;
	}

	/**
	 * Handle --define
	 *
	 * @param array $args
	 * @return aray
	 */
	private function handle_define(array $args) {
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage("--cd missing argument");
		}
		list($name, $value) = explode("=", $arg, 2) + array(
			$arg,
			true,
		);
		if (!defined($name)) {
			define($name, $value);
		} else {
			$this->error("$name command line definition is already defined");
		}
		return $args;
	}

	/**
	 * Handle --search
	 *
	 * @param array $args
	 * @return array
	 */
	private function handle_search(array $args) {
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage("--search missing argument");
		}
		if (!is_dir($arg)) {
			$this->usage("$arg is not a directory to --search from");
		}
		$this->search[] = $arg;
		if ($this->application) {
			$this->application->logger->warning("--search is ignored - zesk application is already loeded");
		}
		return $args;
	}

	/**
	 * Handle --config
	 *
	 * @param array $args
	 * @return array
	 */
	private function handle_config(array $args) {
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage("--config missing argument");
		}
		if (!is_file($arg)) {
			$this->usage("$arg is not a file to load configuration");
		}
		if ($this->zesk_is_loaded()) {
			/* @var $locale \zesk\Locale */
			$this->debug("Loading configuration file {file}", array(
				"file" => $arg,
			));
			$this->application->loader->load_one($arg);
		} else {
			$this->wait_configs[] = $arg;
			$this->debug("Loading configuration file {file} (queued)", array(
				"file" => $arg,
			));
		}
		return $args;
	}

	/**
	 *
	 * @param string[] $array
	 * @return string[]
	 */
	public static function wrap_brackets($array) {
		$result = array();
		foreach ($array as $k => $v) {
			$result['{' . $k . '}'] = $v;
		}
		return $result;
	}

	/**
	 * Output a debug message
	 *
	 * @param string $message
	 * @return void
	 */
	private function debug($message, array $context = array()) {
		if ($this->debug) {
			$context = self::wrap_brackets($context);
			echo __CLASS__ . " " . rtrim(strtr($message, $context), "\n") . "\n";
		}
	}
}
