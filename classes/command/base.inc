<?php

/**
 * Base functionality for most commands
 *
 * @author kent
 * @copyright &copy; 2013 Market Acumen, Inc.
 */
abstract class Command_Base extends Command {

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
		$this->option_types['verbose'] = 'boolean';
		$this->option_types['quiet'] = 'boolean';
		$this->option_types['help'] = 'boolean';

		$this->option_help['log'] = "Name of the log file to output log messages to (default is stdout, use - to use stdout)";
		$this->option_help['log-level'] = "Name of the log file to output log messages to (default is stdout, use - to use stdout)";
		$this->option_help['debug'] = "Debugging logging enabled";
		$this->option_help['verbose'] = 'Output more messages to assist in debugging problems, or just for fun.';
		$this->option_help['quiet'] = 'Supress all log messages to stdout overriding --verbose and --debug.';
		$this->option_help['help'] = "This help.";
		parent::initialize();
	}

	protected function configure_logging() {
		Module::internal('log_echo');
		if (($log = $this->option("log")) !== null) {
			if ($log !== '-') {
				log::file($log);
				Module::object('log_echo')->disabled(true);
			} else {
				log::file(null);
			}
		} else {
			if ($this->option_bool("quiet")) {
				log::file(null);
				Module::object('log_echo')->disabled(true);
				log::level(0);
			}
		}
		if ($this->has_option("log-level")) {
			log::level($this->option("log-level"));
		} else {
			if ($this->option_bool('debug')) {
				log::level(log::DEBUG);
			} else {
				log::level(log::NOTICE);
			}
		}
	}

	protected function hook_run_before() {
		$this->configure_logging();
		if ($this->option_bool('help')) {
			$this->usage();
		}
	}
}
