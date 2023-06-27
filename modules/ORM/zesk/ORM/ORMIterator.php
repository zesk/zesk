<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\ORM;

use zesk\Database\ResultIterator;
use zesk\Exception\ParseException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Semantics;
use zesk\JSON;
use zesk\ORM\Database\Query\SelectBase;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;

/**
 *
 * @author kent
 *
 */
class ORMIterator extends ResultIterator
{
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
	protected array $classOptions = [];

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
	protected string $parentMember = '';

	/**
	 * Current object
	 *
	 * @var null|ORMBase
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
	 * @param string $class Class to iterate over
	 * @param SelectBase $query Executed query to iterate
	 */
	public function __construct(string $class, SelectBase $query, array $options = [])
	{
		parent::__construct($query);
		$this->class = $class;
		$options['initialize'] = true;
		$this->classOptions = $options;
	}

	/**
	 *
	 * @param ORMBase $parent
	 * @param string $member
	 * @return ORMIterator
	 */
	public function setParent(ORMBase $parent, string $member = ''): self
	{
		$this->parent = $parent;
		$this->parentMember = $member;
		return $this;
	}

	/**
	 * Current object
	 *
	 * @return null|ORMBase
	 * @see ResultIterator::current()
	 */
	public function current(): null|ORMBase
	{
		return $this->object;
	}

	/**
	 * Current object ID
	 *
	 * @return string
	 * @throws Semantics
	 * @see ResultIterator::key()
	 */
	public function key(): mixed
	{
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
	protected function parentSupport(ORMBase $object): void
	{
		if ($this->parent) {
			$message = '';
			$check_id = '-';
			$parentId = '-';

			try {
				$parentId = $this->parent->id();
				$check_id = $this->object->memberInteger($this->parentMember);
				if ($check_id === $parentId) {
					$this->object->setMember($this->parentMember, $this->parent);
				} else {
					$message = 'mismatched';
				}
			} catch (ORMNotFound|ORMEmpty|KeyNotFound|ParseException $e) {
				$message = $e->getMessage();
			}
			if ($message) {
				$object->application->logger->error('ORM iterator for {class}, {message} {member} #{id} (expecting #{parentId})', [
					'class' => $this->class, 'member' => $this->parentMember, 'message' => $message, 'id' => $check_id,
					'parentId' => $parentId,
				]);
			}
		}
	}

	/**
	 * Next object in results
	 *
	 * BEWARE: ORMIterators::next jumps over this!
	 *
	 * @return void
	 * @see ORMIterators::next
	 * @see ResultIterator::next()
	 */
	public function next(): void
	{
		parent::next();
		$this->_updateObjectAndId();
	}

	/**
	 * @return void
	 */
	protected function _updateObjectAndId(): void
	{
		if ($this->_valid) {
			$members = $this->_row;
			// We do create, then fetch to support polymorphism - if ORM supports factory polymorphism, then shorten this to single factory call
			try {
				$this->object = $this->query->memberModelFactory($this->parentMember, $this->class, $members, ['initialize' => true, ] + $this->classOptions);
				$this->id = $this->object->id();
				$this->parentSupport($this->object);
				return;
			} catch (ORMEmpty) {
			}
		}
		$this->id = null;
		$this->object = null;
	}

	/**
	 * Convert to an array
	 *
	 * @return ORMBase[]
	 * @see ResultIterator::toArray()
	 */
	public function toArray($key = null): array
	{
		$result = [];
		if ($key === null) {
			foreach ($this as $object) {
				try {
					$result[$object->id()] = $object;
				} catch (ORMEmpty) {
				}
			}
		} elseif ($key === false) {
			foreach ($this as $object) {
				$result[] = $object;
			}
		} else {
			foreach ($this as $object) {
				try {
					$result[strval($object->member($key))] = $object;
				} catch (ORMNotFound|KeyNotFound) {
				}
			}
		}
		return $result;
	}

	/**
	 * Convert to a list
	 *
	 * @return ORMBase[]
	 */
	public function toList(): array
	{
		return $this->toArray();
	}
}
