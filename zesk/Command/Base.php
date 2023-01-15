<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Log\LogLevel;
use zesk\Logger\Handler;

/**
 * @see Command
 * @author kent
 *
 */
abstract class Command_Base extends Command implements Handler {
	/**
	 *
	 * @var boolean
	 */
	protected bool $quiet = false;

	/**
	 *
	 * @var array
	 */
	private static array $quiet_levels = [
		'info' => true,
		'notice' => true,
		'debug' => true,
	];

	/**
	 *
	 * @param mixed $message
	 * @param array $context
	 * @return void
	 */
	public function log(mixed $message, array $context = []): void {
		if ($this->quiet) {
			$severity = $context['severity'] ?? LogLevel::INFO;
			if (array_key_exists($severity, self::$quiet_levels)) {
				return;
			}
		}
		parent::log($message, $context);
	}

	/**
	 * Pre-flight, and add standard options
	 *
	 * @see Command::initialize()
	 */
	protected function initialize(): void {
		$this->inheritConfiguration();
		$this->option_types['log'] = 'string';
		$this->option_types['log-level'] = 'string';
		$this->option_types['debug'] = 'boolean';
		$this->option_types['debug-config'] = 'boolean';
		$this->option_types['no-config'] = 'boolean';
		$this->option_types['verbose'] = 'boolean';
		$this->option_types['ansi'] = 'boolean';
		$this->option_types['no-ansi'] = 'boolean';
		$this->option_types['quiet'] = 'boolean';
		$this->option_types['help'] = 'boolean';

		$this->option_help['log'] = 'Name of the log file to output log messages to (default is stdout, use - to use stdout)';
		$this->option_help['severity'] = 'Maximum log severity to output';
		$this->option_help['debug'] = 'Debugging logging enabled (implies --severity debug)';
		$this->option_help['debug-config'] = 'Output the configuration load order similar to the zesk config command.';
		$this->option_help['no-config'] = 'Do not load the command configuration for this command (error when no configuration exists)';
		$this->option_help['verbose'] = 'Be more verbose';
		$this->option_help['ansi'] = 'Force ANSI colors in output (defaults to true if a console is detected)';
		$this->option_help['no-ansi'] = 'Disable ANSI colors in output (overrides any automatic detection)';
		$this->option_help['quiet'] = 'Suppress all log messages to stdout overriding --verbose and --debug.';
		$this->option_help['help'] = 'This help.';

		if (isset($this->option_types['format']) && !isset($this->option_help['format'])) {
			$this->option_help['format'] = 'Output format: JSON, Text, Serialize, PHP';
		}
		parent::initialize();
	}

	public function stdout() {
		if (defined('STDOUT')) {
			return STDOUT;
		}
		static $stdout = null;
		if ($stdout) {
			return $stdout;
		}
		return $stdout = fopen('php://stdout', 'wb');
	}

	/**
	 */
	protected function configure_logging(): void {
		if ($this->optionBool('quiet')) {
			$this->quiet = true;
			return;
		}
		$debug = $this->optionBool('debug');
		$severity = $this->option('severity', $this->option('log_level', $debug ? 'debug' : 'info'));
		$logger = $this->application->logger;
		$all_levels = $logger->levelsSelect($severity);

		if (($filename = $this->option('log')) !== null) {
			$this->application->modules->load('Logger_File');
			$log_file = new Logger\File();
			if ($filename !== '-') {
				$log_file->filename($filename);
			} else {
				$log_file->fp(self::stdout());
			}
			$logger->registerHandler('Command', $log_file, $all_levels);
			if ($this->option('debug_log_file')) {
				$logger->info('Registered {log_file} for {all_levels}', compact('log_file', 'all_levels'));
			}
		} else {
			$logger->registerHandler('Command', $this, $all_levels);
			if ($this->option('debug_log_file')) {
				$logger->info('Registered generic logger {all_levels}', compact('all_levels'));
			}
		}
	}

	/**
	 * @return void
	 * @throws Exception_Exited
	 */
	protected function hook_run_before(): void {
		$this->configure_logging();
		if ($this->optionBool('help')) {
			$this->usage();

			throw new Exception_Exited();
		}
		if ($this->optionBool('debug-config')) {
			$this->application->addHook(\zesk\Hooks::HOOK_CONFIGURED, [
				$this,
				'action_debug_configured',
			]);
		}
	}

	protected function handle_base_options() {
		if ($this->optionBool('debug-config')) {
			return $this->action_debug_configured(false);
		}
	}

	/**
	 */
	public function action_debug_configured($exit = true) {
		require_once($this->application->zeskHome('command/config.php'));
		$config = new Command_Config($this->application, [], $this->options());
		$result = $config->run();
		if ($exit) {
			exit($result);
		}
		return $result;
	}
}
