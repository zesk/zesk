<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Ruler, LLC
 */

namespace zesk;

use \DateTimeZone;

/**
 *
 * @author kent
 *
 */
abstract class Database_Query_Select_Base extends Database_Query {
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
	 * Execute query and retrieve a single field or row
	 *
	 * @param string|int $field
	 * @param mixed $default
	 * @return mixed
	 */
	public function one(string|int $field = 0, mixed $default = null): mixed {
		return $this->database()->query_one($this->__toString(), $field, $default);
	}

	/**
	 * This method should be overriden in subclasses if it has class_object support
	 *
	 * @param string $class
	 * @return string
	 */
	public function class_alias(string $class = null) {
		if ($class === null) {
			return null;
		}
		return '';
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param string|int $field
	 *            Field to retrieve
	 * @param mixed $default
	 *            Default value to retrieve
	 * @return int
	 */
	public function one_integer(string|int $field = 0, int $default = 0): int {
		return $this->integer($field, $default);
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @param mixed $default
	 *            Default value to retrieve
	 * @param DateTimeZone|null $timezone
	 * @return Timestamp
	 */
	public function one_timestamp(string|int $field = 0, Timestamp $default = null, DateTimeZone $timezone = null):
	Timestamp {
		return $this->timestamp($field, $default, $timezone);
	}

	/**
	 * Execute query and retrieve a single field, a double
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @param mixed $default
	 *            Default value to retrieve
	 * @return integer
	 */
	public function double(string|int $field = 0, $default = null) {
		return to_double($this->one($field), $default);
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @param ?int $default
	 *            Default value to retrieve
	 * @return ?int
	 */
	public function integer(string|int $field = 0, ?int $default = 0): ?int {
		return $this->database()->query_integer($this->__toString(), $field, $default);
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @param mixed $default
	 *            Default value to retrieve
	 * @param DateTimeZone|null $timezone
	 * @return Timestamp
	 * @throws Exception_NotFound
	 */
	public function timestamp(int|string $field = 0, Timestamp $default = null, DateTimeZone $timezone = null):
	Timestamp {
		$value = $this->database()->query_one($this->__toString(), $field, $default);
		if (empty($value)) {
			throw new Exception_NotFound('Timestamp {field} not found', ['field' => $field]);
		}
		return new Timestamp($value, $timezone);
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 * @param array $default
	 * @return array
	 * @deprecated 2022-04
	 */
	public function to_array(int|string $key = null, int|string $value = null, array $default = []): array {
		return $this->toArray($key, $value, $default);
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 * @param array $default
	 * @return array
	 */
	public function toArray(int|string $key = null, int|string $value = null, array $default = []) {
		return $this->database()->query_array($this->__toString(), $key, $value, $default);
	}

	/**
	 */
	abstract public function __toString();

	/**
	 *
	 * @param string $class Optional ORM class to use as target for iteration (overrides `$this->orm_class()`)
	 * @param array $options Options passed to each ORM class upon creation
	 * @return ORMIterator
	 */
	public function orm_iterator(string $class = null, array $options = []): ORMIterator {
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
	public function orm_iterators(array $options = []) {
		return new ORMIterators($this->class, $this, $this->objects_prefixes, $options);
	}

	/**
	 * Execute query and convert to a Model
	 *
	 * @param string $class Class of object, pass NULL to use already configured class
	 * @param array $options Options to pass to object creator
	 * @return Model
	 */
	public function model(string $class = null, array $options = []): Model {
		$result = $this->one();
		if ($result === null) {
			throw new Exception_ORM_NotFound('{class} not found', ['class' => $class]);
		}
		return $this->application->model_factory($this->orm_class($class), $result, ['from_database' => true, ] + $options);
	}

	/**
	 * Execute query and convert to an ORM. A bit of syntactic sugar.
	 * @param string|null $class
	 * @param array $options
	 * @return ORM
	 * @throws Exception_ORM_NotFound
	 */
	public function orm(string $class = null, array $options = []): ORM {
		$result = $this->one();
		if ($result === null) {
			throw new Exception_ORM_NotFound('{class} not found', ['class' => $class]);
		}
		return $this->application->orm_factory($this->orm_class($class), $result, ['from_database' => true, ] + $options + $this->ormClassOptions());
	}

	/**
	 * Convert this query into an ORM Iterator (returns single object per row)
	 *
	 * @param string $class
	 *            Class to iterate on (inherited from default settings for this query)
	 * @param array $options
	 *            Options passed to each object upon creation
	 * @return ORMIterator
	 * @deprecated 2017-12 Blame PHP 7.2
	 * @see Database_Query_Select_Base::orm_iterator
	 */
	public function object_iterator($class = null, array $options = []) {
		$this->application->deprecated();
		return $this->orm_iterator($class, $options);
	}

	/**
	 * Convert this query into an ORMs Iterator
	 *
	 * @param array $options
	 *            Options passed to each object upon creation
	 * @return ORMIterators
	 * @see Database_Query_Select_Base::orms_iterator
	 * @deprecated 2017-12 Blame PHP 7.2
	 */
	public function objects_iterator(array $options = []) {
		$this->application->deprecated();
		return $this->orm_iterators($options);
	}

	/**
	 * Execute query and return the first returned row as an object
	 *
	 * @param string $class
	 *            An optional class to return the first row as
	 * @param array $options
	 *            Optional options to be passed to the object upon instantiation
	 * @return ORM
	 * @deprecated 2017-12
	 */
	public function one_object($class = null, array $options = []) {
		$this->application->deprecated();
		return $this->orm($class, $options);
	}

	/**
	 * Execute query and convert to an object
	 *
	 * @param string $class
	 *            Class of object
	 * @param array $options
	 * @return Model
	 * @throws Exception_Deprecated
	 * @deprecated 2017-12
	 */
	public function object($class = null, array $options = []) {
		zesk()->deprecated();
		return $this->model($class, $options);
	}

	/**
	 * Append "what" fields for an entire object class, with $prefix before it, using alias $alias
	 *
	 * @param string $class
	 *            Class to add what fields for; if not supplied uses the class associated with the
	 *            query
	 * @param string $alias
	 *            the alias associated with the class query, uses default (X) if not supplied
	 * @param string $prefix
	 *            Prefix all output field names with this string, blank for nothing
	 * @param string $object_mixed
	 *            Pass to class_table_columns for dynamic table objects
	 * @param array|null $object_options
	 *            Pass to class_table_columns for dynamic table objects
	 * @return Database_Query_Select
	 * @deprecated 2022-01
	 */
	public function what_object(string $class = null, string $alias = null, string $prefix = null, mixed $object_mixed = null, array $object_options = []) {
		return $this->ormWhat($class, $alias, $prefix, $object_mixed, $object_options);
	}

	/**
	 * Convert this query into an ORMs Iterator (returns multiple objects per row)
	 *
	 * @param array $options
	 *            Options passed to each object upon creation
	 * @return ORMIterators
	 * @deprecated 2020-11
	 * @see $this->orm_iterators()
	 */
	public function orms_iterator(array $options = []) {
		return $this->orm_iterators($options);
	}
}
