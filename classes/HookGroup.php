<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Utility class to allow dynamic evaluation of first/last rules
 *
 * @author kent
 *
 */
class HookGroup {
	public array $first = [];

	public array $middle = [];

	public array $last = [];

	/**
	 * Merge two groups together
	 *
	 * @param HookGroup $merge
	 * @return HookGroup
	 */
	public function merge(HookGroup $merge): self {
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
	public function definitions(): array {
		return $this->first + $this->middle + $this->last;
	}

	/**
	 *
	 * @param string $callable_string
	 * @return boolean
	 */
	public function has(string $callable_string): bool {
		return isset($this->first[$callable_string]) || isset($this->middle[$callable_string]) || isset($this->last[$callable_string]);
	}
}
