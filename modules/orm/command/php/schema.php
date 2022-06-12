<?php declare(strict_types=1);
namespace zesk;

/**
 * Display a list of all included files so far
 *
 * @category Debugging
 */
class Command_PHP_Schema extends Command {
	protected array $option_types = [
		'class' => 'string',
	];

	public function run(): void {
		$db = $this->application->database_registry();

		$class = $this->option('class');
		if (!$class) {
			echo "/* No class specified */\n";
			exit(3);
		}

		try {
			$object = $this->application->ormFactory($class);
		} catch (Exception_Class_NotFound $e) {
			echo "/* $class: No such class $class */\n";
			exit(2);
		}
		/* @var $schema ORM_Schema */
		$schema = $object->schema();
		if (!$schema) {
			echo "/* $class: No schema */\n";
			exit(2);
		}
		$schema = $schema->schema();
		if (empty($schema)) {
			echo "/* $class: Schema empty */\n";
			exit(1);
		} else {
			echo $this->application->theme('command/php/schema', [
				'class_name' => 'Schema_' . get_class($object),
				'schema' => $schema,
			]);
		}
	}
}
