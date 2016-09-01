<?php
use zesk\Timestamp as Timestamp;

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/command.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
abstract class Command extends Hookable {
	
	/**
	 * Application running this command
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public $has_configuration = false;
	
	/**
	 *
	 * @var string
	 */
	private $program;
	
	/**
	 * Original arguments passed to command, unchanged, unedited.
	 *
	 * @var array
	 */
	private $arguments = array();
	
	/**
	 * errors encountered during command processing.
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * Help string
	 *
	 * @var string
	 */
	protected $help = null;
	
	/**
	 * Debugging enabled for this command
	 *
	 * @var boolean
	 */
	protected $debug = false;
	
	/**
	 * Current state of the argument parsing.
	 * Should be modified by subclasses when parsing custom arguments
	 *
	 * @var array
	 */
	protected $argv;
	
	/**
	 * Array of character => option name
	 *
	 * Aliases for regular options
	 *
	 * @var array
	 */
	protected $option_chars = array();
	
	/**
	 * Array of option name => option type
	 *
	 * @var array
	 */
	protected $option_types = array();
	
	/**
	 * Array of option name => option default value
	 *
	 * @var array
	 */
	protected $option_defaults = array();
	
	/**
	 * Array of option name => value as passed and parsed on the command line
	 *
	 * @var array
	 */
	protected $option_values = array();
	
	/**
	 * Array of option name => option help string
	 *
	 * @var array
	 */
	protected $option_help = array();
	
	/**
	 * File name of the configuration file for this command (if any)
	 *
	 * @var string
	 */
	protected $config = null;
	
	/**
	 * Configuration for this command (if any)
	 *
	 * @var array
	 */
	protected $configuration = array();
	
	/**
	 * Running commands (currently)
	 *
	 * @var array of Command
	 */
	static $commands = array();
	
	/**
	 * Path for the history file for ->prompt (set in subclasses to keep history)
	 *
	 * @var string
	 */
	protected $history_file_path = null;
	
	/**
	 *
	 * @var resource
	 */
	private $history_file = null;
	
	/**
	 * Autocomplete possibilities - set before prompt for default behavior
	 *
	 * $var array
	 */
	protected $completions = array();
	
	/**
	 * Create a new Command.
	 * Command line arguments can be passed in. If null, uses command-line arguments from
	 * superglobals.
	 *
	 * @param array $argv        	
	 */
	function __construct($argv = null, array $options = array()) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		
		$this->application = Application::instance();
		
		if ($argv === null) {
			$argv = avalue($_SERVER, 'argv', null);
		}
		$this->option_types = $this->optFormat();
		$this->option_defaults = $this->optDefaults();
		$this->option_help = $this->optHelp();
		
		$defaults = $this->parse_option_defaults($this->option_defaults);
		
		parent::__construct($options);
		
		$this->set_option($defaults, null, false);
		
		if (is_array($argv) || zesk::$console) {
			$this->program = array_shift($argv);
			$this->arguments = $argv;
			$this->argv = $argv;
		} else {
			$this->program = avalue($_SERVER, 'PHP_SELF');
			$this->arguments = $_REQUEST;
			foreach ($this->arguments as $k => $v) {
				$this->argv[] = "--$k=$v";
			}
		}
		
		$this->initialize();
		
		$this->_parse_options();
		
		if ($this->debug) {
			log::debug("{class}({args})", array(
				"class" => get_class($this),
				"args" => var_export($argv, true)
			));
		}
		$zesk->console = true;
		$zesk->newline = "\n";
		
		if ($this->has_errors()) {
			$this->usage(implode("\n", $this->errors()));
			exit(1);
		}
	}
	
	/**
	 * 
	 * @return string[]|NULL[]
	 */
	private static function configuration_path() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$paths = array();
		if (is_dir(($path = $zesk->paths->application('etc')))) {
			$paths[] = $path;
		}
		$uid_path = $zesk->paths->uid();
		if ($uid_path) {
			$paths[] = $uid_path;
		}
		$paths[] = "/etc/zesk";
		return $paths;
	}
	
	/**
	 * Load command options from a configuration file.
	 *
	 * KMD 2015-01-30 Changed semantics of default file to use to be the most
	 *
	 * @param string $name
	 *        	basename of configuration file
	 * @param boolean $create
	 *        	Create a blank file if it doesn't exist
	 * @return string Path of configuration file
	 */
	private function _configuration_config($name) {
		$path = $this->configuration_path();
		$result = array(
			'path' => $path,
			'file' => $file = zesk\file::name_clean(strtolower($name)) . '.conf',
			'default' => $default = zesk::find_file($path, $file)
		);
		if (empty($default)) {
			$result['default'] = path(first($path), $file);
		}
		return $result;
	}
	
	/**
	 * Retrieve the full path of the default configuration file, using user and system configuration
	 * paths.
	 *
	 * @param string $name
	 *        	Name of the configuration file we're looking for (e.g. update)
	 * @return string First path found, or null if not found
	 */
	public static function default_configuration_file($name) {
		$path = self::configuration_path();
		return zesk::find_file($path, $name . ".conf");
	}
	
	/**
	 * Load global values which affect the operation of this command
	 */
	protected function hook_construct() {
		$this->debug = $this->option('debug', $this->debug);
	}
	
	/**
	 * Load a configuration file for this command
	 *
	 * @param string $name
	 *        	Configuration file name to use (either /etc/zesk/$name.conf or ~/.zesk/$name.conf)
	 * @return string LAST configuration file path
	 */
	protected function configure($name, $create = false) {
		$configure_options = $this->_configuration_config($name);
		// Load global include
		

		$this->application = $app = Application::instance();
		$app->configure_include($configure_options['file']);
		
		$this->configuration = $app->reconfigure();
		
		$this->inherit_global_options();
		$this->config = $config = $configure_options['default'];
		$config_settings = null;
		$exists = file_exists($config);
		if ($exists || $create) {
			if (empty($config)) {
				throw new Exception_Parameter("No configuration file name for {name}", array(
					"name" => $name,
					"create" => $create
				));
			}
			if ($exists) {
				$this->verbose_log("Loading {name} configuration from {config}", array(
					"name" => $name,
					"config" => $config
				));
			} else {
				if (!is_writable(dirname($config))) {
					$this->error("Can not write {name} configuration file ({config}) - directory is not writable", compact("name", "config"));
				} else {
					$this->verbose_log("Creating {name} configuration file ({config})", array(
						"name" => $name,
						"config" => $config
					));
					file_put_contents($config, "# Created $name on " . date('Y-m-d H:i:s') . "\n");
				}
			}
			$this->debug = $this->option_bool('debug', $this->debug);
			return $config;
		}
		// $this->options = $this->option_values;
		if ($this->option_bool('debug_config')) {
			$this->log("Loaded configuration:");
			$this->log($this->configuration);
		}
		$app->configured();
		return $config;
	}
	
	/**
	 * Save new configuration settings in file
	 *
	 * @param string $name
	 *        	Configuration file
	 * @param array $edits        	
	 * @throws Exception_File_NotFound
	 * @return
	 *
	 */
	protected function configure_edit($name, array $edits) {
		$config = $this->default_configuration_file($name);
		if (!$config) {
			throw new Exception_File_NotFound("Configuration {name} not found", array(
				"name" => $name
			));
		}
		return conf::edit($config, $edits);
	}
	function __toString() {
		return php::dump(array_merge(array(
			$this->program
		), $this->arguments));
	}
	protected function initialize() {
	}
	
	/**
	 * Old-school way to supply options
	 */
	protected function optHelp() {
		return $this->option_help;
	}
	
	/**
	 * Old-school way to supply options
	 */
	protected function optFormat() {
		return $this->option_types;
	}
	protected function optDefaults() {
		return $this->option_defaults;
	}
	protected function parse_argument($arg_name, $arg_type) {
		return false;
	}
	protected function arg_to_DateTime($arg) {
		if (!is_date($arg)) {
			$this->usage("Need to format like a date: $arg");
		}
		return Timestamp::factory($arg);
	}
	protected function arg_to_Date($arg) {
		if (!is_date($arg)) {
			$this->usage("Need to format like a date: $arg");
		}
		return new Date($arg);
	}
	
	/**
	 *
	 * @param unknown $type        	
	 * @return string
	 */
	private function default_help($type) {
		switch ($type) {
			case "dir":
				return "This option is followed by a path";
			case "dir+":
			case "dir[]":
				return "This option is followed by a path, and may be specified multiple times";
			case "file":
				return "This option is followed by a file path";
			case "file[]":
				return "This option is followed by a file path, and may be specified multiple times";
			case "string":
				return "This option is followed by a single string";
			case "string*":
			case "string[]":
				return "This option is followed by a single string, may be specified more than once.";
			case "boolean":
				return "This presence of this option turns this feature on.";
			case "list":
				return "This option is followed by a list.";
			case "integer":
				return "This option is followed by a integer value";
			case "real":
				return "This option is followed by a decimal value";
			case "date":
				return "This option is followed by a date value";
			case "datetime":
				return "This option is followed by a date value";
			case "time":
				return "This option is followed by a time value";
		}
		return "Unkown type: $type";
	}
	protected $wordwrap = 120;
	
	/**
	 * Output the usage information
	 *
	 * @param string $message        	
	 */
	function usage($message = null, array $arguments = array()) {
		if (is_array($message)) {
			$message = implode("\n", $message);
		}
		$message = map($message, $arguments);
		$maxlen = 0;
		$types = array();
		$commands = array();
		$aliases = arr::flip_multiple(arr::kprefix($this->option_chars, "-"));
		foreach ($this->option_types as $k => $type) {
			$cmd = "--$k" . arr::join_prefix(avalue($aliases, $k, array()), "|");
			switch ($type) {
				case "dir":
				case "dir+":
				case "dir[]":
					$cmd .= " dir";
					break;
				case "string":
					$cmd .= " string";
					break;
				case "string[]":
				case "string*":
					$cmd .= " string";
					break;
				case "list":
					$cmd .= " item1;item2;...";
					break;
				case "integer":
					$cmd .= " number";
					break;
				case "real":
					$cmd .= " real-number";
					break;
				case "path":
					$cmd .= " path";
					break;
				case "file":
				case "file[]":
					$cmd .= " file";
					break;
				case "boolean":
					break;
				default :
					$cmd .= " $type";
					break;
			}
			if ($k == "*" || $k == "+") {
				$cmd = "...";
			}
			$maxlen = max($maxlen, strlen($cmd));
			$commands[$k] = $cmd;
			$types[$type] = true;
		}
		if ($message) {
			$result[] = wordwrap($message, $this->wordwrap, "\n");
			$result[] = "";
		}
		$result[] = $this->program;
		$result[] = "";
		if ($this->help) {
			$result[] = wordwrap($this->help, $this->wordwrap, "\n");
			$result[] = "";
		}
		
		$maxlen += 4;
		$wrap_len = $this->wordwrap - $maxlen - 1;
		foreach ($commands as $k => $cmd) {
			$help = explode("\n", wordwrap(avalue($this->option_help, $k, $this->default_help($this->option_types[$k])), $wrap_len, "\n"));
			$help = implode("\n" . str_repeat(" ", $maxlen + 1), $help);
			$result[] = $cmd . str_repeat(" ", $maxlen - strlen($cmd) + 1) . $help;
		}
		foreach (array_keys($types) as $type) {
			switch ($type) {
				case "list":
					$result[] = "";
					$result[] = "Lists are delimited by semicolons: item1;item2;item3";
					break;
			}
		}
		$this->error(implode("\n", $result) . "\n");
		exit(($message === null) ? 0 : 1);
	}
	
	/**
	 * Did errors occur?
	 *
	 * @return boolean
	 */
	function has_errors() {
		return count($this->errors) !== 0;
	}
	
	/**
	 * Return the errors
	 *
	 * @return array
	 */
	function errors() {
		return $this->errors;
	}
	
	/**
	 * Parse the option default values
	 *
	 * @param string $options        	
	 * @return array
	 */
	private function parse_option_defaults($options = false) {
		foreach ($this->option_types as $k => $t) {
			$k = self::_option_key($k);
			switch (strtolower($t)) {
				case "boolean":
					$options[$k] = false;
					break;
				default :
					$v = avalue($options, $k);
					if ($v !== null) {
						$options[$k] = $v;
					}
					break;
			}
		}
		return $options;
	}
	
	/**
	 * Log a message to output
	 *
	 * @param string $message        	
	 * @param array $arguments        	
	 */
	public function log($message, array $arguments = array()) {
		$newline = to_bool(avalue($arguments, "newline", true));
		if (is_array($message)) {
			$message = text::format_pairs($message);
		} else {
			$message = strval($message);
		}
		$message = map($message, $arguments);
		if ($newline && $message && $message[strlen($message) - 1] !== "\n") {
			$message .= "\n";
		}
		echo $message;
	}
	private static function stderr() {
		if (defined("STDERR")) {
			return STDERR;
		}
		static $stderr = null;
		if ($stderr !== null) {
			return $stderr;
		}
		$stderr = fopen("php://stderr", "w");
		return $stderr;
	}
	/**
	 *
	 * @param string $message        	
	 * @param array $arguments        	
	 */
	function error($message, array $arguments = array()) {
		if (!$message) {
			return;
		}
		if ($message instanceof Exception) {
			$message = $message->__toString();
		} else {
			$message = map($message, $arguments);
		}
		fwrite(self::stderr(), "$message\n");
		$this->errors[] = $message;
	}
	
	/**
	 * Debug message, only when debugging is turned on
	 *
	 * @param string $message        	
	 */
	protected function debug_log($message, array $arguments = array()) {
		if ($this->option_bool('debug') || $this->debug) {
			$this->log($message, $arguments);
		}
	}
	
	/**
	 * Log messages to the logger at $level
	 *
	 * @param string $message        	
	 * @param array $arguments        	
	 * @param integer $level        	
	 */
	function verbose_log($message, array $arguments = array(), $level = log::NOTICE) {
		if ($this->option_bool('verbose')) {
			$this->log($message, $arguments);
		}
	}
	
	/**
	 * Peek at the next argument to be processed
	 *
	 * @return string null
	 */
	protected function peek_arg() {
		return avalue($this->argv, 0);
	}
	
	/**
	 * Return original arguments passed to this command (not affected by parsing, etc)
	 *
	 * @return multitype:
	 */
	public function arguments() {
		return $this->arguments;
	}
	
	/**
	 * Retrieve remaining arguments to be processed, optionally deleting them
	 *
	 * @param string $clean        	
	 * @return array
	 */
	public function arguments_remaining($clean = false) {
		$argv = $this->argv;
		if ($clean) {
			$this->argv = array();
		}
		return $argv;
	}
	
	/**
	 * Retrieve remaining arguments to be processed, optionally deleting them
	 *
	 * @param string $clean        	
	 * @return array
	 * @deprecated 2015-06-03 Tooltip works better for arguments_remaining
	 * @see self::arguments_remaining
	 */
	public function remaining_arguments($clean = false) {
		zesk::deprecated();
		return $this->arguments_remaining($clean);
	}
	
	/**
	 * Is there an argument waiting to be processed?
	 *
	 * @return boolean
	 */
	protected function has_arg() {
		return count($this->argv) > 0;
	}
	
	/**
	 * Assumes "has_arg()" is true
	 *
	 * @param string $arg
	 *        	Argument name
	 *        	
	 * @return string
	 */
	protected function get_arg($arg) {
		if (count($this->argv) === 0) {
			$this->error("No argument parameter for $arg");
		}
		return array_shift($this->argv);
	}
	
	/**
	 * Parse command-line options for this command
	 */
	private function _parse_options() {
		$this->argv = $this->arguments;
		$optional_arguments = isset($this->option_types["*"]);
		$eatExtras = isset($this->option_types["+"]) || $optional_arguments;
		
		$option_values = array();
		while (($arg = array_shift($this->argv)) !== null) {
			if (is_array($arg)) {
				$this->set_option($arg);
				continue;
			}
			if (substr($arg, 0, 1) == "-") {
				$saveArg = $arg;
				if (strlen($arg) === 1) {
					break;
				}
				if ($arg[1] == "-") {
					$arg = substr($arg, 2);
					if ($arg === false) {
						break;
					}
				} else {
					$arg = substr($arg, 1);
					$argl = strlen($arg);
					if ($argl > 1) {
						// Break -abcd into -a -b -c -d
						for ($i = 0; $i < strlen($arg); $i++) {
							array_unshift($this->argv, "-" . $arg[$i]);
						}
						continue;
					} else {
						// Convert to a named argument
						$arg = avalue($this->option_chars, $arg);
					}
				}
				if (!array_key_exists($arg, $this->option_types)) {
					$this->usage("Unknown argument: $saveArg");
					array_unshift($this->argv, $saveArg);
					break;
				}
				
				$format = $this->option_types[$arg];
				$this->debug_log("Found arg \"$saveArg\" with format \"$format\"");
				switch (strtolower($this->option_types[$arg])) {
					case "boolean":
						$option_values[$arg] = true;
						$this->set_option($arg, !$this->option_bool($arg));
						$this->debug_log("Set $arg to " . ($this->option_bool($arg) ? "ON" : "off"));
						break;
					case "string":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->set_option($arg, $param);
							$this->debug_log("Set $arg to \"$param\"");
						}
						break;
					case "string[]":
					case "string*":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->option_append_list($arg, $param);
							$this->debug_log("Added $arg to \"$param\"");
						}
						break;
					case "integer":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_numeric($param)) {
								$this->error("Integer argument $saveArg not followed by number");
							} else {
								$param = intval($param);
								$option_values[$arg] = true;
								$this->set_option($arg, $param);
								$this->debug_log("Set $arg to $param");
							}
						}
						break;
					case "list":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->set_option($arg, to_list($param, array()));
							$this->debug_log("Set $arg to list: $param");
						}
						break;
					case "dir":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_dir($param)) {
								$this->error("Argument $arg $param is not a directory.");
							} else {
								$option_values[$arg] = true;
								$this->set_option($arg, $param);
								$this->debug_log("Set directory $arg to $param");
							}
						}
						break;
					case "dir+":
					case "dir[]":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_dir($param)) {
								$this->error("Argument $arg $param is not a directory.");
							} else {
								$option_values[$arg] = true;
								$this->option_append_list($arg, $param);
								$this->debug_log("Added direcory $arg to list: $param");
							}
						}
						break;
					case "file":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_file($param)) {
								$this->error("Argument $arg $param is not a file.");
							} else {
								$option_values[$arg] = true;
								$this->set_option($arg, $param);
								$this->debug_log("Set file $arg to file: $param");
							}
						}
						break;
					case "file+":
					case "file[]":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_file($param)) {
								$this->error("Argument $arg $param is not a file.");
							} else {
								$option_values[$arg] = true;
								$this->option_append_list($arg, $param);
								$this->debug_log("Added file $arg to list: $param");
							}
						}
						break;
					case "datetime":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$param = $this->arg_to_DateTime($param);
							$this->set_option($arg, $param);
							$this->debug_log("Added datetime $arg: $param");
						}
						break;
					case "date":
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$param = $this->arg_to_Date($param);
							$this->set_option($arg, $param);
							$this->debug_log("Added date $arg: $param");
						}
						break;
					default :
						if (!$this->parse_argument($arg, $this->option_types[$arg])) {
							$this->error("Unknown argument type " . $this->option_types[$arg]);
						}
						break;
				}
			} else {
				$this->debug_log("Stopping parsing at $arg (not a switch, shifting back into stack)");
				array_unshift($this->argv, $arg);
				break;
			}
		}
		
		if ($eatExtras) {
			if (count($this->argv) === 0) {
				if (!$optional_arguments) {
					$this->error("No arguments supplied");
				}
			}
		} else if (count($this->argv) !== 0 && !$optional_arguments) {
			if ($this->option_bool("error_unhandled_arguments")) {
				$this->error("Unhandled arguments starting at " . $this->argv[0]);
			}
		}
		
		$this->option_values = $this->options_include(array_keys($option_values));
	}
	
	/**
	 * Quote a variable for use in the shell
	 *
	 * @param string $var        	
	 * @return string
	 */
	public static function shell_quote($var) {
		return '"' . str_replace('"', '\"', $var) . '"';
	}
	private static function has_readline() {
		return function_exists('readline');
	}
	private function _init_history() {
		if ($this->history_file !== null) {
			// Have history file and is open for writing
			return null;
		}
		if ($this->history_file_path === null) {
			// No history file specified, no-op
			return null;
		}
		if (is_file($this->history_file_path) && $this->has_readline()) {
			foreach (zesk\file::lines($this->history_file_path) as $line) {
				readline_add_history($line);
			}
		}
		$this->history_file = fopen($this->history_file_path, "a");
	}
	public function default_completion_function() {
		return $this->completions;
	}
	protected function completion_function($function = null) {
		if ($this->has_readline()) {
			if ($function === null) {
				$function = __CLASS__ . "::default_completion_function";
			}
			readline_completion_function($function);
		}
	}
	public function readline($prompt, $default = null) {
		if ($this->has_readline()) {
			$result = readline($prompt);
			if ($result === false) {
				echo "\rexit " . str_repeat(" ", 80) . "\n";
				return null;
			}
			if (!empty($result)) {
				readline_add_history($result);
			}
		} else {
			echo $prompt;
			$result = fgets(STDIN);
			if (feof(STDIN)) {
				return null;
			}
		}
		$command = rtrim($result, "\n\r");
		if (empty($command)) {
			return $command;
		}
		if ($this->history_file) {
			fwrite($this->history_file, $command . "\n");
		}
		return $command;
	}
	
	/**
	 * Prompt for arbitrary input
	 *
	 * @param string $message        	
	 * @param string $default        	
	 * @return string NULL
	 */
	public function prompt($message, $default = null) {
		$this->_init_history();
		while (true) {
			$prompt = rtrim($message) . " ";
			if ($default) {
				$prompt .= "(default: $default) ";
			}
			$result = $this->readline($prompt);
			if ($result === "quit" || $result === "exit") {
				break;
			}
			if ($result === null) {
				return $default;
			}
			if ($result !== "" && $result !== null & $result !== false) {
				return $result;
			}
			if ($default !== null) {
				return $default;
			}
		}
		return null;
	}
	
	/**
	 * Prompt yes or no
	 *
	 * @param string $message        	
	 * @param boolean $default        	
	 * @return boolean
	 */
	public function prompt_yes_no($message, $default = true) {
		if ($this->option_bool("yes")) {
			return true;
		}
		if ($this->option_bool("no")) {
			return false;
		}
		do {
			echo rtrim($message) . " " . ($default === null ? "(y/n)" : ($default ? "(Y/n)" : "(y/N)")) . " ";
			$this->completions = ($default === null ? array(
				"yes",
				"no"
			) : ($default ? array(
				"yes",
				"no"
			) : array(
				"no",
				"yes"
			)));
			$result = trim(fgets(STDIN));
			$result = ($result === "") ? $default : to_bool($result, null);
		} while ($result === null);
		return $result;
	}
	
	/**
	 * Execute a shell command - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command        	
	 * @throws Exception_Command
	 * @return array
	 */
	protected function exec($command) {
		$args = func_get_args();
		array_shift($args);
		if (count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}
		return zesk::execute_array($command, $args);
	}
	
	/**
	 * Execute a shell command and output to STDOUT - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command        	
	 * @throws Exception_Command
	 * @return array
	 */
	protected function passthru($command) {
		$args = func_get_args();
		array_shift($args);
		return zesk::execute_array($command, $args, true);
	}
	
	/**
	 * Main entry point for running a command
	 *
	 * @return numeric
	 */
	final function go() {
		self::$commands[] = $this;
		$this->hook("run_before");
		if ($this->has_errors()) {
			$this->usage();
		}
		try {
			$result = $this->run();
		} catch (Exception $e) {
			global $zesk;
			$this->error("Exception thrown by command {class} : {exception_class} {message}\n{backtrace}", array(
				"class" => get_class($this),
				"exception_class" => get_class($e),
				"message" => $e->getMessage(),
				"backtrace" => $e->getTraceAsString()
			));
			$zesk->hooks->call("exception", $e);
			if ($this->option_bool('debug', $this->application->development())) {
				$this->error($e->getTraceAsString());
			}
			$code = intval($e->getCode());
			return ($code === 0) ? -1 : $code;
		}
		$result = $this->hook_array("run_after", array(
			$result
		), $result);
		if ($result === true) {
			$result = 0;
		} else if ($result === false) {
			$result = -1;
		}
		assert(count(self::$commands) > 0);
		array_pop(self::$commands);
		return $result;
	}
	
	/**
	 * Is a command running?
	 *
	 * @return Command
	 */
	public static function running() {
		return last(self::$commands);
	}
	public function render_format($content, $format = null, $default_format = "text") {
		if ($format === null) {
			$format = $this->option('format', $default_format);
		}
		switch ($format) {
			case "html":
				echo theme("dl", $content);
				return;
			case "php":
				echo php::dump($content);
				return;
			case "serialize":
				echo serialize($content);
				return;
			case "json":
				echo json_encode($content, JSON_PRETTY_PRINT);
				return;
			case "text":
				echo text::format_pairs($content);
				break;
			default :
				$this->error("Unknown format: {format}", array(
					"format" => $format
				));
				return false;
		}
		return true;
	}
	/**
	 * Main run code
	 */
	abstract protected function run();
}

