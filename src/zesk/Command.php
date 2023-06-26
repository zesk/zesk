<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use ReflectionClass;
use Throwable;
use Stringable;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use zesk\Exception\ClassNotFound;
use zesk\Exception\CommandFailed;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\ExitedException;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Redirect;
use zesk\Exception\SemanticsException;
use zesk\Exception\UnsupportedException;
use zesk\Interface\Promptable;

/**
 *
 * @author kent
 */
abstract class Command extends Hookable implements LoggerInterface, Promptable {
	use LoggerTrait;

	public const OPTION_ANSI = 'ansi';

	public const OPTION_NO_ANSI = 'no-ansi';

	public const END_OF_ARGUMENT_MARKER = '--';

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
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public bool $has_configuration = false;

	/**
	 * The argv[0] which started this
	 *
	 * @var string
	 */
	private string $program;

	/**
	 * List of commands this command supports (for the CLI)
	 *
	 * @var array
	 */
	protected array $shortcuts = [];

	/**
	 * Original arguments passed to command, unchanged, unedited.
	 *
	 * @var array
	 */
	private array $arguments;

	/**
	 * errors encountered during command processing.
	 *
	 * @var array
	 */
	private array $errors = [];

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
		LogLevel::EMERGENCY => '31;31m', LogLevel::CRITICAL => '31;31m', LogLevel::ERROR => '31;31m',
		LogLevel::WARNING => '40;33m', LogLevel::INFO => '33;33m', LogLevel::DEBUG => '37;40m', 'success' => '0;32m',
		'reset' => '0m',
	];

	/**
	 *
	 * @var array
	 */
	private static array $severityIsError = [
		LogLevel::EMERGENCY => true, LogLevel::ALERT => true, LogLevel::CRITICAL => true, LogLevel::ERROR => true,
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
	 * Create a new uninitialized command.
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
	}

	/**
	 * @return array
	 */
	final public function shortcuts(): array {
		return $this->shortcuts;
	}

	/**
	 * @return void
	 */
	protected function initialize(): void {
		// Hello, world.
	}

	/**
	 * Parse arguments and set up Command for running.
	 *
	 * Command line arguments must be passed in. Use $_SERVER['argv]
	 *
	 * @param array $argv
	 * @return $this
	 * @throws ParameterException
	 */
	public function parseArguments(array $argv): self {
		$this->initialize();

		$this->setOptions($this->parseOptionDefaults());

		$this->program = array_shift($argv) ?? get_class($this);
		$this->arguments = $argv;
		$this->argv = $argv;

		$this->isANSI();

		foreach ($this->register_classes as $class) {
			$this->application->registerClass($class);
		}

		try {
			$this->_parseOptions();
		} catch (SemanticsException $e) {
			throw new ParameterException('Invalid arguments for {class}: {argv}', [
				'class' => $this::class, 'argv' => $argv,
			], $e->getCode(), $e);
		}

		if ($this->optionBool(self::OPTION_DEBUG)) {
			$this->application->debug('{class}({args})', [
				'class' => get_class($this), 'args' => var_export($argv, true),
			]);
		}

		if ($this->hasErrors()) {
			throw new ParameterException("Invalid arguments for {class}: {argv}\n{errors}", [
				'class' => $this::class, 'argv' => $argv, 'errors' => $this->errors(),
			]);
		}
		return $this;
	}

	/**
	 * Configure the application additionally upon run
	 * @throws ConfigurationException
	 */
	protected function applicationConfigure(): void {
		$application = $this->application;
		$logger = $application->logger();
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
	private function configurationPaths(): array {
		$paths[] = '/etc/zesk';
		$paths = [];
		if (is_dir(($path = $this->application->path('etc')))) {
			$paths[] = $path;
		}
		$uid_path = $this->application->paths->userHome();
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
	private function _configurationFiles(string $name): array {
		$file = File::nameClean($name);
		$suffixes = [
			"$file.conf", "$file.json",
		];
		$paths = $this->configurationPaths();
		$files = [];
		foreach ($paths as $path) {
			foreach ($suffixes as $suffix) {
				$files[] = Directory::path($path, $suffix);
			}
		}

		try {
			$default = File::findFirst(array_reverse($files));
		} catch (NotFoundException) {
			$default = ArrayTools::last($files);
		}
		$result = [
			'files' => $files, 'default' => $default,
		];
		if (empty($default)) {
			$result['default'] = Directory::path(ArrayTools::first($paths), $file);
		}
		return $result;
	}

	/**
	 * Load a configuration file for this command.
	 *
	 * RETHINK commands and application setup - commands extend existing application configuration and may ADD modules
	 * and load other configurations but in essence - app should be assumed loaded and ready to go
	 * upon command invocation as it is NOT already. TODO KMD 2022-12
	 *
	 * @param string $name
	 *            Configuration file name to use (either /etc/zesk/$name.conf or ~/.zesk/$name.conf)
	 * @param bool $create
	 * @return string LAST configuration file path
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @throws ParameterException
	 * @throws SemanticsException
	 * @throws UnsupportedException|NotFoundException|ParseException
	 */
	protected function configure(string $name, bool $create = false): string {
		$configure_options = $this->_configurationFiles($name);
		$this->config = $filename = $configure_options['default'];
		if ($this->optionBool('no-config')) {
			$this->verboseLog('Configuration file {name} not loaded due to --no-config option', [
				'name' => $name,
			]);
			return $filename;
		}
		if (empty($filename)) {
			throw new ParameterException('No configuration file name for {name}', [
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
		}
		$app->configured();
		return $filename;
	}

	/**
	 * Write the default configuration for this command (as requested with $create = true)
	 * @param string $name
	 * @param string $filename
	 * @return void
	 * @throws SemanticsException|FilePermission
	 */
	protected function write_default_configuration(string $name, string $filename): void {
		if (!is_writable(dirname($filename))) {
			$this->error('Can not write {name} configuration file ({filename}) - directory is not writable', [
				'name' => $name, 'filename' => $filename,
			]);
			return;
		}
		$this->verboseLog('Creating {name} configuration file ({filename})', [
			'name' => $name, 'filename' => $filename,
		]);
		$extension = File::extension($filename);
		if ($extension === 'conf') {
			File::put($filename, "# Created $name on " . date('Y-m-d H:i:s') . " at $filename\n");
		} else if ($extension === 'json') {
			File::put($filename, JSON::encode([
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
	 * @param string $name
	 * @param string $type
	 * @return bool
	 * @throws ParameterException
	 */
	protected function parse_argument(string $name, string $type): bool {
		throw new ParameterException('Unknown parameter {name} of {type}', ['name' => $name, 'type' => $type]);
	}

	/**
	 * @param string $arg
	 * @return Timestamp
	 * @throws ParameterException
	 */
	protected function arg_to_DateTime(string $arg): Timestamp {
		try {
			return Timestamp::factory()->parse($arg);
		} catch (ParseException) {
			$this->error('Need to format like a date: {arg}', ['arg' => $arg]);

			throw new ParameterException($arg);
		}
	}

	/**
	 * @param string $arg
	 * @return Date
	 * @throws ParameterException
	 */
	protected function arg_to_Date(string $arg): Date {
		try {
			return Date::factory()->parse($arg);
		} catch (ParseException) {
			$this->error('Need to format like a date: {arg}', ['arg' => $arg]);

			throw new ParameterException($arg);
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
			default => "Unknown type: $type",
		};
	}

	/**
	 * @var array|string[]
	 */
	private static array $argType = [
		'dir' => 'dir', 'dir+' => 'dir', 'dir[]' => 'dir', 'string*' => 'string', 'string[]' => 'string',
		'string' => 'string', 'list' => 'item1;item2;...', 'integer' => 'number', 'real' => 'real-number',
		'path' => 'path', 'file' => 'file', 'file[]' => 'file', 'boolean' => '',
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
			$result[] = wordwrap($message, $this->wordwrap);
			$result[] = '';
		}
		$result[] = 'Usage: ' . $this->program;
		$result[] = '';
		if (!$this->help) {
			$this->help = $this->docCommentHelp();
		}
		if ($this->help) {
			$result[] = wordwrap($this->help, $this->wordwrap);
			$result[] = '';
		}

		$max_length += 4;
		$wrap_len = $this->wordwrap - $max_length - 1;
		foreach ($commands as $k => $cmd) {
			$help = explode("\n", wordwrap($this->option_help[$k] ?? $this->defaultHelp($this->option_types[$k]), $wrap_len));
			$help = implode("\n" . str_repeat(' ', $max_length + 1), $help);
			$result[] = $cmd . str_repeat(' ', $max_length - strlen($cmd) + 1) . $help;
		}
		$types = array_keys($types);
		if (in_array('list', $types)) {
			$result[] = '';
			$result[] = 'Lists are delimited by semicolons: item1;item2;item3';
		}
		$this->error(implode("\n", $result), $arguments);
		if ($this->optionBool('exit')) {
			exit(($message === null) ? 0 : $arguments['exitCode'] ?? self::EXIT_CODE_ENVIRONMENT);
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
	 * @return array
	 */
	private function parseOptionDefaults(): array {
		$options = [];
		foreach ($this->option_types as $k => $t) {
			$newKey = self::_optionKey($k);
			switch (strtolower($t)) {
				case 'boolean':
					$options[$newKey] = $this->optionBool($newKey);

					break;
				default:
					$v = $this->option($newKey);
					if ($v !== null) {
						$options[$newKey] = $v;
					}
					break;
			}
		}
		return $options;
	}

	/**
	 * Log a message to output or stderr.
	 * Do not do anything if a theme is currently being rendered.
	 *
	 * @param $level
	 * @param Stringable|string $message
	 * @param array $context
	 */
	public function log($level, Stringable|string $message, array $context = []): void {
		if ($this->application->themes->themeCurrent() !== null) {
			return;
		}
		$this->logLine($message, Logger::contextualize($level, $message, $context));
	}

	/**
	 * Log a message to output or stderr.
	 * Do not do anything if a theme is currently being rendered.
	 *
	 * @param $level
	 * @param array $message
	 * @param array $context
	 */
	public function logMany($level, array $message, array $context = []): void {
		foreach ($message as $m) {
			$this->logLine($m, $context);
		}
	}

	/**
	 * Log a single line to stderr or stdout
	 *
	 * @param string $message
	 * @param array $context
	 */
	private function logLine(Stringable|string $message, array $context = []): void {
		$newline = Types::toBool($context['newline'] ?? true);
		$message = rtrim(strval(ArrayTools::map($message, $context)));
		$suffix = '';
		if ($newline) {
			if (strlen($message) == 0 || $message[strlen($message) - 1] !== "\n") {
				$suffix = "\n";
			}
		}
		$prefix = '';
		$severity = strtolower($context['_severity'] ?? $context['severity'] ?? LogLevel::INFO);

		if ($severity && !$this->isANSI()) {
			$prefix = strtoupper($severity) . ': ';
		}
		if ($this->hasOption('prefix')) {
			$prefix .= $this->option('prefix') . ' ';
		}
		if ($this->hasOption('suffix')) {
			$suffix = ' ' . $this->option('suffix') . $suffix;
		}
		[$prefix, $suffix] = $this->ansiAnnotate($prefix, $suffix, $severity);
		$isError = isset(self::$severityIsError[$severity]);

		if ($isError) {
			fwrite($this->errorStream(), $prefix . $message . $suffix);
			$this->errors[] = $message;
		} else {
			echo $prefix . implode("\n" . str_repeat(' ', strlen($prefix)), explode("\n", $message)) . $suffix;
			flush();
		}
	}

	/**
	 * Is the terminal an ANSI terminal?
	 */
	private function isANSI(): bool {
		if ($this->optionBool(self::OPTION_NO_ANSI)) {
			return false;
		} else if ($this->optionBool(self::OPTION_ANSI)) {
			return true;
		} else {
			// On Windows, enable ANSI for ANSICON and ConEmu only
			return is_windows() ? (false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI')) : function_exists('posix_isatty') && posix_isatty(1);
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
	private function ansiAnnotate(string $prefix, string $suffix, string $severity = 'info'): array {
		if (!$this->isANSI() || !array_key_exists($severity, self::$ansi_styles)) {
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
	 * @return resource
	 */
	private function errorStream(): mixed {
		return $this->optionBool('stdout') ? STDOUT : STDERR;
	}

	/**
	 * Debug message, only when debugging is turned on
	 *
	 * @param string|array $message
	 * @param array $arguments
	 * @return void
	 */
	protected function debugLog(string|array $message, array $arguments = []): void {
		if ($this->optionBool(self::OPTION_DEBUG)) {
			$this->debug($message, $arguments);
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
			$this->info($message, $arguments);
		}
	}

	/**
	 * Return original arguments passed to this command (not affected by parsing, etc.)
	 *
	 * @return array:
	 */
	public function arguments(): array {
		return $this->arguments;
	}

	/**
	 * Retrieve remaining arguments to be processed, optionally deleting them
	 *
	 * @param bool $endOfArgumentMarker When true, return up to and including end of arguments marker
	 * @return array
	 */
	public function argumentsRemaining(bool $endOfArgumentMarker = true): array {
		$arguments = [];
		while ($this->hasArgument($endOfArgumentMarker)) {
			try {
				$arguments[] = $this->getArgument(__METHOD__, $endOfArgumentMarker);
			} catch (SemanticsException) {
			}
		}
		if (!$endOfArgumentMarker && ArrayTools::first($arguments) === self::END_OF_ARGUMENT_MARKER) {
			array_shift($arguments);
		}
		return $arguments;
	}

	/**
	 * @param array $arguments
	 * @return $this
	 */
	public function argumentsPush(array $arguments): self {
		$this->argv = array_merge($arguments, $this->argv);
		return $this;
	}

	/**
	 * Is there an argument waiting to be processed?
	 *
	 * @param bool $endOfArgumentMarker Honor the `--` argument marker
	 * @return bool
	 */
	protected function hasArgument(bool $endOfArgumentMarker = true): bool {
		if (count($this->argv) === 0) {
			return false;
		}
		if ($endOfArgumentMarker && $this->argv[0] === self::END_OF_ARGUMENT_MARKER) {
			return false;
		}
		return true;
	}

	/**
	 * Assumes "hasArgument()" is true
	 *
	 * @param string $arg
	 * @param bool $endOfArgumentMarker
	 * @return string
	 * @throws SemanticsException
	 */
	protected function getArgument(string $arg = '', bool $endOfArgumentMarker = true): string {
		if (count($this->argv) === 0) {
			$this->error("No argument parameter for $arg");

			throw new SemanticsException('No arguments');
		}
		if ($endOfArgumentMarker) {
			if ($this->argv[0] === self::END_OF_ARGUMENT_MARKER) {
				$this->error("End of arguments marker found for $arg");

				throw new SemanticsException('End of arguments marker found');
			}
		}
		return array_shift($this->argv);
	}

	/**
	 * Parse command-line options for this command
	 * @throws SemanticsException
	 * @throws ParameterException
	 */
	private function _parseOptions(): void {
		$this->argv = $this->arguments;
		$optional_arguments = isset($this->option_types['*']);
		$eatExtras = isset($this->option_types['+']) || $optional_arguments;

		$option_values = [];
		while (($arg = array_shift($this->argv)) !== null) {
			if (!is_string($arg)) {
				$this->error('Non-string argument {type} encountered, skipping', ['type' => Types::type($arg)]);
				continue;
			}
			if (!str_starts_with($arg, '-')) {
				$this->debugLog("Stopping parsing at $arg (not a switch, shifting back into stack)");
				array_unshift($this->argv, $arg);

				break;
			}
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
					// Break -args into -a -r -g -s
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
				array_unshift($this->argv, $saveArg);
				if ($optional_arguments) {
					break;
				}
				$this->usage("Unknown argument: $saveArg");
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
					$param = $this->getArgument($arg, false);
					$option_values[$arg] = true;
					$this->setOption($arg, $param);
					$this->debugLog("Set $arg to \"$param\"");
					break;
				case 'string[]':
				case 'string*':
					$param = $this->getArgument($arg, false);
					$option_values[$arg] = true;
					$this->optionAppend($arg, $param);
					$this->debugLog("Added \"$arg\" to \"$param\"");
					break;
				case 'integer':
					$param = $this->getArgument($arg, false);
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
					$param = $this->getArgument($arg, false);
					$option_values[$arg] = true;
					$this->setOption($arg, Types::toList($param));
					$this->debugLog("Set $arg to list: $param");
					break;
				case 'dir':
					$param = $this->getArgument($arg, false);
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
					$param = $this->getArgument($arg, false);
					if (!is_dir($param)) {
						$this->error("Argument \"--$arg $param\" is not a directory.");
					} else {
						$option_values[$arg] = true;
						$this->optionAppend($arg, $param);
						$this->debugLog("Added directory $arg to list: $param");
					}
					break;
				case 'file':
					$param = $this->getArgument($arg, false);
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
					$param = $this->getArgument($arg, false);
					if (!$this->validateFileArgument($param)) {
						$this->error("Argument \"--$arg $param\" is not a file.");
					} else {
						$option_values[$arg] = true;
						$this->optionAppend($arg, $param);
						$this->debugLog("Added file $arg to list: $param");
					}

					break;
				case 'datetime':
					$param = $this->getArgument($arg, false);
					$option_values[$arg] = true;
					$param = $this->arg_to_DateTime($param);
					$this->setOption($arg, $param);
					$this->debugLog("Added datetime $arg: $param");

					break;
				case 'date':
					$param = $this->getArgument($arg, false);
					$option_values[$arg] = true;
					$param = $this->arg_to_Date($param);
					$this->setOption($arg, $param);
					$this->debugLog("Added date $arg: $param");

					break;
				default:
					$this->parse_argument($arg, $this->option_types[$arg]);
					// Marked as unreachable, but parse_argument is overridden in other classes, wink wink.
					break;
			}
		}

		if ($eatExtras) {
			if (count($this->argv) === 0) {
				if (!$optional_arguments) {
					$this->error('{class} - No arguments supplied', ['class' => get_class($this)]);
				}
			}
		} else if (count($this->argv) !== 0 && !$optional_arguments) {
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
	private static function hasReadline(): bool {
		return function_exists('readline');
	}

	/**
	 * Handle managing history and history file, initialization of history file
	 *
	 * @return void
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
		if ($this->history_file_path && $this->hasReadline()) {
			try {
				foreach (File::lines($this->history_file_path) as $line) {
					readline_add_history($line);
				}
			} catch (FileNotFound) {
				// pass
			}
		}

		try {
			$this->history_file = File::open($this->history_file_path, 'ab');
		} catch (FilePermission $e) {
			$this->application->error('{message}', $e->variables());
		}
	}

	/**
	 * Function which may be overridden by subclasses to return a list of possible completions for the current readline request
	 *
	 * @param string $command
	 * @return array
	 */
	public function defaultCompletionFunction(string $command): array {
		return ArrayTools::filterKeyPrefixes($this->completions, $command);
	}

	/**
	 * Set the readline completion function
	 *
	 * @param null|callable $function
	 */
	protected function setCompletionFunction(callable $function = null): void {
		if ($this->hasReadline()) {
			if ($function === null) {
				$function = [$this, 'defaultCompletionFunction'];
			}
			readline_completion_function($function);
		}
	}

	/**
	 * Read a line from standard in
	 *
	 * @param string $prompt
	 * @return ?string
	 * @throws StopIteration
	 */
	public function readline(string $prompt): ?string {
		if ($this->hasReadline()) {
			$result = readline($prompt);
			if ($result === false) {
				throw new StopIteration('readline returned false');
			}
			if (!empty($result)) {
				readline_add_history($result);
			}
		} else {
			echo $prompt;
			$result = fgets(STDIN);
			if (feof(STDIN)) {
				throw new StopIteration('feof(STDIN) is true');
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
	 * @throws StopIteration|SemanticsException
	 */
	public function prompt(string $message, string $default = null, array $completions = null): string {
		if ($this->option('non-interactive')) {
			if ($default === null) {
				$this->error('Non-interactive set but input is required for {message}', [
					'message' => $message,
				]);

				throw new SemanticsException('Non-interactive set but input is required for {message}', [
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

			try {
				$result = $this->readline($prompt);
				if (is_string($result)) {
					return $result;
				}
				if ($default !== null) {
					return $default;
				}
			} catch (StopIteration) {
				throw new StopIteration('exit');
			}
		}
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
			$result = ($result === '') ? $default : Types::toBool($result, null);
		} while ($result === null);
		return $result;
	}

	/**
	 * Execute a shell command - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command
	 * @return array
	 * @throws CommandFailed
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
	 * @return array
	 * @throws CommandFailed
	 */
	protected function zesk_cli(string $command, array $arguments = []): array {
		$app = $this->application;
		$bin = $app->zeskHome('bin/zesk');
		return $app->process->executeArguments("$bin --search {appRoot} $command", [
				'appRoot' => $app->path(),
			] + $arguments);
	}

	/**
	 * Execute a shell command and output to STDOUT - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command
	 * @return array
	 * @throws CommandFailed
	 */
	protected function passThru(string $command): array {
		$args = func_get_args();
		array_shift($args);
		return $this->application->process->executeArguments($command, $args, true);
	}

	public const HOOK_RUN_BEFORE = self::class . 'runBefore';

	public const FILTER_RUN_AFTER = self::class . 'runAfter';

	/**
	 * Main entry point for running a command
	 *
	 * @return int
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws UnsupportedException
	 * @throws ParseException
	 */
	final public function go(): int {
		self::$commands[] = $this;
		$this->application->modules->loadMultiple($this->load_modules);
		// Moved from Command_Loader
		$this->applicationConfigure();

		try {
			$this->invokeHooks(self::HOOK_RUN_BEFORE, [$this]);
		} catch (ExitedException $e) {
			return $e->getCode();
		}

		if ($this->hasErrors()) {
			$this->usage();
		}

		try {
			$result = $this->run();
			$result = $this->invokeTypedFilters(self::FILTER_RUN_AFTER, $result, [$this]);
			if (is_bool($result)) {
				$result = $result ? self::EXIT_CODE_SUCCESS : -1;
			} else if ($result === null) {
				$result = self::EXIT_CODE_SUCCESS;
			} else if (!is_int($result)) {
				$result = -1;
			}
			assert(count(self::$commands) > 0);
			array_pop(self::$commands);
			return $result;
		} catch (FileNotFound $e) {
			$this->error('File not found {path}', $e->variables());
			return self::EXIT_CODE_ENVIRONMENT;
		} catch (Throwable $e) {
			$this->error("Exception thrown by command {class} : {exceptionClass} {message}\n{backtrace}", Exception::exceptionVariables($e) + [
					'class' => get_class($this),
				]);
			$this->application->invokeHooks(Application::HOOK_EXCEPTION, [$this->application, $e]);
			if ($this->optionBool('debug', $this->application->development())) {
				$this->error($e->getTraceAsString());
			}
			$code = intval($e->getCode());
			return ($code === self::EXIT_CODE_SUCCESS) ? -1 : $code;
		}
	}

	/**
	 * Is a command running? (Any command, not just this one)
	 *
	 * @return Command
	 */
	public static function running(): self {
		return ArrayTools::last(self::$commands);
	}

	public const OPTION_FORMAT = 'format';

	/**
	 * --format html
	 */
	public const FORMAT_HTML = 'html';

	/**
	 * --format text
	 */
	public const FORMAT_TEXT = 'text';

	/**
	 * --format json
	 */
	public const FORMAT_JSON = 'json';

	/**
	 * --format serialize
	 */
	public const FORMAT_SERIALIZE = 'serialize';

	/**
	 * Output as parsable PHP
	 *
	 * --format php
	 */
	public const FORMAT_PHP = 'php';

	/**
	 *
	 * @param string|array $content
	 * @param string $format
	 * @param string $default_format
	 * @return boolean
	 */
	public function renderFormat(string|array $content, string $format = '', string $default_format = self::FORMAT_TEXT): bool {
		if ($format === '') {
			$format = $this->optionString(self::OPTION_FORMAT, $default_format);
		}
		switch ($format) {
			case self::FORMAT_HTML:
				try {
					echo $this->application->themes->theme('dl', $content);
				} catch (Redirect $e) {
					echo $e->url();
				}
				break;
			case self::FORMAT_PHP:
				echo PHP::dump($content);

				break;
			case self::FORMAT_SERIALIZE:
				echo serialize($content);

				break;
			case self::FORMAT_JSON:
				echo JSON::encodePretty($content);

				break;
			case self::FORMAT_TEXT:
				if (count($content) > 0) {
					echo Text::formatPairs($content);
				}
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
	 * Add help from the docComment.
	 * One place for docs is preferred.
	 *
	 * @return string
	 */
	private function docCommentHelp(): string {
		$reflection_class = new ReflectionClass(get_class($this));
		$comment = $reflection_class->getDocComment();
		if (!is_string($comment)) {
			return '';
		}
		$parsed = DocComment::instance($comment)->variables();
		return implode("\n", array_filter([
			$parsed['desc'] ?? null, $parsed['description'] ?? null,
		]));
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
	 * @throws FileNotFound
	 * @throws Exception
	 */
	abstract protected function run(): int;
}
