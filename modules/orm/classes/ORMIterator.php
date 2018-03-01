<?php
/**
 *
 */
namespace zesk;

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
	protected $class;
	/**
	 * Options to be passed to each object constructor
	 *
	 * @var array
	 */
	protected $class_options;
	
	/**
	 * Current parent
	 *
	 * @var ORM
	 */
	private $parent = null;
	
	/**
	 * Current parent
	 *
	 * @var ORM
	 */
	private $parent_member = null;
	
	/**
	 * Current object
	 *
	 * @var ORM
	 */
	protected $object;
	
	/**
	 * Current key
	 *
	 * @var mixed
	 */
	protected $id;
	
	/**
	 * Create an object iterator
	 *
	 * @param string $class
	 *        	Class to iterate over
	 * @param Database_Query_Select $query
	 *        	Executed query to iterate
	 */
	function __construct($class, Database_Query_Select_Base $query, array $options = array()) {
		parent::__construct($query);
		$this->class = $class;
		$options['initialize'] = true;
		$this->class_options = $options;
	}
	
	/**
	 *
	 * @param ORM $parent
	 * @param string $member
	 * @return \zesk\ORM_Iterator
	 */
	public function set_parent(ORM $parent, $member = null) {
		$this->parent = $parent;
		if ($member !== null) {
			$this->parent_member = $member;
		} else {
			$this->parent_member = null;
		}
		return $this;
	}
	
	/**
	 * Current object
	 *
	 * @see Database_Result_Iterator::current()
	 * @return ORM
	 */
	public function current() {
		return $this->object;
	}
	
	/**
	 * Current object ID
	 *
	 * @see Database_Result_Iterator::key()
	 * @return string
	 */
	public function key() {
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
	protected function parent_support(ORM $object) {
		if ($this->parent) {
			$check_id = $this->object->member_integer($this->parent_member);
			if ($check_id === $this->parent->id()) {
				$this->object->__set($this->parent_member, $this->parent);
			} else {
				$object->application->logger->error("ORM iterator for {class}, mismatched parent member {member} #{id} (expecting #{expect_id})", array(
					'class' => $this->class,
					'member' => $this->parent_member,
					'id' => $check_id,
					'expect_id' => $this->parent->id()
				));
			}
		}
	}
	/**
	 * Next object in results
	 *
	 * @see Database_Result_Iterator::next()
	 * @return ORM
	 */
	public function next() {
		parent::next();
		if ($this->_valid) {
			// We do create, then fetch to support polymorphism - if ORM supports factory polymorphism, then shorten this to single factory call
			$this->object = $this->query->model_factory($this->class, $this->_row, array(
				'initialize' => true
			) + $this->class_options);
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
	 * @see Database_Result_Iterator::to_array()
	 * @return ORM[]
	 */
	public function to_array($key = null) {
		$result = array();
		if ($key === null) {
			foreach ($this as $object) {
				$result[$object->id()] = $object;
			}
		} else if ($key === false) {
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
	 * @return \zesk\ORM[]
	 */
	public function to_list() {
		return $this->to_array(false);
	}
}
