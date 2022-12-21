<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Ruler, LLC
 */

namespace zesk\ORM;

use DateTimeZone;
use zesk\Exception_Parameter;
use zesk\Database_Result_Iterator;
use zesk\Database_Exception_SQL;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Timestamp;
use zesk\Model;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_NotFound as ZeskNotFound;
use zesk\ORM\Exception_ORMNotFound as ORMNotFound;
use zesk\Database\SelectableInterface;

/**
 *
 * @author kent
 *
 */
abstract class Database_Query_Select_Base extends Database_Query implements SelectableInterface {
	/**
	 *
	 * @var array
	 */
	protected array $objects_prefixes = [];

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Database_Query::__sleep()
	 */
	public function __sleep(): array {
		return array_merge(parent::__sleep(), ['objects_prefixes', ]);
	}

	/**
	 * @param Database_Query_Select_Base $from
	 * @return $this
	 */
	protected function _copy_from_base(Database_Query_Select_Base $from): self {
		parent::_copy_from_query($from);
		$this->objects_prefixes = $from->objects_prefixes;
		return $this;
	}

	/**
	 * Convert this query into an iterator
	 *
	 * @param string $key
	 *            Use this field as a key for the iterator
	 * @param string $value
	 *            Use this column as a value for the iterator, null means use entire object/row
	 * @return Database_Result_Iterator
	 */
	public function iterator(string $key = '', string $value = ''): Database_Result_Iterator {
		return new Database_Result_Iterator($this, $key, $value);
	}

	/**
	 * Execute query and retrieve a single row or field in each tow
	 *
	 * @param string|int|null $field
	 * @return mixed
	 * @throws Database_Exception_SQL|Exception_Key
	 */
	public function one(string|int|null $field = null): mixed {
		return $this->database()->queryOne($this->__toString(), $field);
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param string|int $field
	 *            Field to retrieve
	 * @return int
	 * @throws Exception_Key|Database_Exception_SQL
	 * @deprecated 2022-05
	 */
	public function one_integer(string|int $field = 0): int {
		$this->application->deprecated(__METHOD__);
		return $this->integer($field);
	}

	/**
	 * Execute query and retrieve a single field, a double
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @return float
	 * @throws Exception_Key|Database_Exception_SQL|Exception_Deprecated
	 * @see self::float
	 * @deprecated 2022-11 use self::float
	 */
	public function double(string|int $field = 0): float {
		$this->application->deprecated(__METHOD__);
		return self::float($field);
	}

	/**
	 * Execute query and retrieve a single field, a double
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @return float
	 * @throws Exception_Key|Database_Exception_SQL
	 */
	public function float(string|int $field = 0): float {
		return toFloat($this->one($field));
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string|int $field
	 * @return int
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 */
	public function integer(string|int $field = 0): int {
		return $this->database()->queryInteger($this->__toString(), $field);
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param int|string $field Field to retrieve
	 * @param DateTimeZone|null $timezone
	 * @return Timestamp
	 * @return Timestamp
	 * @throws Database_Exception_SQL
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws ZeskNotFound
	 * @throws Exception_Parameter
	 */
	public function timestamp(int|string $field = 0, DateTimeZone $timezone = null): Timestamp {
		$value = $this->database()->queryOne($this->__toString(), $field);
		if (empty($value)) {
			throw new ZeskNotFound('Timestamp {field} not found', ['field' => $field]);
		}
		return new Timestamp($value, $timezone);
	}

	/**
	 *
	 * @param int|string|null $key
	 * @param int|string|null $value
	 * @return array
	 */
	public function toArray(int|string $key = null, int|string $value = null): array {
		return $this->database()->queryArray($this->__toString(), $key, $value);
	}

	/**
	 */
	abstract public function __toString(): string;

	/**
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	abstract public function toSQL(): string;

	/**
	 *
	 * @param ?string $class Optional ORM class to use as target for iteration (overrides `$this->orm_class()`)
	 * @param array $options Options passed to each ORM class upon creation
	 * @return ORMIterator
	 */
	public function ormIterator(string $class = null, array $options = []): ORMIterator {
		if ($class !== null) {
			$this->setORMClass($class);
		}
		return new ORMIterator($this->class, $this, $this->class_options + $options);
	}

	/**
	 * Convert this query into an `ORMIterators` (returns multiple objects per row)
	 *
	 * @param array $options
	 *            Options passed to each object upon creation
	 * @return ORMIterators
	 */
	public function ormIterators(array $options = []): ORMIterators {
		return new ORMIterators($this->class, $this, $this->objects_prefixes, $options);
	}

	/**
	 * Execute query and convert to a Model
	 *
	 * @param string|null $class Class of object, pass NULL to use already configured class
	 * @param array $options Options to pass to object creator
	 * @return Model
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws ZeskNotFound
	 */
	public function model(string $class = null, array $options = []): Model {
		$result = $this->one();
		if ($result === null) {
			throw new ZeskNotFound($class);
		}
		if ($class) {
			$this->setORMClass($class);
		}
		return $this->application->modelFactory($this->ormClass(), $result, ['from_database' => true, ] + $options);
	}

	/**
	 * Execute query and convert to an ORM. A bit of syntactic sugar.
	 * @param string|null $class
	 * @param array $options
	 * @return ORMBase
	 * @throws Exception_Key
	 * @throws ORMNotFound
	 */
	public function orm(string $class = null, array $options = []): ORMBase {
		try {
			$result = $this->one();
		} catch (Database_Exception_SQL) {
			throw new ORMNotFound($class);
		}
		return $this->application->ormFactory($class ?? $this->class, $result, ['from_database' => true, ] + $options + $this->ormClassOptions());
	}

	/**
	 * @param int|string|null $key
	 * @param int|string|null $value
	 * @param array $default
	 * @return array
	 * @deprecated 2022-04
	 */
	public function to_array(int|string $key = null, int|string $value = null, array $default = []): array {
		$this->application->deprecated(__METHOD__);
		return $this->toArray($key, $value, $default);
	}
}
