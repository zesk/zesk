<?php
/**
 * 
 */
namespace zesk;

/**
 * Generate doccomment @property list for any Object/Class_Object pair in the system
 *
 * @category Object System
 * @author kent
 */
class Command_Class_Properties extends Command_Base {
	protected $option_types = array(
		"*" => "string"
	);
	static $types_map = array(
		Class_Object::type_serialize => 'array',
		Class_Object::type_ip => 'string',
		Class_Object::type_created => '\zesk\Timestamp',
		Class_Object::type_modified => '\zesk\Timestamp',
		Class_Object::type_timestamp => '\zesk\Timestamp',
		Class_Object::type_time => '\zesk\Time',
		Class_Object::type_date => '\zesk\Date',
		Class_Object::type_hex => 'string',
		Class_Object::type_text => 'string',
		Class_Object::type_id => 'integer'
	);
	private function all_classes() {
		return arr::key_value($this->application->all_classes(), null, "class");
	}
	function run() {
		$classes = array();
		while ($this->has_arg()) {
			$arg = $this->get_arg("class");
			if ($arg === "all") {
				$classes = array_merge($classes, $this->all_classes());
			} else {
				$classes[] = $arg;
			}
		}
		foreach ($classes as $class) {
			$class_object = $this->application->class_object($class);
			if (!$class_object) {
				$this->error("No such class $arg");
				continue;
			}
			$result = array();
			foreach ($class_object->column_types as $name => $type) {
				$type = avalue(self::$types_map, $type, $type);
				$result[$name] = "@property $type \$$name";
			}
			foreach ($class_object->has_one as $name => $type) {
				$result[$name] = "@property \\$type \$$name";
			}
			echo "/**\n * @see " . get_class($class_object) . "\n" . arr::join_wrap($result, " * ", "\n") . " */\n\n";
		}
	}
}
