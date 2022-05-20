<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\Command_Base;

/**
 * Manage web apps using a command-line API
 *
 * Current commands are: generate, index, configure
 *
 * @author kent
 * @category Web Application Manager
 * @author kent
 */
class Command_WebApp_API extends Command_Base {
	protected array $load_modules = [
		'WebApp',
	];

	protected array $option_types = [
		'*' => 'string',
	];

	protected array $option_help = [
		'*' => 'api commands to run',
	];

	/**
	 *
	 * @var Module
	 */
	private $webapp = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	protected function run() {
		$this->configure('webapp-api');

		$this->webapp = $this->application->webapp_module();

		$result = [];
		while ($this->has_arg()) {
			$command = $this->get_arg('command');
			if ($command === '--') {
				return 0;
			}
			if ($this->has_hook("command_$command")) {
				$result[$command] = $this->call_hook("command_$command");
			} else {
				$this->error('No such command {command}', [
					'command' => $command,
				]);
			}
		}
		if (count($result) > 0) {
			$this->render_format($result, $this->option('format'), 'json');
		}
		return 0;
	}

	/**
	 * Command: configure
	 *
	 * @return array
	 */
	protected function hook_command_configure() {
		return $this->webapp->server_actions('configure');
	}

	/**
	 * Command: configure
	 *
	 * @return array
	 */
	protected function hook_command_generate() {
		return $this->webapp->server_actions('generate');
	}

	/**
	 * Command: configure
	 *
	 * @return array
	 */
	protected function hook_command_index() {
		return $this->webapp->server_actions('index');
	}
}
