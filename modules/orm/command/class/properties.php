<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Generate doccomment @property list for any ORM/Class_ORM pair in the system
 *
 * @category ORM Module
 * @author kent
 */
class Command_Class_Properties extends Command_Base {
	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'*' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	public static $types_map = [
		Class_ORM::type_serialize => 'array',
		Class_ORM::type_ip => 'string',
		Class_ORM::type_created => '\zesk\Timestamp',
		Class_ORM::type_modified => '\zesk\Timestamp',
		Class_ORM::type_timestamp => '\zesk\Timestamp',
		Class_ORM::type_time => '\zesk\Time',
		Class_ORM::type_date => '\zesk\Date',
		Class_ORM::type_hex => 'string',
		Class_ORM::type_text => 'string',
		Class_ORM::type_id => 'integer',
	];

	/**
	 *
	 * @return string[]
	 */
	private function all_classes() {
		return ArrayTools::extract($this->application->orm_module()->all_classes(), null, 'class');
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run(): void {
		$classes = [];
		while ($this->has_arg()) {
			$arg = $this->get_arg('class');
			if ($arg === 'all') {
				$classes = array_merge($classes, $this->all_classes());
			} else {
				$classes[] = $arg;
			}
		}
		foreach ($classes as $class) {
			$class_object = $this->application->class_ormRegistry($class);
			if (!$class_object) {
				$this->error("No such class $arg");

				continue;
			}
			$result = [];
			foreach ($class_object->column_types as $name => $type) {
				$type = avalue(self::$types_map, $type, $type);
				$result[$name] = "@property $type \$$name";
			}
			foreach ($class_object->has_one as $name => $type) {
				$result[$name] = "@property \\$type \$$name";
			}
			echo "/**\n * @see " . $class_object::class . "\n" . ArrayTools::joinWrap($result, ' * ', "\n") . " */\n\n";
		}
	}
}
