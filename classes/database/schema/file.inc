<?php
/**
 * $URL$
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * Handle schema definitions which are SQL files on disk
 *
 * @author kent
 * @see Database_Schema
 */
class Database_Schema_File extends Database_Schema {
	/**
	 * Path of sql file loaded
	 *
	 * @var string
	 */
	protected $sql_file_path = null;
	
	/**
	 * Raw SQL in file
	 *
	 * @var string
	 */
	protected $sql = null;
	/**
	 * Which parser to use
	 *
	 * @var Database_Parser
	 */
	protected $parser = null;
	
	/**
	 * SQL Aftrer map has been applied
	 *
	 * @var string
	 */
	protected $mapped_sql = null;
	
	/**
	 * Create a schema file object for a given Class_Object, optionally specifying a string SQL
	 * schema.
	 *
	 * @param Class_Object $object        	
	 * @param string $sql        	
	 */
	function __construct(Class_Object $class_object, Object $object = null, $sql = null) {
		parent::__construct($class_object, $object);
		if ($sql !== null) {
			$this->sql = $sql;
			$this->parser = Database_Parser::parse_factory($this->database(), $this->sql, zesk::calling_function());
			log::debug("Parsing SQL {sql} using {parse_class} for class {class}", array(
				"sql" => $sql,
				"parse_class" => get_class($this->parser),
				"class" => get_class($class_object)
			));
		} else {
			$path = $this->schema_path();
			if ($path) {
				$this->sql_file_path = $path;
				$this->sql = file_get_contents($path);
				$this->parser = Database_Parser::parse_factory($this->database(), $this->sql, $path);
				log::debug("Parsing {path} using {parse_class} for class {class}", array(
					"path" => $path,
					"parse_class" => get_class($this->parser),
					"class" => get_class($class_object)
				));
			}
		}
		$this->mapped_sql = map($this->sql, $this->schema_map());
	}
	
	/**
	 * Does the file exist on disk?
	 *
	 * @return boolean
	 */
	public function exists() {
		return $this->schema_path() !== null;
	}
	
	/**
	 * Does this schema have some SQL associated with it?
	 *
	 * @return boolean
	 */
	public function has_sql() {
		return !empty($this->sql);
	}
	
	/**
	 * Handle finding the SQL file on disk using the autoload path.
	 *
	 * For object Foo_Bar, search is:
	 *
	 * class/foo/bar.sql
	 * foo/bar.sql
	 *
	 * It then uses the $class->schema_file
	 *
	 * @return string Path of first found find
	 */
	protected function schema_path() {
		$result = $this->_schema_path();
		if ($this->option_bool('debug')) {
			log::debug("{class_object} found file {result}", array(
				"class_object" => get_class($this->class_object),
				"result" => $result
			));
		}
		return $result;
	}
	
	/**
	 * Search for the schema file
	 *
	 * @return string Path to schema file
	 */
	private function _schema_path() {
		global $zesk;
		
		$class_object = $this->class_object;
		foreach ($zesk->classes->hierarchy($class_object, "Class_Object") as $class_name) {
			$schema_path = $zesk->autoloader->search($class_name, array(
				'sql'
			));
			if ($schema_path) {
				return $schema_path;
			}
		}
		$schema_path = $zesk->autoloader->search($class_object->class, array(
			'sql'
		));
		if ($schema_path) {
			return $schema_path;
		}
		// Old-style way of finding - deprecate the template_schema_paths method
		$file = $class_object->schema_file ? $class_object->schema_file : $class_object->class . ".sql";
		$schema_paths = to_array(self::template_schema_paths());
		foreach ($schema_paths as $schema_path) {
			$path = path($schema_path, $file);
			if (file_exists($path)) {
				zesk::deprecated();
				return $path;
			}
		}
		return null;
	}
	
	/**
	 * Get file modification time of schema file
	 *
	 * @return integer|null
	 */
	public function schema_mtime() {
		$schema_path = $this->schema_path();
		if ($schema_path) {
			return filemtime($schema_path);
		}
		return null;
	}
	
	/**
	 * 
	 * @var array
	 */
	private static $schema_paths = null;
	/**
	 * 
	 * @param zesk\Kernel $zesk
	 */
	public static function hooks(zesk\Kernel $zesk) {
		$zesk->hooks->add("configured", __CLASS__ . '::configured');
	}
	
	/**
	 * 
	 * @param zesk\Application $application
	 */
	public static function configured(zesk\Application $application) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		self::$schema_paths = to_list($zesk->configuration->path_get("Object::schema_path", array(
			$zesk->paths->application('sql/schema'),
			$zesk->paths->zesk('sql/schema')
		)));
	}
	/**
	 * Retrieve a list of paths where schema files are stored
	 *
	 * @param string $add
	 *        	Optionally add a path
	 *        	
	 * @return array
	 */
	public static function template_schema_paths($add = null) {
		if (self::$schema_paths === null) {
			throw new Exception_Semantics("Accessed \$schema_paths before configured - probably should fix this");
		}
		if ($add !== null) {
			self::$schema_paths[] = $add;
		}
		return self::$schema_paths;
	}
	
	/**
	 * Convert this schema object into the array-based schema
	 *
	 * @see Database_Schema::schema()
	 * @return array
	 */
	public function schema() {
		$db = $this->database();
		if ($this->sql === null) {
			return array();
		}
		$sqls = $this->parser->split_sql_commands($this->mapped_sql);
		$create_table = null;
		$tables = array();
		foreach ($sqls as $sql) {
			$sql = trim($sql);
			if (empty($sql)) {
				continue;
			}
			$parse_result = $db->parse_sql($sql);
			$table_name = avalue($parse_result, 'table', '');
			/* @var $table Database_Table */
			$table = avalue($tables, $table_name);
			$statement = avalue($parse_result, "command");
			$__ = array(
				"table" => $table_name,
				"sql" => $sql,
				"file" => $this->sql_file_path,
				"statement" => $statement
			);
			if ($statement === "create table") {
				$table = $this->parser->create_table($sql);
				if (!$table) {
					throw new Database_Exception_Schema($db, $sql, "Can not parse create table in {file}", $__);
				}
				$table_name = $table->name();
				if (array_key_exists($table_name, $tables)) {
					throw new Database_Exception_Schema($db, $sql, "Duplicate definition in {file} of {table}", $__);
				}
				$tables[$table_name] = $table;
			} else if ($statement === "create index") {
				if ($table) {
					$result = $this->parser->create_index($table, $sql);
					$table->source($sql, true);
					if (!$result) {
						throw new Database_Exception_Schema($db, $sql, "Can not parse CREATE INDEX statement in {file} result={result}", $__ + array(
							'result' => _dump($result)
						));
					}
				} else {
					throw new Database_Exception_Schema($db, $sql, "CREATE INDEX in {file} on unknown table {table}", $__);
				}
			} else if ($statement === "insert" || $statement === "drop table") {
				if (!$table) {
					$table = first(array_values($tables));
					log::warning("{statement} \"{sql}\" on unknown table {table} in {file}", $__);
				}
				$table->option_append_list("on create", $sql);
			} else if ($statement !== "none") {
				log::error("Unknown SQL statement ({statement}) found in file {file}: {sql}", $__);
			}
		}
		
		$schema = array();
		foreach ($tables as $table) {
			$table_spec = array();
			$table_spec['source'] = $table->source();
			$table_spec['engine'] = $table->type();
			foreach ($table->columns() as $col) {
				/* @var $col Database_Column */
				$table_spec['columns'][$col->name()] = $col->option();
			}
			foreach ($table->indexes() as $index) {
				/* @var $index Database_Index */
				switch ($index->type()) {
					case Database_Index::Index:
						$table_spec['indexes'][$index->name()] = $index->column_sizes();
						break;
					case Database_Index::Unique:
						$table_spec['unique keys'][$index->name()] = $index->column_sizes();
						break;
					case Database_Index::Primary:
						$table_spec['primary keys'] = $index->columns();
						break;
				}
			}
			if ($table->has_option('on create')) {
				$table_spec['on create'] = $table->option_list('on create');
			}
			$table_name = $table->name();
			if (!array_key_exists($table_name, $schema)) {
				$schema[$table_name] = array();
			}
			$schema[$table_name] = $table_spec + $schema[$table_name];
		}
		return $schema;
	}
}
