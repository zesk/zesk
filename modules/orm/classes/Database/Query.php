<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query {
	/**
	 *
	 * @var Application
	 */
	public Application $application;

	/**
	 * Type of query
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * Database
	 *
	 * @var Database
	 */
	protected Database $db;

	/**
	 * Database code
	 *
	 * @var string
	 */
	protected string $dbname;

	/**
	 * Object class used when iterating
	 *
	 * @var string
	 */
	protected string $class = "";

	/**
	 * Inherited class options when linking or iterating
	 *
	 * @var array
	 */
	protected array $class_options = [];

	/**
	 *
	 * @var Interface_Member_Model_Factory
	 */
	protected Interface_Member_Model_Factory $factory;

	/**
	 * Create a new query
	 *
	 * @param string $type
	 * @param Database $db
	 */
	public function __construct(string $type, Database $db) {
		$this->db = $db;
		$this->application = $db->application;
		$this->factory = $this->application;
		$this->type = strtoupper($type);
		$this->dbname = $db->code_name();
		$this->class = "";
		$this->class_options = [];
	}

	/**
	 * Set the object which creates other objects
	 *
	 * @param Interface_Member_Model_Factory $factory
	 * @return self
	 */
	public function set_factory(Interface_Member_Model_Factory $factory): self {
		$this->application->deprecated("set_factory");
		return $this->setFactory($factory);
	}

	/**
	 * Set the object which creates other objects
	 *
	 * @param Interface_Member_Model_Factory $factory
	 * @return self
	 */
	public function setFactory(Interface_Member_Model_Factory $factory): self {
		$this->factory = $factory;
		return $this;
	}

	/**
	 *
	 * @return string[]
	 */
	public function __sleep(): array {
		return ["type", "dbname", "class", ];
	}

	/**
	 */
	public function __wakeup(): void {
		// Reconnect upon wakeup
		$this->application = __wakeup_application();
		$this->db = $this->application->database_registry($this->dbname);
	}

	/**
	 *
	 * @param Database_Query $from
	 * @return \zesk\Database_Query
	 */
	protected function _copy_from_query(Database_Query $from): self {
		$this->type = $from->type;
		$this->db = $from->db;
		$this->dbname = $from->dbname;
		$this->class = $from->class;
		$this->class_options = $from->class_options;
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	public function duplicate(): self {
		return clone $this;
	}

	/**
	 *
	 * @return Database
	 */
	public function database(): Database {
		return $this->db;
	}

	/**
	 *
	 * @return string
	 */
	public function database_name(): string {
		return $this->db->code_name();
	}

	/**
	 *
	 * @return Database_SQL
	 */
	public function sql(): Database_SQL {
		return $this->db->sql();
	}

	/**
	 *
	 * @return Database_Parser
	 */
	public function parser(): Database_Parser {
		return $this->db->parser();
	}

	/**
	 * Set or get a class associated with this query
	 *
	 * @param string $class
	 * @return string
	 */
	public function orm_class(string $class = null): string {
		if ($class !== null) {
			$this->application->deprecated("setORMClass now");
		}
		return $this->class;
	}

	/**
	 * Set or get a class associated with this query
	 *
	 * @param string $class
	 * @return Database_Query string
	 */
	public function setORMClass(string $class): self {
		$this->class = $class;
		return $this;
	}

	/**
	 * Set or get the class options associated with this query
	 *
	 * @param ?array $options
	 * @return mixed
	 */
	public function orm_class_options(array $options = null): mixed {
		$this->application->deprecated("ormClassOptions");
		return $options ? $this->setORMClassOptions($options) : $this->ormClassOptions();
	}

	/**
	 * Set the class options associated with this query
	 *
	 * @param ?array $options
	 * @return self
	 */
	public function setORMClassOptions(array $options = null): self {
		$this->class_options = $options;
		return $this;
	}

	/**
	 * Get the class options associated with this query
	 *
	 * @return array
	 */
	public function ormClassOptions(): array {
		return $this->class_options;
	}

	/**
	 *
	 * @return \zesk\Class_ORM
	 */
	public function class_orm() {
		return $this->application->class_orm_registry($this->class);
	}

	/**
	 * Create objects in the current application context
	 *
	 * @param string $class
	 * @param ?mixed $mixed
	 * @param array $options
	 * @return ?Model
	 */
	public function member_model_factory(string $member, string $class, mixed $mixed = null, array $options = []): ?Model {
		return $this->factory->member_model_factory($member, $class, $mixed, $options);
	}

	/**
	 * Cache for Object definitions, do not modify these objects
	 *
	 * @param string $class
	 * @return \zesk\ORM
	 */
	protected function orm_registry(string $class): ORM {
		return $this->application->orm_registry($class);
	}
}
