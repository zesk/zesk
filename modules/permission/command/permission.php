<?php
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
	protected $option_types = array(
		"format" => "string"
	);
	protected $option_help = array(
		"format" => "Output format"
	);
	/**
	 * @var Module_Permission
	 */
	protected $module = null;
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	function run() {
		$command = $this->get_arg("command");
		if (!$command) {
			return $this->usage();
		}
		$this->module = $this->application->modules->object("permission");
		$hook = "command_$command";
		if (!$this->has_hook($hook)) {
			$this->usage("Unknown command {command}", array(
				"command" => $command
			));
		}
		return $this->call_hook($hook);
	}
	function hook_command_hooks() {
		return $this->render_format(array_values($this->module->hook_methods()));
	}
}