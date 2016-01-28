<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/iterator/multi.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:26 PM
 */

class Iterator_Multi implements Iterator
{
	/**
	 * Current iterator
	 * @var integer
	 */
	private $index = 0;

	/**
	 * Inner Iterators
	 * @var array:Iterator
	 */
	private $iterators;

	/** Construct a new empty MultipleIterator
     * @param flags MIT_* flags
     */
	public function __construct()
	{
		$this->iterators = array();
		$this->index = null;
	}

	/**
     * @param $iter new Iterator to add
     */
	public function add(Iterator $iter)
	{
		$this->iterators[] = $iter;
	}

	/**
     * Rewind all attached Iterator instances.
     * */
	public function rewind()
	{
		foreach ($this->iterators as $iterator) {
			$iterator->rewind();
		}
		$this->index = 0;
	}

	/**
    * @return whether all or one sub iterator is valid depending on flags.
    * In mode MIT_NEED_ALL we expect all sub iterators to be valid and
    * return flase on the first non valid one. If that flag is not set we
    * return true on the first valid sub iterator found. If no Iterator
    * is attached, we always return false.
    */
	public function valid()
	{
		$n_iterators = count($this->iterators);
		if ($n_iterators === 0) {
			return false;
		}
		while ($this->index < $n_iterators) {
			if ($this->iterators[$this->index]->valid()) {
				return true;
			}
			$this->index++;
		}
		return false;
	}

	/**
	 * Move the current iterator forward
     */
	public function next()
	{
		if (count($this->iterators) === 0) {
			return;
		}
		$this->iterators[$this->index]->next();
	}

	public function current()
	{
		return $this->iterators[$this->index]->current();
	}

	public function key()
	{
		return $this->iterators[$this->index]->key();
	}
}
