<?php
/**
 *
 */
namespace zesk;

/**
 * Add a module to zesk, creating basic class names and configuration files.
 *
 * @category Modules
 */
class Command_Class_New extends Command {
	protected $option_types = array(
		'app' => 'boolean',
		'zesk' => 'boolean',
		'sql' => 'boolean',
		'schema' => 'boolean',
		'*' => 'string',
	);

	protected $option_help = array(
		'app' => 'Create class in the application (default)',
		'zesk' => 'Create classin zesk',
		'sql' => 'Create SQL file instead of a Schema file (default)',
		'schema' => 'Create Schema class instead of a SQL file',
		'*' => "Names of the classes to create (capitalization matters)",
	);

	public function run() {
		$names = $this->arguments_remaining(true);
		if (count($names) === 0) {
			$this->usage("Must specify class names to create");
		}
		foreach ($names as $class) {
			if (!PHP::valid_class($class)) {
				$this->error("Class $class is not a valid class name");

				continue;
			}

			try {
				$object = $this->application->orm_factory($class);
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
	}

	public function questionnaire($class) {
		throw new Exception_Unimplemented(__METHOD__);
	}
}
