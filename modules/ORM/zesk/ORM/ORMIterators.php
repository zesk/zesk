<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

/**
 * @see ORMIterator
 * @see ORMIterators
 */
class ORMIterators extends ORMIterator {
	/**
	 * Class we're iterating over
	 * @var array
	 */
	private $objects_prefixes = [];

	/**
	 * Create an object iterator
	 * @param string $class Class to iterate over
	 * @param Database_Query_Select $query Executed query to iterate
	 */
	public function __construct($class, Database_Query_Select_Base $query, array $objects_prefixes, array $options = []) {
		parent::__construct($class, $query, $options);
		$this->objects_prefixes = $objects_prefixes;
		$self = $this;
	}

	/**
	 * Next object in results
	 * @see Database_Result_Iterator::next()
	 * @see ORMIterator::next
	 */
	public function next(): void {
		// Skip ORMIterator::next
		$this->dbnext();
		if ($this->_valid) {
			$result = [];
			$first = null;
			foreach ($this->objects_prefixes as $prefix => $class_name) {
				[$alias, $class] = $class_name;
				$members = ArrayTools::keysRemovePrefix($this->_row, $prefix, true);
				$object = $result[$alias] = $this->query->memberModelFactory($this->parent_member . '.' . $prefix, $class, $members, [
					'initialize' => true,
				] + $this->class_options);
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
