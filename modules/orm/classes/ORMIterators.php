<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see ORMIterator
 * @see ORMIterators
 */
class ORMIterators extends ORMIterator {
	/**
	 * Class we're iterating over
	 * @var string
	 */
	private $objects_prefixes = array();
	
	/**
	 * Create an object iterator
	 * @param string $class Class to iterate over
	 * @param Database_Query_Select $query Executed query to iterate
	 */
	function __construct($class, Database_Query_Select_Base $query, array $objects_prefixes, array $options = array()) {
		parent::__construct($class, $query, $options);
		$this->objects_prefixes = $objects_prefixes;
	}
	
	/**
	 * Next object in results
	 * @see Database_Result_Iterator::next()
	 */
	public function next() {
		parent::next();
		if ($this->_valid) {
			$result = array();
			$first = null;
			foreach ($this->objects_prefixes as $prefix => $class_name) {
				list($alias, $class) = $class_name;
				$members = ArrayTools::kunprefix($this->_row, $prefix, true);
				$object = $result[$alias] = $this->query->model_factory($class, $members, array(
					'initialize' => true
				) + $this->class_options);
				if (!$first) {
					$first = $object;
				}
			}
			// We do create, then fetch to support polymorphism - if ORM supports factory polymorphism, then shorten this to single factory call
			$this->id = $first->id();
			$this->object = $result + $this->_row;
			$this->parent_support($first);
		} else {
			$this->id = null;
			$this->object = null;
		}
	}
}
