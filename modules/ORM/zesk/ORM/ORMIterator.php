<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

use zesk\Database_Result_Iterator;

/**
 *
 * @author kent
 *
 */
class ORMIterator extends Database_Result_Iterator {
	/**
	 * Class we're iterating over
	 *
	 * @var string
	 */
	protected string $class;

	/**
	 * Options to be passed to each object constructor
	 *
	 * @var array
	 */
	protected array $class_options = [];

	/**
	 * Current parent
	 *
	 * @var ?ORMBase
	 */
	protected ?ORMBase $parent = null;

	/**
	 * Current parent
	 *
	 * @var string
	 */
	protected string $parent_member = '';

	/**
	 * Current object
	 *
	 * @var ORMBase
	 */
	protected ?ORMBase $object = null;

	/**
	 * Current key
	 *
	 * @var mixed
	 */
	protected mixed $id = null;

	/**
	 * Create an object iterator
	 *
	 * @param string $class
	 *        	Class to iterate over
	 * @param Database_Query_Select $query
	 *        	Executed query to iterate
	 */
	public function __construct(string $class, Database_Query_Select_Base $query, array $options = []) {
		parent::__construct($query);
		$this->class = $class;
		$options['initialize'] = true;
		$this->class_options = $options;
	}

	/**
	 *
	 * @param ORMBase $parent
	 * @param string $member
	 * @return ORMIterator
	 */
	public function set_parent(ORMBase $parent, string $member = '') : self {
		$this->db->application->deprecated('set_parent');
		return $this->setParent($parent, $member);
	}

	/**
	 *
	 * @param ORMBase $parent
	 * @param string $member
	 * @return ORMIterator
	 */
	public function setParent(ORMBase $parent, string $member = ''): self {
		$this->parent = $parent;
		$this->parent_member = $member;
		return $this;
	}

	/**
	 * Current object
	 *
	 * @return ORMBase
	 *@see Database_Result_Iterator::current()
	 */
	public function current(): mixed {
		return $this->object;
	}

	/**
	 * Current object ID
	 *
	 * @see Database_Result_Iterator::key()
	 * @return string
	 */
	public function key(): mixed {
		return is_array($this->id) ? JSON::encode($this->id) : $this->id;
	}

	/**
	 * Maintain parent object to avoid cyclical store(), and for memory saving
	 *
	 * If at any point we do ORM-system caching, we can remove this as instantiation will re-use
	 * an object.
	 *
	 * @todo Decide on object system caching or this method.
	 */
	protected function parent_support(ORMBase $object): void {
		if ($this->parent) {
			$check_id = $this->object->memberInteger($this->parent_member);
			if ($check_id === $this->parent->id()) {
				$this->object->__set($this->parent_member, $this->parent);
			} else {
				$object->application->logger->error('ORM iterator for {class}, mismatched parent member {member} #{id} (expecting #{expect_id})', [
					'class' => $this->class,
					'member' => $this->parent_member,
					'id' => $check_id,
					'expect_id' => $this->parent->id(),
				]);
			}
		}
	}

	/**
	 * Next object in results
	 *
	 * BEWARE: ORMIterators::next jumps over this!
	 *
	 * @return ORMBase
	 *@see ORMIterators::next
	 * @see Database_Result_Iterator::next()
	 */
	public function next(): void {
		parent::next();
		if ($this->_valid) {
			$members = $this->_row;
			// We do create, then fetch to support polymorphism - if ORM supports factory polymorphism, then shorten this to single factory call
			$this->object = $this->query->memberModelFactory($this->parent_member, $this->class, $members, [
				'initialize' => true,
			] + $this->class_options);
			$this->id = $this->object->id();
			$this->parent_support($this->object);
		} else {
			$this->id = null;
			$this->object = null;
		}
	}

	/**
	 * Convert to an array
	 *
	 * @return ORMBase[]
	 *@see Database_Result_Iterator::toArray()
	 */
	public function toArray($key = null): array {
		$result = [];
		if ($key === null) {
			foreach ($this as $object) {
				$result[$object->id()] = $object;
			}
		} elseif ($key === false) {
			foreach ($this as $object) {
				$result[] = $object;
			}
		} else {
			foreach ($this as $object) {
				$result[strval($object->member($key))] = $object;
			}
		}
		return $result;
	}

	/**
	 * Convert to a list
	 *
	 * @return ORMBase[]
	 */
	public function toList(): array {
		return $this->toArray(false);
	}
}
