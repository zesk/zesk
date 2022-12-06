<?php declare(strict_types=1);
namespace zesk;

/**
 * Permission commands:
 *
 *     zesk permission hooks - Output list of hooks called to generate permissions
 *
 * @author kent
 * @category ORM Module
 */
class Command_Permission extends Command_Base {
	protected array $option_types = [
		'format' => 'string',
	];

	protected array $option_help = [
		'format' => 'Output format',
	];

	/**
	 * @var Module_Permission
	 */
	protected $module = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run() {
		$command = $this->getArgument('command');
		if (!$command) {
			return $this->usage();
		}
		$this->module = $this->application->modules->object('permission');
		$hook = "command_$command";
		if (!$this->hasHook($hook)) {
			$this->usage('Unknown command {command}', [
				'command' => $command,
			]);
		}
		return $this->callHook($hook);
	}

	public function hook_command_hooks() {
		return $this->renderFormat(array_values($this->module->hook_methods()));
	}
}
