<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 */
abstract class Command extends Hookable implements Logger\Handler, Interface_Prompt {
	/**
	 *
	 * @var integer
	 */
	protected $wordwrap = 120;

	/**
	 * Application running this command
	 *
	 * @var Application
	 */
	public Application $application;

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
	private $arguments = [];

	/**
	 * errors encountered during command processing.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Does the terminal support ANSI colors?
	 *
	 * @var array
	 */
	protected bool $ansi = false;

	/**
	 *
	 * @var string
	 */
	public const ANSI_ESCAPE = "\033[";

	/**
	 *
	 * @var array
	 */
	public static $ansi_styles = [
		'emergency' => '31;31m',
		'critical' => '31;31m',
		'error' => '31;31m',
		'warning' => '40;33m',
		'success' => '0;32m',
		'info' => '33;33m',
		'debug' => '37;40m',
		'reset' => '0m',
	];

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
	protected bool $debug = false;

	/**
	 * Current state of the argument parsing.
	 * Should be modified by subclasses when parsing custom arguments
	 *
	 * @var array
	 */
	protected array $argv;

	/**
	 * Array of character => option name
	 *
	 * Aliases for regular options
	 *
	 * @var array
	 */
	protected array $option_chars = [];

	/**
	 * Array of option name => option type
	 *
	 * @var array
	 */
	protected array $option_types = [];

	/**
	 * Array of option name => option default value
	 *
	 * @var array
	 */
	protected array $option_defaults = [];

	/**
	 * Array of option name => value as passed and parsed on the command line
	 *
	 * @var array
	 */
	protected array $option_values = [];

	/**
	 * Array of option name => option help string
	 *
	 * @var array
	 */
	protected array $option_help = [];

	/**
	 * File name of the configuration file for this command (if any)
	 *
	 * @var string
	 */
	protected ?string $config = null;

	/**
	 * Configuration for this command (if any)
	 *
	 * @var array
	 */
	protected array $configuration = [];

	/**
	 * Running commands (currently)
	 *
	 * @var array of Command
	 */
	public static array $commands = [];

	/**
	 * Path for the history file for ->prompt (set in subclasses to keep history)
	 *
	 * @var string
	 */
	protected ?string $history_file_path = null;

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
	protected array $completions = [];

	/**
	 * Load these modules prior to running command
	 *
	 * $var array
	 */
	protected array $load_modules = [];

	/**
	 *
	 * @var array
	 */
	protected array $register_classes = [];

	/**
	 * Create a new Command.
	 * Command line arguments can be passed in. If null, uses command-line arguments from
	 * superglobals.
	 *
	 * @param array $argv
	 */
	public function __construct(Application $application, $argv = null, array $options = []) {
		parent::__construct($application, $options);

		$argv ??= $_SERVER['argv'] ?? null;

		$this->option_types = $this->optFormat();
		$this->option_defaults = $this->optDefaults();
		$this->option_help = $this->optHelp();

		$this->initialize();

		$this->setOptions($this->parse_option_defaults($this->option_defaults));

		if (is_array($argv) || $application->console()) {
			$this->program = array_shift($argv);
			$this->arguments = $argv;
			$this->argv = $argv;
		} else {
			$this->program = $_SERVER['PHP_SELF'] ?? null;
			$this->arguments = $_REQUEST;
			foreach ($this->arguments as $k => $v) {
				$this->argv[] = "--$k=$v";
			}
		}

		$this->determine_ansi();

		$this->application->register_class($this->register_classes);

		$this->_parse_options();

		if ($this->debug) {
			$application->logger->debug('{class}({args})', [
				'class' => get_class($this),
				'args' => var_export($argv, true),
			]);
		}

		if ($this->has_errors()) {
			//$this->usage();
			exit(1);
		}
	}

	/**
	 * Optionally configure the application upon run
	 */
	protected function application_configure(): void {
		$application = $this->application;
		$logger = $application->logger;
		/* @var $command_object Command */
		if (!$this->has_configuration) {
			$logger->debug('Command {class} does not have configuration, calling {app}->configured()', [
				'class' => get_class($this),
				'app' => get_class($application),
			]);
			if (!$application->configured()) {
				$logger->debug('Command {class} {app} WAS ALREADY CONFIGURED!!!!', [
					'class' => get_class($this),
					'app' => get_class($application),
				]);
			}
		} else {
			$logger->debug('Command {class} has configuration, skipping configured call', [
				'class' => get_class($this),
			]);
		}
	}

	/**
	 *
	 * @return string[]|NULL[]
	 */
	private function configuration_path() {
		$paths[] = '/etc/zesk';
		$paths = [];
		if (is_dir(($path = $this->application->path('etc')))) {
			$paths[] = $path;
		}
		$uid_path = $this->application->paths->uid();
		if ($uid_path) {
			$paths[] = $uid_path;
		}
		return $paths;
	}

	/**
	 * Load command options from a configuration file.
	 *
	 * KMD 2015-01-30 Changed semantics of default file to use to be the most
	 *
	 * @param string $name
	 *            basename of configuration file
	 * @param boolean $create
	 *            Create a blank file if it doesn't exist
	 * @return array List of files and a default file
	 */
	private function _configuration_config($name) {
		$file = File::name_clean(strtolower($name));
		$suffixes = [
			"$file.conf",
			"$file.json",
		];
		$paths = $this->configuration_path();
		$files = [];
		foreach ($paths as $path) {
			foreach ($suffixes as $suffix) {
				$files[] = path($path, $suffix);
			}
		}

		try {
			$default = File::find_first(array_reverse($files));
		} catch (Exception_NotFound) {
			$default = last($files);
		}
		$result = [
			'files' => $files,
			'default' => $default,
		];
		if (empty($default)) {
			$result['default'] = path(first($paths), $file);
		}
		return $result;
	}

	/**
	 * Retrieve the full path of the default configuration file, using user and system configuration
	 * paths.
	 *
	 * @param string $name
	 *            Name of the configuration file we're looking for (e.g. update)
	 * @return string First path found, or null if not found
	 */
	public function default_configuration_file($name) {
		$path = $this->configuration_path();
		return File::find_first($path, $name . '.conf');
	}

	/**
	 * Load global values which affect the operation of this command
	 */
	protected function hook_construct(): void {
		$this->debug = $this->option('debug', $this->debug);
	}

	/**
	 * Load a configuration file for this command
	 *
	 * @param string $name
	 *            Configuration file name to use (either /etc/zesk/$name.conf or ~/.zesk/$name.conf)
	 * @return string LAST configuration file path
	 */
	protected function configure($name, $create = false) {
		$configure_options = $this->_configuration_config($name);
		$this->config = $filename = $configure_options['default'];
		if ($this->optionBool('no-config')) {
			$this->verbose_log('Configuration file {name} not loaded due to --no-config option', [
				'name' => $name,
			]);
			return $filename;
		}
		if (empty($filename)) {
			throw new Exception_Parameter('No configuration file name for {name}', [
				'name' => $name,
				'create' => $create,
			]);
		}

		// Load global include
		$app = $this->application;
		$app->configureInclude($configure_options['files']);
		$app->reconfigure();

		try {
			$this->inheritConfiguration();
		} catch (Exception_Lock $e) {
			// Noop
		}

		$exists = file_exists($filename);
		if ($exists || $create) {
			if ($exists) {
				$this->verbose_log('Loading {name} configuration from {config}', [
					'name' => $name,
					'config' => $filename,
				]);
			} else {
				$this->write_default_configuration($name, $filename);
			}
			$this->debug = $this->optionBool('debug', $this->debug);
		}
		$app->configured();
		return $filename;
	}

	/**
	 * Write the default configuration for this command (as requested with $create = true)
	 * @param unknown $name
	 * @param unknown $filename
	 */
	protected function write_default_configuration(string $name, string $filename): void {
		if (!is_writable(dirname($filename))) {
			$this->error('Can not write {name} configuration file ({filename}) - directory is not writable', compact('name', 'filename'));
		} else {
			$this->verbose_log('Creating {name} configuration file ({filename})', [
				'name' => $name,
				'filename' => $filename,
			]);
			$extension = File::extension($filename);
			if ($extension === 'conf') {
				file_put_contents($filename, "# Created $name on " . date('Y-m-d H:i:s') . " at $filename\n");
			} elseif ($extension === 'json') {
				file_put_contents($filename, JSON::encode([
					get_class($this) => [
						'configuration_file' => [
							'created' => date('Y-m-d H:i:s'),
							'file' => $filename,
							'name' => $name,
						],
					],
				]));
			} else {
				$this->error('Can not write {name} configuration file ({filename}) - unknown file type {extension}', compact('name', 'filename', 'extension'));
			}
		}
	}

	/**
	 * Save new configuration settings in file
	 *
	 * @param string $name
	 *            Configuration file
	 * @param array $edits
	 * @return
	 *
	 * @throws Exception_File_NotFound
	 */
	protected function configure_edit($name, array $edits) {
		$config = $this->default_configuration_file($name);
		if (!$config) {
			throw new Exception_File_NotFound('Configuration {name} not found', [
				'name' => $name,
			]);
		}
		$contents = File::contents($config);
		$editor = Configuration_Parser::factory(File::extension($config), '')->editor($contents);
		return File::put($config, $editor->edit($edits));
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		return PHP::dump(array_merge([
			$this->program,
		], $this->arguments));
	}

	/**
	 */
	protected function initialize(): void {
	}

	/**
	 * Old-school way to supply options
	 */
	final protected function optHelp() {
		return $this->option_help;
	}

	/**
	 * Old-school way to supply options
	 */
	final protected function optFormat() {
		return $this->option_types;
	}

	final protected function optDefaults() {
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
			case 'dir':
				return 'This option is followed by a path';
			case 'dir+':
			case 'dir[]':
				return 'This option is followed by a path, and may be specified multiple times';
			case 'file':
				return 'This option is followed by a file path';
			case 'file[]':
				return 'This option is followed by a file path, and may be specified multiple times';
			case 'string':
				return 'This option is followed by a single string';
			case 'string*':
			case 'string[]':
				return 'This option is followed by a single string, may be specified more than once.';
			case 'boolean':
				return 'This presence of this option turns this feature on.';
			case 'list':
				return 'This option is followed by a list.';
			case 'integer':
				return 'This option is followed by a integer value';
			case 'real':
				return 'This option is followed by a decimal value';
			case 'date':
				return 'This option is followed by a date value';
			case 'datetime':
				return 'This option is followed by a date value';
			case 'time':
				return 'This option is followed by a time value';
		}
		return "Unkown type: $type";
	}

	/**
	 * Output the usage information
	 *
	 * @param string $message
	 */
	public function usage(array|string $message = null, array $arguments = []): void {
		$max_length = 0;
		$types = [];
		$commands = [];
		$aliases = ArrayTools::valuesFlipAppend(ArrayTools::prefixKeys($this->option_chars, '-'));
		foreach ($this->option_types as $k => $type) {
			$cmd = "--$k" . ArrayTools::joinPrefix($aliases[$k] ?? [], '|');
			switch ($type) {
				case 'dir':
				case 'dir+':
				case 'dir[]':
					$cmd .= ' dir';

					break;
				case 'string':
					$cmd .= ' string';

					break;
				case 'string[]':
				case 'string*':
					$cmd .= ' string';

					break;
				case 'list':
					$cmd .= ' item1;item2;...';

					break;
				case 'integer':
					$cmd .= ' number';

					break;
				case 'real':
					$cmd .= ' real-number';

					break;
				case 'path':
					$cmd .= ' path';

					break;
				case 'file':
				case 'file[]':
					$cmd .= ' file';

					break;
				case 'boolean':
					break;
				default:
					$cmd .= " $type";

					break;
			}
			if ($k == '*' || $k == '+') {
				$cmd = '...';
			}
			$max_length = max($max_length, strlen($cmd));
			$commands[$k] = $cmd;
			$types[$type] = true;
		}
		if ($message) {
			if (is_array($message)) {
				$message = implode("\n", $message);
			}
			$result[] = wordwrap($message, $this->wordwrap, "\n");
			$result[] = '';
		}
		$result[] = 'Usage: ' . $this->program;
		$result[] = '';
		if (!$this->help) {
			$this->help = $this->docCommentHelp();
		}
		if ($this->help) {
			$result[] = wordwrap($this->help, $this->wordwrap, "\n");
			$result[] = '';
		}

		$max_length += 4;
		$wrap_len = $this->wordwrap - $max_length - 1;
		foreach ($commands as $k => $cmd) {
			$help = explode("\n", wordwrap($this->option_help[$k] ?? $this->default_help($this->option_types[$k]), $wrap_len, "\n"));
			$help = implode("\n" . str_repeat(' ', $max_length + 1), $help);
			$result[] = $cmd . str_repeat(' ', $max_length - strlen($cmd) + 1) . $help;
		}
		foreach (array_keys($types) as $type) {
			switch ($type) {
				case 'list':
					$result[] = '';
					$result[] = 'Lists are delimited by semicolons: item1;item2;item3';

					break;
			}
		}
		$this->error($result);
		exit(($message === null) ? 0 : 1);
	}

	/**
	 * Did errors occur?
	 *
	 * @return boolean
	 */
	public function has_errors() {
		return count($this->errors) !== 0;
	}

	/**
	 * Return the errors
	 *
	 * @return array
	 */
	public function errors() {
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
			$newk = self::_optionKey($k);
			switch (strtolower($t)) {
				case 'boolean':
					$options[$newk] = toBool($options[$k] ?? false);

					break;
				default:
					$v = $options[$k] ?? null;
					if ($v !== null) {
						$options[$newk] = $v;
					}

					break;
			}
		}
		return $options;
	}

	/**
	 *
	 * @var boolean[severity]
	 */
	private static $severity_is_error = [
		'emergency' => true,
		'alert' => true,
		'critical' => true,
		'error' => true,
	];

	/**
	 * Log a message to output or stderr.
	 * Do not do anything if a theme is currently being rendered.
	 *
	 * @param string|array $message
	 * @param array $arguments
	 */
	public function log(mixed $message, array $arguments = []): void {
		if ($this->application->theme_current() !== null) {
			return;
		}
		if (is_array($message)) {
			if (ArrayTools::isList($message)) {
				foreach ($message as $m) {
					$this->logline($m, $arguments);
				}
				return;
			}
			$message = Text::format_pairs($message);
		} else {
			$message = strval($message);
		}
		$this->logline($message, $arguments);
	}

	/**
	 * Log a single line to stderr or stdout
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	private function logline($message, array $arguments = []): void {
		$newline = toBool($arguments['newline'] ?? true);
		$message = rtrim(map($message, $arguments));
		$suffix = '';
		if ($newline) {
			if (strlen($message) == 0 || $message[strlen($message) - 1] !== "\n") {
				$suffix = "\n";
			}
		}
		$prefix = '';
		$severity = strtolower($arguments['_severity'] ?? $arguments['severity'] ?? 'none');
		if ($severity && !$this->ansi) {
			$prefix = strtoupper($severity) . ': ';
		}
		if ($this->hasOption('prefix')) {
			$prefix .= $this->option('prefix') . ' ';
		}
		if ($this->hasOption('suffix')) {
			$suffix = ' ' . $this->option('suffix') . $suffix;
		}
		[$prefix, $suffix] = $this->ansi_annotate($prefix, $suffix, $severity);
		if (isset(self::$severity_is_error[$severity])) {
			fwrite(self::stderr(), $prefix . $message . $suffix);
			$this->errors[] = $message;
		} else {
			echo $prefix . implode("\n" . str_repeat(' ', strlen($prefix)), explode("\n", $message)) . $suffix;
			flush();
		}
	}

	/**
	 * Is the terminal an ANSI terminal?
	 */
	private function determine_ansi(): void {
		if ($this->optionBool('no-ansi')) {
			$this->ansi = false;
		} elseif ($this->optionBool('ansi')) {
			$this->ansi = true;
		} else {
			// On Windows, enable ANSI for ANSICON and ConEmu only
			$this->ansi = is_windows() ? (false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI')) : function_exists('posix_isatty') && posix_isatty(1);
		}
	}

	/**
	 * Annotate a prefix/suffix using ansi colors based on the message severity
	 *
	 * @param string $prefix
	 * @param string $suffix
	 * @param string $severity
	 *
	 * @return array[2] of [$new_prefix, $new_suffix]
	 */
	private function ansi_annotate($prefix, $suffix, $severity = 'info') {
		if (!$this->ansi || !array_key_exists($severity, self::$ansi_styles)) {
			return [
				$prefix,
				$suffix,
			];
		}
		$prefix = self::ANSI_ESCAPE . self::$ansi_styles[$severity] . $prefix;
		$suffix = explode("\n", $suffix);
		$suffix = implode(self::ANSI_ESCAPE . self::$ansi_styles['reset'] . "\n", $suffix);
		return [
			$prefix,
			$suffix,
		];
	}

	/**
	 *
	 * @return string|NULL|resource
	 */
	private static function stderr() {
		if (defined('STDERR')) {
			return STDERR;
		}
		static $stderr = null;
		if ($stderr !== null) {
			return $stderr;
		}
		$stderr = fopen('php://stderr', 'wb');
		return $stderr;
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	public function error($message, array $arguments = []): void {
		if (!$message) {
			return;
		}
		$this->log($message, [
				'severity' => 'error',
			] + $arguments);
	}

	/**
	 * Debug message, only when debugging is turned on
	 *
	 * @param string $message
	 */
	protected function debug_log($message, array $arguments = []): void {
		if ($this->optionBool('debug') || $this->debug) {
			$this->log($message, $arguments);
		}
	}

	/**
	 * Log messages to the logger at $level
	 *
	 * @param string $message
	 * @param array $arguments
	 * @param int $level
	 */
	public function verbose_log($message, array $arguments = []): void {
		if ($this->optionBool('verbose')) {
			$this->log($message, $arguments);
		}
	}

	/**
	 * Peek at the next argument to be processed
	 *
	 * @return string null
	 */
	protected function peek_arg() {
		return $this->argv[0] ?? null;
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
		$result = $this->argv;
		if ($clean) {
			$this->argv = [];
		}
		return $result;
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
	 *            Argument name
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
	private function _parse_options(): void {
		$this->argv = $this->arguments;
		$optional_arguments = isset($this->option_types['*']);
		$eatExtras = isset($this->option_types['+']) || $optional_arguments;

		$option_values = [];
		while (($arg = array_shift($this->argv)) !== null) {
			if (is_array($arg)) {
				$this->setOptions($arg);

				continue;
			}
			if (substr($arg, 0, 1) == '-') {
				$saveArg = $arg;
				if (strlen($arg) === 1) {
					break;
				}
				if ($arg[1] == '-') {
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
							array_unshift($this->argv, '-' . $arg[$i]);
						}

						continue;
					} else {
						// Convert to a named argument
						$arg = $this->option_chars[$arg] ?? null;
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
					case 'boolean':
						$option_values[$arg] = true;
						$this->setOption($arg, !$this->optionBool($arg));
						$this->debug_log("Set $arg to " . ($this->optionBool($arg) ? 'ON' : 'off'));

						break;
					case 'string':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->setOption($arg, $param);
							$this->debug_log("Set $arg to \"$param\"");
						}
						break;
					case 'string[]':
					case 'string*':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->optionAppend($arg, $param);
							$this->debug_log("Added \"$arg\" to \"$param\"");
						}
						break;
					case 'integer':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_numeric($param)) {
								$this->error("Integer argument \"$saveArg\" not followed by number");
							} else {
								$param = intval($param);
								$option_values[$arg] = true;
								$this->setOption($arg, $param);
								$this->debug_log("Set $arg to $param");
							}
						}
						break;
					case 'list':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$this->setOption($arg, to_list($param, []));
							$this->debug_log("Set $arg to list: $param");
						}
						break;
					case 'dir':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_dir($param)) {
								$this->error("Argument \"--$arg $param\" is not a directory.");
							} else {
								$option_values[$arg] = true;
								$this->setOption($arg, $param);
								$this->debug_log("Set directory $arg to $param");
							}
						}
						break;
					case 'dir+':
					case 'dir[]':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!is_dir($param)) {
								$this->error("Argument \"--$arg $param\" is not a directory.");
							} else {
								$option_values[$arg] = true;
								$this->optionAppend($arg, $param);
								$this->debug_log("Added direcory $arg to list: $param");
							}
						}
						break;
					case 'file':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!$this->validate_file($param)) {
								$this->error("Argument \"--$arg $param\" is not a file or link.");
							} else {
								$option_values[$arg] = true;
								$this->setOption($arg, $param);
								$this->debug_log("Set file $arg to file: $param");
							}
						}

						break;
					case 'file+':
					case 'file[]':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							if (!$this->validate_file($param)) {
								$this->error("Argument \"--$arg $param\" is not a file.");
							} else {
								$option_values[$arg] = true;
								$this->optionAppend($arg, $param);
								$this->debug_log("Added file $arg to list: $param");
							}
						}

						break;
					case 'datetime':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$param = $this->arg_to_DateTime($param);
							$this->setOption($arg, $param);
							$this->debug_log("Added datetime $arg: $param");
						}

						break;
					case 'date':
						$param = $this->get_arg($arg);
						if ($param !== null) {
							$option_values[$arg] = true;
							$param = $this->arg_to_Date($param);
							$this->setOption($arg, $param);
							$this->debug_log("Added date $arg: $param");
						}

						break;
					default:
						if (!$this->parse_argument($arg, $this->option_types[$arg])) {
							$this->error('Unknown argument type ' . $this->option_types[$arg]);
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
					$this->error('No arguments supplied');
				}
			}
		} elseif (count($this->argv) !== 0 && !$optional_arguments) {
			if ($this->optionBool('error_unhandled_arguments')) {
				$this->error('Unhandled arguments starting at ' . $this->argv[0]);
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

	/**
	 * Does the current PHP installation have the readline utility installed?
	 *
	 * @return boolean
	 */
	private static function has_readline() {
		return function_exists('readline');
	}

	/**
	 * Handle managing history and history file, initialization of history file
	 *
	 * @return void
	 */
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
			foreach (File::lines($this->history_file_path) as $line) {
				readline_add_history($line);
			}
		}
		$this->history_file = fopen($this->history_file_path, 'ab');
	}

	/**
	 * Function which may be overridden by subclasses to return a list of possible completions for the current readline request
	 *
	 * @return string[]
	 */
	public function default_completion_function() {
		return $this->completions;
	}

	/**
	 * Set the readline completion function
	 *
	 * @param callable $function
	 */
	protected function completion_function($function = null): void {
		if ($this->has_readline()) {
			if ($function === null) {
				$function = __CLASS__ . '::default_completion_function';
			}
			readline_completion_function($function);
		}
	}

	/**
	 * Read a line from standard in
	 *
	 * @param string $prompt
	 * @return ?string
	 */
	public function readline(string $prompt): ?string {
		if ($this->has_readline()) {
			$result = readline($prompt);
			if ($result === false) {
				echo "\rexit " . str_repeat(' ', 80) . "\n";
				return 'exit';
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
	 * @param string|null $default
	 * @param array|null $completions
	 * @return string
	 * @throws Exception_Command
	 */
	public function prompt(string $message, string $default = null, array $completions = null): string {
		$this->_init_history();
		if ($completions) {
			$this->completions = $completions;
		}
		while (true) {
			$prompt = rtrim($message) . ' ';
			if ($default) {
				$prompt .= "(default: $default) ";
			}
			$result = $this->readline($prompt);
			if ($result === 'quit' || $result === 'exit') {
				break;
			}
			if (is_string($result)) {
				return $result;
			}
			if ($default !== null) {
				return $default;
			}
		}

		throw new Exception_Command('Exited.');
	}

	/**
	 * Prompt yes or no
	 *
	 * @param string $message
	 * @param boolean $default
	 * @return boolean
	 */
	public function prompt_yes_no(string $message, bool $default = true): bool {
		if ($this->optionBool('yes')) {
			return true;
		}
		if ($this->optionBool('no')) {
			return false;
		}
		do {
			echo rtrim($message) . ' ' . ($default === null ? '(y/n)' : ($default ? '(Y/n)' : '(y/N)')) . ' ';
			$this->completions = ($default === null ? [
				'yes',
				'no',
			] : ($default ? [
				'yes',
				'no',
			] : [
				'no',
				'yes',
			]));
			$result = trim(fgets(STDIN));
			$result = ($result === '') ? $default : toBool($result, null);
		} while ($result === null);
		return $result;
	}

	/**
	 * Execute a shell command - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command
	 * @return array
	 * @throws Exception_Command
	 */
	public function exec(string $command): array {
		$args = func_get_args();
		array_shift($args);
		if (count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}
		return $this->application->process->execute_arguments($command, $args);
	}

	/**
	 * Run a zesk command using the CLI
	 *
	 * @param string $command
	 * @param array $arguments
	 */
	protected function zesk_cli(string $command, array $arguments = []): array {
		$app = $this->application;
		$bin = $app->zeskHome('bin/zesk.sh');
		return $app->process->execute_arguments("$bin --search {app_root} $command", [
				'app_root' => $app->path(),
			] + $arguments);
	}

	/**
	 * Execute a shell command and output to STDOUT - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command
	 * @return array
	 * @throws Exception_Command
	 */
	protected function passthru(string $command): array {
		$args = func_get_args();
		array_shift($args);
		return $this->application->process->execute_arguments($command, $args, true);
	}

	/**
	 * Main entry point for running a command
	 *
	 * @return numeric
	 */
	final public function go(): int {
		self::$commands[] = $this;
		$this->application->modules->load($this->load_modules);
		// Moved from Command_Loader
		$this->application_configure();

		$this->call_hook('run_before');

		if ($this->has_errors()) {
			$this->usage();
		}

		try {
			$result = $this->run();
		} catch (Exception_File_NotFound $e) {
			$this->error('File not found {filename}', $e->variables());
		} catch (\Exception $e) {
			$this->error("Exception thrown by command {class} : {exception_class} {message}\n{backtrace}", [
				'class' => get_class($this),
				'exception_class' => get_class($e),
				'message' => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
				'backtrace-4' => Text::head($e->getTraceAsString(), 6),
			]);
			$this->application->hooks->call('exception', $e);
			if ($this->optionBool('debug', $this->application->development())) {
				$this->error($e->getTraceAsString());
			}
			$code = intval($e->getCode());
			return ($code === 0) ? -1 : $code;
		}
		$result = $this->call_hook_arguments('run_after', [
			$result,
		], $result);
		if ($result === true) {
			$result = 0;
		} elseif ($result === false) {
			$result = -1;
		} elseif ($result === null) {
			$result = 0;
		}
		assert(count(self::$commands) > 0);
		array_pop(self::$commands);
		return $result;
	}

	/**
	 * Is a command running? (Any command, not just this one)
	 *
	 * @return Command
	 */
	public static function running(): self {
		return last(self::$commands);
	}

	/**
	 *
	 * @param string|array $content
	 * @param ?string $format
	 * @param string $default_format
	 * @return boolean
	 */
	public function render_format(string|array $content, string $format = null, string $default_format = 'text'): bool {
		if ($format === null) {
			$format = $this->option('format', $default_format);
		}
		switch ($format) {
			case 'html':
				echo $this->application->theme('dl', $content);

				break;
			case 'php':
				echo PHP::dump($content);

				break;
			case 'serialize':
				echo serialize($content);

				break;
			case 'json':
				echo JSON::encode_pretty($content);

				break;
			case 'text':
				echo Text::format_pairs($content);

				break;
			default:
				$this->error('Unknown format: {format}', [
					'format' => $format,
				]);
				return false;
		}
		return true;
	}

	/**
	 * Add help from the doccomment.
	 * One place for docs is preferred.
	 *
	 * @return string
	 */
	private function docCommentHelp(): string {
		$reflection_class = new \ReflectionClass(get_class($this));
		$comment = $reflection_class->getDocComment();
		$parsed = DocComment::instance($comment)->variables();
		return implode("\n", array_filter([
			$parsed['desc'] ?? null,
			$parsed['description'] ?? null,
		]));
	}

	/**
	 * Is a terminal?
	 *
	 * @return boolean
	 */
	public function isTerminal(): bool {
		return $this->ansi;
	}

	/**
	 * Validate a file parameter
	 *
	 * @param string $file
	 * @return boolean
	 */
	public function validate_file(string $file): bool {
		return is_file($file) || is_link($file);
	}

	/**
	 * Main run code
	 * @throws Exception_File_NotFound
	 */
	abstract protected function run();
}
