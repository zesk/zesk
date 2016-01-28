<?php

/**
 * Display a list of all included files so far
 * @category Debugging
 */
class Command_PHP_Schema extends Command {
	
	protected $option_types = array(
		'class' => 'string'
	);
	function run() {
		$db = Database::instance();
		
		$class = $this->option('class');
		if (!$class) {
			echo "/* No class specified */\n";
			exit(3);
		}
		try {
			$object = Object::factory($class);
		} catch (Exception_Class_NotFound $e) {
			echo "/* $class: No such class $class */\n";
			exit(2);
		}
		/* @var $schema Database_Schema */
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
			echo Template::instance('command/php/schema.tpl', array(
				'class_name' => 'Schema_' . get_class($object), 
				'schema' => $schema
			));
		}
	}
}