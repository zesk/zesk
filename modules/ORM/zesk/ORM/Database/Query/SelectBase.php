<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Ruler, LLC
 */

namespace zesk\ORM\Database\Query;

use DateTimeZone;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Database\ResultIterator;
use zesk\Database\SelectableInterface;
use zesk\Exception\ClassNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException as ZeskNotFound;
use zesk\Exception\Semantics;
use zesk\Model;
use zesk\ORM\Database\Query;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\ORMBase;
use zesk\ORM\ORMIterator;
use zesk\ORM\ORMIterators;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
abstract class SelectBase extends Query implements SelectableInterface
{
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
	public function __sleep(): array
	{
		return array_merge(parent::__sleep(), ['objects_prefixes', ]);
	}

	/**
	 * @param SelectBase $from
	 * @return $this
	 */
	protected function _copy_from_base(SelectBase $from): self
	{
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
	 * @return ResultIterator
	 */
	public function iterator(string $key = '', string $value = ''): ResultIterator
	{
		return new ResultIterator($this, $key, $value);
	}

	/**
	 * Execute query and retrieve a single row or field in each tow
	 *
	 * @param string|int|null $field
	 * @return mixed
	 * @throws KeyNotFound
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function one(string|int|null $field = null): mixed
	{
		return $this->database()->queryOne($this->__toString(), $field);
	}

	/**
	 * Execute query and retrieve a single field, a double
	 *
	 * @param string|integer $field
	 *            Field to retrieve
	 * @return float
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function float(string|int $field = 0): float
	{
		return Types::toFloat($this->one($field));
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string|int $field
	 * @return int
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function integer(string|int $field = 0): int
	{
		return $this->database()->queryInteger($this->__toString(), $field);
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string|int $field
	 * @return string
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function string(string|int $field = 0): string
	{
		return strval($this->one($field));
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param int|string $field Field to retrieve
	 * @param DateTimeZone|null $timezone
	 * @return Timestamp
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws ZeskNotFound
	 */
	public function timestamp(int|string $field = 0, DateTimeZone $timezone = null): Timestamp
	{
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
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	public function toArray(int|string $key = null, int|string $value = null): array
	{
		return $this->database()->queryArray($this->__toString(), $key, $value);
	}

	/**
	 */
	abstract public function __toString(): string;

	/**
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	abstract public function toSQL(): string;

	/**
	 *
	 * @param ?string $class Optional ORM class to use as target for iteration (overrides `$this->orm_class()`)
	 * @param array $options Options passed to each ORM class upon creation
	 * @return ORMIterator
	 */
	public function ormIterator(string $class = null, array $options = []): ORMIterator
	{
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
	 * @todo test this
	 */
	public function ormIterators(array $options = []): ORMIterators
	{
		return new ORMIterators($this->class, $this, $this->objects_prefixes, $options);
	}

	/**
	 * Execute query and convert to a Model
	 *
	 * @param string|null $class Class of object, pass NULL to use already configured class
	 * @param array $options Options to pass to object creator
	 * @return Model
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws ZeskNotFound
	 * @throws ClassNotFound
	 */
	public function model(string $class = null, array $options = []): Model
	{
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	public function orm(string $class = null, array $options = []): ORMBase
	{
		try {
			$result = $this->one();
		} catch (SQLException $e) {
			throw new ORMNotFound($class, 'No {class} - {throwableClass}', ['class' => $class] + $e->variables(), $e);
		}
		return $this->application->ormFactory($class ?? $this->class, $result, ['from_database' => true, ] + $options + $this->ormClassOptions());
	}
}
