<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Command_Base extends Command {
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
		"info" => true,
		"notice" => true,
		"debug" => true,
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::log()
	 */
	public function log(mixed $message, array $arguments = []): void {
		if ($this->quiet) {
			$severity = avalue($arguments, "severity", "info");
			if (array_key_exists($severity, self::$quiet_levels)) {
				return;
			}
		}
		parent::log($message, $arguments);
	}

	/**
	 * Pre-flight, and add standard options
	 *
	 * @see Command::initialize()
	 */
	protected function initialize(): void {
		$this->inherit_global_options();
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

		$this->option_help['log'] = "Name of the log file to output log messages to (default is stdout, use - to use stdout)";
		$this->option_help['log-level'] = "(Deprecated) Use --severity.";
		$this->option_help['severity'] = "Maximum log severity to output";
		$this->option_help['debug'] = "Debugging logging enabled";
		$this->option_help['debug-config'] = "Output the configuration load order similar to the zesk config command.";
		$this->option_help['no-config'] = "Do not load the configuration for this command when $this->configure(\"name\") is called";
		$this->option_help['verbose'] = 'Output more messages to assist in debugging problems, or just for fun.';
		$this->option_help['ansi'] = 'Force ANSI colors in output';
		$this->option_help['no-ansi'] = 'Disable ANSI colors in output';
		$this->option_help['quiet'] = 'Supress all log messages to stdout overriding --verbose and --debug.';
		$this->option_help['help'] = "This help.";

		if (isset($this->option_types['format']) && !isset($this->option_help['format'])) {
			$this->option_help['format'] = "Output format: JSON, Text, Serialize, PHP";
		}
		parent::initialize();
	}

	public function stdout() {
		if (defined("STDOUT")) {
			return STDOUT;
		}
		static $stdout = null;
		if ($stdout) {
			return $stdout;
		}
		return $stdout = fopen("php://stdout", "wb");
	}

	/**
	 */
	protected function configure_logging(): void {
		if ($this->optionBool("quiet")) {
			$this->quiet = true;
			return;
		}
		$debug = $this->optionBool("debug");
		$severity = $this->option("severity", $this->option("log-level", $debug ? "debug" : "info"));
		$logger = $this->application->logger;
		$all_levels = $logger->levels_select($severity);

		if (($filename = $this->option("log")) !== null) {
			$modules = $this->application->modules->load("Logger_File");
			$log_file = new Logger\File();
			if ($filename !== '-') {
				$log_file->filename($filename);
			} else {
				$log_file->fp(self::stdout());
			}
			$logger->register_handler("Command", $log_file, $all_levels);
			if ($this->option("debug_log_file")) {
				$logger->info("Registered {log_file} for {all_levels}", compact("log_file", "all_levels"));
			}
		} else {
			$logger->register_handler("Command", $this, $all_levels);
			if ($this->option("debug_log_file")) {
				$logger->info("Registered generic logger {all_levels}", compact("all_levels"));
			}
		}
	}

	/**
	 */
	protected function hook_run_before(): void {
		$this->configure_logging();
		if ($this->optionBool('help')) {
			$this->usage();
		}
		if ($this->optionBool('debug-config')) {
			$this->application->hooks->add("zesk\Application::configured", [
				$this,
				"action_debug_configured",
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
		require_once($this->application->zesk_home("command/config.php"));
		$config = new Command_Config($this->application, [], $this->options());
		$result = $config->run();
		if ($exit) {
			exit($result);
		}
		return $result;
	}
}
