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
	private $zesk_loaded = false;

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

		$_ZESK['zesk']['command'] = true; // TODO Is this actually looked at anywere?
		$_ZESK['zesk\application']['configure_options']['skip_configured'] = true; // TODO confirm this now used

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

		$argv = $_SERVER['argv'];
		assert('is_array($argv)');
		$argv = $this->fix_zend_studio_arguments($argv);
		$argv = $this->argument_sugar($argv);
		$this->command = array_shift($argv);

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
		 * We can preset globals using the $_ZESK global. Only possible issue between using
		 * $_ZESK is that the structure should match here and in classes/zesk.inc
		 *
		 * Commands process and handle arguments after the command.
		 *
		 * Each command handles its own arguments itself.
		 */
		$first_command = null;
		$wait_set = array();
		$wait_configs = array();
		while (count($argv) > 0) {
			$arg = array_shift($argv);
			if (substr($arg, 0, 2) === '--') {
				$func = "handle_" . substr($arg, 2);
				if (method_exists($this, $func)) {
					$argv = $this->$func($argv);
					continue;
				}
				array_unshift($argv, substr($arg, 2));
				$argv = $this->handle_set($argv);
				continue;
			}
			if (!class_exists('zesk\\Kernel', false)) {
				$first_command = $this->find_application();
				if (substr($first_command, -4) === ".inc") {
					$new_first_command = substr($first_command, 0, -4) . ".php";
					$this->error("Application files ending in .inc is deprecated in Zesk 0.9.0 as of 2017-03-01, do:\n\n\tmv $first_command $new_first_command\n\n");
				}
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
			$argv = $this->run_command($arg, $argv);
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
		$stderr = fopen("php://stderr", "a");
		return $stderr;
	}

	/**
	 * Run a command
	 *
	 * @param string $arg
	 * @param array $argv
	 * @return array
	 */
	public function run_command($arg, array $argv) {
		$application = $this->application;
		$command = avalue($this->aliases, $arg, $arg);
		$command = strtr($command, array(
			"_" => "/",
			"-" => "/"
		));
		list($class, $path) = $this->find_command_class($command);
		if (!$class) {
			return $argv;
		}
		if (!class_exists($class, false)) {
			$this->error("Command class $class does not exist in $path ... skipping\n");
			return $argv;
		}
		/* @var $command_object Command */
		$command_object = $application->objects->factory($class, $application, array_merge(array(
			$arg
		), $argv), array(
			"debug" => $this->debug
		));

		/* @var $command_object Command */
		if (!$command_object->has_configuration) {
			$this->debug("Command {class} does not have configuration, calling {app} configured", array(
				"class" => $class,
				"app" => get_class($application)
			));
			if (!$application->configured()) {
				$this->debug("Command {class} {app} WAS ALREADY CONFIGURED!!!!", array(
					"class" => $class,
					"app" => get_class($application)
				));
			}
		} else {
			$this->debug("Command {class} has configuration, skipping configured call", array(
				"class" => $class
			));
		}
		$result = $command_object->go();
		$argv = $command_object->arguments_remaining();
		$this->debug("Remaining class arguments: " . json_encode($argv));
		if ($result !== 0 && $result !== null) {
			$this->debug("Command $class returned $result");
		} else {
			$result = 0;
		}
		if ($result !== 0 || count($argv) === 0) {
			exit($result);
		}
		return $argv;
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
			"/" => "_"
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
					 * @deprecated 2017-013
					 */
					$new_file = File::extension_change($file, "php");
					$this->error("Command files ending with .inc are deprecated in Zesk 0.9.0, please rename\n\n\tmv $file $new_file\n\nto use the .php extension\n");
				}
				require_once $file;
				return array(
					$prefix . $class,
					$file
				);
			}
		}
		$this->debug("Search path: \n\t{paths}", array(
			"paths" => implode("\n\t", ArrayTools::suffix(array_keys($paths), "/$command.inc"))
		));
		$this->error("Ignoring command $command - not found\n");
		return array(
			null,
			null
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
	 * and then fake out a real $argv.
	 *
	 * For debugging only.
	 *
	 * @param array $argv
	 * @return array New argv
	 */
	private function fix_zend_studio_arguments(array $argv) {
		if (PHP_SAPI === 'cli') {
			foreach ($argv as $index => $arg) {
				$argv[$index] = urldecode($arg);
			}
			return $argv;
		}
		if (count($argv) === 1 && array_key_exists(0, $argv)) {
			$qs_argv = $argv[0];
		}
		$qs_argv = explode("&", $qs_argv);
		$argv = array(
			__FILE__
		);
		$found = false;
		foreach ($qs_argv as $arg) {
			if ($found || (substr($arg, 0, 1) === "-") || (strpos($arg, "=")) === false) {
				$argv[] = $arg;
				$found = true;
			}
		}
		return $argv;
	}

	/**
	 * Provide some syntactic sugar for input arguments, converting ___ to \
	 *
	 * @param array $argv
	 * @return string
	 */
	private function argument_sugar(array $argv) {
		foreach ($argv as $index => $arg) {
			$argv[$index] = strtr($arg, array(
				"___" => "\\"
			));
		}
		return $argv;
	}
	/**
	 * Find the application file from the CWD or the search directory
	 *
	 * @return string
	 */
	private function find_application() {
		global $_ZESK;
		if (count($this->search) === 0) {
			$this->search[] = getcwd();
		}
		foreach (array(
			$_ZESK,
			$_SERVER
		) as $super) {
			$zesk_root_files = array_key_exists('zesk_root_files', $super) ? $super['zesk_root_files'] : null;
			if ($zesk_root_files) {
				break;
			}
		}
		if (!$zesk_root_files) {
			$zesk_root_files = "*.application.php *.application.inc";
		}
		$zesk_root_files = explode(" ", $zesk_root_files);
		foreach ($this->search as $dir) {
			while (!empty($dir)) {
				foreach ($zesk_root_files as $zesk_root_file) {
					$found = glob(rtrim($dir, '/') . "/$zesk_root_file");
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
		$this->usage("No zesk " . implode(", ", $zesk_root_files) . " found in: " . implode(", ", $this->search));
		return null;
	}

	/**
	 *
	 * @param string $arg
	 * @return boolean
	 */
	private function zesk_loaded($arg = null) {
		if ($this->zesk_loaded) {
			return true;
		}
		if (!$this->zesk_is_loaded()) {
			if ($arg === null) {
				return false;
			}
			$this->usage("Zesk not initialized correctly.\n\n    $arg\n\nmust contain reference to:\n\n    require_once '" . ZESK_ROOT . "zesk.inc';\n\n");
		}
		$this->zesk_loaded = true;
		$zesk = zesk();
		$zesk->autoloader->path(ZESK_ROOT . 'command', array(
			"class_prefix" => "zesk\\Command",
			"lower" => true
		));
		$app = $zesk->application();
		$this->aliases = array();
		$loader = new Configuration_Loader(array(
			$app->path("etc/command-aliases.json"),
			$app->zesk_root("etc/command-aliases.json")
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
	 * @param array $argv
	 * @return array
	 */
	private function handle_set(array $argv) {
		$pair = array_shift($argv);
		if ($pair === null) {
			$this->usage("--set missing argument");
		}

		list($key, $value) = explode("=", $pair, 2) + array(
			null,
			true
		);
		if ($key === "debug") {
			$this->debug = true;
		}
		if ($this->zesk_is_loaded()) {
			$this->debug("Set global $key to $value");
			zesk()->configuration->path_set($key, $value);
		} else {
			global $_ZESK;
			$key = _zesk_global_key($key);
			$this->global_context[implode(ZESK_GLOBAL_KEY_SEPARATOR, $key)] = $value;
			\apath_set($_ZESK, $key, $value, ZESK_GLOBAL_KEY_SEPARATOR);
			$this->debug("Set global " . implode(ZESK_GLOBAL_KEY_SEPARATOR, $key) . " to $value");
		}
		return $argv;
	}

	/**
	 * Handle --unset
	 *
	 * Consumes one additional argument of form name=value
	 *
	 * @param array $argv
	 * @return array
	 */
	private function handle_unset(array $argv) {
		$key = array_shift($argv);
		if ($key === null) {
			$this->usage("--unset missing argument");
		}
		if ($this->zesk_is_loaded()) {
			zesk()->configuration->path_set($key, null);
		} else {
			global $_ZESK;
			$key = _zesk_global_key($key);
			$this->global_context[implode(ZESK_GLOBAL_KEY_SEPARATOR, $key)] = null;
			\apath_set($_ZESK, $key, null, ZESK_GLOBAL_KEY_SEPARATOR);
		}
		return $argv;
	}

	/**
	 * Handle --cd
	 *
	 * @param array $argv
	 * @return aray
	 */
	private function handle_cd(array $argv) {
		$arg = array_shift($argv);
		if ($arg === null) {
			$this->usage("--cd missing argument");
		}
		if (!is_dir($arg) && !is_link($arg)) {
			$this->usage("$arg is not a directory to --cd to");
		}
		chdir($arg);
		return $argv;
	}

	/**
	 * Handle --define
	 *
	 * @param array $argv
	 * @return aray
	 */
	private function handle_define(array $argv) {
		$arg = array_shift($argv);
		if ($arg === null) {
			$this->usage("--cd missing argument");
		}
		list($name, $value) = explode("=", $arg, 2) + array(
			$arg,
			true
		);
		if (!defined($name)) {
			define($name, $value);
		} else {
			$this->error("$name command line definition is already defined");
		}
		return $argv;
	}

	/**
	 * Handle --search
	 *
	 * @param array $argv
	 * @return array
	 */
	private function handle_search(array $argv) {
		$arg = array_shift($argv);
		if ($arg === null) {
			$this->usage("--search missing argument");
		}
		if (!is_dir($arg)) {
			$this->usage("$arg is not a directory to --search from");
		}
		$this->search[] = $arg;
		if (class_exists('zesk\Kernel')) {
			zesk()->logger->warning("--search is ignored - zesk application is already loeded");
		}
		return $argv;
	}

	/**
	 * Handle --config
	 *
	 * @param array $argv
	 * @return array
	 */
	private function handle_config(array $argv) {
		$arg = array_shift($argv);
		if ($arg === null) {
			$this->usage("--config missing argument");
		}
		if (!is_file($arg)) {
			$this->usage("$arg is not a file to load configuration");
		}
		if ($this->zesk_is_loaded()) {
			/* @var $zesk \zesk\Kernel */
			$this->debug("Loading configuration file {file}", array(
				"file" => $arg
			));
			$this->application->loader->load_one($arg);
		} else {
			$this->wait_configs[] = $arg;
			$this->debug("Loading configuration file {file} (queued)", array(
				"file" => $arg
			));
		}
		return $argv;
	}
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
	 */
	private function debug($message, array $context = array()) {
		if ($this->debug) {
			$context = self::wrap_brackets($context);
			echo __CLASS__ . " " . rtrim(strtr($message, $context), "\n") . "\n";
		}
	}
}
