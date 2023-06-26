<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM\Database;

use zesk\Application;
use zesk\Database\Base;
use zesk\Database\SQLDialect;
use zesk\Database\SQLParser;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\Semantics;
use zesk\Interface\MemberModelFactory;
use zesk\Kernel;
use zesk\Model;
use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;

/**
 *
 * @author kent
 *
 */
class Query {
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
	 * @var Base
	 */
	protected Base $db;

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
	protected string $class = '';

	/**
	 * Inherited class options when linking or iterating
	 *
	 * @var array
	 */
	protected array $class_options = [];

	/**
	 *
	 * @var MemberModelFactory
	 */
	protected MemberModelFactory $factory;

	/**
	 * Create a new query
	 *
	 * @param string $type
	 * @param Base $db
	 */
	public function __construct(string $type, Base $db) {
		$this->db = $db;
		$this->application = $db->application;
		$this->factory = $this->application;
		$this->type = strtoupper($type);
		$this->dbname = $db->codeName();
		$this->class = '';
		$this->class_options = [];
	}

	/**
	 * Set the object which creates other objects
	 *
	 * @param MemberModelFactory $factory
	 * @return self
	 */
	public function setFactory(MemberModelFactory $factory): self {
		$this->factory = $factory;
		return $this;
	}

	/**
	 *
	 * @return string[]
	 */
	public function __sleep(): array {
		return ['type', 'dbname', 'class', ];
	}

	/**
	 * @return void
	 * @throws Semantics
	 */
	public function __wakeup(): void {
		// Reconnect upon wakeup
		$this->application = Kernel::wakeupApplication();
		$this->db = $this->application->databaseRegistry($this->dbname);
	}

	/**
	 *
	 * @param Query $from
	 * @return Query
	 */
	protected function _copy_from_query(Query $from): self {
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
	 * @return Base
	 */
	public function database(): Base {
		return $this->db;
	}

	/**
	 *
	 * @return string
	 */
	public function databaseName(): string {
		return $this->db->codeName();
	}

	/**
	 *
	 * @return SQLDialect
	 */
	public function sql(): SQLDialect {
		return $this->db->sqlDialect();
	}

	/**
	 *
	 * @return SQLParser
	 */
	public function parser(): SQLParser {
		return $this->db->sqlParser();
	}

	/**
	 * Set or get a class associated with this query
	 *
	 * @return string
	 */
	public function ormClass(): string {
		return $this->class;
	}

	/**
	 * Set or get a class associated with this query
	 *
	 * @param string $class
	 * @return Query string
	 */
	public function setORMClass(string $class): self {
		$this->class = $class;
		return $this;
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
	 * @return Class_Base
	 */
	public function class_orm(): Class_Base {
		return $this->application->class_ormRegistry($this->class);
	}

	/**
	 * Create objects in the current application context
	 *
	 * @param string $member
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Model
	 * @throws NotFoundException|ClassNotFound
	 */
	public function memberModelFactory(string $member, string $class, mixed $mixed = null, array $options = []): Model {
		return $this->factory->memberModelFactory($member, $class, $mixed, $options);
	}

	/**
	 * Create objects in the current application context
	 *
	 * @param string $member
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return ORMBase
	 * @throws NotFoundException|ClassNotFound
	 */
	public function memberORMFactory(string $member, string $class, mixed $mixed = null, array $options = []): ORMBase {
		$result = $this->factory->memberModelFactory($member, $class, $mixed, $options);
		assert($result instanceof ORMBase);
		return $result;
	}

	/**
	 * Cache for Object definitions, do not modify these objects
	 *
	 * @param string $class
	 * @return ORMBase
	 */
	protected function ormRegistry(string $class): ORMBase {
		return $this->application->ormRegistry($class);
	}
}
