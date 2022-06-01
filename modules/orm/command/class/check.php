<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * Checks \zesk\ORM descendants for issues with missing fields, etc.
 *
 * @category Debugging
 * @author kent
 */
class Command_Class_Check extends Command_Base {
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
		'serialize' => 'array',
		'ip' => 'string',
		'ip' => 'string',
		'created' => 'Timestamp',
		'created' => 'Timestamp',
		'modified' => 'Timestamp',
	];

	/**
	 *
	 * @return \zesk\Ambigous[]
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
		$logger = $this->application->logger;
		$classes = [];
		while ($this->has_arg()) {
			$arg = $this->get_arg('class');
			if ($arg === 'all') {
				$classes = array_merge($classes, $this->all_classes());
			} else {
				$classes[] = $arg;
			}
		}
		if (count($classes) === 0) {
			$classes = $this->all_classes();
		}
		foreach ($classes as $class) {
			$this->verbose_log('Checking class {class}', [
				'class' => $class,
			]);
			/* @var $class_object Class_ORM */
			/* @var $object \zesk\ORM */
			$class_object = $this->application->class_orm_registry($class);
			if (!$class_object) {
				$this->error("No such class $arg");

				continue;
			}
			$error_args = [
				'class' => $class,
				'table' => $class_object->table,
			];
			$object = $this->application->orm_registry($class);
			if (!$object) {
				$logger->notice('Object class {class} does not have an associated object', [
					'class' => $class,
				]);

				continue;
			}
			$schema = $object->schema();
			if (!$schema) {
				$logger->notice('Object class {class} does not have an associated schema', [
					'class' => $class,
				]);

				continue;
			}
			$schema = $schema->map($schema->schema());
			$table = avalue($schema, $object->table());
			if (!$table) {
				$this->error('{class} does not have table ({table}) associated with schema: {tables} {debug}', $error_args + [
					'tables' => array_keys($schema),
					'debug' => _dump($schema),
				]);

				continue;
			}
			$table_columns = $table['columns'];
			$missing = [];
			foreach ($table_columns as $column => $column_options) {
				if (!array_key_exists($column, $class_object->column_types)) {
					$missing[] = "'$column' => self::type_" . $this->guess_type($class_object->database(), $column, $column_options['type']) . ',';
				}
			}
			if (count($missing)) {
				$this->error("{class} is missing:\npublic \$column_types = array(\n\t{missing}\n);", $error_args + [
					'missing' => implode("\n\t", $missing),
				]);
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
					$this->error('{class} defined $has_one[{column}] but does not exist, please add it: $column_types => "{column}" => self::type_object,', $error_args + [
						'column' => $column,
					]);
				} elseif ($class_object->column_types[$column] !== Class_ORM::type_object) {
					$this->error('{class} defined $has_one[{column}] but wrong type {type}: $column_types => "{column}" => self::type_object,', $error_args + [
						'column' => $column,
						'type' => $class_object->column_types[$column],
					]);
				}
			}
		}
		$this->log('Done');
	}

	/**
	 *
	 * @var array
	 */
	public static $guess_names = [
		'timestamp' => [
			'created' => 'created',
			'modified' => 'modified',
		],
		'integer' => [
			'id' => 'id',
		],
	];

	/**
	 *
	 * @var array
	 */
	public static $guess_types = [
		'timestamp' => 'timestamp',
		'blob' => 'serialize',
	];

	/**
	 *
	 * @param Database $db
	 * @param unknown $name
	 * @param unknown $type
	 * @return mixed|array|mixed|string
	 */
	private function guess_type(Database $db, $name, $type) {
		$schema_type = avalue(avalue(self::$guess_names, $type, []), strtolower($name));
		if ($schema_type) {
			return $schema_type;
		}
		if (array_key_exists($type, self::$guess_types)) {
			return self::$guess_types[$type];
		}
		return $db->data_type()->native_type_to_data_type($type);
	}
}
