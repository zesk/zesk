<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Command_Base as CommandBase;
use zesk\Database;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Semantics;

/**
 * Checks \zesk\ORM descendants for issues with missing fields, etc.
 *
 * @category Debugging
 * @author kent
 */
class Command_ClassCheck extends CommandBase {
	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'*' => 'string',
	];

	protected array $shortcuts = ['class-check', 'cc'];

	/**
	 *
	 * @return array
	 */
	private function all_classes(): array {
		return ArrayTools::extract($this->application->ormModule()->all_classes(), null, 'class');
	}

	/**
	 *
	 * @return int
	 * @throws Exception_Semantics
	 */
	public function run(): int {
		$logger = $this->application->logger;
		$classes = [];
		$arg = '';
		while ($this->hasArgument()) {
			$arg = $this->getArgument('class');
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
			$this->verboseLog('Checking class {class}', [
				'class' => $class,
			]);
			/* @var $class_object Class_Base */
			/* @var $object ORMBase */
			$class_object = $this->application->class_ORMRegistry($class);
			if (!$class_object) {
				$this->error("No such class $arg");

				continue;
			}
			$error_args = [
				'class' => $class,
				'table' => $class_object->table,
			];
			$object = $this->application->ormRegistry($class);
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
			$table = $schema[$object->table()] ?? null;
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
					try {
						$guessed = $this->guess_type($class_object->database(), $column, $column_options['type']);
					} catch (Exception_Class_NotFound) {
						$guessed = 'text';
					}
					$missing[] = "'$column' => self::type_" . $guessed . ',';
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
				} elseif ($class_object->column_types[$column] !== Class_Base::type_object) {
					$this->error('{class} defined $has_one[{column}] but wrong type {type}: $column_types => "{column}" => self::type_object,', $error_args + [
						'column' => $column,
						'type' => $class_object->column_types[$column],
					]);
				}
			}
		}
		$this->log('Done');
		return 0;
	}

	/**
	 *
	 * @var array
	 */
	public static array $guess_names = [
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
	public static array $guess_types = [
		'timestamp' => 'timestamp',
		'blob' => 'serialize',
	];

	/**
	 *
	 * @param Database $db
	 * @param string $name
	 * @param string $type
	 * @return string
	 * @throws Exception_Class_NotFound
	 */
	private function guess_type(Database $db, string $name, string $type): string {
		$schema_type = (self::$guess_names[$type] ?? [])[strtolower($name)] ?? null;
		if ($schema_type) {
			return $schema_type;
		}
		if (array_key_exists($type, self::$guess_types)) {
			return self::$guess_types[$type];
		}
		return $db->data_type()->native_type_to_data_type($type);
	}
}
