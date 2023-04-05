<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use Psr\Log\LogLevel;
use zesk\Application\Hooks;
use zesk\Command;
use zesk\Exception\ExitedException;
use zesk\Logger\FileLogger;
use zesk\Logger\Handler;
use const STDOUT;

/**
 * @see Command
 * @author kent
 *
 */
abstract class SimpleCommand extends Command implements Handler {
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
		'info' => true, 'notice' => true, 'debug' => true,
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
		$levels = [
			LogLevel::INFO => true, LogLevel::NOTICE => true, LogLevel::DEBUG => $this->optionBool('debug'),
		];
		if (($filename = $this->option('log')) !== null) {
			$log_file = new FileLogger($filename !== '-' ? $filename : self::stdout());
			$log_file->setLevels($levels);
			$this->application->logger = $log_file->setChild($this->application->logger);
			if ($this->option('debug_log_file')) {
				$this->application->logger->info('Registered file logger {file}', ['file' => $filename]);
			}
		}
	}

	/**
	 * @return void
	 * @throws ExitedException
	 */
	protected function hook_runBefore(): void {
		$this->configure_logging();
		if ($this->optionBool('help')) {
			$this->usage();

			throw new ExitedException();
		}
		if ($this->optionBool('debug-config')) {
			$this->application->addHook(Hooks::HOOK_CONFIGURED, [
				$this, 'action_debug_configured',
			]);
		}
	}

	protected function handle_base_options(): int {
		if ($this->optionBool('debug-config')) {
			return $this->action_debug_configured(false);
		}
		return 0;
	}

	/**
	 */
	public function action_debug_configured(bool $exit = true): int {
		require_once($this->application->zeskHome('command/config.php'));
		$config = new Command_Configuration($this->application, $this->options());
		$result = $config->parseArguments([])->run();
		if ($exit) {
			exit($result);
		}
		return $result;
	}
}
