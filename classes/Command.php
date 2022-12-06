<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use resource;

/**
 *
 * @author kent
 */
abstract class Command extends Hookable implements Logger\Handler, Interface_Prompt {
	/**
	 * Success
	 */
	public const EXIT_CODE_SUCCESS = 0;

	/**
	 * Environment requirements are not met (including PHP build)
	 */
	public const EXIT_CODE_ENVIRONMENT = 2;

	/**
	 * When the command can not run due to improper, incomplete, or missing arguments
	 */
	public const EXIT_CODE_ARGUMENTS = 2;

	/**
	 * Where to wrap the help lines
	 *
	 * @var integer
	 */
	protected int $wordwrap = 120;

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
	public bool $has_configuration = false;

	/**
	 *
	 * @var string
	 */
	private string $program;

	/**
	 * Original arguments passed to command, unchanged, unedited.
	 *
	 * @var array
	 */
	private array $arguments = [];

	/**
	 * errors encountered during command processing.
	 *
	 * @var array
	 */
	private array $errors = [];

	/**
	 * Does the terminal support ANSI colors?
	 *
	 * @var bool
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
	public static array $ansi_styles = [
		'emergency' => '31;31m', 'critical' => '31;31m', 'error' => '31;31m', 'warning' => '40;33m',
		'success' => '0;32m', 'info' => '33;33m', 'debug' => '37;40m', 'reset' => '0m',
	];

	/**
	 * Help string
	 *
	 * @var string
	 */
	protected string $help = '';

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
	protected string $config = '';

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
	 * @var ?string
	 */
	protected ?string $history_file_path = null;

	/**
	 *
	 * @var resource
	 */
	private mixed $history_file = null;

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
	 * @param ?array $argv
	 * @param array $options Additional options
	 */
	public function __construct(Application $application, array $argv = null, array $options = []) {
		parent::__construct($application, $options);

		$argv ??= $_SERVER['argv'] ?? null;

		$this->option_types = $this->optFormat();
		$this->option_defaults = $this->optDefaults();
		$this->option_help = $this->optHelp();

		$this->initialize();

		$this->setOptions($this->parseOptionDefaults($this->option_defaults));

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

		$this->application->registerClass($this->register_classes);

		$this->_parseOptions();

		if ($this->debug) {
			$application->logger->debug('{class}({args})', [
				'class' => get_class($this), 'args' => var_export($argv, true),
			]);
		}

		if ($this->hasErrors()) {
			$this->usage(null, ['exit_code' => self::EXIT_CODE_ARGUMENTS]);
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
				'class' => get_class($this), 'app' => $application::class,
			]);
			if (!$application->configured()) {
				$logger->debug('Command {class} {app} WAS ALREADY CONFIGURED!!!!', [
					'class' => get_class($this), 'app' => $application::class,
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
	 * @param string $name
	 * @return array
	 */
	private function _configuration_config(string $name): array {
		$file = File::name_clean(strtolower($name));
		$suffixes = [
			"$file.conf", "$file.json",
		];
		$paths = $this->configuration_path();
		$files = [];
		foreach ($paths as $path) {
			foreach ($suffixes as $suffix) {
				$files[] = path($path, $suffix);
			}
		}

		try {
			$default = File::findFirst(array_reverse($files));
		} catch (Exception_NotFound) {
			$default = last($files);
		}
		$result = [
			'files' => $files, 'default' => $default,
		];
		if (empty($default)) {
			$result['default'] = path(first($paths), $file);
		}
		return $result;
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
	 * @param bool $create
	 * @return string LAST configuration file path
	 * @throws Exception_Parameter
	 */
	protected function configure(string $name, bool $create = false): string {
		$configure_options = $this->_configuration_config($name);
		$this->config = $filename = $configure_options['default'];
		if ($this->optionBool('no-config')) {
			$this->verboseLog('Configuration file {name} not loaded due to --no-config option', [
				'name' => $name,
			]);
			return $filename;
		}
		if (empty($filename)) {
			throw new Exception_Parameter('No configuration file name for {name}', [
				'name' => $name, 'create' => $create,
			]);
		}

		// Load global include
		$app = $this->application;
		$app->configureInclude($configure_options['files']);
		$app->reconfigure();

		$this->inheritConfiguration();

		$exists = file_exists($filename);
		if ($exists || $create) {
			if ($exists) {
				$this->verboseLog('Loading {name} configuration from {config}', [
					'name' => $name, 'config' => $filename,
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
			$this->verboseLog('Creating {name} configuration file ({filename})', [
				'name' => $name, 'filename' => $filename,
			]);
			$extension = File::extension($filename);
			if ($extension === 'conf') {
				file_put_contents($filename, "# Created $name on " . date('Y-m-d H:i:s') . " at $filename\n");
			} elseif ($extension === 'json') {
				file_put_contents($filename, JSON::encode([
					get_class($this) => [
						'configuration_file' => [
							'created' => date('Y-m-d H:i:s'), 'file' => $filename, 'name' => $name,
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

	/**
	 * @param string $arg
	 * @return Timestamp
	 * @throws Exception_Parameter
	 */
	protected function arg_to_DateTime(string $arg): Timestamp {
		try {
			return Timestamp::factory($arg);
		} catch (Exception_Convert|Exception_Parameter) {
			$this->error('Need to format like a date: {arg}', ['arg' => $arg]);

			throw new Exception_Parameter($arg);
		}
	}

	/**
	 * @param string $arg
	 * @return Date
	 * @throws Exception_Parameter
	 */
	protected function arg_to_Date(string $arg): Date {
		try {
			return Date::factory($arg);
		} catch (Exception_Convert|Exception_Parameter|Exception_Parse) {
			$this->error('Need to format like a date: {arg}', ['arg' => $arg]);

			throw new Exception_Parameter($arg);
		}
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 */
	private function defaultHelp(string $type): string {
		return match ($type) {
			'dir' => 'This option is followed by a path',
			'dir+', 'dir[]' => 'This option is followed by a path, and may be specified multiple times',
			'file' => 'This option is followed by a file path',
			'file[]' => 'This option is followed by a file path, and may be specified multiple times',
			'string' => 'This option is followed by a single string',
			'string*', 'string[]' => 'This option is followed by a single string, may be specified more than once.',
			'boolean' => 'This presence of this option turns this feature on.',
			'list' => 'This option is followed by a list.',
			'integer' => 'This option is followed by a integer value',
			'real' => 'This option is followed by a decimal value',
			'date' => 'This option is followed by a date value',
			'datetime' => 'This option is followed by a date and time value',
			'time' => 'This option is followed by a time value',
			default => "Unkown type: $type",
		};
	}

	/**
	 * @var array|string[]
	 */
	private static array $argType = [
		'dir' =>'dir',
		'dir+'=>'dir',
		'dir[]'=>'dir',
		'string*'=> 'string',
		'string[]'=>'string',
		'string'=>'string',
		'list'=>'item1;item2;...',
		'integer'=>'number',
		'real' => 'real-number',
		'path' => 'path',
		'file' => 'file',
		'file[]' => 'file',
		'boolean' => '',
	];

	/**
	 * Output the usage information
	 * @param array|string|null $message
	 * @param array $arguments
	 * @return void
	 */
	public function usage(array|string $message = null, array $arguments = []): void {
		$max_length = 0;
		$types = [];
		$commands = [];
		$aliases = ArrayTools::valuesFlipAppend(ArrayTools::prefixKeys($this->option_chars, '-'));
		foreach ($this->option_types as $k => $type) {
			$cmd = "--$k" . ArrayTools::joinPrefix($aliases[$k] ?? [], '|');
			$append = self::$argType[$type] ?? $type;
			if ($append) {
				$cmd .= " $append";
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
			$help = explode("\n", wordwrap($this->option_help[$k] ?? $this->defaultHelp($this->option_types[$k]), $wrap_len, "\n"));
			$help = implode("\n" . str_repeat(' ', $max_length + 1), $help);
			$result[] = $cmd . str_repeat(' ', $max_length - strlen($cmd) + 1) . $help;
		}
		$types = array_keys($types);
		if (in_array('list', $types)) {
			$result[] = '';
			$result[] = 'Lists are delimited by semicolons: item1;item2;item3';
		}
		$this->error($result, $arguments);
		if ($this->optionBool('exit')) {
			exit(($message === null) ? 0 : $arguments['exit_code'] ?? self::EXIT_CODE_ENVIRONMENT);
		}
	}

	/**
	 * Did errors occur?
	 *
	 * @return boolean
	 */
	public function hasErrors(): bool {
		return count($this->errors) !== 0;
	}

	/**
	 * Return the errors
	 *
	 * @return array
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Parse the option default values
	 *
	 * @param array $options
	 * @return array
	 */
	private function parseOptionDefaults(array $options = []): array {
		foreach ($this->option_types as $k => $t) {
			$newKey = self::_optionKey($k);
			switch (strtolower($t)) {
				case 'boolean':
					$options[$newKey] = toBool($options[$k] ?? false);

					break;
				default:
					$v = $options[$k] ?? null;
					if ($v !== null) {
						$options[$newKey] = $v;
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
	private static array $severity_is_error = [
		'emergency' => true, 'alert' => true, 'critical' => true, 'error' => true,
	];

	/**
	 * Log a message to output or stderr.
	 * Do not do anything if a theme is currently being rendered.
	 *
	 * @param string|array $message
	 * @param array $context
	 */
	public function log(mixed $message, array $context = []): void {
		if ($this->application->themeCurrent() !== null) {
			return;
		}
		if (is_array($message)) {
			if (ArrayTools::isList($message)) {
				foreach ($message as $m) {
					$this->logLine($m, $context);
				}
				return;
			}
			$message = Text::format_pairs($message);
		} else {
			$message = strval($message);
		}
		$this->logLine($message, $context);
	}

	/**
	 * Log a single line to stderr or stdout
	 *
	 * @param string $message
	 * @param array $context
	 */
	private function logLine(string $message, array $context = []): void {
		$newline = toBool($context['newline'] ?? true);
		$message = rtrim(map($message, $context));
		$suffix = '';
		if ($newline) {
			if (strlen($message) == 0 || $message[strlen($message) - 1] !== "\n") {
				$suffix = "\n";
			}
		}
		$prefix = '';
		$severity = strtolower($context['_severity'] ?? $context['severity'] ?? 'none');
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
				$prefix, $suffix,
			];
		}
		$prefix = self::ANSI_ESCAPE . self::$ansi_styles[$severity] . $prefix;
		$suffix = explode("\n", $suffix);
		$suffix = implode(self::ANSI_ESCAPE . self::$ansi_styles['reset'] . "\n", $suffix);
		return [
			$prefix, $suffix,
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
	 * @param string|array $message
	 * @param array $arguments
	 */
	public function error(string|array $message, array $arguments = []): void {
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
	protected function debugLog(string|array $message, array $arguments = []): void {
		if ($this->optionBool('debug') || $this->debug) {
			$this->log($message, $arguments);
		}
	}

	/**
	 * Log messages to the logger at $level
	 *
	 * @param string|array $message
	 * @param array $arguments
	 */
	public function verboseLog(string|array $message, array $arguments = []): void {
		if ($this->optionBool('verbose')) {
			$this->log($message, $arguments);
		}
	}

	/**
	 * Return original arguments passed to this command (not affected by parsing, etc)
	 *
	 * @return array:
	 */
	public function arguments(): array {
		return $this->arguments;
	}

	/**
	 * Retrieve remaining arguments to be processed, optionally deleting them
	 *
	 * @param bool $clean
	 * @return array
	 */
	public function argumentsRemaining(bool $clean = false): array {
		$result = $this->argv;
		if ($clean) {
			$this->argv = [];
		}
		return $result;
	}

	/**
	 * Is there an argument waiting to be processed?
	 *
	 * @return bool
	 */
	protected function hasArgument(): bool {
		if (count($this->argv) === 0) {
			return false;
		}
		if ($this->argv[0] === '--') {
			return false;
		}
		return true;
	}

	/**
	 * Assumes "hasArgument()" is true
	 *
	 * @param string $arg
	 *            Argument name
	 *
	 * @return string
	 * @throws Exception_Semantics
	 */
	protected function getArgument(string $arg): string {
		if (count($this->argv) === 0) {
			$this->error("No argument parameter for $arg");

			throw new Exception_Semantics('No arguments');
		}
		return array_shift($this->argv);
	}

	/**
	 * Parse command-line options for this command
	 */
	private function _parseOptions(): void {
		$this->argv = $this->arguments;
		$optional_arguments = isset($this->option_types['*']);
		$eatExtras = isset($this->option_types['+']) || $optional_arguments;

		$option_values = [];
		while (($arg = array_shift($this->argv)) !== null) {
			if (is_array($arg)) {
				$this->setOptions($arg);

				continue;
			}
			if (str_starts_with($arg, '-')) {
				$saveArg = $arg;
				if (strlen($arg) === 1) {
					break;
				}
				if ($arg[1] == '-') {
					$arg = substr($arg, 2);
					if ($arg === '') {
						break;
					}
				} else {
					$arg = substr($arg, 1);
					$argLength = strlen($arg);
					if ($argLength > 1) {
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
				$this->debugLog("Found arg \"$saveArg\" with format \"$format\"");
				switch (strtolower($this->option_types[$arg])) {
					case 'boolean':
						$option_values[$arg] = true;
						$this->setOption($arg, !$this->optionBool($arg));
						$this->debugLog("Set $arg to " . ($this->optionBool($arg) ? 'ON' : 'off'));

						break;
					case 'string':
						$param = $this->getArgument($arg);
						$option_values[$arg] = true;
						$this->setOption($arg, $param);
						$this->debugLog("Set $arg to \"$param\"");
						break;
					case 'string[]':
					case 'string*':
						$param = $this->getArgument($arg);
						$option_values[$arg] = true;
						$this->optionAppend($arg, $param);
						$this->debugLog("Added \"$arg\" to \"$param\"");
						break;
					case 'integer':
						$param = $this->getArgument($arg);
						if (!is_numeric($param)) {
							$this->error("Integer argument \"$saveArg\" not followed by number");
						} else {
							$param = intval($param);
							$option_values[$arg] = true;
							$this->setOption($arg, $param);
							$this->debugLog("Set $arg to $param");
						}
						break;
					case 'list':
						$param = $this->getArgument($arg);
						$option_values[$arg] = true;
						$this->setOption($arg, to_list($param, []));
						$this->debugLog("Set $arg to list: $param");
						break;
					case 'dir':
						$param = $this->getArgument($arg);
						if (!is_dir($param)) {
							$this->error("Argument \"--$arg $param\" is not a directory.");
						} else {
							$option_values[$arg] = true;
							$this->setOption($arg, $param);
							$this->debugLog("Set directory $arg to $param");
						}
						break;
					case 'dir+':
					case 'dir[]':
						$param = $this->getArgument($arg);
						if (!is_dir($param)) {
							$this->error("Argument \"--$arg $param\" is not a directory.");
						} else {
							$option_values[$arg] = true;
							$this->optionAppend($arg, $param);
							$this->debugLog("Added direcory $arg to list: $param");
						}
						break;
					case 'file':
						$param = $this->getArgument($arg);
						if (!$this->validateFileArgument($param)) {
							$this->error("Argument \"--$arg $param\" is not a file or link.");
						} else {
							$option_values[$arg] = true;
							$this->setOption($arg, $param);
							$this->debugLog("Set file $arg to file: $param");
						}

						break;
					case 'file+':
					case 'file[]':
						$param = $this->getArgument($arg);
						if (!$this->validateFileArgument($param)) {
							$this->error("Argument \"--$arg $param\" is not a file.");
						} else {
							$option_values[$arg] = true;
							$this->optionAppend($arg, $param);
							$this->debugLog("Added file $arg to list: $param");
						}

						break;
					case 'datetime':
						$param = $this->getArgument($arg);
						$option_values[$arg] = true;
						$param = $this->arg_to_DateTime($param);
						$this->setOption($arg, $param);
						$this->debugLog("Added datetime $arg: $param");

						break;
					case 'date':
						$param = $this->getArgument($arg);
						$option_values[$arg] = true;
						$param = $this->arg_to_Date($param);
						$this->setOption($arg, $param);
						$this->debugLog("Added date $arg: $param");

						break;
					default:
						if (!$this->parse_argument($arg, $this->option_types[$arg])) {
							$this->error('Unknown argument type ' . $this->option_types[$arg]);
						}

						break;
				}
			} else {
				$this->debugLog("Stopping parsing at $arg (not a switch, shifting back into stack)");
				array_unshift($this->argv, $arg);

				break;
			}
		}

		if ($eatExtras) {
			if (count($this->argv) === 0) {
				if (!$optional_arguments) {
					$this->error('{class} - No arguments supplied', ['class' => get_class($this)]);
				}
			}
		} elseif (count($this->argv) !== 0 && !$optional_arguments) {
			if ($this->optionBool('error_unhandled_arguments')) {
				$this->error('Unhandled arguments starting at ' . $this->argv[0]);
			}
		}

		$this->option_values = $this->options(array_keys($option_values));
	}

	/**
	 * Quote a variable for use in the shell
	 *
	 * @param string $var
	 * @return string
	 */
	public static function shellQuote(string $var): string {
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
	 * @throws Exception_File_Permission
	 */
	private function _init_history(): void {
		if ($this->history_file) {
			// Have history file and is open for writing
			return;
		}
		if ($this->history_file_path === '') {
			// No history file specified, no-op
			return;
		}
		if ($this->history_file_path && $this->has_readline()) {
			try {
				foreach (File::lines($this->history_file_path) as $line) {
					readline_add_history($line);
				}
			} catch (Exception_File_NotFound) {
				// pass
			}
		}

		try {
			$this->history_file = File::open($this->history_file_path, 'ab');
		} catch (Exception_File_Permission $e) {
			$this->application->logger->error('Unable to open history file {filename} with mode {mode}', $e->variables());
		}
	}

	/**
	 * Function which may be overridden by subclasses to return a list of possible completions for the current readline request
	 *
	 * @return array
	 */
	public function default_completion_function(string $command): string {
		return ArrayTools::filterKeyPrefixes($this->completions, $command);
	}

	/**
	 * Set the readline completion function
	 *
	 * @param callable $function
	 */
	protected function completion_function($function = null): void {
		if ($this->has_readline()) {
			if ($function === null) {
				$function = [__CLASS__, 'default_completion_function'];
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
	 * @throws Exception_Command|Exception_Semantics
	 */
	public function prompt(string $message, string $default = null, array $completions = null): string {
		if ($this->option('non-interactive')) {
			if ($default === null) {
				$this->error('Non-interactive set but input is required for {message}', [
					'message' => $message,
				]);

				throw new Exception_Semantics('Non-interactive set but input is required for {message}', [
					'message' => $message,
				]);
			}
			return $default;
		}
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

		throw new Exception_Command('Exited.', 0, []);
	}

	/**
	 * Prompt yes or no
	 *
	 * @param string $message
	 * @param boolean $default
	 * @return boolean
	 */
	public function promptYesNo(string $message, ?bool $default = true): bool {
		if ($this->optionBool('yes')) {
			return true;
		}
		if ($this->optionBool('no')) {
			return false;
		}
		if ($this->optionBool('non-interactive')) {
			return true;
		}
		do {
			echo rtrim($message) . ' ' . ($default === null ? '(y/n)' : ($default ? '(Y/n)' : '(y/N)')) . ' ';
			$this->completions = ($default === null ? [
				'yes', 'no',
			] : ($default ? [
				'yes', 'no',
			] : [
				'no', 'yes',
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
		return $this->application->process->executeArguments($command, $args);
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
		return $app->process->executeArguments("$bin --search {app_root} $command", [
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
		return $this->application->process->executeArguments($command, $args, true);
	}

	/**
	 * Main entry point for running a command
	 *
	 * @return int
	 */
	final public function go(): int {
		self::$commands[] = $this;
		$this->application->modules->load($this->load_modules);
		// Moved from Command_Loader
		$this->application_configure();

		$this->callHook('run_before');

		if ($this->hasErrors()) {
			$this->usage();
		}

		try {
			$result = $this->run();
		} catch (Exception_File_NotFound $e) {
			$this->error('File not found {filename}', $e->variables());
		} catch (\Exception $e) {
			$this->error("Exception thrown by command {class} : {exceptionClass} {message}\n{backtrace}", [
				'class' => get_class($this), 'exception_class' => $e::class, 'message' => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(), 'backtrace-4' => Text::head($e->getTraceAsString(), 6),
			]);
			$this->application->hooks->call('exception', $e);
			if ($this->optionBool('debug', $this->application->development())) {
				$this->error($e->getTraceAsString());
			}
			$code = intval($e->getCode());
			return ($code === 0) ? -1 : $code;
		}
		$result = $this->callHookArguments('run_after', [
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
	public function renderFormat(string|array $content, string $format = null, string $default_format = 'text'): bool {
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
				echo JSON::encodePretty($content);

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
			$parsed['desc'] ?? null, $parsed['description'] ?? null,
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
	public function validateFileArgument(string $file): bool {
		return is_file($file) || is_link($file);
	}

	/**
	 * Main run code
	 * @throws Exception_File_NotFound
	 */
	abstract protected function run();
}
