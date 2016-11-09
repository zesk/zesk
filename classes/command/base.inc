<?php

/**
 * Base functionality for most commands
 *
 * @author kent
 * @copyright &copy; 2013 Market Acumen, Inc.
 */
abstract class Command_Base extends Command {
	protected $quiet = false;
	public function log($message, array $arguments = array()) {
		if ($this->quiet) {
			return;
		}
		return parent::log($message, $arguments);
	}
	/**
	 * Pre-flight, and add standard options
	 *
	 * @see Command::initialize()
	 */
	protected function initialize() {
		$this->inherit_global_options();
		$this->option_types['log'] = 'string';
		$this->option_types['log-level'] = 'string';
		$this->option_types['debug'] = 'boolean';
		$this->option_types['debug-config'] = 'boolean';
		$this->option_types['verbose'] = 'boolean';
		$this->option_types['quiet'] = 'boolean';
		$this->option_types['help'] = 'boolean';
		
		$this->option_help['log'] = "Name of the log file to output log messages to (default is stdout, use - to use stdout)";
		$this->option_help['log-level'] = "(Deprecated) Use --severity.";
		$this->option_help['severity'] = "Maximum log severity to output";
		$this->option_help['debug'] = "Debugging logging enabled";
		$this->option_help['debug-config'] = "Output the configuration load order similar to the zesk config command.";
		$this->option_help['verbose'] = 'Output more messages to assist in debugging problems, or just for fun.';
		$this->option_help['quiet'] = 'Supress all log messages to stdout overriding --verbose and --debug.';
		$this->option_help['help'] = "This help.";
		parent::initialize();
	}
	
	/**
	 * 
	 */
	protected function configure_logging() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		if ($this->option_bool("quiet")) {
			$this->quiet = true;
			return;
		}
		$debug = $this->option_bool("debug");
		$severity = $this->option("severity", $this->option("log-level", $debug ? "debug" : "info"));
		$all_levels = $zesk->logger->levels_select($severity);
		
		if (($filename = $this->option("log")) !== null) {
			$modules = $this->application->modules->load("Logger_File");
			$log_file = new zesk\Logger\File();
			if ($filename !== '-') {
				$log_file->filename($filename);
			} else {
				$log_file->fp(self::stdout());
			}
			$zesk->logger->register_handler("Command", $log_file, $all_levels);
			if ($this->option("debug_log_file")) {
				$zesk->logger->info("Registered {log_file} for {all_levels}", compact("log_file", "all_levels"));
			}
		} else {
			$zesk->logger->register_handler("Command", $this, $all_levels);
			if ($this->option("debug_log_file")) {
				$zesk->logger->info("Registered generic logger {all_levels}", compact("all_levels"));
			}
		}
	}
	
	/**
	 * 
	 */
	protected function hook_run_before() {
		$this->configure_logging();
		if ($this->option_bool('help')) {
			$this->usage();
		}
		if ($this->option_bool('debug-config')) {
			$this->application->hooks->add("zesk\Application::configured", array(
				$this,
				"action_debug_configured"
			));
		}
	}
	
	/**
	 * 
	 */
	public function action_debug_configured() {
		$config = new Command_Config();
		exit($config->go());
	}
}
