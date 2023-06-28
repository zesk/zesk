<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Database\Column;
use zesk\Database\Exception\SchemaException;
use zesk\Database\Index;
use zesk\Database\SQLParser;
use zesk\Database\Table;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Kernel;

/**
 * Handle schema definitions which are SQL files on disk
 *
 * @author kent
 * @see SchemaException
 */
class FileSchema extends Schema
{
	/**
	 * Path of sql file loaded
	 *
	 * @var string
	 */
	protected string $sql_file_path = '';

	/**
	 * Raw SQL in file
	 *
	 * @var string
	 */
	protected string $sql = '';

	/**
	 * Which parser to use
	 *
	 * @var SQLParser
	 */
	protected SQLParser $parser;

	/**
	 * SQL After map has been applied
	 *
	 * @var string
	 */
	protected string $mapped_sql = '';

	/**
	 * List of search paths when nothing found
	 *
	 * @var array
	 */
	protected array $searched = [];

	/**
	 * Create a schema file object for a given Class_Base, optionally specifying a string SQL
	 * schema.
	 *
	 * @param Class_Base $class_object
	 * @param ORMBase|null $object
	 * @param string $sql
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 */
	public function __construct(Class_Base $class_object, ORMBase $object = null, string $sql = '')
	{
		parent::__construct($class_object, $object);
		if ($sql !== '') {
			$this->_set_sql($sql);
			$this->parser = SQLParser::parseFactory($this->database(), $this->sql, Kernel::callingFunction());
			$this->application->logger->debug('Parsing SQL {sql} using {parse_class} for class {class}', [
				'sql' => $sql,
				'parse_class' => get_class($this->parser),
				'class' => $class_object::class,
			]);
		} else {
			$path = $this->schemaPath();
			if ($path) {
				$this->sql_file_path = $path;
				$this->_set_sql(file_get_contents($path));
				$this->parser = SQLParser::parseFactory($this->database(), $this->sql, $path);
				$this->application->logger->debug("Parsing {path} using {parse_class} for class {class}\nSQL:\n{sql}\n", [
					'path' => $path,
					'sql' => $this->mapped_sql,
					'parse_class' => get_class($this->parser),
					'class' => $class_object::class,
				]);
			}
		}
	}

	/**
	 * Set all variables dependent on the SQL parsed
	 *
	 * @param string $sql
	 */
	private function _set_sql(string $sql): void
	{
		$this->sql = $sql;
		$this->mapped_sql = ArrayTools::map($this->sql, $this->schemaVariables());
	}

	/**
	 * List of paths searched
	 *
	 * @return array
	 */
	public function searches(): array
	{
		return $this->searched;
	}

	/**
	 * Does the file exist on disk?
	 *
	 * @return bool
	 */
	public function exists(): bool
	{
		return $this->schemaPath() !== null;
	}

	/**
	 * Does this schema have some SQL associated with it?
	 *
	 * @return boolean
	 */
	public function hasSQL(): bool
	{
		return !empty($this->sql);
	}

	/**
	 * Handle finding the SQL file on disk using the autoloader
	 *
	 * For object Foo_Bar, search is:
	 *
	 * class/foo/bar.sql
	 * foo/bar.sql
	 *
	 * It then uses the $class->schema_file
	 *
	 * @return string Path of first found file
	 */
	protected function schemaPath(): string
	{
		$result = $this->_schemaPath();
		if ($this->optionBool('debug')) {
			$this->application->logger->debug('{class_object} found file {result}', [
				'class_object' => get_class($this->class_object),
				'result' => $result,
			]);
		}
		return $result;
	}

	/**
	 * Search for the schema file
	 *
	 * @return string Path to schema file
	 */
	private function _schemaPath(): string
	{
		$all_searches = [];
		$class_object = $this->class_object;
		foreach ($this->application->classes->hierarchy($class_object, Class_Base::class) as $class_name) {
			$searches = [];
			$schema_path = $this->application->autoloader->search($class_name, [
				'sql',
			], $searches);
			if ($schema_path) {
				return $schema_path;
			}
			$all_searches = array_merge($all_searches, $searches);
		}
		$schema_path = $this->application->autoloader->search($class_object->class, [
			'sql',
		], $searches);
		if ($schema_path) {
			return $schema_path;
		}
		$all_searches = array_merge($all_searches, $searches);
		$this->searched = $all_searches;
		return '';
	}

	/**
	 * Get file modification time of schema file
	 *
	 * @return int
	 */
	public function schemaModificationTime(): int
	{
		$schema_path = $this->schemaPath();
		if ($schema_path) {
			return filemtime($schema_path);
		}
		return 0;
	}

	/**
	 * Convert this schema object into the array-based schema
	 *
	 * @return array
	 * @throws SchemaException
	 */
	public function schema(): array
	{
		$db = $this->database();
		if ($this->sql === '') {
			return [];
		}
		$sqlStatements = $this->parser->splitSQLStatements($this->mapped_sql);
		$tables = [];
		foreach ($sqlStatements as $sql) {
			$sql = trim($sql);
			if (empty($sql)) {
				continue;
			}
			$parse_result = $this->parser->parseSQL($sql);
			$table_name = $parse_result['table'] ?? '';
			/* @var $table Table */
			$table = $tables[$table_name] ?? null;
			$statement = $parse_result['command'] ?? null;
			$__ = [
				'table' => $table_name,
				'sql' => $sql,
				'file' => $this->sql_file_path,
				'statement' => $statement,
			];
			if ($statement === 'create table') {
				$table = $this->parser->createTable($sql);
				$table_name = $table->name();
				if (array_key_exists($table_name, $tables)) {
					throw new SchemaException($db, $sql, 'Duplicate definition in {file} of {table}', $__);
				}
				$tables[$table_name] = $table;
			} elseif ($statement === 'create index') {
				if ($table) {
					$result = $this->parser->createIndex($table, $sql);
					$table->setSource($sql, true);
					if (!$result) {
						throw new SchemaException($db, $sql, 'Can not parse CREATE INDEX statement in {file} result=false', $__);
					}
				} else {
					throw new SchemaException(
						$db,
						$sql,
						'Failed to CREATE INDEX in {file} on unknown table {table}',
						$__
					);
				}
			} elseif ($statement === 'insert' || $statement === 'drop table') {
				if (!$table) {
					$table = ArrayTools::first(array_values($tables));
					$this->application->logger->warning('{statement} "{sql}" on unknown table {table} in {file}', $__);
				}
				$table->optionAppend('on create', $sql);
			} elseif ($statement !== 'none') {
				$this->application->logger->error('Unknown SQL statement ({statement}) found in file {file}: {sql}', $__);
			}
		}

		$schema = [];
		foreach ($tables as $table) {
			$table_spec = [];
			$table_spec['source'] = $table->source();
			$table_spec['engine'] = $table->type();
			foreach ($table->columns() as $col) {
				/* @var $col Column */
				$table_spec['columns'][$col->name()] = $col->options();
			}
			foreach ($table->indexes() as $index) {
				/* @var $index Index */
				switch ($index->type()) {
					case Index::TYPE_INDEX:
						$table_spec['indexes'][$index->name()] = $index->columnSizes();

						break;
					case Index::TYPE_UNIQUE:
						$table_spec['unique keys'][$index->name()] = $index->columnSizes();

						break;
					case Index::TYPE_PRIMARY:
						$table_spec['primary keys'] = $index->columns();

						break;
				}
			}
			if ($table->hasOption('on create')) {
				$table_spec['on create'] = $table->optionIterable('on create');
			}
			$table_name = $table->name();
			if (!array_key_exists($table_name, $schema)) {
				$schema[$table_name] = [];
			}
			$schema[$table_name] = $table_spec + $schema[$table_name];
		}
		return $schema;
	}
}
