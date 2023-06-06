<?php
declare(strict_types=1);

namespace zesk\ORM\Command;

use zesk\Command;
use zesk\Exception\ClassNotFound;

/**
 * Display a list of all included files so far
 *
 * @category Debugging
 */
class PHPSchema extends Command {
	protected array $option_types = [
		'class' => 'string',
	];

	protected array $shortcuts = ['php-schema'];

	public function run(): int {
		$app = $this->application;
		$class = $this->option('class');
		if (!$class) {
			echo "/* No class specified */\n";
			exit(3);
		}

		try {
			$object = $app->ormModule()->ormFactory($app, $class);
		} catch (ClassNotFound) {
			echo "/* $class: No such class $class */\n";
			return self::EXIT_CODE_ENVIRONMENT;
		}
		$schema = $object->schema();
		if (!$schema) {
			echo "/* $class: No schema */\n";
			return self::EXIT_CODE_ENVIRONMENT;
		}
		$schema = $schema->schema();
		if (empty($schema)) {
			echo "/* $class: Schema empty */\n";
			return self::EXIT_CODE_ENVIRONMENT;
		} else {
			echo $this->application->themes->theme('Command/PHPSchema', [
				'class_name' => 'Schema_' . $object::class,
				'schema' => $schema,
			]);
		}
		return 0;
	}
}
