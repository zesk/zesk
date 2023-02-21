<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM\Command;

use zesk\Database\Base;
use zesk\ArrayTools;
use zesk\Command_Base as CommandBase;
use zesk\Debug;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\ORM\Class_Base;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\ORMBase;

/**
 * Checks \zesk\ORM descendants for issues with missing fields, etc.
 *
 * @category Debugging
 * @author kent
 */
class ClassCheck extends CommandBase {
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
		return ArrayTools::extract($this->application->ormModule()->allClasses(), null, 'class');
	}

	/**
	 *
	 * @return int
	 * @throws Semantics
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ORMNotFound
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
					'debug' => Debug::dump($schema),
				]);

				continue;
			}
			$table_columns = $table['columns'];
			$missing = [];
			foreach ($table_columns as $column => $column_options) {
				if (!array_key_exists($column, $class_object->column_types)) {
					$guessed = $this->guessType($class_object->database(), $column, $column_options['type']);
					$missing[] = "'$column' => self::TYPE_" . $guessed . ',';
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
					$this->error('{class} defined $has_one[{column}] but does not exist, please add it: $column_types => "{column}" => self::TYPE_OBJECT,', $error_args + [
						'column' => $column,
					]);
				} elseif ($class_object->column_types[$column] !== Class_Base::TYPE_OBJECT) {
					$this->error('{class} defined $has_one[{column}] but wrong type {type}: $column_types => "{column}" => self::TYPE_OBJECT,', $error_args + [
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
		'TIMESTAMP' => [
			'CREATED' => 'TYPE_CREATED',
			'MODIFIED' => 'TYPE_MODIFIED',
		],
		'INTEGER' => [
			'ID' => 'TYPE_ID',
		],
	];

	/**
	 *
	 * @var array
	 */
	public static array $guess_types = [
		'TIMESTAMP' => 'TYPE_TIMESTAMP',
		'BLOB' => 'TYPE_SERIALIZE',
	];

	/**
	 *
	 * @param Base $db
	 * @param string $name
	 * @param string $type
	 * @return string
	 */
	private function guessType(Base $db, string $name, string $type): string {
		$schema_type = (self::$guess_names[strtoupper($type)] ?? [])[strtoupper($name)] ?? null;
		if ($schema_type) {
			return strtoupper($schema_type);
		}
		if (array_key_exists($type, self::$guess_types)) {
			return self::$guess_types[$type];
		}
		return $db->types()->native_type_to_data_type($type);
	}
}
