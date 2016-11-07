<?php
/**
 * 
 */
use zesk\Database;
use zesk\Timestamp;

/**
 * Generate doccomment @property list for any Object/Class_Object pair in the system
 *
 * @category Object System
 * @author kent
 */
class Command_Class_Check extends Command_Base {
	protected $option_types = array(
		"*" => "string"
	);
	static $types_map = array(
		'serialize' => 'array',
		'ip' => 'string',
		'ip' => 'string',
		'created' => 'Timestamp',
		'created' => 'Timestamp',
		'modified' => 'Timestamp'
	);
	function run() {
		$classes = array();
		while ($this->has_arg()) {
			$arg = $this->get_arg("class");
			if ($arg === "all") {
				$classes = array_merge($classes, Application::instance()->classes());
			} else {
				$classes[] = $arg;
			}
		}
		if (count($classes) === 0) {
			$classes = Application::instance()->classes();
		}
		foreach ($classes as $class) {
			$this->verbose_log("Checking class {class}", array(
				"class" => $class
			));
			/* @var $class_object Class_Object */
			/* @var $object Object */
			$class_object = Object::cache_class($class, "class");
			if (!$class_object) {
				$this->error("No such class $arg");
				continue;
			}
			$error_args = array(
				"class" => $class,
				"table" => $class_object->table
			);
			$object = Object::cache_class($class, "object");
			if (!$object) {
				log::notice("Object class {class} does not have an associated object", array(
					"class" => $class
				));
				continue;
			}
			$schema = $object->schema();
			if (!$schema) {
				log::notice("Object class {class} does not have an associated schema", array(
					"class" => $class
				));
				continue;
			}
			$schema = $schema->map($schema->schema());
			$table = avalue($schema, $object->table());
			if (!$table) {
				$this->error("{class} does not have table ({table}) associated with schema: {tables} {debug}", $error_args + array(
					"tables" => array_keys($schema),
					"debug" => _dump($schema)
				));
				continue;
			}
			$table_columns = $table['columns'];
			$missing = array();
			foreach ($table_columns as $column => $column_options) {
				if (!array_key_exists($column, $class_object->column_types)) {
					$missing[] = "'$column' => self::type_" . $this->guess_type($class_object->database(), $column, $column_options['type']) . ",";
				}
			}
			if (count($missing)) {
				$this->error("{class} is missing:\npublic \$column_types = array(\n\t{missing}\n);", $error_args + array(
					'missing' => implode("\n\t", $missing)
				));
			}
			foreach ($class_object->column_types as $column => $simple_type) {
				if (!array_key_exists($column, $table_columns)) {
					$this->error("{class} defined \$column_types[$column] but does not exist in SQL", $error_args);
				}
			}
			foreach ($class_object->has_one as $column => $class_type) {
				if (!array_key_exists($column, $table_columns)) {
					$this->error("{class} defined \$has_one[$column] but does not exist in SQL", $error_args);
				}
				if (!array_key_exists($column, $class_object->column_types)) {
					$this->error("{class} defined \$has_one[{column}] but does not exist, please add it: \$column_types => \"{column}\" => self::type_object,", $error_args + array(
						"column" => $column
					));
				} else if ($class_object->column_types[$column] !== Class_Object::type_object) {
					$this->error("{class} defined \$has_one[{column}] but wrong type {type}: \$column_types => \"{column}\" => self::type_object,", $error_args + array(
						"column" => $column,
						"type" => $class_object->column_types[$column]
					));
				}
			}
		}
		$this->log("Done");
	}
	static $guess_names = array(
		'timestamp' => array(
			'created' => 'created',
			'modified' => 'modified'
		),
		'integer' => array(
			'id' => 'id'
		)
	);
	static $guess_types = array(
		'timestamp' => 'timestamp',
		'blob' => 'serialize'
	);
	private function guess_type(Database $db, $name, $type) {
		$schema_type = avalue(avalue(self::$guess_names, $type, array()), strtolower($name));
		if ($schema_type) {
			return $schema_type;
		}
		if (array_key_exists($type, self::$guess_types)) {
			return self::$guess_types[$type];
		}
		return $db->data_type()->native_type_to_data_type($type);
	}
}
