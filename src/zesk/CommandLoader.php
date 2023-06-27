<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use ReflectionClass;
use ReflectionException;
use Throwable;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\ExitedException;
use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\UnsupportedException;
use zesk\Locale\Locale;
use const STDERR;
use const ZESK_ROOT;

/**
 * Loads a Zesk Command from the command-line
 *
 * @author kent
 */
class CommandLoader
{
	/**
	 *
	 */
	public const EXIT_CODE_SUCCESS = 0;

	/**
	 * Error with environment
	 */
	public const EXIT_CODE_ENVIRONMENT = 1;

	/**
	 * Problem with command arguments
	 */
	public const EXIT_CODE_ARGUMENTS = 2;

	/**
	 * Search these paths to find application
	 *
	 * @var array
	 */
	private array $search = [];

	/**
	 * Main command run
	 *
	 * @var string
	 */
	private string $command = '';

	/**
	 * List of config files to load after loading application
	 *
	 * @var array
	 */
	private array $wait_configs = [];

	/**
	 * A log of loaded files
	 *
	 * @var array
	 */
	private array $loaded_configs = [];

	/**
	 * Shortcut to classname
	 *
	 * @var array
	 */
	protected array $commands = [];

	/**
	 *
	 * @var boolean
	 */
	private bool $debug = false;

	/**
	 * Collect command-line context
	 *
	 * @var array
	 */
	private array $global_context = [];

	/**
	 *
	 * @var ?Application
	 */
	public ?Application $application = null;

	/**
	 * Set up PHP basics, to allow detecting errors while testing, etc.
	 */
	public function __construct()
	{
		global $_ZESK;

		if (!is_array($_ZESK)) {
			$_ZESK = [];
		}

		$_ZESK[Application::class]['configure_options']['skip_configured'] = true; // Is honored
		// 2018-03-10 KMD

		ini_set('error_prepend_string', "\nPHP-ERROR " . str_repeat('=', 80) . "\n");
		ini_set('error_append_string', "\n" . str_repeat('*', 80) . "\n");
	}

	/**
	 * Create instance
	 *
	 * @return self
	 */
	public static function factory(): self
	{
		return new self();
	}

	/**
	 *
	 * @return array
	 */
	public function context(): array
	{
		return $this->global_context;
	}

	/**
	 * Use this outside of a CLI context, use this to set the application context.
	 *
	 * @param Application $application
	 * @return $this
	 */
	public function setApplication(Application $application): self
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * Run the command.
	 * Main entry point into this class after initialization, normally.
	 * @return int
	 * @throws ConfigurationException
	 * @throws ExitedException
	 * @throws ParameterException
	 * @throws SemanticsException
	 * @throws UnsupportedException
	 */
	public function run(): int
	{
		if (!array_key_exists('argv', $_SERVER) || !is_array($_SERVER['argv'])) {
			throw new ParameterException('No argv in $_SERVER');
		}

		$args = $_SERVER['argv'];
		assert(is_array($args));
		$args = $this->argumentSugar($args);
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
		while (count($args) > 0) {
			$arg = array_shift($args);

			try {
				if (str_starts_with($arg, '--')) {
					$args = $this->handleCoreArguments($arg, $args);
					continue;
				}
			} catch (Exception $e) {
				$this->error($e->getMessage() . PHP_EOL);
				return self::EXIT_CODE_ARGUMENTS;
			}
			if ($this->isIncludeCommand($arg)) {
				$exitCode = $this->runIncludeCommand($arg);
				if ($exitCode !== 0) {
					return $exitCode;
				}
				if ($this->zeskIsLoaded()) {
					$this->zeskLoaded($arg);
				}
				continue;
			}
			if (!$this->zeskIsLoaded()) {
				$exitCode = $this->bootstrapApplication();
				if ($exitCode !== 0) {
					return $exitCode;
				}
			}

			try {
				$args = $this->runCommand($arg, $args);
			} catch (NotFoundException $e) {
				$this->error(ArrayTools::map("{command} not found: {commands}\n", $e->variables()));
				return self::EXIT_CODE_ARGUMENTS;
			}
		}
		return self::EXIT_CODE_SUCCESS;
	}

	/**
	 * @return int
	 * @throws ExitedException
	 * @throws SemanticsException
	 */
	private function bootstrapApplication(): int
	{
		$first_command = $this->findApplication();
		if (!$first_command) {
			return self::EXIT_CODE_ENVIRONMENT;
		}

		require_once $first_command;
		$exitCode = $this->zeskLoaded($first_command);
		if ($exitCode) {
			return $exitCode;
		}

		if ($this->application->configuration->getBool('DEBUG') || $this->debug || $this->application->optionBool(Application::OPTION_DEBUG)) {
			$this->debug = true;
		}
		$this->debug("Loaded application file $first_command\n");
		$this->application->objects->setSingleton($this);
		return 0;
	}

	private function applicationWasLoaded(): void
	{
		$this->application = Kernel::singleton()->application();
	}

	/**
	 * @param string $arg
	 * @param array $args
	 * @return array
	 * @throws ParseException
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws ParameterException
	 */
	private function handleCoreArguments(string $arg, array $args): array
	{
		return match ($arg) {
			'--cd' => $this->handleCD($args),
			'--config' => $this->handleConfig($args),
			'--define' => $this->handleDefine($args),
			'--set' => $this->handleSet($args),
			'--unset' => $this->handleUnset($args),
			default => $this->handleSet(array_merge([substr($arg, 2)], $args)),
		};
	}

	/**
	 * @param string $arg
	 * @return bool
	 */
	private function isIncludeCommand(string $arg): bool
	{
		return str_starts_with($arg, '/') || str_starts_with($arg, './');
	}

	/**
	 * @param string $arg
	 * @return int
	 */
	private function runIncludeCommand(string $arg): int
	{
		try {
			File::depends($arg);
		} catch (FileNotFound) {
			$this->error("File not found: $arg");
			return self::EXIT_CODE_ARGUMENTS;
		}

		try {
			require_once($arg);
		} catch (Throwable $e) {
			$this->error(ArrayTools::map("require_once($arg) threw error: {class} {message}\n", ['arg' => $arg] + Exception::exceptionVariables($e)));
			return self::EXIT_CODE_ARGUMENTS;
		}
		return 0;
	}

	/**
	 * @param int $exit
	 * @return int
	 */
	public function terminate(int $exit): int
	{
		$this->application?->shutdown();
		return $exit;
	}

	/**
	 *
	 * @param string $message
	 * @return void
	 */
	private function error(string $message): void
	{
		fprintf($this->stderr(), $message);
	}

	/**
	 * Determine the STDERR file
	 *
	 * @return resource
	 */
	private function stderr(): mixed
	{
		if (defined('STDERR')) {
			return STDERR;
		}
		static $stderr = null;
		if ($stderr) {
			return $stderr;
		}
		$stderr = fopen('php://stderr', 'ab');
		return $stderr;
	}

	/**
	 * @return array
	 */
	public function collectCommands(): array
	{
		$failures = [];
		foreach ($this->application->zeskCommandPath() as $path => $prefix) {
			if (is_numeric($path)) {
				$path = $prefix;
			}
			if (is_file($path)) {
				try {
					$this->application->load($path);
				} catch (Throwable $e) {
					$failures[$path] = $e;
				}
			} elseif (is_dir($path)) {
				try {
					$commands = Directory::listRecursive($path, [
						Directory::LIST_RULE_FILE => [
							"#\.php$#" => true, false,
						], Directory::LIST_RULE_DIRECTORY_WALK => [
							"#/\.#" => false, true,
						], Directory::LIST_RULE_DIRECTORY => false, Directory::LIST_ADD_PATH => true,
					]);
					foreach ($commands as $commandInclude) {
						try {
							include_once($commandInclude);
						} catch (Throwable $e) {
							$failures[$commandInclude] = $e;
						}
					}
				} catch (ParameterException) {
				}
			}
		}
		if (count($failures)) {
			foreach ($failures as $path => $throwable) {
				$this->application->error('Command {path} failed {message}', Exception::exceptionVariables($throwable) + ['path' => $path]);
			}
		}
		$this->application->classes->register(get_declared_classes());
		return $this->application->classes->subclasses(Command::class);
	}

	public function collectCommandShortcuts(): array
	{
		$allShortcuts = [];
		$failures = [];
		foreach ($this->collectCommands() as $commandClass) {
			try {
				$reflectionClass = new ReflectionClass($commandClass);
				if ($reflectionClass->isAbstract()) {
					continue;
				}
				$instance = $reflectionClass->newInstanceArgs([$this->application]);
			} catch (ReflectionException $e) {
				$failures[$commandClass] = $e;
				$instance = null;
			}
			if (!$instance instanceof Command) {
				continue;
			}
			$shortcuts = $instance->shortcuts();
			foreach ($shortcuts as $shortcut) {
				if (array_key_exists($shortcut, $allShortcuts)) {
					if (str_starts_with($commandClass, __NAMESPACE__ . '\\')) {
						$this->application->info('Shortcut {shortcut} for {previousClass} overridden by handler {class}', [
							'shortcut' => $shortcut, 'class' => $commandClass,
							'previousClass' => $allShortcuts[$shortcut],
						]);
						$allShortcuts[$shortcut] = $commandClass;
					} else {
						$this->application->debug('Shortcut {shortcut} for {class} will not override existing handler {currentClass}', [
							'shortcut' => $shortcut, 'class' => $commandClass,
							'currentClass' => $allShortcuts[$shortcut],
						]);
					}
				} else {
					$allShortcuts[$shortcut] = $commandClass;
				}
			}
		}
		if (count($failures)) {
			foreach ($failures as $path => $throwable) {
				$this->application->error('Command {path} failed {message}', Exception::exceptionVariables($throwable) + ['path' => $path]);
			}
		}
		return $allShortcuts;
	}

	/**
	 * Run a command
	 *
	 * @param string $shortcut
	 * @param array $args
	 * @return array
	 * @throws NotFoundException
	 */
	public function runCommand(string $shortcut, array $args): array
	{
		$commands = $this->collectCommandShortcuts();
		if (!array_key_exists($shortcut, $commands)) {
			throw new NotFoundException('Command {command} not found', [
				'command' => $shortcut, 'commands' => array_keys($commands),
			]);
		}
		$class = $commands[$shortcut];
		$this->debug("Running $shortcut -> $class");
		$application = $this->application;
		/* @var $command Command */
		try {
			$command = $application->objects->factory($class, $application, array_merge([
				$shortcut,
			], $args), [
				'debug' => $this->debug,
			]);
		} catch (ClassNotFound $e) {
			throw new NotFoundException('Command {command} not found', ['command' => $shortcut], 404, $e);
		}
		$application->setCommand($command);

		try {
			$result = $command->parseArguments(array_merge([$shortcut], $args))->go();
		} catch (ParameterException $e) {
			$command->usage($e->getRawMessage(), $e->variables() + ['exitCode' => Command::EXIT_CODE_ARGUMENTS]);
			$result = Command::EXIT_CODE_ARGUMENTS;
		}

		$args = $command->argumentsRemaining(false);

		try {
			$this->debug('Remaining class arguments: ' . JSON::encode($args));
		} catch (SemanticsException) {
			// JSON failed, bah
		}
		if ($result !== 0) {
			$this->debug("Command $class returned $result");
		}
		if ($result !== 0 || count($args) === 0) {
			exit($result);
		}
		return $args;
	}

	/**
	 * Show usage
	 *
	 * @param string $error
	 * @param int $exitCode
	 * @return int
	 */
	private function usage(string $error = '', int $exitCode = 2): int
	{
		if ($error) {
			$message[] = $error;
			$message[] = '';
		}
		$me = basename($this->command);
		$message[] = 'Usage: ' . $me . ' [ configuration-opts... ] shortcut0 [ shortcut0args ... ]';
		$message[] = '';
		$message[] = 'Runs a command derived from ' . Command::class . ' in the current application.';
		$message[] = '';
		$message[] = 'configuration-opts are:';
		$message[] = '';
		$message[] = '  --set name=value     Sets the global to the value';
		$message[] = '  --unset name         Unset a global or remove the setting';
		$message[] = '  --define name=value  PHP define a value (will only work if not defined)';
		$message[] = '  --cd directory       Change directory to this directory';
		$message[] = '  --config fileName    Load a configuration file into the global application configuration';
		$message[] = '';
		$message[] = 'In addition, when processing environment-args non-matching values are equivalent:';
		$message[] = '';
		$message[] = '  --name=value         Any non-matching argument is considered `--set name=value`';
		$message[] = '  --name               Considered the equivalent of `--set name=true`';
		$message[] = '';
		$message[] = 'Commands are run via shortcut, and all arguments are passed to the command to process.';
		$message[] = '';
		$message[] = 'You can run multiple shortcuts in a single command line:';
		$message[] = '';
		$message[] = '1. Process arguments until a shortcut is found';
		$message[] = '2. (Once and only once) Load the application context (--cd affects this) (foo.application.php)';
		$message[] = '3. Pass remaining arguments and run the shortcut';
		$message[] = '4. Retrieve unused arguments, and repeat step 1.';
		$message[] = '';
		$message[] = 'Some commands support a fixed number of arguments, use the `--` argument to manually delimit ';
		$message[] = 'arguments if needed.';
		$message[] = '';
		$message[] = 'Most commands support `--help`';
		$message[] = 'Try: ' . $me . ' help';

		fwrite(STDERR, implode("\n", $message) . "\n");
		return $exitCode;
	}

	/**
	 * Provide some syntactic sugar for input arguments, converting ___ to \
	 * to avoid fugly command lines to escape backslashes.
	 *
	 * @param array $args
	 * @return array
	 */
	private function argumentSugar(array $args): array
	{
		foreach ($args as $index => $arg) {
			$args[$index] = strtr($arg, [
				'___' => '\\',
			]);
		}
		return $args;
	}

	private function getApplicationPatterns(): array
	{
		global $_ZESK;
		$root_files = null;
		$keys = ['ZESK_APPLICATION_PATTERNS', 'zesk_root_files'];
		foreach ($keys as $key) {
			foreach ([
				$_ZESK, $_SERVER,
			] as $super) {
				if (!is_array($super)) {
					continue;
				}
				$root_files = $super[$key] ?? null;
				if ($root_files) {
					break;
				}
			}
		}
		if (!$root_files) {
			$root_files = '*.application.php';
		}
		return explode(' ', $root_files);
	}

	private function findApplicationAbove(string $dir): string
	{
		$root_files = $this->getApplicationPatterns();
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
		return '';
	}

	/**
	 * Find the application file from the CWD or the search directory
	 *
	 * @return string
	 */
	public function findApplication(): string
	{
		$root_files = $this->getApplicationPatterns();
		if (count($this->search) === 0) {
			$this->search[] = getcwd();
		}
		foreach ($this->search as $dir) {
			$result = $this->findApplicationAbove($dir);
			if ($result) {
				return $result;
			}
		}
		$this->error('No zesk ' . implode(', ', $root_files) . ' found in: ' . implode(', ', $this->search) . "\n");
		return '';
	}

	/**
	 *
	 * @param string $arg
	 * @return int
	 * @throws ExitedException
	 */
	private function zeskLoaded(string $arg): int
	{
		if ($this->application instanceof Application) {
			return 0;
		}
		if (!$this->zeskIsLoaded()) {
			return $this->usage("Zesk not initialized correctly.\n\n    $arg\n\nmust contain reference to:\n\n    require_once '" . ZESK_ROOT . "autoload.php';\n\n");
		}

		try {
			$app = Kernel::singleton()->application();
			$app->setConsole(true);
			$this->application = $app;
			if (count($this->wait_configs) > 0) {
				$this->debug('Loading ' . implode(', ', $this->wait_configs) . ' ...');
				$app->configureFiles($this->wait_configs);
				$this->loaded_configs = array_merge($this->loaded_configs, $this->wait_configs);
				$this->wait_configs = [];
			}

			$this->commands = $this->collectCommandShortcuts();
			return 0;
		} catch (SemanticsException $e) {
			throw new ExitedException('No application', [], 0, $e);
		}
	}

	/**
	 *
	 * @return boolean
	 */
	private function zeskIsLoaded(): bool
	{
		return class_exists('zesk\Kernel', false);
	}

	/**
	 * Handle --set name=value
	 * Handle --name=value
	 *
	 * Consumes one additional argument of form name=value
	 *
	 * @param array $args
	 * @return array
	 */
	private function handleSet(array $args): array
	{
		$pair = array_shift($args);
		if ($pair === null) {
			$this->usage('--set missing argument');
		}

		[$key, $value] = explode('=', $pair, 2) + [
			null, true,
		];
		if ($key === 'debug') {
			$this->debug = true;
		}
		$this->debug("Set global $key to $value");
		if ($this->zeskIsLoaded()) {
			$this->application->configuration->setPath($key, $value);
		} else {
			global $_ZESK;
			$keyPath = Types::configurationKey($key);
			$this->global_context[Types::keyToString($keyPath)] = $value;
			ArrayTools::setPath($_ZESK, $keyPath, $value);
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
	private function handleUnset(array $args): array
	{
		$key = array_shift($args);
		if ($key === null) {
			$this->usage('--unset missing argument');
		}
		$this->debug("Unset global $key");
		if ($this->zeskIsLoaded()) {
			$this->application->configuration->setPath($key);
		} else {
			global $_ZESK;
			$keyPath = Types::configurationKey($key);
			$this->global_context[Types::keyToString($keyPath)] = null;
			ArrayTools::unsetPath($_ZESK, $key);
		}
		return $args;
	}

	/**
	 * Handle --cd
	 *
	 * @param array $args
	 * @return array
	 * @throws ParameterException
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	private function handleCD(array $args): array
	{
		$arg = array_shift($args);
		if ($arg === null) {
			throw new ParameterException('--cd missing directory argument');
		}
		if (!is_dir($arg) && !is_link($arg)) {
			throw new DirectoryNotFound($arg, 'Not a directory "{path}"');
		}
		if (!@chdir($arg)) {
			throw new DirectoryPermission($arg, 'Unable to change directory to "{path}"');
		}
		return $args;
	}

	/**
	 * Handle --define
	 *
	 * @param array $args
	 * @return array
	 */
	private function handleDefine(array $args): array
	{
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage('--define missing argument');
		}
		[$name, $value] = explode('=', $arg, 2) + [
			$arg, true,
		];
		if (!defined($name)) {
			define($name, $value);
		} else {
			$this->error("$name command line definition is already defined");
		}
		return $args;
	}

	/**
	 * Handle --config
	 *
	 * @param array $args
	 * @return array
	 * @throws ParseException
	 */
	private function handleConfig(array $args): array
	{
		$arg = array_shift($args);
		if ($arg === null) {
			$this->usage('--config missing argument');
		}
		if (!is_file($arg)) {
			$this->usage("$arg is not a file to load configuration");
		}
		if ($this->zeskIsLoaded()) {
			/* @var $locale Locale */
			$this->debug('Loading configuration file {file}', [
				'file' => $arg,
			]);
			$this->application->loader->loadFile($arg);
		} else {
			$this->wait_configs[] = $arg;
			$this->debug('Loading configuration file {file} (queued)', [
				'file' => $arg,
			]);
		}
		return $args;
	}

	/**
	 *
	 * @param string[] $array
	 * @return string[]
	 */
	public static function wrap_brackets(array $array): array
	{
		$result = [];
		foreach ($array as $k => $v) {
			$result['{' . $k . '}'] = $v;
		}
		return $result;
	}

	/**
	 * Output a debug message
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	private function debug(string $message, array $context = []): void
	{
		if ($this->debug) {
			$context = self::wrap_brackets($context);
			echo __CLASS__ . ' ' . rtrim(strtr($message, $context), "\n") . "\n";
		}
	}
}
