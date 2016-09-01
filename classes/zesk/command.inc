<?php

/**
 * Run zesk from the command line
 *
 * @author kent
 */
class Zesk_Command {
	
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
	const configure_options = 'application::configure_options';
	
	/**
	 * Set up PHP basics so we can detect errors while testing, etc.
	 */
	public function __construct() {
		global $_ZESK;
		
		if (!is_array($_ZESK)) {
			$_ZESK = array();
		}
		
		$_ZESK['zesk']['command'] = true;
		$_ZESK['application']['configure_options']['skip_configured'] = true;
		
		ini_set('error_prepend_string', "\nPHP-ERROR " . str_repeat("=", 80) . "\n");
		ini_set('error_append_string', "\n" . str_repeat("*", 80) . "\n");
	}
	
	/**
	 * Create instance
	 *
	 * @return Zesk_Command
	 */
	public static function instance() {
		return new self();
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
			if (!class_exists('zesk', false)) {
				$first_command = $this->find_application();
				require_once $first_command;
				$this->zesk_loaded($first_command);
				if (zesk()->configuration->debug) {
					$this->debug("Loaded application file $first_command\n");
				}
			}
			if (substr($arg, 0, 1) === '/' && is_file($arg)) {
				require_once $arg;
				continue;
			}
			$argv = $this->run_command($arg, $argv);
		}
		return 0;
	}
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$command = avalue($this->aliases, $arg, $arg);
		$command = strtr($command, array(
			"_" => "/",
			"-" => "/"
		));
		$debug = $zesk->configuration->debug;
		$find_command = zesk::find_file(zesk::zesk_command_path(), "$command.inc");
		if ($find_command === null) {
			if ($debug) {
				$this->debug("Search path: \n\t{paths}", array(
					"paths" => implode("\n\t", arr::suffix($zesk->paths->zesk_command(), "/$command.inc"))
				));
			}
			fprintf($this->stderr(), "Ignoring command $arg - not found\n");
			return $argv;
		}
		require_once $find_command;
		$func = zesk::clean_function('zesk_command_' . $command);
		$class = zesk::clean_function("Command_" . $command);
		if (class_exists($class, false)) {
			$command_object = $zesk->objects->factory($class, array_merge(array(
				$arg
			), $argv));
			/* @var $command_object Command */
			$application = Application::instance();
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
			if ($debug) {
				$this->debug("Remaining class arguments: " . json_encode($argv));
			}
			if ($result !== 0 && $result !== null) {
				$this->debug("Command $class returned $result");
			} else {
				$result = 0;
			}
			exit($result);
		} else if (function_exists($func)) {
			Application::instance()->configured();
			$result = $func(array_merge($argv));
			if (is_array($result)) {
				$argv = $result;
			}
			if ($debug) {
				$this->debug("Remaining function arguments: " . json_encode($argv));
			}
		} else {
			fprintf($this->stderr(), "Command $func and class $class does not exist in $find_command ... skipping\n");
		}
		return $argv;
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
			$zesk_root_files = "*.application.inc";
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
		if (!class_exists('zesk', false)) {
			if ($arg === null) {
				return false;
			}
			$this->usage("Zesk not initialized correctly.\n\n    $arg\n\nmust contain reference to:\n\n    require_once '" . ZESK_ROOT . "zesk.inc';\n\n");
		}
		$this->zesk_loaded = true;
		zesk::autoload_path(zesk::root('command'), array(
			"class_prefix" => "command"
		));
		$this->aliases = conf::load_inherit("command-aliases.conf", Application::instance()->configuration_path());
		if (count($this->wait_configs) > 0) {
			foreach ($this->wait_configs as $wait_config) {
				$this->debug("Loading $wait_config ...");
				zesk::set(conf::load($wait_config));
			}
			$this->wait_configs = array();
		}
		return true;
	}
	
	/**
	 * Convert global syntax
	 *
	 * @see zesk::normalize_global_key - it should match this
	 * @param string $key        	
	 * @return string
	 */
	static function normalize_global_key($key) {
		return strtr(strtolower($key), array(
			"__" => "::",
			"." => "_",
			"/" => "_",
			"-" => "_",
			" " => "_"
		));
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
		if (class_exists('zesk')) {
			zesk::set($key, $value);
		} else {
			global $_ZESK;
			apath_set($_ZESK, _zesk_global_key($key), $value, ZESK_GLOBAL_KEY_SEPARATOR);
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
		if (class_exists('zesk')) {
			zesk::set($key, null);
		} else {
			global $_ZESK;
			apath_set($_ZESK, _zesk_global_key($key), null, ZESK_GLOBAL_KEY_SEPARATOR);
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
		if (!is_dir($arg)) {
			$this->usage("$arg is not a directory to cd to");
		}
		chdir($arg);
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
			$this->usage("--cd missing argument");
		}
		if (!is_dir($arg)) {
			$this->usage("$arg is not a directory to cd to");
		}
		$this->search[] = $arg;
		if (class_exists('zesk')) {
			log::warning("--search is ignored - zesk application is already loeded");
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
		if (class_exists('zesk')) {
			zesk::set(conf::load($arg));
		} else {
			$this->wait_configs[] = $arg;
		}
		return $argv;
	}
	
	/**
	 * Output a debug message
	 * 
	 * @param string $message
	 */
	private function debug($message, array $context = array()) {
		echo rtrim(map($message, $context), "\n") . "\n";
	}
}
