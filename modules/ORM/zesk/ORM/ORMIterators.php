<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Exception\Semantics;
use zesk\JSON;
use zesk\Database\ResultIterator;
use zesk\ORM\Database\Query\SelectBase;
use zesk\ORM\Exception\ORMEmpty;

/**
 * @see ORMIterator
 * @see ORMIterators
 */
class ORMIterators extends ResultIterator {
	/**
	 * @var string
	 */
	private string $name;

	/**
	 * Options for creating classes
	 *
	 * @var array
	 */
	private array $classOptions;

	/**
	 * Class we're iterating over
	 * @var array
	 */
	private array $objects_prefixes;

	/**
	 * Our row ID
	 *
	 * @var string
	 */
	private string $id = '';

	/**
	 * @var array
	 */
	private array $objects;

	/**
	 * Create an object iterator
	 * @param SelectBase $query Executed query to iterate
	 */
	public function __construct(string $name, SelectBase $query, array $objects_prefixes, array $classOptions =
	[]) {
		parent::__construct($query);
		$this->name = $name;
		$this->objects_prefixes = $objects_prefixes;
		$this->classOptions = $classOptions;
	}

	/**
	 * Next object in results
	 * @see ResultIterator::next()
	 * @see ORMIterator::next
	 */
	public function next(): void {
		$this->databaseNext();
		if ($this->_valid) {
			$result = [];
			$ids = [];
			foreach ($this->objects_prefixes as $prefix => $class_name) {
				[$alias, $class] = $class_name;
				$members = ArrayTools::keysRemovePrefix($this->_row, $prefix, true);
				$object = $result[$alias] = $this->query->memberModelFactory(
					$this->name . '.' . $prefix,
					$class,
					$members,
					[
						'initialize' => true,
					] + $this->classOptions
				);
				assert($object instanceof ORMBase);

				try {
					$ids[$prefix] = $object->id();
				} catch (ORMEmpty) {
					$ids[$prefix] = null;
				}
			}
			ksort($ids);

			try {
				$this->id = JSON::encode($ids);
			} catch (Semantics) {
				$this->id = serialize($ids);
			}
			$this->objects = $result;
		} else {
			$this->id = '';
		}
	}

	/**
	 * Current object row
	 *
	 * @return array
	 */
	public function current(): array {
		return $this->objects;
	}

	/**
	 * Return current row key (ID or index)
	 *
	 * @return mixed
	 */
	public function key(): string {
		return $this->id;
	}
}
