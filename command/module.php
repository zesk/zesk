<?php declare(strict_types=1);
namespace zesk;

/**
 * Load a module
 *
 * @category Modules
 */
class Command_Module extends Command_Base {
	protected $help = 'Load a module.';

	protected array $option_types = [
		'+' => 'string',
	];

	public function run() {
		$this->application->modules->load($this->get_arg('module'));
		return 0;
	}
}
