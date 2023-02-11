<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

use zesk\Exception;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Unimplemented;
use zesk\PHP;
use zesk\Command_Base as CommandBase;

/**
 * Add a module to zesk, creating basic class names and configuration files.
 *
 * @category Modules
 */
class Command_ClassNew extends CommandBase {
	protected array $option_types = [
		'app' => 'boolean',
		'zesk' => 'boolean',
		'sql' => 'boolean',
		'schema' => 'boolean',
		'*' => 'string',
	];

	protected array $shortcuts = ['new-class'];

	protected array $option_help = [
		'app' => 'Create class in the application (default)',
		'zesk' => 'Create class in zesk',
		'sql' => 'Create SQL file instead of a Schema file (default)',
		'schema' => 'Create Schema class instead of a SQL file',
		'*' => 'Names of the classes to create (capitalization matters)',
	];

	public function run(): int {
		$names = $this->argumentsRemaining();
		if (count($names) === 0) {
			$this->usage('Must specify class names to create');
		}
		foreach ($names as $class) {
			if (!PHP::validClass($class)) {
				$this->error("Class $class is not a valid class name");

				continue;
			}

			try {
				$object = $this->application->ormFactory($class);
				$this->error("Class $class already exists");

				continue;
			} catch (Exception_Class_NotFound $e) {
			}

			try {
				$this->questionnaire($class);
			} catch (Exception $e) {
				$this->error($e);

				continue;
			}
		}
		return 0;
	}

	public function questionnaire($class): void {
		throw new Exception_Unimplemented(__METHOD__);
	}
}
