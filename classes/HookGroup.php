<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Utility class to allow dynamic evaluation of first/last rules
 *
 * @author kent
 *
 */
class HookGroup {
	public $first = array();

	public $middle = array();

	public $last = array();

	/**
	 * Merge two groups together
	 *
	 * @param HookGroup $merge
	 * @return \zesk\HookGroup
	 */
	public function merge(HookGroup $merge) {
		$this->first = array_merge($this->first, $merge->first);
		$this->middle = array_merge($this->middle, $merge->middle);
		$this->last = array_merge($this->last, $merge->last);
		return $this;
	}

	/**
	 * Combine all three together
	 *
	 * @return array
	 */
	public function definitions() {
		return $this->first + $this->middle + $this->last;
	}

	/**
	 *
	 * @param string $callable_string
	 * @return boolean
	 */
	public function has($callable_string) {
		return isset($this->first[$callable_string]) || isset($this->middle[$callable_string]) || isset($this->last[$callable_string]);
	}
}
